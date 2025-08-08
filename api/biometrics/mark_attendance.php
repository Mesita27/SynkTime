<?php
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/biometric_integration.php';
$config = require __DIR__ . '/../../config/biometrics.php';

header('Content-Type: application/json');

// Enhanced mark_attendance that integrates with existing attendance system
try {
    $employeeId = $_POST['employee_id'] ?? null;
    $channel    = $_POST['channel'] ?? null; // face|fingerprint|photo
    $score      = isset($_POST['score']) ? (float)$_POST['score'] : null;
    $imageData  = $_POST['image'] ?? null; // base64 foto (o null para huella)
    $providerRef= $_POST['provider_ref'] ?? null; // subject_id o fingerprint_id
    
    if (!$employeeId || !$channel) {
        http_response_code(400);
        echo json_encode(['error' => 'employee_id and channel are required']);
        exit;
    }

    // Use the enhanced integration function
    $result = process_biometric_attendance($employeeId, $channel, $score, $imageData, $providerRef);
    
    echo json_encode([
        'ok' => true, 
        'attendance_id' => $result['attendance_id'],
        'biometric_event_id' => $result['biometric_event_id'],
        'image_path' => $result['image_path']
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}