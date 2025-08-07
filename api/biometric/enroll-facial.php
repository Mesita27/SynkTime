<?php
/**
 * API endpoint for facial pattern enrollment
 * Registers facial recognition data for employees
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

    if (!$employee_id || !$facial_data) {
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

    // Validate facial data is valid JSON
    $facial_images = json_decode($facial_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Datos faciales inválidos');
    }

    if (!is_array($facial_images) || count($facial_images) < 1) {
        throw new Exception('Se requiere al menos una imagen facial');
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

    // Create facial_images directory if it doesn't exist
    $upload_dir = '../../uploads/facial/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Save facial images and create a reference array
    $saved_images = [];
    foreach ($facial_images as $index => $image_data) {
        $filename = saveFacialImage($image_data, $employee_id, $index + 1);
        $saved_images[] = $filename;
    }

    // Store facial data reference in database
    $facial_data_json = json_encode([
        'images' => $saved_images,
        'enrollment_date' => date('Y-m-d H:i:s'),
        'image_count' => count($saved_images)
    ]);

    // Check if facial data already exists
    $stmt = $conn->prepare("
        SELECT ID FROM biometric_data 
        WHERE ID_EMPLEADO = ? AND BIOMETRIC_TYPE = 'facial' AND ACTIVO = 1
    ");
    $stmt->execute([$employee_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing facial data
        $stmt = $conn->prepare("
            UPDATE biometric_data 
            SET BIOMETRIC_DATA = ?, UPDATED_AT = NOW()
            WHERE ID = ?
        ");
        $stmt->execute([$facial_data_json, $existing['ID']]);
        $action = 'actualizado';
    } else {
        // Insert new facial data
        $stmt = $conn->prepare("
            INSERT INTO biometric_data (
                ID_EMPLEADO, 
                BIOMETRIC_TYPE, 
                BIOMETRIC_DATA,
                CREATED_AT
            ) VALUES (?, 'facial', ?, NOW())
        ");
        $stmt->execute([$employee_id, $facial_data_json]);
        $action = 'registrado';
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
        ) VALUES (?, 'facial', 1, CURDATE(), CURTIME(), NOW())
    ");
    $stmt->execute([$employee_id]);

    echo json_encode([
        'success' => true,
        'message' => "Patrón facial {$action} correctamente",
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'image_count' => count($saved_images),
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

/**
 * Save facial image
 */
function saveFacialImage($image_data, $employee_id, $image_number) {
    $upload_dir = '../../uploads/facial/';
    
    // Remove data URL prefix
    $image_data = str_replace('data:image/jpeg;base64,', '', $image_data);
    $image_data = str_replace(' ', '+', $image_data);
    $image_binary = base64_decode($image_data);
    
    if ($image_binary === false) {
        throw new Exception('Datos de imagen facial inválidos');
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "facial_{$employee_id}_{$image_number}_{$timestamp}.jpg";
    $filepath = $upload_dir . $filename;
    
    if (file_put_contents($filepath, $image_binary) === false) {
        throw new Exception('Error al guardar la imagen facial');
    }
    
    return $filename;
}
?>