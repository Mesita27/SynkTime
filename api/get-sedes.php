<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
$empresaId = $_SESSION['id_empresa'] ?? null;
if (!$empresaId) {
    echo json_encode(['success' => false, 'sedes' => []]); exit;
}
$stmt = $conn->prepare("SELECT ID_SEDE, NOMBRE FROM SEDE WHERE ID_EMPRESA = :empresaId AND ESTADO = 'A' ORDER BY NOMBRE");
$stmt->bindValue(':empresaId', $empresaId, PDO::PARAM_INT);
$stmt->execute();
$sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'sedes' => $sedes]);