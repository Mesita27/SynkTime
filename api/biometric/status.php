<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$employee_id = $_GET['employee_id'] ?? null;

if (!$employee_id) {
    echo json_encode(['success' => false, 'message' => 'Employee ID required']);
    exit;
}

try {
    // Check biometric enrollment status for the employee
    $stmt = $conn->prepare("
        SELECT 
            biometric_type,
            enrollment_date,
            quality_score,
            is_active
        FROM employee_biometrics 
        WHERE employee_id = ? AND is_active = TRUE
    ");
    $stmt->execute([$employee_id]);
    $biometrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $status = [
        'fingerprint_enrolled' => false,
        'facial_enrolled' => false,
        'fingerprint_quality' => null,
        'facial_quality' => null,
        'fingerprint_date' => null,
        'facial_date' => null
    ];
    
    foreach ($biometrics as $biometric) {
        if ($biometric['biometric_type'] === 'fingerprint') {
            $status['fingerprint_enrolled'] = true;
            $status['fingerprint_quality'] = $biometric['quality_score'];
            $status['fingerprint_date'] = $biometric['enrollment_date'];
        } elseif ($biometric['biometric_type'] === 'facial') {
            $status['facial_enrolled'] = true;
            $status['facial_quality'] = $biometric['quality_score'];
            $status['facial_date'] = $biometric['enrollment_date'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'employee_id' => $employee_id,
        'fingerprint_enrolled' => $status['fingerprint_enrolled'],
        'facial_enrolled' => $status['facial_enrolled'],
        'status' => $status
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving biometric status: ' . $e->getMessage()
    ]);
}
?>