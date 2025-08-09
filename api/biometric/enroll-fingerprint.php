<?php
/**
 * Fingerprint Enrollment API Endpoint
 * Handles enrollment of fingerprint biometric data
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
    $fingerType = $input['finger_type'] ?? '';
    $fingerprintData = $input['fingerprint_data'] ?? null;
    
    if (!$employeeId || !$fingerType) {
        throw new Exception('ID de empleado y tipo de dedo son requeridos');
    }
    
    // Validate employee exists
    $stmt = $conn->prepare("SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleado WHERE ID_EMPLEADO = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado');
    }
    
    // Generate simulated fingerprint template if not provided
    if (!$fingerprintData) {
        $fingerprintData = generateFingerprintTemplate($employeeId, $fingerType);
    }
    
    // Check if fingerprint for this finger already exists
    $stmt = $conn->prepare("
        SELECT ID FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'fingerprint' AND FINGER_TYPE = ?
    ");
    $stmt->execute([$employeeId, $fingerType]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing fingerprint
        $stmt = $conn->prepare("
            UPDATE biometric_data 
            SET BIOMETRIC_DATA = ?, UPDATED_AT = CURRENT_TIMESTAMP, ACTIVO = 1
            WHERE ID = ?
        ");
        $stmt->execute([$fingerprintData, $existing['ID']]);
        $action = 'updated';
    } else {
        // Insert new fingerprint
        $stmt = $conn->prepare("
            INSERT INTO biometric_data 
            (ID_EMPLEADO, BIOMETRIC_TYPE, FINGER_TYPE, BIOMETRIC_DATA, ACTIVO) 
            VALUES (?, 'fingerprint', ?, ?, 1)
        ");
        $stmt->execute([$employeeId, $fingerType, $fingerprintData]);
        $action = 'enrolled';
    }
    
    // Log enrollment
    logBiometricEnrollment($conn, $employeeId, 'fingerprint', true);
    
    echo json_encode([
        'success' => true,
        'message' => "Huella dactilar {$action} correctamente",
        'employee' => [
            'id' => $employee['ID_EMPLEADO'],
            'name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO']
        ],
        'finger_type' => $fingerType,
        'action' => $action
    ]);
    
} catch (Exception $e) {
    // Log failed enrollment
    if (isset($employeeId)) {
        logBiometricEnrollment($conn, $employeeId, 'fingerprint', false);
    }
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Generate simulated fingerprint template
 */
function generateFingerprintTemplate($employeeId, $fingerType) {
    // Generate a simulated but consistent fingerprint template
    $seed = $employeeId . $fingerType . 'fingerprint_seed';
    $template = [];
    
    // Generate 256 bytes of template data based on seed
    for ($i = 0; $i < 256; $i++) {
        $template[] = (crc32($seed . $i) % 256);
    }
    
    // Encode template as base64
    return base64_encode(pack('C*', ...$template));
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
            VALUES (?, ?, ?, 'enrollment', CURDATE(), CURTIME(), 'enroll_fingerprint_api')
        ");
        $stmt->execute([$employeeId, $method, $success ? 1 : 0]);
    } catch (Exception $e) {
        error_log("Error logging biometric enrollment: " . $e->getMessage());
    }
}
?>