<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/session.php';

// Verificar autenticación
requireAuth();

header('Content-Type: application/json');

$currentUser = getCurrentUser();
$empresaId = $currentUser['id_empresa'] ?? null;

if (!$empresaId) {
    echo json_encode(['success' => false, 'sedes' => []]);
    exit;
}

$stmt = $conn->prepare("SELECT ID_SEDE, NOMBRE FROM SEDE WHERE ID_EMPRESA = :empresaId AND ACTIVO = 'S' ORDER BY NOMBRE");
$stmt->bindValue(':empresaId', $empresaId, PDO::PARAM_INT);
$stmt->execute();
$sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'sedes' => $sedes]);