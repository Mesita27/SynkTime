<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');

$employee_id = $_POST['employee_id'] ?? null;
$biometric_type = $_POST['biometric_type'] ?? null;
$biometric_data = $_POST['biometric_data'] ?? null;
$metadata = $_POST['metadata'] ?? '{}';

if (!$employee_id || !$biometric_type || !$biometric_data) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos requeridos: ID empleado, tipo biométrico y datos biométricos'
    ]);
    exit;
}

// Validate biometric type
if (!in_array($biometric_type, ['FINGERPRINT', 'FACIAL'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipo biométrico inválido'
    ]);
    exit;
}

try {
    // Verify employee exists
    $sqlEmployee = "SELECT COUNT(*) as count FROM EMPLEADO WHERE ID_EMPLEADO = ?";
    $stmt = $conn->prepare($sqlEmployee);
    $stmt->execute([$employee_id]);
    
    if ($stmt->fetch(PDO::FETCH_ASSOC)['count'] == 0) {
        throw new Exception('Empleado no encontrado');
    }
    
    // Check if biometric data already exists for this employee and type
    $sqlExists = "SELECT COUNT(*) as count FROM EMPLEADO_BIOMETRICO 
                  WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = ? AND ACTIVO = 'S'";
    $stmt = $conn->prepare($sqlExists);
    $stmt->execute([$employee_id, $biometric_type]);
    
    $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
    
    // Encrypt biometric data for security
    $encrypted_data = encryptBiometricData($biometric_data);
    
    $current_user_id = $_SESSION['user_id'] ?? null;
    
    if ($exists) {
        // Update existing biometric data
        $sql = "UPDATE EMPLEADO_BIOMETRICO 
                SET DATOS_BIOMETRICO = ?, METADATA = ?, FECHA_ACTUALIZACION = CURRENT_TIMESTAMP
                WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = ? AND ACTIVO = 'S'";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([$encrypted_data, $metadata, $employee_id, $biometric_type]);
        
        $action = 'actualizado';
    } else {
        // Insert new biometric data
        $sql = "INSERT INTO EMPLEADO_BIOMETRICO 
                (ID_EMPLEADO, TIPO_BIOMETRICO, DATOS_BIOMETRICO, METADATA, CREADO_POR) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([$employee_id, $biometric_type, $encrypted_data, $metadata, $current_user_id]);
        
        $action = 'registrado';
    }
    
    if ($success) {
        // Log successful registration
        $log_sql = "INSERT INTO BIOMETRIC_VERIFICATION_LOG 
                    (ID_EMPLEADO, TIPO_BIOMETRICO, RESULTADO, CONFIDENCE_SCORE, DETALLE_ERROR, IP_ADDRESS, USER_AGENT)
                    VALUES (?, ?, 'SUCCESS', 100.0, ?, ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->execute([
            $employee_id,
            $biometric_type,
            "Datos biométricos {$action} exitosamente",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => "Datos biométricos {$action} exitosamente",
            'action' => $action,
            'biometric_type' => $biometric_type
        ]);
    } else {
        throw new Exception('Error al guardar datos biométricos en la base de datos');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar datos biométricos: ' . $e->getMessage()
    ]);
}

function encryptBiometricData($data) {
    // Simple encryption for demo purposes
    // In production, use proper encryption methods like AES-256
    $key = 'SynkTime_Biometric_Key_2024';
    return base64_encode(openssl_encrypt($data, 'AES-128-CBC', $key, 0, substr(md5($key), 0, 16)));
}

function decryptBiometricData($encrypted_data) {
    // Corresponding decryption function
    $key = 'SynkTime_Biometric_Key_2024';
    return openssl_decrypt(base64_decode($encrypted_data), 'AES-128-CBC', $key, 0, substr(md5($key), 0, 16));
}
?>