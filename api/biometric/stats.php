<?php
/**
 * API endpoint for biometric statistics
 * Returns enrollment statistics for dashboard
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

try {
    // Tables are created in database.php, no need to create them here
    
    // Get fingerprint enrollment count
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT ID_EMPLEADO) as count 
        FROM biometric_data 
        WHERE BIOMETRIC_TYPE = 'fingerprint' AND ACTIVO = 1
    ");
    $stmt->execute();
    $fingerprint_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get facial enrollment count
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT ID_EMPLEADO) as count 
        FROM biometric_data 
        WHERE BIOMETRIC_TYPE = 'facial' AND ACTIVO = 1
    ");
    $stmt->execute();
    $facial_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get complete biometric count (employees with both fingerprint and facial)
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT bd1.ID_EMPLEADO) as count
        FROM biometric_data bd1
        INNER JOIN biometric_data bd2 ON bd1.ID_EMPLEADO = bd2.ID_EMPLEADO
        WHERE bd1.BIOMETRIC_TYPE = 'fingerprint' 
        AND bd2.BIOMETRIC_TYPE = 'facial'
        AND bd1.ACTIVO = 1 
        AND bd2.ACTIVO = 1
    ");
    $stmt->execute();
    $complete_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get total active employees count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM empleados 
        WHERE ACTIVO = 1
    ");
    $stmt->execute();
    $total_employees = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Calculate pending enrollment (employees without any biometric data)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM empleados e
        LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO AND bd.ACTIVO = 1
        WHERE e.ACTIVO = 1 AND bd.ID_EMPLEADO IS NULL
    ");
    $stmt->execute();
    $pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    $stats = [
        'fingerprint_enrolled' => intval($fingerprint_count),
        'facial_enrolled' => intval($facial_count),
        'complete_biometric' => intval($complete_count),
        'pending_enrollment' => intval($pending_count),
        'total_employees' => intval($total_employees)
    ];

    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>