<?php
/**
 * Enhanced API endpoint for fingerprint enrollment with WebAuthn integration
 * Supports WebAuthn credentials and traditional fingerprint data storage
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $employee_id = $_POST['employee_id'] ?? null;
    $finger_type = $_POST['finger_type'] ?? null;
    $fingerprint_data = $_POST['fingerprint_data'] ?? null;
    $webauthn_credential_id = $_POST['webauthn_credential_id'] ?? null;
    $public_key = $_POST['public_key'] ?? null;

    if (!$employee_id || (!$fingerprint_data && !$webauthn_credential_id)) {
        throw new Exception('Datos incompletos');
    }

    // Validate employee exists and is active
    $stmt = $conn->prepare("
        SELECT ID_EMPLEADO, NOMBRE, APELLIDO 
        FROM empleados 
        WHERE ID_EMPLEADO = ? AND ACTIVO = 1
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }

    // Validate finger type if provided
    if ($finger_type) {
        $valid_fingers = [
            'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_pinky',
            'right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_pinky'
        ];

        if (!in_array($finger_type, $valid_fingers)) {
            throw new Exception('Tipo de dedo inválido');
        }
    }

    // Enhanced biometric_data table with WebAuthn support
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS biometric_data (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
            FINGER_TYPE VARCHAR(20),
            BIOMETRIC_DATA LONGTEXT,
            FACIAL_DESCRIPTOR JSON,
            WEBAUTHN_CREDENTIAL_ID VARCHAR(500),
            PUBLIC_KEY TEXT,
            CONFIDENCE_SCORE DECIMAL(5,4),
            API_SOURCE VARCHAR(50) DEFAULT 'internal',
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ACTIVO TINYINT(1) DEFAULT 1,
            FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO),
            INDEX idx_employee_biometric (ID_EMPLEADO, BIOMETRIC_TYPE),
            INDEX idx_webauthn_credential (WEBAUTHN_CREDENTIAL_ID)
        )
    ";
    $conn->exec($create_table_sql);

    // Determine API source
    $api_source = 'internal';
    if ($webauthn_credential_id) {
        $api_source = 'webauthn';
    }

    // Check if fingerprint already exists for this finger/employee
    $existing_query = "
        SELECT ID FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'fingerprint' AND ACTIVO = 1
    ";
    $existing_params = [$employee_id];

    if ($finger_type) {
        $existing_query .= " AND FINGER_TYPE = ?";
        $existing_params[] = $finger_type;
    }

    $stmt = $conn->prepare($existing_query);
    $stmt->execute($existing_params);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing fingerprint
        $stmt = $conn->prepare("
            UPDATE biometric_data 
            SET BIOMETRIC_DATA = ?, 
                WEBAUTHN_CREDENTIAL_ID = ?,
                PUBLIC_KEY = ?,
                API_SOURCE = ?,
                UPDATED_AT = NOW()
            WHERE ID = ?
        ");
        $stmt->execute([
            $fingerprint_data, 
            $webauthn_credential_id,
            $public_key,
            $api_source,
            $existing['ID']
        ]);
        $action = 'actualizada';
    } else {
        // Insert new fingerprint
        $stmt = $conn->prepare("
            INSERT INTO biometric_data (
                ID_EMPLEADO, 
                BIOMETRIC_TYPE, 
                FINGER_TYPE, 
                BIOMETRIC_DATA,
                WEBAUTHN_CREDENTIAL_ID,
                PUBLIC_KEY,
                API_SOURCE,
                CREATED_AT
            ) VALUES (?, 'fingerprint', ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $employee_id, 
            $finger_type, 
            $fingerprint_data,
            $webauthn_credential_id,
            $public_key,
            $api_source
        ]);
        $action = 'registrada';
    }

    // Enhanced biometric_logs table
    $create_logs_table_sql = "
        CREATE TABLE IF NOT EXISTS biometric_logs (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
            VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
            CONFIDENCE_SCORE DECIMAL(5,4),
            API_SOURCE VARCHAR(50),
            OPERATION_TYPE ENUM('enrollment', 'verification') DEFAULT 'enrollment',
            FECHA DATE,
            HORA TIME,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO),
            INDEX idx_employee_operation (ID_EMPLEADO, OPERATION_TYPE, CREATED_AT)
        )
    ";
    $conn->exec($create_logs_table_sql);

    // Log enrollment activity
    $stmt = $conn->prepare("
        INSERT INTO biometric_logs (
            ID_EMPLEADO,
            VERIFICATION_METHOD,
            VERIFICATION_SUCCESS,
            API_SOURCE,
            OPERATION_TYPE,
            FECHA,
            HORA,
            CREATED_AT
        ) VALUES (?, 'fingerprint', 1, ?, 'enrollment', CURDATE(), CURTIME(), NOW())
    ");
    $stmt->execute([$employee_id, $api_source]);

    echo json_encode([
        'success' => true,
        'message' => "Huella {$action} correctamente",
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'finger_type' => $finger_type,
        'api_source' => $api_source,
        'action' => $action,
        'has_webauthn' => !empty($webauthn_credential_id),
        'credential_length' => $webauthn_credential_id ? strlen($webauthn_credential_id) : 0
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
?>