<?php
/**
 * API endpoint for biometric attendance registration
 * Handles fingerprint and facial recognition verification
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $id_empleado = $_POST['id_empleado'] ?? null;
    $verification_method = $_POST['verification_method'] ?? null;
    $image_data = $_POST['image_data'] ?? null;

    if (!$id_empleado || !$verification_method) {
        throw new Exception('Datos incompletos');
    }

    // Get employee information
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.ID_ESTABLECIMIENTO, 
               est.NOMBRE as ESTABLECIMIENTO, est.ID_SEDE,
               s.NOMBRE as SEDE
        FROM empleados e
        LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = ? AND e.ACTIVO = 1
    ");
    $stmt->execute([$id_empleado]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        throw new Exception('Empleado no encontrado o inactivo');
    }

    // Get current date and time in Colombia timezone
    $fecha_actual = new DateTime('now', new DateTimeZone('America/Bogota'));
    $fecha = $fecha_actual->format('Y-m-d');
    $hora = $fecha_actual->format('H:i:s');

    // Check if employee has active schedules for today
    $dia_semana = $fecha_actual->format('N'); // 1 = Monday, 7 = Sunday
    
    $stmt = $conn->prepare("
        SELECT h.*, 
               CASE $dia_semana
                   WHEN 1 THEN h.LUNES
                   WHEN 2 THEN h.MARTES  
                   WHEN 3 THEN h.MIERCOLES
                   WHEN 4 THEN h.JUEVES
                   WHEN 5 THEN h.VIERNES
                   WHEN 6 THEN h.SABADO
                   WHEN 7 THEN h.DOMINGO
               END as ACTIVO_HOY
        FROM empleado_horarios eh
        JOIN horarios h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = ? 
        AND eh.ACTIVO = 1 
        AND h.ACTIVO = 1
        AND (eh.FECHA_FIN IS NULL OR eh.FECHA_FIN >= ?)
        HAVING ACTIVO_HOY = 1
        ORDER BY h.HORA_ENTRADA ASC
    ");
    $stmt->execute([$id_empleado, $fecha]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($horarios)) {
        throw new Exception('El empleado no tiene horarios activos para hoy');
    }

    // Determine attendance type (ENTRADA/SALIDA) and which schedule to use
    $tipo_registro = null;
    $horario_usar = null;

    foreach ($horarios as $horario) {
        // Check if there's already an entry for this schedule today
        $stmt = $conn->prepare("
            SELECT COUNT(*) as entradas, 
                   MAX(CASE WHEN TIPO = 'ENTRADA' THEN HORA END) as ultima_entrada,
                   COUNT(CASE WHEN TIPO = 'SALIDA' THEN 1 END) as salidas
            FROM asistencias 
            WHERE ID_EMPLEADO = ? 
            AND FECHA = ? 
            AND ID_HORARIO = ?
        ");
        $stmt->execute([$id_empleado, $fecha, $horario['ID_HORARIO']]);
        $asistencia_info = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($asistencia_info['entradas'] == 0) {
            // No entry yet, this should be an entry
            $tipo_registro = 'ENTRADA';
            $horario_usar = $horario;
            break;
        } elseif ($asistencia_info['salidas'] == 0) {
            // Has entry but no exit, this should be an exit
            $tipo_registro = 'SALIDA';
            $horario_usar = $horario;
            break;
        }
    }

    if (!$tipo_registro || !$horario_usar) {
        throw new Exception('No se puede determinar el tipo de registro para este empleado');
    }

    // Handle image if provided (for facial recognition or traditional)
    $foto_nombre = null;
    if ($image_data && ($verification_method === 'facial' || $verification_method === 'traditional')) {
        $foto_nombre = saveAttendanceImage($image_data, $id_empleado, $tipo_registro);
    }

    // Calculate tardiness for entry
    $tardanza = null;
    if ($tipo_registro === 'ENTRADA') {
        $hora_entrada_prog = new DateTime($fecha . ' ' . $horario_usar['HORA_ENTRADA']);
        $hora_actual = $fecha_actual;
        
        if ($hora_actual > $hora_entrada_prog) {
            $diff = $hora_actual->diff($hora_entrada_prog);
            $tardanza = ($diff->h * 60) + $diff->i; // Minutes late
        }
    }

    // Insert attendance record
    $stmt = $conn->prepare("
        INSERT INTO asistencias (
            ID_EMPLEADO, 
            FECHA, 
            HORA, 
            TIPO, 
            ID_HORARIO, 
            TARDANZA, 
            FOTO,
            VERIFICATION_METHOD,
            CREATED_AT
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $id_empleado,
        $fecha,
        $hora,
        $tipo_registro,
        $horario_usar['ID_HORARIO'],
        $tardanza,
        $foto_nombre,
        $verification_method
    ]);

    // Log biometric verification
    if ($verification_method !== 'traditional') {
        $stmt = $conn->prepare("
            INSERT INTO biometric_logs (
                ID_EMPLEADO,
                VERIFICATION_METHOD,
                VERIFICATION_SUCCESS,
                FECHA,
                HORA,
                CREATED_AT
            ) VALUES (?, ?, 1, ?, ?, NOW())
        ");
        $stmt->execute([$id_empleado, $verification_method, $fecha, $hora]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Asistencia registrada correctamente',
        'tipo' => $tipo_registro,
        'empleado' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
        'verification_method' => $verification_method,
        'hora' => $hora
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Save attendance image
 */
function saveAttendanceImage($image_data, $id_empleado, $tipo) {
    $upload_dir = '../../uploads/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Remove data URL prefix
    $image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);
    $image_binary = base64_decode($image_data);
    
    if ($image_binary === false) {
        throw new Exception('Datos de imagen inválidos');
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "attendance_{$id_empleado}_{$tipo}_{$timestamp}.jpg";
    $filepath = $upload_dir . $filename;
    
    if (file_put_contents($filepath, $image_binary) === false) {
        throw new Exception('Error al guardar la imagen');
    }
    
    return $filename;
}
?>