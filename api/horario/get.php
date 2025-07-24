<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$id = $_GET['id_horario'] ?? null;
if (!$id) {
    echo json_encode(['success'=>false, 'message'=>'Falta ID']);
    exit;
}

$stmt = $conn->prepare("
    SELECT h.*, e.NOMBRE AS ESTABLECIMIENTO
    FROM HORARIO h
    INNER JOIN ESTABLECIMIENTO e ON e.ID_ESTABLECIMIENTO = h.ID_ESTABLECIMIENTO
    WHERE h.ID_HORARIO = ?
");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success'=>false, 'message'=>'No encontrado']);
    exit;
}

// Dias
$stmt2 = $conn->prepare("
    SELECT d.ID_DIA, d.NOMBRE
    FROM HORARIO_DIA hd
    JOIN DIA_SEMANA d ON d.ID_DIA = hd.ID_DIA
    WHERE hd.ID_HORARIO = ?
    ORDER BY d.ID_DIA
");
$stmt2->execute([$id]);
$dias = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$row['DIAS'] = array_column($dias, 'NOMBRE');
$row['DIAS_ID'] = array_column($dias, 'ID_DIA');

echo json_encode(['success'=>true, 'horario'=>$row]);