<?php
require_once '../auth/session.php';
require_once '../config/database.php';

// Verificar autenticación
requireAuth();

// Verificar que se recibió el ID de empresa
if (!isset($_GET['empresa_id'])) {
    echo json_encode(['error' => 'ID de empresa no proporcionado']);
    exit;
}

$empresaId = (int)$_GET['empresa_id'];

try {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT ID_SEDE, NOMBRE
        FROM SEDE
        WHERE ID_EMPRESA = :empresaId AND ESTADO = 'A'
        ORDER BY NOMBRE
    ");
    $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
    $stmt->execute();
    
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'sedes' => $sedes]);
} catch (PDOException $e) {
    error_log("Error obteniendo sedes: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener sedes']);
}
?>