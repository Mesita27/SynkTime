<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

header('Content-Type: application/json');

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    $employeeId = $_GET['id'] ?? null;
    
    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado no proporcionado']);
        exit;
    }

    // Get biometric data for the employee
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.FACIAL_RECOGNITION_ENABLED,
            e.FINGERPRINT_ENABLED,
            e.BIOMETRIC_UPDATED_AT,
            e.BIOMETRIC_UPDATED_BY,
            CASE 
                WHEN e.FACIAL_DATA IS NOT NULL THEN 'Y'
                ELSE 'N'
            END as has_facial_data,
            CASE 
                WHEN e.FINGERPRINT_DATA IS NOT NULL THEN 'Y'
                ELSE 'N'
            END as has_fingerprint_data
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :employee_id AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':employee_id', $employeeId);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar datos biométricos: ' . $e->getMessage()
    ]);
}
?>