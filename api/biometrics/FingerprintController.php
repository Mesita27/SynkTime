<?php
/**
 * FingerprintController
 * API controller for fingerprint recognition operations
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../services/Biometrics/FingerprintService.php';
require_once __DIR__ . '/../../repositories/BiometricDataRepository.php';
require_once __DIR__ . '/../../repositories/BiometricLogsRepository.php';
require_once __DIR__ . '/../../repositories/EmpleadoRepository.php';

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
    $path = $_SERVER['REQUEST_URI'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Initialize services
    $fingerprintService = new FingerprintService();
    $biometricRepo = new BiometricDataRepository($conn);
    $logsRepo = new BiometricLogsRepository($conn);
    $empleadoRepo = new EmpleadoRepository($conn);
    
    // Route handling
    if ($method === 'POST' && strpos($path, '/api/biometrics/fingerprint/enroll') !== false) {
        handleEnroll($fingerprintService, $biometricRepo, $logsRepo, $empleadoRepo);
    } elseif ($method === 'POST' && strpos($path, '/api/biometrics/fingerprint/verify') !== false) {
        handleVerify($fingerprintService, $biometricRepo, $logsRepo, $empleadoRepo);
    } elseif ($method === 'GET' && strpos($path, '/api/biometrics/fingerprint/status') !== false) {
        handleStatus($fingerprintService);
    } else {
        throw new Exception('Endpoint no encontrado');
    }
    
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
 * Handle fingerprint enrollment
 */
function handleEnroll($fingerprintService, $biometricRepo, $logsRepo, $empleadoRepo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $employeeId = $input['id_empleado'] ?? null;
    $fingerType = $input['finger_type'] ?? null;
    $imageData = $input['image'] ?? $input['base64'] ?? null;
    
    if (!$employeeId || !$fingerType || !$imageData) {
        throw new Exception('Datos incompletos: se requiere id_empleado, finger_type e imagen');
    }
    
    // Validate employee
    $employee = $empleadoRepo->getById($employeeId);
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Validate finger type
    if (!in_array($fingerType, SUPPORTED_FINGER_TYPES)) {
        throw new Exception('Tipo de dedo no válido');
    }
    
    // Validate image quality
    $qualityCheck = $fingerprintService->validateQuality($imageData);
    if (!$qualityCheck['valid']) {
        throw new Exception('Calidad de huella insuficiente: ' . implode(', ', $qualityCheck['recommendations']));
    }
    
    // Extract fingerprint template
    $enrollment = $fingerprintService->enrollFingerprint($imageData, $fingerType);
    
    // Store template in database
    $fingerprintData = json_encode([
        'template' => $enrollment['template'],
        'quality' => $enrollment['quality'],
        'minutiae_count' => $enrollment['minutiae_count'],
        'enrolled_at' => $enrollment['enrolled_at']
    ]);
    
    // Check if fingerprint already exists for this finger
    $existing = $biometricRepo->getByEmployeeAndType($employeeId, 'fingerprint', $fingerType);
    
    if ($existing) {
        // Update existing data
        $biometricRepo->update($existing['ID'], $fingerprintData);
        $action = 'actualizada';
    } else {
        // Create new enrollment
        $biometricRepo->store($employeeId, 'fingerprint', $fingerprintData, $fingerType);
        $action = 'registrada';
    }
    
    // Log enrollment
    $logsRepo->log([
        'id_empleado' => $employeeId,
        'verification_method' => 'fingerprint',
        'verification_success' => true,
        'confidence_score' => $enrollment['quality'] / 100, // Convert to 0-1 scale
        'operation_type' => 'enrollment',
        'api_source' => 'sourceafis'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Huella dactilar {$action} correctamente",
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'finger_type' => $fingerType,
        'quality' => $enrollment['quality'],
        'action' => $action
    ]);
}

/**
 * Handle fingerprint verification
 */
function handleVerify($fingerprintService, $biometricRepo, $logsRepo, $empleadoRepo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $employeeId = $input['id_empleado'] ?? null;
    $imageData = $input['image'] ?? $input['base64'] ?? null;
    
    if (!$employeeId || !$imageData) {
        throw new Exception('Datos incompletos: se requiere id_empleado e imagen');
    }
    
    // Validate employee
    $employee = $empleadoRepo->getById($employeeId);
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Get all fingerprint data for employee
    $fingerprintData = $biometricRepo->getByEmployee($employeeId);
    $fingerprintData = array_filter($fingerprintData, fn($d) => $d['BIOMETRIC_TYPE'] === 'fingerprint');
    
    if (empty($fingerprintData)) {
        // Log failed verification
        $logsRepo->log([
            'id_empleado' => $employeeId,
            'verification_method' => 'fingerprint',
            'verification_success' => false,
            'operation_type' => 'verification',
            'api_source' => 'sourceafis'
        ]);
        
        throw new Exception('No hay huellas dactilares registradas para este empleado');
    }
    
    // Try to verify against each stored fingerprint
    $bestMatch = null;
    $bestScore = 0;
    $bestFingerType = null;
    
    foreach ($fingerprintData as $stored) {
        $storedTemplate = json_decode($stored['BIOMETRIC_DATA'], true);
        if (!$storedTemplate || !isset($storedTemplate['template'])) {
            continue;
        }
        
        try {
            $verification = $fingerprintService->verifyFingerprint($imageData, $storedTemplate['template']);
            
            if ($verification['score'] > $bestScore) {
                $bestScore = $verification['score'];
                $bestMatch = $verification;
                $bestFingerType = $stored['FINGER_TYPE'];
            }
        } catch (Exception $e) {
            // Continue trying other fingers
            continue;
        }
    }
    
    $isMatch = $bestMatch && $bestMatch['is_match'];
    
    // Log verification attempt
    $logsRepo->log([
        'id_empleado' => $employeeId,
        'verification_method' => 'fingerprint',
        'verification_success' => $isMatch,
        'confidence_score' => $bestScore / 100, // Convert to 0-1 scale
        'operation_type' => 'verification',
        'api_source' => 'sourceafis'
    ]);
    
    echo json_encode([
        'success' => true,
        'verified' => $isMatch,
        'score' => $bestScore,
        'threshold' => FINGER_MATCH_THRESHOLD,
        'finger_type' => $bestFingerType,
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'message' => $isMatch ? 'Verificación de huella exitosa' : 'Verificación de huella fallida'
    ]);
}

/**
 * Handle API status check
 */
function handleStatus($fingerprintService) {
    $status = $fingerprintService->getStatus();
    $status['available'] = $fingerprintService->testConnection();
    
    echo json_encode([
        'success' => true,
        'api_status' => $status,
        'threshold' => FINGER_MATCH_THRESHOLD,
        'api_base' => BIOMETRICS_FINGER_API_BASE,
        'supported_fingers' => SUPPORTED_FINGER_TYPES
    ]);
}
?>