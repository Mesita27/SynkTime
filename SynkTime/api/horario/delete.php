<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$id = $_POST['id_horario'] ?? '';
if (!$id) {
    echo json_encode(['success'=>false, 'message'=>'Falta ID']);
    exit;
}
try {
    $stmt = $conn->prepare("DELETE FROM horario WHERE ID_HORARIO = ?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    echo json_encode(['success'=>false, 'message'=>'No se pudo eliminar']);
}