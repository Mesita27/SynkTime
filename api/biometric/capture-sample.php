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

$session_id = $input['session_id'] ?? null;
$sample_data = $input['sample_data'] ?? null;
$sample_number = $input['sample_number'] ?? null;

if (!$session_id || !$sample_data || !$sample_number) {
    echo json_encode(['success' => false, 'message' => 'Session ID, sample data, and sample number required']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Get session details
    $stmt = $conn->prepare("
        SELECT * FROM biometric_enrollment_sessions WHERE id = ?
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        throw new Exception('Enrollment session not found');
    }
    
    if ($session['session_status'] === 'completed') {
        throw new Exception('Enrollment session already completed');
    }
    
    // Process and validate the sample data
    $processedSample = processBiometricSample($sample_data, $session['biometric_type']);
    
    if (!$processedSample['valid']) {
        throw new Exception('Invalid biometric sample: ' . $processedSample['error']);
    }
    
    // Get existing session data
    $sessionData = json_decode($session['session_data'] ?? '[]', true);
    if (!is_array($sessionData)) {
        $sessionData = [];
    }
    
    // Add new sample
    $sessionData[] = [
        'sample_number' => $sample_number,
        'data' => $processedSample['data'],
        'quality' => $processedSample['quality'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Update session
    $stmt = $conn->prepare("
        UPDATE biometric_enrollment_sessions 
        SET samples_collected = ?, 
            session_data = ?,
            session_status = 'in_progress'
        WHERE id = ?
    ");
    $stmt->execute([
        count($sessionData),
        json_encode($sessionData),
        $session_id
    ]);
    
    // Get updated session
    $stmt = $conn->prepare("
        SELECT * FROM biometric_enrollment_sessions WHERE id = ?
    ");
    $stmt->execute([$session_id]);
    $updatedSession = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sample captured successfully',
        'sample_quality' => $processedSample['quality'],
        'session' => $updatedSession
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error capturing sample: ' . $e->getMessage()
    ]);
}

function processBiometricSample($sampleData, $biometricType) {
    try {
        if ($biometricType === 'fingerprint') {
            // Process fingerprint data
            if (!isset($sampleData['template']) || !isset($sampleData['quality'])) {
                return ['valid' => false, 'error' => 'Missing fingerprint template or quality data'];
            }
            
            // Validate template format (basic validation)
            $template = $sampleData['template'];
            if (empty($template) || strlen($template) < 10) {
                return ['valid' => false, 'error' => 'Invalid fingerprint template format'];
            }
            
            // Encrypt the template for storage
            $encryptedTemplate = base64_encode(openssl_encrypt(
                $template, 
                'AES-256-CBC', 
                'biometric_key_' . date('Y'), 
                0, 
                substr(md5('synktime_biometric'), 0, 16)
            ));
            
            return [
                'valid' => true,
                'data' => $encryptedTemplate,
                'quality' => min(100, max(0, (float)$sampleData['quality']))
            ];
            
        } elseif ($biometricType === 'facial') {
            // Process facial recognition data
            if (!isset($sampleData['descriptor']) || !is_array($sampleData['descriptor'])) {
                return ['valid' => false, 'error' => 'Missing or invalid facial descriptor'];
            }
            
            // Validate descriptor (should be array of 128 floats for face-api.js)
            if (count($sampleData['descriptor']) !== 128) {
                return ['valid' => false, 'error' => 'Invalid facial descriptor length'];
            }
            
            // Calculate quality based on descriptor variance
            $variance = array_sum(array_map(function($v) { return $v * $v; }, $sampleData['descriptor'])) / count($sampleData['descriptor']);
            $quality = min(100, max(30, $variance * 100));
            
            // Encrypt the descriptor
            $encryptedDescriptor = base64_encode(openssl_encrypt(
                json_encode($sampleData['descriptor']), 
                'AES-256-CBC', 
                'biometric_key_' . date('Y'), 
                0, 
                substr(md5('synktime_biometric'), 0, 16)
            ));
            
            return [
                'valid' => true,
                'data' => $encryptedDescriptor,
                'quality' => $quality
            ];
        }
        
        return ['valid' => false, 'error' => 'Unsupported biometric type'];
        
    } catch (Exception $e) {
        return ['valid' => false, 'error' => 'Processing error: ' . $e->getMessage()];
    }
}
?>