<?php
/**
 * API endpoint for fingerprint enrollment
 * Registers fingerprint data for employees
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

    if (!$employee_id || !$finger_type || !$fingerprint_data) {
        throw new Exception('Datos incompletos');
    }

    // Validate employee exists and is active
    $stmt = $conn->prepare("
        SELECT ID_EMPLEADO, NOMBRE, APELLIDO 
        FROM EMPLEADO 
        WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }

    // Validate finger type
    $valid_fingers = [
        'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_pinky',
        'right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_pinky'
    ];

    if (!in_array($finger_type, $valid_fingers)) {
        throw new Exception('Tipo de dedo inválido');
    }

    // Create biometric_data table if it doesn't exist
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS biometric_data (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
            FINGER_TYPE VARCHAR(20),
            BIOMETRIC_DATA LONGTEXT,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ACTIVO TINYINT(1) DEFAULT 1,
            FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO),
            UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
        )
    ";
    $conn->exec($create_table_sql);

    // Check if fingerprint already exists for this finger
    $stmt = $conn->prepare("
        SELECT ID FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND FINGER_TYPE = ? AND ACTIVO = 1
    ");
    $stmt->execute([$employee_id, $finger_type]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing fingerprint
        $stmt = $conn->prepare("
            UPDATE biometric_data 
            SET BIOMETRIC_DATA = ?, UPDATED_AT = NOW()
            WHERE ID = ?
        ");
        $stmt->execute([$fingerprint_data, $existing['ID']]);
        $action = 'actualizada';
    } else {
        // Insert new fingerprint
        $stmt = $conn->prepare("
            INSERT INTO biometric_data (
                ID_EMPLEADO, 
                BIOMETRIC_TYPE, 
                FINGER_TYPE, 
                BIOMETRIC_DATA,
                CREATED_AT
            ) VALUES (?, 'fingerprint', ?, ?, NOW())
        ");
        $stmt->execute([$employee_id, $finger_type, $fingerprint_data]);
        $action = 'registrada';
    }

    // Create biometric_logs table if it doesn't exist
    $create_logs_table_sql = "
        CREATE TABLE IF NOT EXISTS biometric_logs (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
            VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
            FECHA DATE,
            HORA TIME,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO)
        )
    ";
    $conn->exec($create_logs_table_sql);

    // Log enrollment activity
    $stmt = $conn->prepare("
        INSERT INTO biometric_logs (
            ID_EMPLEADO,
            VERIFICATION_METHOD,
            VERIFICATION_SUCCESS,
            FECHA,
            HORA,
            CREATED_AT
        ) VALUES (?, 'fingerprint', 1, CURDATE(), CURTIME(), NOW())
    ");
    $stmt->execute([$employee_id]);

    echo json_encode([
        'success' => true,
        'message' => "Huella {$action} correctamente",
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'finger_type' => $finger_type,
        'action' => $action
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