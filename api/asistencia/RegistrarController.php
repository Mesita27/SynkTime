<?php
/**
 * RegistrarController
 * Main API controller for attendance registration with all verification methods
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../services/AsistenciaService.php';
require_once __DIR__ . '/../../services/EmployeeService.php';
require_once __DIR__ . '/../../services/Biometrics/FaceService.php';
require_once __DIR__ . '/../../services/Biometrics/FingerprintService.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $employeeId = $input['id_empleado'] ?? null;
    $method = $input['method'] ?? null;
    $payload = $input['payload'] ?? [];
    
    if (!$employeeId || !$method) {
        throw new Exception('Datos incompletos: se requiere id_empleado y method');
    }
    
    // Validate method
    $allowedMethods = ['facial', 'fingerprint', 'traditional'];
    if (!in_array($method, $allowedMethods)) {
        throw new Exception('Método de verificación no válido');
    }
    
    // Initialize services
    $asistenciaService = new AsistenciaService($conn);
    $employeeService = new EmployeeService($conn);
    
    // Validate employee can register attendance
    $canRegister = $asistenciaService->canRegisterAttendance($employeeId);
    if (!$canRegister['can_register']) {
        throw new Exception($canRegister['reason']);
    }
    
    // Process verification based on method
    $verificationResult = null;
    $imageData = null;
    $biometricData = null;
    
    switch ($method) {
        case 'facial':
            $verificationResult = processFacialVerification($employeeId, $payload);
            $imageData = $payload['image'] ?? null;
            $biometricData = ['confidence' => $verificationResult['confidence'] ?? 0];
            break;
            
        case 'fingerprint':
            $verificationResult = processFingerprintVerification($employeeId, $payload);
            $imageData = null; // Fingerprint uses placeholder
            $biometricData = ['confidence' => $verificationResult['score'] / 100 ?? 0];
            break;
            
        case 'traditional':
            $verificationResult = processTraditionalCapture($employeeId, $payload);
            $imageData = $payload['image'] ?? null;
            $biometricData = ['confidence' => 1.0]; // Traditional is always 100%
            break;
    }
    
    if (!$verificationResult['success']) {
        throw new Exception($verificationResult['message']);
    }
    
    // Register attendance
    $attendance = $asistenciaService->registerAttendance(
        $employeeId, 
        $method, 
        $imageData, 
        $biometricData
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Asistencia registrada exitosamente',
        'attendance' => $attendance,
        'verification' => $verificationResult
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

/**
 * Process facial verification
 */
function processFacialVerification($employeeId, $payload) {
    $image = $payload['image'] ?? null;
    if (!$image) {
        return ['success' => false, 'message' => 'Imagen facial requerida'];
    }
    
    try {
        $faceService = new FaceService();
        $biometricRepo = new BiometricDataRepository($GLOBALS['conn']);
        
        // Get stored facial data
        $storedData = $biometricRepo->getByEmployeeAndType($employeeId, 'facial');
        if (!$storedData) {
            return ['success' => false, 'message' => 'No hay patrón facial registrado'];
        }
        
        $facialData = json_decode($storedData['BIOMETRIC_DATA'], true);
        if (!$facialData || !isset($facialData['embeddings'])) {
            return ['success' => false, 'message' => 'Datos faciales corruptos'];
        }
        
        // Extract embedding from verification image
        $currentEmbedding = $faceService->extractEmbedding($image);
        
        // Compare against stored embeddings
        $bestSimilarity = 0;
        foreach ($facialData['embeddings'] as $storedEmbedding) {
            $comparison = $faceService->compareFaces(
                $currentEmbedding['embedding'], 
                $storedEmbedding['embedding']
            );
            
            if ($comparison['similarity'] > $bestSimilarity) {
                $bestSimilarity = $comparison['similarity'];
            }
        }
        
        $isMatch = $bestSimilarity >= FACE_MATCH_THRESHOLD;
        
        return [
            'success' => $isMatch,
            'confidence' => $bestSimilarity,
            'threshold' => FACE_MATCH_THRESHOLD,
            'message' => $isMatch ? 'Verificación facial exitosa' : 'Verificación facial fallida'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error en verificación facial: ' . $e->getMessage()];
    }
}

/**
 * Process fingerprint verification
 */
function processFingerprintVerification($employeeId, $payload) {
    $image = $payload['image'] ?? $payload['base64'] ?? null;
    if (!$image) {
        return ['success' => false, 'message' => 'Imagen de huella requerida'];
    }
    
    try {
        $fingerprintService = new FingerprintService();
        $biometricRepo = new BiometricDataRepository($GLOBALS['conn']);
        
        // Get all fingerprint data for employee
        $fingerprintData = $biometricRepo->getByEmployee($employeeId);
        $fingerprintData = array_filter($fingerprintData, fn($d) => $d['BIOMETRIC_TYPE'] === 'fingerprint');
        
        if (empty($fingerprintData)) {
            return ['success' => false, 'message' => 'No hay huellas registradas'];
        }
        
        // Try to verify against each stored fingerprint
        $bestScore = 0;
        $bestFingerType = null;
        
        foreach ($fingerprintData as $stored) {
            $storedTemplate = json_decode($stored['BIOMETRIC_DATA'], true);
            if (!$storedTemplate || !isset($storedTemplate['template'])) {
                continue;
            }
            
            try {
                $verification = $fingerprintService->verifyFingerprint($image, $storedTemplate['template']);
                
                if ($verification['score'] > $bestScore) {
                    $bestScore = $verification['score'];
                    $bestFingerType = $stored['FINGER_TYPE'];
                }
            } catch (Exception $e) {
                continue;
            }
        }
        
        $isMatch = $bestScore >= FINGER_MATCH_THRESHOLD;
        
        return [
            'success' => $isMatch,
            'score' => $bestScore,
            'threshold' => FINGER_MATCH_THRESHOLD,
            'finger_type' => $bestFingerType,
            'message' => $isMatch ? 'Verificación de huella exitosa' : 'Verificación de huella fallida'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error en verificación de huella: ' . $e->getMessage()];
    }
}

/**
 * Process traditional photo capture
 */
function processTraditionalCapture($employeeId, $payload) {
    $image = $payload['image'] ?? null;
    
    // Traditional method doesn't require verification, just capture
    return [
        'success' => true,
        'message' => 'Foto tradicional capturada',
        'confidence' => 1.0
    ];
}
?>