<?php
/**
 * Facial Enrollment API Endpoint
 * Handles enrollment of facial biometric data
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
    
    $employeeId = $input['employee_id'] ?? '';
    $facialData = $input['facial_data'] ?? null;
    
    if (!$employeeId) {
        throw new Exception('ID de empleado es requerido');
    }
    
    // Validate employee exists
    $stmt = $conn->prepare("SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleado WHERE ID_EMPLEADO = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado');
    }
    
    // Generate simulated facial template if not provided
    if (!$facialData) {
        $facialData = generateFacialTemplate($employeeId);
    }
    
    // Check if facial data already exists
    $stmt = $conn->prepare("
        SELECT ID FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'facial'
    ");
    $stmt->execute([$employeeId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing facial data
        $stmt = $conn->prepare("
            UPDATE biometric_data 
            SET BIOMETRIC_DATA = ?, UPDATED_AT = CURRENT_TIMESTAMP, ACTIVO = 1
            WHERE ID = ?
        ");
        $stmt->execute([$facialData, $existing['ID']]);
        $action = 'updated';
    } else {
        // Insert new facial data
        $stmt = $conn->prepare("
            INSERT INTO biometric_data 
            (ID_EMPLEADO, BIOMETRIC_TYPE, BIOMETRIC_DATA, ACTIVO) 
            VALUES (?, 'facial', ?, 1)
        ");
        $stmt->execute([$employeeId, $facialData]);
        $action = 'enrolled';
    }
    
    // Log enrollment
    logBiometricEnrollment($conn, $employeeId, 'facial', true);
    
    echo json_encode([
        'success' => true,
        'message' => "Patrón facial {$action} correctamente",
        'employee' => [
            'id' => $employee['ID_EMPLEADO'],
            'name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO']
        ],
        'action' => $action
    ]);
    
} catch (Exception $e) {
    // Log failed enrollment
    if (isset($employeeId)) {
        logBiometricEnrollment($conn, $employeeId, 'facial', false);
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Generate simulated facial template
 */
function generateFacialTemplate($employeeId) {
    // Generate a simulated but consistent facial template
    $seed = $employeeId . 'facial_seed';
    $template = [];
    
    // Generate facial feature vectors (simulated)
    $features = [
        'eye_distance' => (crc32($seed . 'eyes') % 100) + 50,
        'nose_width' => (crc32($seed . 'nose') % 50) + 25,
        'mouth_width' => (crc32($seed . 'mouth') % 60) + 30,
        'face_width' => (crc32($seed . 'width') % 80) + 120,
        'face_height' => (crc32($seed . 'height') % 100) + 150,
    ];
    
    // Generate feature descriptor array
    for ($i = 0; $i < 128; $i++) {
        $template[] = (crc32($seed . $i . json_encode($features)) % 1000) / 1000.0;
    }
    
    // Encode template as base64
    return base64_encode(json_encode([
        'features' => $features,
        'descriptor' => $template,
        'version' => '1.0',
        'algorithm' => 'simulated_facial_recognition'
    ]));
}

/**
 * Log biometric enrollment
 */
function logBiometricEnrollment($conn, $employeeId, $method, $success) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO biometric_logs 
            (ID_EMPLEADO, VERIFICATION_METHOD, VERIFICATION_SUCCESS, OPERATION_TYPE, 
             FECHA, HORA, API_SOURCE) 
            VALUES (?, ?, ?, 'enrollment', CURDATE(), CURTIME(), 'enroll_facial_api')
        ");
        $stmt->execute([$employeeId, $method, $success ? 1 : 0]);
    } catch (Exception $e) {
        error_log("Error logging biometric enrollment: " . $e->getMessage());
    }
}
?>