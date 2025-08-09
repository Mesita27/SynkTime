<?php
/**
 * TraditionalController
 * API controller for traditional photo capture operations
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';
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
    $logsRepo = new BiometricLogsRepository($conn);
    $empleadoRepo = new EmpleadoRepository($conn);
    
    // Route handling
    if ($method === 'POST' && strpos($path, '/api/biometrics/traditional/capture') !== false) {
        handleCapture($logsRepo, $empleadoRepo);
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
 * Handle traditional photo capture
 */
function handleCapture($logsRepo, $empleadoRepo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $employeeId = $input['id_empleado'] ?? null;
    $imageData = $input['image'] ?? null;
    
    if (!$employeeId || !$imageData) {
        throw new Exception('Datos incompletos: se requiere id_empleado e imagen');
    }
    
    // Validate employee
    $employee = $empleadoRepo->getById($employeeId);
    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }
    
    // Validate image format
    if (!validateImageFormat($imageData)) {
        throw new Exception('Formato de imagen no válido');
    }
    
    // Save photo to disk
    $photoPath = saveTraditionalPhoto($imageData, $employeeId);
    
    // Log traditional capture
    $logsRepo->log([
        'id_empleado' => $employeeId,
        'verification_method' => 'traditional',
        'verification_success' => true, // Traditional is always successful
        'operation_type' => 'verification',
        'api_source' => 'traditional'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Foto capturada correctamente',
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'photo_path' => $photoPath
    ]);
}

/**
 * Validate image format and basic properties
 */
function validateImageFormat($imageData) {
    // Check if it's a valid base64 image
    if (strpos($imageData, 'data:image/') !== 0) {
        return false;
    }
    
    // Extract and validate image data
    $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $imageData);
    $imageBinary = base64_decode($imageData);
    
    if ($imageBinary === false) {
        return false;
    }
    
    // Check file size
    if (strlen($imageBinary) > MAX_PHOTO_SIZE) {
        throw new Exception('Tamaño de imagen excede el límite permitido');
    }
    
    // Check if it's a valid image by trying to get image info
    $tempFile = tempnam(sys_get_temp_dir(), 'validate_image');
    file_put_contents($tempFile, $imageBinary);
    
    $imageInfo = getimagesize($tempFile);
    unlink($tempFile);
    
    if ($imageInfo === false) {
        return false;
    }
    
    // Check MIME type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($imageInfo['mime'], $allowedTypes)) {
        throw new Exception('Tipo de imagen no permitido. Use JPEG o PNG.');
    }
    
    return true;
}

/**
 * Save traditional photo to disk
 */
function saveTraditionalPhoto($imageData, $employeeId) {
    // Create upload directory if it doesn't exist
    $uploadDir = ATTENDANCE_UPLOAD_PATH;
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Clean image data
    $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $imageData);
    $imageData = str_replace(' ', '+', $imageData);
    $imageBinary = base64_decode($imageData);
    
    if ($imageBinary === false) {
        throw new Exception('Datos de imagen inválidos');
    }
    
    // Generate filename
    $timestamp = date('Ymd_His');
    $filename = "trad_{$employeeId}_{$timestamp}.jpg";
    $filepath = $uploadDir . $filename;
    
    if (file_put_contents($filepath, $imageBinary) === false) {
        throw new Exception('Error al guardar la imagen');
    }
    
    return 'uploads/asistencia/' . $filename;
}
?>