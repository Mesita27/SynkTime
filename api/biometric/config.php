<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    // Get biometric configuration from database
    $sql = "SELECT CONFIG_KEY, CONFIG_VALUE FROM BIOMETRIC_CONFIG WHERE CONFIG_KEY IN (
        'fingerprint_enabled', 'facial_enabled', 'fingerprint_confidence_threshold', 
        'facial_confidence_threshold', 'max_verification_attempts', 'verification_timeout'
    )";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $configRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $config = [];
    foreach ($configRows as $row) {
        $key = $row['CONFIG_KEY'];
        $value = $row['CONFIG_VALUE'];
        
        // Convert string values to appropriate types
        if (in_array($key, ['fingerprint_enabled', 'facial_enabled'])) {
            $config[$key] = ($value === 'true');
        } elseif (in_array($key, ['fingerprint_confidence_threshold', 'facial_confidence_threshold'])) {
            $config[$key] = floatval($value);
        } elseif (in_array($key, ['max_verification_attempts', 'verification_timeout'])) {
            $config[$key] = intval($value);
        } else {
            $config[$key] = $value;
        }
    }
    
    echo json_encode([
        'success' => true,
        'config' => $config
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar configuración biométrica: ' . $e->getMessage()
    ]);
}
?>