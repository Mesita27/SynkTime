<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$employee_id = $input['employee_id'] ?? null;
$biometric_type = $input['biometric_type'] ?? null;
$device_id = $input['device_id'] ?? null;
$required_samples = $input['required_samples'] ?? 3;

if (!$employee_id || !$biometric_type) {
    echo json_encode(['success' => false, 'message' => 'Employee ID and biometric type required']);
    exit;
}

if (!in_array($biometric_type, ['fingerprint', 'facial'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid biometric type']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Check if there's an active session for this employee and type
    $stmt = $conn->prepare("
        SELECT id FROM biometric_enrollment_sessions 
        WHERE employee_id = ? AND biometric_type = ? AND session_status IN ('started', 'in_progress')
    ");
    $stmt->execute([$employee_id, $biometric_type]);
    $existingSession = $stmt->fetch();
    
    if ($existingSession) {
        // Update existing session
        $stmt = $conn->prepare("
            UPDATE biometric_enrollment_sessions 
            SET session_status = 'started', 
                samples_collected = 0,
                session_data = NULL,
                started_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$existingSession['id']]);
        $session_id = $existingSession['id'];
    } else {
        // Create new session
        $stmt = $conn->prepare("
            INSERT INTO biometric_enrollment_sessions 
            (employee_id, biometric_type, session_status, required_samples, created_by) 
            VALUES (?, ?, 'started', ?, ?)
        ");
        $stmt->execute([$employee_id, $biometric_type, $required_samples, $_SESSION['user_id'] ?? 1]);
        $session_id = $conn->lastInsertId();
    }
    
    // Get session details
    $stmt = $conn->prepare("
        SELECT * FROM biometric_enrollment_sessions WHERE id = ?
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Enrollment session started',
        'session' => $session
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error starting enrollment session: ' . $e->getMessage()
    ]);
}
?>