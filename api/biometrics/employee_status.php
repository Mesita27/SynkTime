<?php
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/biometric_integration.php';

header('Content-Type: application/json');

try {
    $employeeId = $_GET['employee_id'] ?? null;
    if (!$employeeId) {
        http_response_code(400);
        echo json_encode(['error' => 'employee_id parameter required']);
        exit;
    }

    $status = get_employee_biometric_status($employeeId);
    $stats = get_employee_biometric_stats($employeeId, 30);
    
    echo json_encode([
        'employee_id' => $employeeId,
        'biometric_status' => $status,
        'recent_stats' => $stats
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}