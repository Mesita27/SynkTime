<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir la conexión a la base de datos
require_once '../config/database.php';

// Verificar que se reciba el parámetro sede_id
if (!isset($_GET['sede_id']) || empty($_GET['sede_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID de sede requerido'
    ]);
    exit;
}

$sedeId = (int)$_GET['sede_id'];

try {
    // Obtener establecimientos de la sede
    $stmt = $conn->prepare("
        SELECT 
            ID_ESTABLECIMIENTO,
            NOMBRE,
            DIRECCION,
            ID_SEDE
        FROM ESTABLECIMIENTO 
        WHERE ID_SEDE = :sedeId 
        AND ACTIVO = 'S'
        ORDER BY NOMBRE
    ");
    
    $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
    $stmt->execute();
    
    $establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'establecimientos' => $establecimientos,
        'total' => count($establecimientos)
    ]);
    
} catch (PDOException $e) {
    error_log("Error al obtener establecimientos: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>