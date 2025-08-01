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
$verification_data = $input['verification_data'] ?? null;

if (!$employee_id || !$biometric_type || !$verification_data) {
    echo json_encode(['success' => false, 'message' => 'Employee ID, biometric type, and verification data required']);
    exit;
}

if (!in_array($biometric_type, ['fingerprint', 'facial'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid biometric type']);
    exit;
}

try {
    // Get enrolled biometric data for the employee
    $stmt = $conn->prepare("
        SELECT biometric_data, quality_score 
        FROM employee_biometrics 
        WHERE employee_id = ? AND biometric_type = ? AND is_active = TRUE
    ");
    $stmt->execute([$employee_id, $biometric_type]);
    $enrolledBiometric = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrolledBiometric) {
        echo json_encode([
            'success' => false, 
            'verified' => false,
            'message' => 'No biometric data enrolled for this employee'
        ]);
        exit;
    }
    
    // Perform verification based on biometric type
    $verificationResult = performBiometricVerification(
        $enrolledBiometric['biometric_data'], 
        $verification_data, 
        $biometric_type
    );
    
    // Log verification attempt
    $stmt = $conn->prepare("
        INSERT INTO biometric_verification_logs 
        (employee_id, biometric_type, verification_result, confidence_score, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $employee_id,
        $biometric_type,
        $verificationResult['verified'] ? 'success' : 'failed',
        $verificationResult['confidence'],
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    echo json_encode([
        'success' => true,
        'verified' => $verificationResult['verified'],
        'confidence' => $verificationResult['confidence'],
        'message' => $verificationResult['message']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'verified' => false,
        'message' => 'Verification error: ' . $e->getMessage()
    ]);
}

function performBiometricVerification($enrolledData, $verificationData, $biometricType) {
    try {
        if ($biometricType === 'fingerprint') {
            return verifyFingerprint($enrolledData, $verificationData);
        } elseif ($biometricType === 'facial') {
            return verifyFacial($enrolledData, $verificationData);
        }
        
        return [
            'verified' => false,
            'confidence' => 0,
            'message' => 'Unsupported biometric type'
        ];
        
    } catch (Exception $e) {
        return [
            'verified' => false,
            'confidence' => 0,
            'message' => 'Verification processing error: ' . $e->getMessage()
        ];
    }
}

function verifyFingerprint($enrolledTemplate, $verificationData) {
    // In a real implementation, this would use fingerprint matching algorithms
    // For demo purposes, we'll simulate verification with random success/failure
    
    try {
        // Decrypt enrolled template
        $decryptedEnrolled = openssl_decrypt(
            base64_decode($enrolledTemplate), 
            'AES-256-CBC', 
            'biometric_key_' . date('Y'), 
            0, 
            substr(md5('synktime_biometric'), 0, 16)
        );
        
        if (!$decryptedEnrolled) {
            throw new Exception('Failed to decrypt enrolled fingerprint template');
        }
        
        // Simulate fingerprint matching (in real implementation, use proper matching algorithm)
        $verificationTemplate = $verificationData['template'] ?? '';
        
        // Simple simulation: random success with higher probability for demo
        $matchProbability = 0.8; // 80% success rate for demo
        $isMatch = (mt_rand() / mt_getrandmax()) < $matchProbability;
        
        if ($isMatch) {
            $confidence = mt_rand(80, 95); // High confidence for matches
            return [
                'verified' => true,
                'confidence' => $confidence,
                'message' => 'Fingerprint verified successfully'
            ];
        } else {
            $confidence = mt_rand(20, 40); // Low confidence for non-matches
            return [
                'verified' => false,
                'confidence' => $confidence,
                'message' => 'Fingerprint does not match'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'verified' => false,
            'confidence' => 0,
            'message' => 'Fingerprint verification error: ' . $e->getMessage()
        ];
    }
}

function verifyFacial($enrolledDescriptor, $verificationData) {
    try {
        // Decrypt enrolled descriptor
        $decryptedEnrolled = openssl_decrypt(
            base64_decode($enrolledDescriptor), 
            'AES-256-CBC', 
            'biometric_key_' . date('Y'), 
            0, 
            substr(md5('synktime_biometric'), 0, 16)
        );
        
        if (!$decryptedEnrolled) {
            throw new Exception('Failed to decrypt enrolled facial descriptor');
        }
        
        $enrolledVector = json_decode($decryptedEnrolled, true);
        $verificationVector = $verificationData['descriptor'] ?? [];
        
        if (!is_array($enrolledVector) || !is_array($verificationVector)) {
            throw new Exception('Invalid descriptor format');
        }
        
        if (count($enrolledVector) !== count($verificationVector)) {
            throw new Exception('Descriptor dimension mismatch');
        }
        
        // Calculate Euclidean distance between face descriptors
        $distance = calculateEuclideanDistance($enrolledVector, $verificationVector);
        
        // Convert distance to similarity score (lower distance = higher similarity)
        // Face-api.js typically uses threshold of 0.6 for matching
        $threshold = 0.6;
        $similarity = max(0, (1 - ($distance / $threshold)) * 100);
        
        $isMatch = $distance < $threshold;
        
        return [
            'verified' => $isMatch,
            'confidence' => min(100, max(0, $similarity)),
            'message' => $isMatch ? 'Face verified successfully' : 'Face does not match'
        ];
        
    } catch (Exception $e) {
        return [
            'verified' => false,
            'confidence' => 0,
            'message' => 'Facial verification error: ' . $e->getMessage()
        ];
    }
}

function calculateEuclideanDistance($vector1, $vector2) {
    $sum = 0;
    for ($i = 0; $i < count($vector1); $i++) {
        $diff = $vector1[$i] - $vector2[$i];
        $sum += $diff * $diff;
    }
    return sqrt($sum);
}
?>