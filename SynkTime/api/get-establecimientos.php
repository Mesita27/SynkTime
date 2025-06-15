<?php
require_once '../auth/session.php';
require_once '../config/database.php';

// Verificar autenticación
requireAuth();

// Verificar que se recibió el ID de sede
if (!isset($_GET['sede_id'])) {
    echo json_encode(['error' => 'ID de sede no proporcionado']);
    exit;
}

$sedeId = (int)$_GET['sede_id'];

try {
    $conn = getConnection();
    $stmt = $conn->prepare("
        SELECT ID_ESTABLECIMIENTO, NOMBRE
        FROM ESTABLECIMIENTO
        WHERE ID_SEDE = :sedeId AND ESTADO = 'A'
        ORDER BY NOMBRE
    ");
    $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
    $stmt->execute();
    
    $establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'establecimientos' => $establecimientos]);
} catch (PDOException $e) {
    error_log("Error obteniendo establecimientos: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener establecimientos']);
}
?>