<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');

$employee_id = $_POST['employee_id'] ?? null;
$face_descriptor = $_POST['face_descriptor'] ?? null;

if (!$employee_id || !$face_descriptor) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos de empleado y descriptor facial requeridos'
    ]);
    exit;
}

try {
    // Parse face descriptor
    $descriptor = json_decode($face_descriptor, true);
    if (!$descriptor || !is_array($descriptor)) {
        throw new Exception('Descriptor facial inválido');
    }
    
    // Get employee's registered facial recognition data
    $sql = "SELECT DATOS_BIOMETRICO, METADATA FROM EMPLEADO_BIOMETRICO 
            WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = 'FACIAL' AND ACTIVO = 'S'
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$employee_id]);
    $biometricData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$biometricData) {
        throw new Exception('No se encontraron datos biométricos faciales para este empleado');
    }
    
    // Perform facial recognition verification
    $verificationResult = performFacialRecognition($descriptor, $biometricData);
    
    // Get confidence threshold
    $sqlConfig = "SELECT CONFIG_VALUE FROM BIOMETRIC_CONFIG 
                  WHERE CONFIG_KEY = 'facial_confidence_threshold'";
    $stmt = $conn->prepare($sqlConfig);
    $stmt->execute();
    $threshold = floatval($stmt->fetchColumn() ?: 85.0);
    
    $success = $verificationResult['confidence'] >= $threshold;
    
    // Log verification attempt
    logBiometricVerification(
        $employee_id, 
        'FACIAL', 
        $success ? 'SUCCESS' : 'FAILED',
        $verificationResult['confidence'],
        $success ? null : 'Confidence score below threshold'
    );
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Rostro verificado correctamente',
            'confidence' => $verificationResult['confidence'],
            'verification_id' => $verificationResult['verification_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Rostro no reconocido',
            'confidence' => $verificationResult['confidence']
        ]);
    }
    
} catch (Exception $e) {
    // Log error
    if (isset($employee_id)) {
        logBiometricVerification(
            $employee_id, 
            'FACIAL', 
            'ERROR',
            0,
            $e->getMessage()
        );
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en reconocimiento facial: ' . $e->getMessage()
    ]);
}

function performFacialRecognition($inputDescriptor, $biometricData) {
    // Parse stored facial descriptor
    $storedDescriptor = json_decode($biometricData['DATOS_BIOMETRICO'], true);
    
    if (!$storedDescriptor || !is_array($storedDescriptor)) {
        throw new Exception('Datos biométricos faciales corruptos');
    }
    
    // Calculate Euclidean distance between descriptors
    $distance = calculateEuclideanDistance($inputDescriptor, $storedDescriptor);
    
    // Convert distance to confidence percentage
    // Lower distance = higher confidence
    // This is a simplified approach; real implementations use more sophisticated methods
    $maxDistance = 1.0; // Typical max distance for face descriptors
    $confidence = max(0, (1 - ($distance / $maxDistance)) * 100);
    
    return [
        'confidence' => round($confidence, 2),
        'distance' => round($distance, 4),
        'verification_id' => uniqid('face_verify_', true)
    ];
}

function calculateEuclideanDistance($desc1, $desc2) {
    if (count($desc1) !== count($desc2)) {
        throw new Exception('Descriptores faciales incompatibles');
    }
    
    $sum = 0;
    for ($i = 0; $i < count($desc1); $i++) {
        $diff = $desc1[$i] - $desc2[$i];
        $sum += $diff * $diff;
    }
    
    return sqrt($sum);
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