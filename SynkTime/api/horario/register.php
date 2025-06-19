<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$nombre = $_POST['nombre'] ?? '';
$id_establecimiento = $_POST['establecimiento'] ?? '';
$hora_entrada = $_POST['hora_entrada'] ?? '';
$hora_salida = $_POST['hora_salida'] ?? '';
$dias = json_decode($_POST['dias'] ?? '[]', true);
$tolerancia = isset($_POST['tolerancia']) ? intval($_POST['tolerancia']) : 0;

if (!$nombre || !$id_establecimiento || !$hora_entrada || !$hora_salida || !is_array($dias) || !count($dias)) {
    echo json_encode(['success'=>false, 'message'=>'Faltan datos obligatorios']);
    exit;
}

$conn->beginTransaction();
try {
    $stmt = $conn->prepare("INSERT INTO horario (NOMBRE, ID_ESTABLECIMIENTO, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $id_establecimiento, $hora_entrada, $hora_salida, $tolerancia]);
    $id_horario = $conn->lastInsertId();

    $stmtDia = $conn->prepare("INSERT INTO horario_dia (ID_HORARIO, ID_DIA) VALUES (?, ?)");
    foreach ($dias as $id_dia) {
        $stmtDia->execute([$id_horario, $id_dia]);
    }

    $conn->commit();
    echo json_encode(['success'=>true, 'id_horario'=>$id_horario]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success'=>false, 'message'=>'Error al registrar horario']);
}