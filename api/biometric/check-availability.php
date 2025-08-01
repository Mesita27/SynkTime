<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$employee_id = $_GET['employee_id'] ?? null;

if (!$employee_id) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de empleado requerido'
    ]);
    exit;
}

try {
    // Check if employee has fingerprint data registered
    $sqlFingerprint = "SELECT COUNT(*) as count FROM EMPLEADO_BIOMETRICO 
                       WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = 'FINGERPRINT' AND ACTIVO = 'S'";
    $stmt = $conn->prepare($sqlFingerprint);
    $stmt->execute([$employee_id]);
    $fingerprintCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Check if employee has facial recognition data registered
    $sqlFacial = "SELECT COUNT(*) as count FROM EMPLEADO_BIOMETRICO 
                  WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = 'FACIAL' AND ACTIVO = 'S'";
    $stmt = $conn->prepare($sqlFacial);
    $stmt->execute([$employee_id]);
    $facialCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Check biometric system configuration
    $sqlConfig = "SELECT CONFIG_KEY, CONFIG_VALUE FROM BIOMETRIC_CONFIG 
                  WHERE CONFIG_KEY IN ('fingerprint_enabled', 'facial_enabled')";
    $stmt = $conn->prepare($sqlConfig);
    $stmt->execute();
    $configRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $config = [];
    foreach ($configRows as $row) {
        $config[$row['CONFIG_KEY']] = ($row['CONFIG_VALUE'] === 'true');
    }
    
    $fingerprintEnabled = $config['fingerprint_enabled'] ?? false;
    $facialEnabled = $config['facial_enabled'] ?? false;
    
    echo json_encode([
        'success' => true,
        'fingerprint_available' => ($fingerprintCount > 0 && $fingerprintEnabled),
        'facial_available' => ($facialCount > 0 && $facialEnabled),
        'fingerprint_registered' => $fingerprintCount > 0,
        'facial_registered' => $facialCount > 0,
        'fingerprint_enabled' => $fingerprintEnabled,
        'facial_enabled' => $facialEnabled
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar disponibilidad biométrica: ' . $e->getMessage()
    ]);
}
?>