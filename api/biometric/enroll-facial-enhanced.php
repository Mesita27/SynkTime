<?php
/**
 * Enhanced API endpoint for facial enrollment with external API integration
 * Supports Face-api.js facial descriptors and traditional image storage
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
    $facial_data = $_POST['facial_data'] ?? null;
    $facial_descriptor = $_POST['facial_descriptor'] ?? null; // From Face-api.js
    $confidence_score = $_POST['confidence_score'] ?? null;

    if (!$employee_id || (!$facial_data && !$facial_descriptor)) {
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

    // Create biometric_data table if it doesn't exist (enhanced schema)
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS biometric_data (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
            FINGER_TYPE VARCHAR(20),
            BIOMETRIC_DATA LONGTEXT,
            FACIAL_DESCRIPTOR JSON,
            CONFIDENCE_SCORE DECIMAL(5,4),
            API_SOURCE VARCHAR(50) DEFAULT 'internal',
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ACTIVO TINYINT(1) DEFAULT 1,
            FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO),
            INDEX idx_employee_biometric (ID_EMPLEADO, BIOMETRIC_TYPE)
        )
    ";
    $conn->exec($create_table_sql);

    // Check if facial data already exists for this employee
    $stmt = $conn->prepare("
        SELECT ID FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'facial' AND ACTIVO = 1
    ");
    $stmt->execute([$employee_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    // Prepare facial data storage
    $api_source = 'internal';
    $descriptor_json = null;
    
    if ($facial_descriptor) {
        // Face-api.js descriptor provided
        $api_source = 'face-api.js';
        $descriptor_json = json_encode($facial_descriptor);
        
        // Validate JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Descriptor facial inválido');
        }
    }

    if ($existing) {
        // Update existing facial data
        $stmt = $conn->prepare("
            UPDATE biometric_data 
            SET BIOMETRIC_DATA = ?, 
                FACIAL_DESCRIPTOR = ?, 
                CONFIDENCE_SCORE = ?,
                API_SOURCE = ?,
                UPDATED_AT = NOW()
            WHERE ID = ?
        ");
        $stmt->execute([
            $facial_data, 
            $descriptor_json, 
            $confidence_score,
            $api_source,
            $existing['ID']
        ]);
        $action = 'actualizado';
    } else {
        // Insert new facial data
        $stmt = $conn->prepare("
            INSERT INTO biometric_data (
                ID_EMPLEADO, 
                BIOMETRIC_TYPE, 
                BIOMETRIC_DATA,
                FACIAL_DESCRIPTOR,
                CONFIDENCE_SCORE,
                API_SOURCE,
                CREATED_AT
            ) VALUES (?, 'facial', ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $employee_id, 
            $facial_data, 
            $descriptor_json,
            $confidence_score,
            $api_source
        ]);
        $action = 'registrado';
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
            CONFIDENCE_SCORE,
            API_SOURCE,
            OPERATION_TYPE,
            FECHA,
            HORA,
            CREATED_AT
        ) VALUES (?, 'facial', 1, ?, ?, 'enrollment', CURDATE(), CURTIME(), NOW())
    ");
    $stmt->execute([$employee_id, $confidence_score, $api_source]);

    echo json_encode([
        'success' => true,
        'message' => "Patrón facial {$action} correctamente",
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'api_source' => $api_source,
        'confidence_score' => $confidence_score,
        'action' => $action,
        'has_descriptor' => !empty($facial_descriptor),
        'descriptor_size' => $facial_descriptor ? strlen($descriptor_json) : 0
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