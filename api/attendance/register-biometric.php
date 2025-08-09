<?php
/**
 * Register Biometric Attendance API Endpoint
 * Handles attendance registration with biometric verification
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Verify user session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos de entrada inválidos');
    }
    
    $employeeId = $input['employee_id'] ?? '';
    $tipo = $input['tipo'] ?? '';
    $verificationMethod = $input['verification_method'] ?? 'traditional';
    $imageData = $input['image_data'] ?? '';
    $observacion = $input['observacion'] ?? '';
    $registroManual = $input['registro_manual'] ?? 'N';
    
    if (!$employeeId || !$tipo) {
        throw new Exception('ID de empleado y tipo de asistencia son requeridos');
    }
    
    if (!in_array($tipo, ['ENTRADA', 'SALIDA'])) {
        throw new Exception('Tipo de asistencia debe ser ENTRADA o SALIDA');
    }
    
    if (!in_array($verificationMethod, ['fingerprint', 'facial', 'traditional'])) {
        throw new Exception('Método de verificación no válido');
    }
    
    // Validate employee exists
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.CODIGO, 
               est.NOMBRE as ESTABLECIMIENTO, s.NOMBRE as SEDE
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = ? AND e.ACTIVO = 'S'
    ");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Get current date and time
    $fecha = date('Y-m-d');
    $hora = date('H:i');
    
    // Check for duplicate attendance (same employee, date, and type)
    $stmt = $conn->prepare("
        SELECT ID_ASISTENCIA 
        FROM asistencia 
        WHERE ID_EMPLEADO = ? AND FECHA = ? AND TIPO = ?
    ");
    $stmt->execute([$employeeId, $fecha, $tipo]);
    $existingAttendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingAttendance) {
        throw new Exception("Ya existe un registro de {$tipo} para este empleado en la fecha actual");
    }
    
    // Calculate tardiness if it's an ENTRADA
    $tardanza = null;
    $horario = null;
    if ($tipo === 'ENTRADA') {
        $tardanzaInfo = calculateTardiness($conn, $employeeId, $fecha, $hora);
        $tardanza = $tardanzaInfo['tardanza'];
        $horario = $tardanzaInfo['horario'];
    }
    
    // Handle photo storage
    $photoFileName = null;
    if ($imageData) {
        $photoFileName = saveAttendancePhoto($imageData, $employeeId, $tipo, $fecha, $hora);
    }
    
    // Get schedule ID if available
    $horarioId = $horario['ID_HORARIO'] ?? null;
    
    // Insert attendance record
    $stmt = $conn->prepare("
        INSERT INTO asistencia 
        (ID_EMPLEADO, FECHA, TIPO, HORA, TARDANZA, OBSERVACION, FOTO, 
         REGISTRO_MANUAL, ID_HORARIO, VERIFICATION_METHOD) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $employeeId,
        $fecha,
        $tipo,
        $hora,
        $tardanza,
        $observacion,
        $photoFileName,
        $registroManual,
        $horarioId,
        $verificationMethod
    ]);
    
    $attendanceId = $conn->lastInsertId();
    
    // Log biometric verification if applicable
    if ($verificationMethod !== 'traditional') {
        logBiometricAttendance($conn, $employeeId, $verificationMethod, $attendanceId);
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Asistencia registrada correctamente',
        'attendance' => [
            'id' => $attendanceId,
            'employee_id' => $employeeId,
            'employee_name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
            'employee_code' => $employee['CODIGO'],
            'establecimiento' => $employee['ESTABLECIMIENTO'],
            'sede' => $employee['SEDE'],
            'fecha' => $fecha,
            'hora' => $hora,
            'tipo' => $tipo,
            'tardanza' => $tardanza,
            'verification_method' => $verificationMethod,
            'photo' => $photoFileName,
            'observacion' => $observacion
        ]
    ];
    
    // Add schedule information if available
    if ($horario) {
        $response['attendance']['horario'] = [
            'id' => $horario['ID_HORARIO'],
            'nombre' => $horario['NOMBRE'],
            'hora_entrada' => $horario['HORA_ENTRADA'],
            'hora_salida' => $horario['HORA_SALIDA']
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Calculate tardiness for entrada
 */
function calculateTardiness($conn, $employeeId, $fecha, $hora) {
    $result = ['tardanza' => null, 'horario' => null];
    
    try {
        // Get employee's schedule for the day
        $dayOfWeek = date('N', strtotime($fecha)); // 1 = Monday, 7 = Sunday
        
        $stmt = $conn->prepare("
            SELECT h.ID_HORARIO, h.NOMBRE, h.HORA_ENTRADA, h.HORA_SALIDA,
                   CASE $dayOfWeek
                       WHEN 1 THEN h.LUNES
                       WHEN 2 THEN h.MARTES  
                       WHEN 3 THEN h.MIERCOLES
                       WHEN 4 THEN h.JUEVES
                       WHEN 5 THEN h.VIERNES
                       WHEN 6 THEN h.SABADO
                       WHEN 7 THEN h.DOMINGO
                   END as trabaja_hoy
            FROM empleado_horario eh
            JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
            WHERE eh.ID_EMPLEADO = ? AND h.ACTIVO = 'S'
            AND ? BETWEEN eh.FECHA_INICIO AND IFNULL(eh.FECHA_FIN, '2099-12-31')
            ORDER BY eh.FECHA_INICIO DESC
            LIMIT 1
        ");
        
        $stmt->execute([$employeeId, $fecha]);
        $horario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($horario && $horario['trabaja_hoy'] === 'S') {
            $result['horario'] = $horario;
            
            $horaEntrada = $horario['HORA_ENTRADA'];
            $horaActual = $hora;
            
            // Calculate if late
            if ($horaActual > $horaEntrada) {
                $result['tardanza'] = 'Tardía';
            } else {
                $result['tardanza'] = 'Puntual';
            }
        }
    } catch (Exception $e) {
        error_log("Error calculating tardiness: " . $e->getMessage());
    }
    
    return $result;
}

/**
 * Save attendance photo
 */
function saveAttendancePhoto($imageData, $employeeId, $tipo, $fecha, $hora) {
    try {
        // Create photos directory if it doesn't exist
        $photoDir = '../../uploads/attendance_photos/';
        if (!is_dir($photoDir)) {
            mkdir($photoDir, 0755, true);
        }
        
        // Generate filename
        $timestamp = date('YmdHis', strtotime("$fecha $hora"));
        $fileName = strtolower($tipo) . "_{$employeeId}_{$timestamp}.jpg";
        $filePath = $photoDir . $fileName;
        
        // Decode base64 image
        if (strpos($imageData, 'data:image') === 0) {
            $imageData = substr($imageData, strpos($imageData, ',') + 1);
        }
        
        $decodedImage = base64_decode($imageData);
        
        if ($decodedImage === false) {
            throw new Exception('Error al decodificar la imagen');
        }
        
        // Save image
        if (file_put_contents($filePath, $decodedImage) === false) {
            throw new Exception('Error al guardar la imagen');
        }
        
        return $fileName;
        
    } catch (Exception $e) {
        error_log("Error saving attendance photo: " . $e->getMessage());
        return null;
    }
}

/**
 * Log biometric attendance verification
 */
function logBiometricAttendance($conn, $employeeId, $verificationMethod, $attendanceId) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO biometric_logs 
            (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, OPERATION_TYPE,
             FECHA, HORA, API_SOURCE) 
            VALUES (?, ?, 1, 'verification', CURDATE(), CURTIME(), 'attendance_register')
        ");
        $stmt->execute([$employeeId, $verificationMethod]);
    } catch (Exception $e) {
        error_log("Error logging biometric attendance: " . $e->getMessage());
    }
}
?>