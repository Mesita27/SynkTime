<?php
/**
 * Biometric Verification API Endpoint
 * Handles verification of biometric data against enrolled templates
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Verify user session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Datos de entrada inválidos');
    }
    
    $method = $input['method'] ?? '';
    $employeeId = $input['employee_id'] ?? '';
    
    if (!$method || !$employeeId) {
        throw new Exception('Método de verificación e ID de empleado son requeridos');
    }
    
    $result = performBiometricVerification($conn, $method, $employeeId, $input);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Perform biometric verification based on method
 */
function performBiometricVerification($conn, $method, $employeeId, $data) {
    switch ($method) {
        case 'fingerprint':
            return verifyFingerprint($conn, $employeeId, $data);
        case 'facial':
            return verifyFacial($conn, $employeeId, $data);
        case 'traditional':
            return verifyTraditional($data);
        default:
            throw new Exception('Método de verificación no válido');
    }
}

/**
 * Verify fingerprint against enrolled template
 */
function verifyFingerprint($conn, $employeeId, $data) {
    $fingerType = $data['finger_type'] ?? '';
    $fingerprintData = $data['fingerprint_data'] ?? '';
    
    if (!$fingerType || !$fingerprintData) {
        throw new Exception('Tipo de dedo y datos de huella son requeridos');
    }
    
    // Check if employee has enrolled fingerprint
    $stmt = $conn->prepare("
        SELECT BIOMETRIC_DATA 
        FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'fingerprint' AND FINGER_TYPE = ? AND ACTIVO = 1
    ");
    $stmt->execute([$employeeId, $fingerType]);
    $enrolled = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrolled) {
        throw new Exception('No se encontró huella inscrita para este dedo');
    }
    
    // Simulate fingerprint matching (in real implementation, use proper matching algorithm)
    $confidenceScore = simulateFingerprintMatching($enrolled['BIOMETRIC_DATA'], $fingerprintData);
    $isMatch = $confidenceScore >= 0.85; // 85% confidence threshold
    
    // Log verification attempt
    logBiometricVerification($conn, $employeeId, 'fingerprint', $isMatch, $confidenceScore);
    
    if ($isMatch) {
        return [
            'success' => true,
            'method' => 'fingerprint',
            'confidence_score' => $confidenceScore,
            'message' => 'Verificación de huella exitosa'
        ];
    } else {
        throw new Exception('La huella no coincide con la registrada');
    }
}

/**
 * Verify facial recognition against enrolled template
 */
function verifyFacial($conn, $employeeId, $data) {
    $imageData = $data['image_data'] ?? '';
    
    if (!$imageData) {
        throw new Exception('Datos de imagen son requeridos');
    }
    
    // Check if employee has enrolled facial data
    $stmt = $conn->prepare("
        SELECT BIOMETRIC_DATA 
        FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'facial' AND ACTIVO = 1
    ");
    $stmt->execute([$employeeId]);
    $enrolled = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrolled) {
        throw new Exception('No se encontró patrón facial inscrito para este empleado');
    }
    
    // Simulate facial recognition matching
    $confidenceScore = simulateFacialMatching($enrolled['BIOMETRIC_DATA'], $imageData);
    $isMatch = $confidenceScore >= 0.80; // 80% confidence threshold
    
    // Log verification attempt
    logBiometricVerification($conn, $employeeId, 'facial', $isMatch, $confidenceScore);
    
    if ($isMatch) {
        return [
            'success' => true,
            'method' => 'facial',
            'confidence_score' => $confidenceScore,
            'image_data' => $imageData,
            'message' => 'Reconocimiento facial exitoso'
        ];
    } else {
        throw new Exception('El rostro no coincide con el registrado');
    }
}

/**
 * Handle traditional photo verification (always succeeds for compatibility)
 */
function verifyTraditional($data) {
    $imageData = $data['image_data'] ?? '';
    
    if (!$imageData) {
        throw new Exception('Datos de imagen son requeridos');
    }
    
    return [
        'success' => true,
        'method' => 'traditional',
        'image_data' => $imageData,
        'message' => 'Foto capturada correctamente'
    ];
}

/**
 * Simulate fingerprint matching algorithm
 */
function simulateFingerprintMatching($enrolledData, $verificationData) {
    // In a real implementation, this would use proper biometric matching algorithms
    // For simulation, we'll use a simple comparison with some randomness
    
    $enrolledHash = md5($enrolledData);
    $verificationHash = md5($verificationData);
    
    // Simulate matching with some variance
    $similarity = 0.6 + (rand(0, 40) / 100); // 60-100% similarity
    
    // If hashes are similar (first 8 characters), boost similarity
    if (substr($enrolledHash, 0, 8) === substr($verificationHash, 0, 8)) {
        $similarity = max($similarity, 0.85);
    }
    
    return round($similarity, 4);
}

/**
 * Simulate facial matching algorithm
 */
function simulateFacialMatching($enrolledData, $verificationData) {
    // In a real implementation, this would use facial recognition algorithms
    // For simulation, we'll analyze image similarity
    
    $enrolledHash = md5($enrolledData);
    $verificationHash = md5($verificationData);
    
    // Simulate facial matching with some variance
    $similarity = 0.5 + (rand(0, 50) / 100); // 50-100% similarity
    
    // If image hashes are similar, boost similarity
    if (substr($enrolledHash, 0, 6) === substr($verificationHash, 0, 6)) {
        $similarity = max($similarity, 0.80);
    }
    
    return round($similarity, 4);
}

/**
 * Log biometric verification attempt
 */
function logBiometricVerification($conn, $employeeId, $method, $success, $confidenceScore) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO biometric_logs 
            (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, CONFIDENCE_SCORE, 
             OPERATION_TYPE, FECHA, HORA, API_SOURCE) 
            VALUES (?, ?, ?, ?, 'verification', CURDATE(), CURTIME(), 'verify_api')
        ");
        $stmt->execute([$employeeId, $method, $success ? 1 : 0, $confidenceScore]);
    } catch (Exception $e) {
        // Log error but don't fail the verification
        error_log("Error logging biometric verification: " . $e->getMessage());
    }
}
?>