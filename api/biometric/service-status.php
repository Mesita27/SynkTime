<?php
/**
 * API endpoint to check biometric service status
 * Returns information about external API configurations
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';
require_once 'BiometricVerificationService.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

try {
    $verificationService = new BiometricVerificationService($conn);
    $status = $verificationService->getServiceStatus();
    
    // Add system information
    $system_info = [
        'local_algorithms' => [
            'facial_comparison' => true,
            'fingerprint_comparison' => true
        ],
        'external_apis' => [
            'facial_recognition' => $status['facial_recognition'],
            'fingerprint_recognition' => $status['fingerprint_recognition']
        ],
        'system_capabilities' => [
            'device_detection' => true,
            'auto_capture' => true,
            'manual_verification' => true,
            'fallback_traditional' => true
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'status' => $system_info,
        'recommendations' => generateRecommendations($status)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estado del servicio: ' . $e->getMessage()
    ]);
}

/**
 * Generate recommendations based on service status
 */
function generateRecommendations($status) {
    $recommendations = [];
    
    if (!$status['facial_recognition']['enabled']) {
        $recommendations[] = [
            'type' => 'info',
            'title' => 'API Externa de Reconocimiento Facial',
            'message' => 'Considera configurar una API externa como Face++ o Azure Face para mayor precisión en el reconocimiento facial.',
            'action' => 'configure_facial_api'
        ];
    }
    
    if ($status['facial_recognition']['enabled'] && $status['facial_recognition']['configured']) {
        $recommendations[] = [
            'type' => 'success',
            'title' => 'Reconocimiento Facial Optimizado',
            'message' => 'API externa configurada correctamente para reconocimiento facial avanzado.',
            'action' => null
        ];
    }
    
    return $recommendations;
}
?>