<?php
/**
 * API endpoint for real facial recognition enrollment
 * Uses face descriptors instead of just storing images
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
    $face_template = $_POST['face_template'] ?? null;
    $captures_data = $_POST['captures_data'] ?? null;

    if (!$employee_id || !$face_template) {
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

    // Validate face template
    $template_data = json_decode($face_template, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Datos de plantilla facial inválidos');
    }

    if (!isset($template_data['descriptor']) || !is_array($template_data['descriptor'])) {
        throw new Exception('Descriptor facial inválido');
    }

    // Create biometric_data table if it doesn't exist
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS biometric_data (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
            FINGER_TYPE VARCHAR(20),
            BIOMETRIC_DATA LONGTEXT,
            FACE_DESCRIPTOR JSON,
            TEMPLATE_QUALITY DECIMAL(5,2),
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ACTIVO TINYINT(1) DEFAULT 1,
            FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO),
            UNIQUE KEY unique_employee_facial (ID_EMPLEADO, BIOMETRIC_TYPE)
        )
    ";
    $conn->exec($create_table_sql);

    // Create facial_images directory if it doesn't exist
    $upload_dir = '../../uploads/facial/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Save capture images if provided
    $saved_images = [];
    if ($captures_data) {
        $captures = json_decode($captures_data, true);
        if (is_array($captures)) {
            foreach ($captures as $index => $capture) {
                if (isset($capture['image'])) {
                    $filename = saveFacialImage($capture['image'], $employee_id, $index + 1);
                    $saved_images[] = [
                        'filename' => $filename,
                        'quality' => $capture['quality'] ?? 0,
                        'timestamp' => $capture['timestamp'] ?? date('c')
                    ];
                }
            }
        }
    }

    // Prepare enrollment data
    $enrollment_data = [
        'template' => $template_data,
        'images' => $saved_images,
        'enrollment_date' => date('c'),
        'version' => '2.0',
        'algorithm' => 'face-api.js'
    ];

    // Store the face descriptor separately for efficient comparison
    $face_descriptor_json = json_encode($template_data['descriptor']);
    $template_quality = $template_data['avgQuality'] ?? 0;

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
            SET BIOMETRIC_DATA = ?, 
                FACE_DESCRIPTOR = ?,
                TEMPLATE_QUALITY = ?,
                UPDATED_AT = NOW()
            WHERE ID = ?
        ");
        $stmt->execute([
            json_encode($enrollment_data), 
            $face_descriptor_json,
            $template_quality,
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
                FACE_DESCRIPTOR,
                TEMPLATE_QUALITY,
                CREATED_AT
            ) VALUES (?, 'facial', ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $employee_id, 
            json_encode($enrollment_data),
            $face_descriptor_json,
            $template_quality
        ]);
        $action = 'registrado';
    }

    // Create biometric_logs table if it doesn't exist
    $create_logs_table_sql = "
        CREATE TABLE IF NOT EXISTS biometric_logs (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            VERIFICATION_METHOD ENUM('fingerprint', 'facial', 'traditional') NOT NULL,
            VERIFICATION_SUCCESS TINYINT(1) DEFAULT 0,
            CONFIDENCE_SCORE DECIMAL(5,2),
            FECHA DATE,
            HORA TIME,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ID_EMPLEADO) REFERENCES empleados(ID_EMPLEADO)
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
            FECHA,
            HORA,
            CREATED_AT
        ) VALUES (?, 'facial', 1, ?, CURDATE(), CURTIME(), NOW())
    ");
    $stmt->execute([$employee_id, $template_quality]);

    echo json_encode([
        'success' => true,
        'message' => "Reconocimiento facial {$action} correctamente",
        'employee' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
        'template_quality' => $template_quality,
        'image_count' => count($saved_images),
        'action' => $action,
        'algorithm' => 'face-api.js'
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
    $filename = "facial_real_{$employee_id}_{$image_number}_{$timestamp}.jpg";
    $filepath = $upload_dir . $filename;
    
    if (file_put_contents($filepath, $image_binary) === false) {
        throw new Exception('Error al guardar la imagen facial');
    }
    
    return $filename;
}
?>