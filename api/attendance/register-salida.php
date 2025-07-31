<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Verificar que existe el directorio uploads
$uploads_dir = __DIR__ . '/../../uploads/';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Recoge los datos POST
$id_empleado = $_POST['id_empleado'] ?? null;
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$id_horario = $_POST['id_horario'] ?? null;

// Validación de datos obligatorios
if (!$id_empleado || !$id_horario) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Verificar que no exista ya una salida para este empleado y horario en esta fecha
$sql_check = "SELECT COUNT(*) FROM ASISTENCIA 
              WHERE ID_EMPLEADO = ? AND FECHA = ? AND ID_HORARIO = ? AND TIPO = 'SALIDA'";
$stmt = $conn->prepare($sql_check);
$stmt->execute([$id_empleado, $fecha, $id_horario]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'Ya existe una salida registrada para este empleado en este horario.']);
    exit;
}

// Buscar información del horario
$sqlHorario = "
    SELECT H.HORA_SALIDA, H.TOLERANCIA
    FROM HORARIO H
    WHERE H.ID_HORARIO = ?
";
$stmt = $conn->prepare($sqlHorario);
$stmt->execute([$id_horario]);
$horario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$horario) {
    echo json_encode(['success' => false, 'message' => 'No se encontró el horario especificado.']);
    exit;
}

$hora_actual = date('H:i:s');
$hora_salida_horario = $horario['HORA_SALIDA'];
$tolerancia = (int)($horario['TOLERANCIA'] ?? 0);

// Calcular si es una salida temprana
$ts_actual = strtotime($fecha . ' ' . $hora_actual);
$ts_salida = strtotime($fecha . ' ' . $hora_salida_horario);

$tardanza = "N"; // Por defecto, salida normal
if ($ts_actual < $ts_salida - $tolerancia * 60) {
    $tardanza = "S"; // Salida temprana (antes de la hora programada menos la tolerancia)
}

// Registrar la salida
$sql = "INSERT INTO ASISTENCIA 
        (ID_EMPLEADO, FECHA, TIPO, HORA, TARDANZA, OBSERVACION, FOTO, REGISTRO_MANUAL, ID_HORARIO)
        VALUES (?, ?, 'SALIDA', ?, ?, NULL, NULL, 'S', ?)";

try {
    $stmt = $conn->prepare($sql);
    $ok = $stmt->execute([$id_empleado, $fecha, $hora_actual, $tardanza, $id_horario]);
    
    if ($ok) {
        echo json_encode([
            'success' => true, 
            'message' => 'Salida registrada correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar la salida.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>