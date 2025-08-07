<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Capturar filtros
$sede = $_GET['sede'] ?? '';
$establecimiento = $_GET['establecimiento'] ?? '';
$codigo = $_GET['codigo'] ?? '';
$biometric_filter = $_GET['biometric_filter'] ?? 'all'; // 'all', 'partial', 'none', 'complete'

// Create biometric_data table if it doesn't exist
try {
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS biometric_data (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
            FINGER_TYPE VARCHAR(20),
            BIOMETRIC_DATA LONGTEXT,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ACTIVO TINYINT(1) DEFAULT 1,
            FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO),
            UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
        )
    ";
    $conn->exec($create_table_sql);
} catch (Exception $e) {
    // Table may already exist, continue
}

// Construir la consulta base para obtener todos los empleados activos
$where = ['e.ACTIVO = "S"'];
$params = [];

if ($sede) {
    $where[] = 's.ID_SEDE = ?';
    $params[] = $sede;
}
if ($establecimiento) {
    $where[] = 'est.ID_ESTABLECIMIENTO = ?';
    $params[] = $establecimiento;
}
if ($codigo) {
    $where[] = 'e.ID_EMPLEADO = ?';
    $params[] = $codigo;
}

$whereClause = implode(' AND ', $where);

// Consulta para obtener empleados con información biométrica
$sql = "
    SELECT 
        e.ID_EMPLEADO,
        e.NOMBRE,
        e.APELLIDO,
        e.ID_ESTABLECIMIENTO,
        est.NOMBRE AS ESTABLECIMIENTO,
        s.NOMBRE AS SEDE,
        MAX(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 1 THEN 1 ELSE 0 END) as has_fingerprint,
        MAX(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' AND bd.ACTIVO = 1 THEN 1 ELSE 0 END) as has_facial
    FROM EMPLEADO e
    JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
    JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
    LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO
    WHERE $whereClause
    GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.ID_ESTABLECIMIENTO, est.NOMBRE, s.NOMBRE
";

// Apply biometric filter if specified
if ($biometric_filter === 'partial') {
    // Only employees with partial biometric data (only fingerprint OR facial, not both)
    $sql .= " HAVING (has_fingerprint = 1 AND has_facial = 0) OR (has_fingerprint = 0 AND has_facial = 1)";
} elseif ($biometric_filter === 'none') {
    // Only employees without any biometric data
    $sql .= " HAVING has_fingerprint = 0 AND has_facial = 0";
} elseif ($biometric_filter === 'complete') {
    // Only employees with complete biometric data (both fingerprint AND facial)
    $sql .= " HAVING has_fingerprint = 1 AND has_facial = 1";
}

$sql .= " ORDER BY e.APELLIDO, e.NOMBRE";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para cada empleado, verificar sus horarios y asistencias
$result = [];
$fecha_actual = date('Y-m-d');

foreach ($empleados as $empleado) {
    // Obtener el día de la semana (1=Lunes ... 7=Domingo)
    $dia_semana = date('N', strtotime($fecha_actual));
    
    // Buscar todos los horarios asignados al empleado para este día
    $sqlHorarios = "
        SELECT 
            H.ID_HORARIO, 
            H.NOMBRE AS HORARIO_NOMBRE, 
            H.HORA_ENTRADA, 
            H.HORA_SALIDA
        FROM EMPLEADO_HORARIO EH
        JOIN HORARIO H ON H.ID_HORARIO = EH.ID_HORARIO
        JOIN HORARIO_DIA HD ON HD.ID_HORARIO = H.ID_HORARIO
        WHERE EH.ID_EMPLEADO = ?
          AND HD.ID_DIA = ?
          AND EH.FECHA_DESDE <= ?
          AND (EH.FECHA_HASTA IS NULL OR EH.FECHA_HASTA >= ?)
        ORDER BY H.HORA_ENTRADA
    ";
    
    $stmtHorarios = $conn->prepare($sqlHorarios);
    $stmtHorarios->execute([$empleado['ID_EMPLEADO'], $dia_semana, $fecha_actual, $fecha_actual]);
    $horarios = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);
    
    // Si el empleado no tiene horarios asignados hoy, no mostrarlo
    if (empty($horarios)) {
        continue;
    }
    
    // Para cada horario, verificar si ya se registró entrada y salida
    $horarios_disponibles = [];
    $tiene_horarios_disponibles = false;
    
    foreach ($horarios as $horario) {
        // Consultar registros de asistencia para este horario específico
        $sqlAsistencias = "
            SELECT TIPO
            FROM ASISTENCIA
            WHERE ID_EMPLEADO = ?
              AND FECHA = ?
              AND ID_HORARIO = ?
            ORDER BY HORA
        ";
        
        $stmtAsistencias = $conn->prepare($sqlAsistencias);
        $stmtAsistencias->execute([$empleado['ID_EMPLEADO'], $fecha_actual, $horario['ID_HORARIO']]);
        $asistencias = $stmtAsistencias->fetchAll(PDO::FETCH_ASSOC);
        
        $tiene_entrada = false;
        $tiene_salida = false;
        
        foreach ($asistencias as $asistencia) {
            if ($asistencia['TIPO'] === 'ENTRADA') $tiene_entrada = true;
            if ($asistencia['TIPO'] === 'SALIDA') $tiene_salida = true;
        }
        
        // Determinar el estado del horario
        $estado_horario = [];
        if (!$tiene_entrada && !$tiene_salida) {
            $estado_horario = [
                'estado' => 'disponible',
                'proximo_registro' => 'ENTRADA',
                'mensaje' => 'Registrar entrada'
            ];
            $tiene_horarios_disponibles = true;
        } else if ($tiene_entrada && !$tiene_salida) {
            $estado_horario = [
                'estado' => 'disponible',
                'proximo_registro' => 'SALIDA',
                'mensaje' => 'Registrar salida'
            ];
            $tiene_horarios_disponibles = true;
        } else {
            $estado_horario = [
                'estado' => 'completado',
                'mensaje' => 'Entrada y salida registradas'
            ];
        }
        
        $horarios_disponibles[] = [
            'id_horario' => $horario['ID_HORARIO'],
            'nombre' => $horario['HORARIO_NOMBRE'],
            'hora_entrada' => $horario['HORA_ENTRADA'],
            'hora_salida' => $horario['HORA_SALIDA'],
            'estado' => $estado_horario
        ];
    }
    
    // Agregar información de horarios disponibles y biometric status
    $empleado['HORARIOS'] = $horarios_disponibles;
    $empleado['TIENE_HORARIOS_DISPONIBLES'] = $tiene_horarios_disponibles;
    $empleado['HAS_FINGERPRINT'] = (bool) $empleado['has_fingerprint'];
    $empleado['HAS_FACIAL'] = (bool) $empleado['has_facial'];
    
    // Determine biometric status
    if ($empleado['has_fingerprint'] && $empleado['has_facial']) {
        $empleado['BIOMETRIC_STATUS'] = 'complete';
    } elseif ($empleado['has_fingerprint'] || $empleado['has_facial']) {
        $empleado['BIOMETRIC_STATUS'] = 'partial';
    } else {
        $empleado['BIOMETRIC_STATUS'] = 'none';
    }
    
    $result[] = $empleado;
}

echo json_encode([
    'success' => true, 
    'data' => $result,
    'filter_info' => [
        'fecha' => $fecha_actual,
        'dia_semana' => $dia_semana ?? date('N'),
        'total_empleados' => count($result),
        'biometric_filter' => $biometric_filter
    ]
]);
?>