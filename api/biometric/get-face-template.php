<?php
/**
 * API endpoint to get stored face template for verification
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

    if (!$employee_id) {
        throw new Exception('ID de empleado requerido');
    }

    // Get employee's face template
    $stmt = $conn->prepare("
        SELECT bd.FACE_DESCRIPTOR, bd.TEMPLATE_QUALITY, bd.BIOMETRIC_DATA,
               e.NOMBRE, e.APELLIDO
        FROM biometric_data bd
        JOIN empleados e ON bd.ID_EMPLEADO = e.ID_EMPLEADO
        WHERE bd.ID_EMPLEADO = ? 
        AND bd.BIOMETRIC_TYPE = 'facial' 
        AND bd.ACTIVO = 1
        AND e.ACTIVO = 1
    ");
    $stmt->execute([$employee_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        echo json_encode([
            'success' => false,
            'message' => 'No hay datos biométricos registrados para este empleado'
        ]);
        return;
    }

    // Parse the face descriptor
    $face_descriptor = json_decode($result['FACE_DESCRIPTOR'], true);
    if (!$face_descriptor) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos biométricos corruptos'
        ]);
        return;
    }

    // Parse additional template data
    $biometric_data = json_decode($result['BIOMETRIC_DATA'], true);
    $template_info = $biometric_data['template'] ?? null;

    echo json_encode([
        'success' => true,
        'template' => [
            'descriptor' => $face_descriptor,
            'quality' => $result['TEMPLATE_QUALITY'],
            'avgQuality' => $template_info['avgQuality'] ?? $result['TEMPLATE_QUALITY'],
            'captureCount' => $template_info['captureCount'] ?? 1,
            'algorithm' => $biometric_data['algorithm'] ?? 'face-api.js'
        ],
        'employee' => [
            'name' => $result['NOMBRE'] . ' ' . $result['APELLIDO']
        ]
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