<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$id = $_POST['id_horario'] ?? '';
$nombre = $_POST['nombre'] ?? '';
$id_establecimiento = $_POST['establecimiento'] ?? '';
$hora_entrada = $_POST['hora_entrada'] ?? '';
$hora_salida = $_POST['hora_salida'] ?? '';
$dias = json_decode($_POST['dias'] ?? '[]', true);

if (!$id || !$nombre || !$id_establecimiento || !$hora_entrada || !$hora_salida || !is_array($dias) || !count($dias)) {
    echo json_encode(['success'=>false, 'message'=>'Faltan datos obligatorios']);
    exit;
}

$conn->beginTransaction();
try {
    $stmt = $conn->prepare("UPDATE horario SET NOMBRE=?, ID_ESTABLECIMIENTO=?, HORA_ENTRADA=?, HORA_SALIDA=? WHERE ID_HORARIO=?");
    $stmt->execute([$nombre, $id_establecimiento, $hora_entrada, $hora_salida, $id]);
    // Actualizar días: eliminar todos y volver a insertar
    $stmtDel = $conn->prepare("DELETE FROM horario_dia WHERE ID_HORARIO=?");
    $stmtDel->execute([$id]);
    $stmtDia = $conn->prepare("INSERT INTO horario_dia (ID_HORARIO, ID_DIA) VALUES (?, ?)");
    foreach ($dias as $id_dia) {
        $stmtDia->execute([$id, $id_dia]);
    }
    $conn->commit();
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success'=>false, 'message'=>'Error al actualizar horario']);
}