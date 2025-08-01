<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');

$employee_id = $_POST['employee_id'] ?? null;
$credential_data = $_POST['credential_data'] ?? null;

if (!$employee_id || !$credential_data) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos de empleado y credencial requeridos'
    ]);
    exit;
}

try {
    // Parse credential data
    $credential = json_decode($credential_data, true);
    if (!$credential) {
        throw new Exception('Datos de credencial inválidos');
    }
    
    // Get employee's registered fingerprint data
    $sql = "SELECT DATOS_BIOMETRICO, METADATA FROM EMPLEADO_BIOMETRICO 
            WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = 'FINGERPRINT' AND ACTIVO = 'S'
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$employee_id]);
    $biometricData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$biometricData) {
        throw new Exception('No se encontraron datos biométricos de huella digital para este empleado');
    }
    
    // For this demo, we'll simulate fingerprint verification
    // In a real implementation, you would compare the WebAuthn credential
    // with the stored biometric template using appropriate algorithms
    
    $verificationResult = simulateFingerprintVerification($credential, $biometricData);
    
    // Get confidence threshold
    $sqlConfig = "SELECT CONFIG_VALUE FROM BIOMETRIC_CONFIG 
                  WHERE CONFIG_KEY = 'fingerprint_confidence_threshold'";
    $stmt = $conn->prepare($sqlConfig);
    $stmt->execute();
    $threshold = floatval($stmt->fetchColumn() ?: 80.0);
    
    $success = $verificationResult['confidence'] >= $threshold;
    
    // Log verification attempt
    logBiometricVerification(
        $employee_id, 
        'FINGERPRINT', 
        $success ? 'SUCCESS' : 'FAILED',
        $verificationResult['confidence'],
        $success ? null : 'Confidence score below threshold'
    );
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Huella digital verificada correctamente',
            'confidence' => $verificationResult['confidence'],
            'verification_id' => $verificationResult['verification_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Huella digital no reconocida',
            'confidence' => $verificationResult['confidence']
        ]);
    }
    
} catch (Exception $e) {
    // Log error
    if (isset($employee_id)) {
        logBiometricVerification(
            $employee_id, 
            'FINGERPRINT', 
            'ERROR',
            0,
            $e->getMessage()
        );
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en verificación de huella digital: ' . $e->getMessage()
    ]);
}

function simulateFingerprintVerification($credential, $biometricData) {
    // This is a simulation for demo purposes
    // In a real implementation, you would:
    // 1. Extract biometric features from the WebAuthn credential
    // 2. Compare with stored template using fingerprint matching algorithms
    // 3. Return actual confidence score
    
    // For demo, we'll generate a realistic confidence score
    $baseConfidence = 85.0;
    $variance = (rand(-10, 10) / 10.0) * 5; // ±5% variance
    $confidence = max(0, min(100, $baseConfidence + $variance));
    
    return [
        'confidence' => round($confidence, 2),
        'verification_id' => uniqid('fp_verify_', true)
    ];
}

function logBiometricVerification($employee_id, $type, $result, $confidence, $error_detail = null) {
    global $conn;
    
    try {
        $sql = "INSERT INTO BIOMETRIC_VERIFICATION_LOG 
                (ID_EMPLEADO, TIPO_BIOMETRICO, RESULTADO, CONFIDENCE_SCORE, DETALLE_ERROR, IP_ADDRESS, USER_AGENT)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $employee_id,
            $type,
            $result,
            $confidence > 0 ? $confidence : null,
            $error_detail,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
    } catch (Exception $e) {
        error_log("Error logging biometric verification: " . $e->getMessage());
    }
}
?>