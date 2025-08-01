<?php
/**
 * API endpoint for biometric verification during attendance registration
 * Performs real verification before allowing attendance registration
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';
require_once 'BiometricVerificationService.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $employee_id = $_POST['employee_id'] ?? null;
    $verification_method = $_POST['verification_method'] ?? null;
    $biometric_data = $_POST['biometric_data'] ?? null;
    $finger_type = $_POST['finger_type'] ?? null; // For fingerprint verification

    if (!$employee_id || !$verification_method || !$biometric_data) {
        throw new Exception('Datos incompletos para verificación');
    }

    // Initialize verification service
    $verificationService = new BiometricVerificationService($conn);

    // Get employee information
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO
        FROM EMPLEADO e
        WHERE e.ID_EMPLEADO = ? AND e.ACTIVO = 'S'
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Empleado no encontrado o inactivo');
    }

    $verification_result = null;

    // Perform verification based on method
    switch ($verification_method) {
        case 'fingerprint':
            if (empty($finger_type)) {
                throw new Exception('Tipo de dedo requerido para verificación por huella');
            }
            $verification_result = $verificationService->verifyFingerprint(
                $employee_id, 
                $biometric_data, 
                $finger_type
            );
            break;

        case 'facial':
            $verification_result = $verificationService->verifyFacial(
                $employee_id, 
                $biometric_data
            );
            break;

        case 'traditional':
            // Traditional method always succeeds (no biometric verification)
            $verification_result = [
                'success' => true,
                'confidence' => 1.0,
                'message' => 'Verificación tradicional'
            ];
            break;

        default:
            throw new Exception('Método de verificación no válido');
    }

    // Log the verification attempt
    $verificationService->logVerificationAttempt(
        $employee_id,
        $verification_method,
        $verification_result['success'],
        $verification_result['confidence'] ?? null
    );

    // Return verification result
    $response = [
        'success' => $verification_result['success'],
        'verification_result' => $verification_result,
        'employee' => [
            'id' => $employee['ID_EMPLEADO'],
            'name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO']
        ],
        'verification_method' => $verification_method
    ];

    // If verification failed, include additional info
    if (!$verification_result['success']) {
        $response['error'] = 'Verificación biométrica fallida';
        $response['details'] = $verification_result['message'];
        
        // Suggest alternative verification methods if available
        $suggestions = [];
        
        // Check if employee has other biometric data
        $stmt = $conn->prepare("
            SELECT DISTINCT BIOMETRIC_TYPE 
            FROM biometric_data 
            WHERE ID_EMPLEADO = ? AND ACTIVO = 1
        ");
        $stmt->execute([$employee_id]);
        $available_methods = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('fingerprint', $available_methods) && $verification_method !== 'fingerprint') {
            $suggestions[] = 'fingerprint';
        }
        if (in_array('facial', $available_methods) && $verification_method !== 'facial') {
            $suggestions[] = 'facial';
        }
        $suggestions[] = 'traditional'; // Always available as fallback
        
        $response['alternative_methods'] = $suggestions;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'verification_result' => [
            'success' => false,
            'message' => $e->getMessage(),
            'confidence' => 0
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage(),
        'verification_result' => [
            'success' => false,
            'message' => 'Error de base de datos',
            'confidence' => 0
        ]
    ]);
}
?>