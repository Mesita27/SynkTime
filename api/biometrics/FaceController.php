<?php
/**
 * FaceController
 * API controller for facial recognition operations
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../services/Biometrics/FaceService.php';
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
    $faceService = new FaceService();
    $biometricRepo = new BiometricDataRepository($conn);
    $logsRepo = new BiometricLogsRepository($conn);
    $empleadoRepo = new EmpleadoRepository($conn);
    
    // Route handling
    if ($method === 'POST' && strpos($path, '/api/biometrics/face/enroll') !== false) {
        handleEnroll($faceService, $biometricRepo, $logsRepo, $empleadoRepo);
    } elseif ($method === 'POST' && strpos($path, '/api/biometrics/face/verify') !== false) {
        handleVerify($faceService, $biometricRepo, $logsRepo, $empleadoRepo);
    } elseif ($method === 'GET' && strpos($path, '/api/biometrics/face/status') !== false) {
        handleStatus($faceService);
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
 * Handle facial enrollment
 */
function handleEnroll($faceService, $biometricRepo, $logsRepo, $empleadoRepo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $employeeId = $input['id_empleado'] ?? null;
    $images = $input['images'] ?? [];
    
    if (!$employeeId || empty($images)) {
        throw new Exception('Datos incompletos: se requiere id_empleado e imágenes');
    }
    
    // Validate employee
    $employee = $empleadoRepo->getById($employeeId);
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Validate images
    if (!is_array($images) || count($images) < MIN_FACIAL_IMAGES || count($images) > MAX_FACIAL_IMAGES) {
        throw new Exception('Se requieren entre ' . MIN_FACIAL_IMAGES . ' y ' . MAX_FACIAL_IMAGES . ' imágenes faciales');
    }
    
    // Extract embeddings from images
    $embeddings = [];
    foreach ($images as $index => $imageData) {
        try {
            $embedding = $faceService->extractEmbedding($imageData);
            $embeddings[] = $embedding;
        } catch (Exception $e) {
            throw new Exception("Error procesando imagen " . ($index + 1) . ": " . $e->getMessage());
        }
    }
    
    // Store embeddings in database
    $facialData = json_encode([
        'embeddings' => $embeddings,
        'image_count' => count($embeddings),
        'enrollment_date' => date('Y-m-d H:i:s'),
        'model' => $embeddings[0]['model'] ?? 'arcface'
    ]);
    
    // Check if facial data already exists
    $existing = $biometricRepo->getByEmployeeAndType($employeeId, 'facial');
    
    if ($existing) {
        // Update existing data
        $biometricRepo->update($existing['ID'], $facialData);
        $action = 'actualizado';
    } else {
        // Create new enrollment
        $biometricRepo->store($employeeId, 'facial', $facialData);
        $action = 'registrado';
    }
    
    // Log enrollment
    $logsRepo->log([
        'id_empleado' => $employeeId,
        'verification_method' => 'facial',
        'verification_success' => true,
        'operation_type' => 'enrollment',
        'api_source' => 'insightface-rest'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => "Patrón facial {$action} correctamente",
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'image_count' => count($embeddings),
        'action' => $action
    ]);
}

/**
 * Handle facial verification
 */
function handleVerify($faceService, $biometricRepo, $logsRepo, $empleadoRepo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $employeeId = $input['id_empleado'] ?? null;
    $image = $input['image'] ?? null;
    
    if (!$employeeId || !$image) {
        throw new Exception('Datos incompletos: se requiere id_empleado e imagen');
    }
    
    // Validate employee
    $employee = $empleadoRepo->getById($employeeId);
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Get stored facial data
    $storedData = $biometricRepo->getByEmployeeAndType($employeeId, 'facial');
    if (!$storedData) {
        // Log failed verification
        $logsRepo->log([
            'id_empleado' => $employeeId,
            'verification_method' => 'facial',
            'verification_success' => false,
            'operation_type' => 'verification',
            'api_source' => 'insightface-rest'
        ]);
        
        throw new Exception('No hay datos faciales registrados para este empleado');
    }
    
    $facialData = json_decode($storedData['BIOMETRIC_DATA'], true);
    if (!$facialData || !isset($facialData['embeddings'])) {
        throw new Exception('Datos faciales corruptos');
    }
    
    // Extract embedding from verification image
    $currentEmbedding = $faceService->extractEmbedding($image);
    
    // Compare against stored embeddings
    $bestMatch = null;
    $bestSimilarity = 0;
    
    foreach ($facialData['embeddings'] as $storedEmbedding) {
        $comparison = $faceService->compareFaces(
            $currentEmbedding['embedding'], 
            $storedEmbedding['embedding']
        );
        
        if ($comparison['similarity'] > $bestSimilarity) {
            $bestSimilarity = $comparison['similarity'];
            $bestMatch = $comparison;
        }
    }
    
    $isMatch = $bestMatch && $bestMatch['is_match'];
    
    // Log verification attempt
    $logsRepo->log([
        'id_empleado' => $employeeId,
        'verification_method' => 'facial',
        'verification_success' => $isMatch,
        'confidence_score' => $bestSimilarity,
        'operation_type' => 'verification',
        'api_source' => 'insightface-rest'
    ]);
    
    echo json_encode([
        'success' => true,
        'verified' => $isMatch,
        'confidence' => $bestSimilarity,
        'threshold' => FACE_MATCH_THRESHOLD,
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'message' => $isMatch ? 'Verificación facial exitosa' : 'Verificación facial fallida'
    ]);
}

/**
 * Handle API status check
 */
function handleStatus($faceService) {
    $status = $faceService->getStatus();
    $status['available'] = $faceService->testConnection();
    
    echo json_encode([
        'success' => true,
        'api_status' => $status,
        'threshold' => FACE_MATCH_THRESHOLD,
        'api_base' => BIOMETRICS_FACE_API_BASE
    ]);
}
?>