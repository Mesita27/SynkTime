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

// Consulta para obtener todos los empleados (no filtrar por asistencia)
$sql = "
    SELECT 
        e.ID_EMPLEADO,
        e.NOMBRE,
        e.APELLIDO,
        e.ID_ESTABLECIMIENTO,
        est.NOMBRE AS ESTABLECIMIENTO,
        s.NOMBRE AS SEDE
    FROM EMPLEADO e
    JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
    JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
    WHERE $whereClause
    ORDER BY e.APELLIDO, e.NOMBRE
";

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
    
    // Agregar información de horarios disponibles
    $empleado['HORARIOS'] = $horarios_disponibles;
    $empleado['TIENE_HORARIOS_DISPONIBLES'] = $tiene_horarios_disponibles;
    
    $result[] = $empleado;
}

echo json_encode([
    'success' => true, 
    'data' => $result,
    'filter_info' => [
        'fecha' => $fecha_actual,
        'dia_semana' => $dia_semana ?? date('N'),
        'total_empleados' => count($result)
    ]
]);
?>