<?php
/**
 * API endpoint for real facial recognition verification
 * Compares live face with stored face descriptors
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
    $live_descriptor = $_POST['live_descriptor'] ?? null;
    $threshold = $_POST['threshold'] ?? 0.6;

    if (!$employee_id || !$live_descriptor) {
        throw new Exception('Datos incompletos');
    }

    // Validate employee exists and is active
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, 
               bd.FACE_DESCRIPTOR, bd.TEMPLATE_QUALITY
        FROM empleados e
        LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO 
        WHERE e.ID_EMPLEADO = ? AND e.ACTIVO = 1 
        AND bd.BIOMETRIC_TYPE = 'facial' AND bd.ACTIVO = 1
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Empleado no encontrado o sin datos biométricos registrados');
    }

    if (!$employee['FACE_DESCRIPTOR']) {
        throw new Exception('No hay datos de reconocimiento facial para este empleado');
    }

    // Parse descriptors
    $stored_descriptor = json_decode($employee['FACE_DESCRIPTOR'], true);
    $live_descriptor_array = json_decode($live_descriptor, true);

    if (!$stored_descriptor || !$live_descriptor_array) {
        throw new Exception('Error al procesar datos biométricos');
    }

    // Calculate Euclidean distance between descriptors
    $distance = calculateEuclideanDistance($stored_descriptor, $live_descriptor_array);
    $confidence = max(0, (1 - $distance) * 100);
    $verification_success = $distance < $threshold;

    // Get current date and time
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');

    // Log verification attempt
    $stmt = $conn->prepare("
        INSERT INTO biometric_logs (
            ID_EMPLEADO,
            VERIFICATION_METHOD,
            VERIFICATION_SUCCESS,
            CONFIDENCE_SCORE,
            FECHA,
            HORA,
            CREATED_AT
        ) VALUES (?, 'facial', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$employee_id, $verification_success ? 1 : 0, $confidence, $fecha, $hora]);

    $response = [
        'success' => $verification_success,
        'employee' => [
            'id' => $employee['ID_EMPLEADO'],
            'name' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
            'template_quality' => $employee['TEMPLATE_QUALITY']
        ],
        'verification' => [
            'confidence' => round($confidence, 2),
            'distance' => round($distance, 4),
            'threshold' => $threshold,
            'timestamp' => date('c')
        ]
    ];

    if ($verification_success) {
        $response['message'] = "Verificación exitosa - Confianza: {$response['verification']['confidence']}%";
        
        // If verification is successful, we can proceed with attendance registration
        // This would be called from the attendance registration flow
        
    } else {
        $response['message'] = "Verificación fallida - Confianza insuficiente: {$response['verification']['confidence']}%";
        http_response_code(401);
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'verification' => [
            'confidence' => 0,
            'distance' => 1,
            'threshold' => $threshold
        ]
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}

/**
 * Calculate Euclidean distance between two face descriptors
 */
function calculateEuclideanDistance($descriptor1, $descriptor2) {
    if (count($descriptor1) !== count($descriptor2)) {
        throw new Exception('Los descriptores tienen diferentes dimensiones');
    }
    
    $sum = 0;
    for ($i = 0; $i < count($descriptor1); $i++) {
        $diff = $descriptor1[$i] - $descriptor2[$i];
        $sum += $diff * $diff;
    }
    
    return sqrt($sum);
}

/**
 * Alternative: Cosine similarity calculation
 */
function calculateCosineSimilarity($descriptor1, $descriptor2) {
    if (count($descriptor1) !== count($descriptor2)) {
        throw new Exception('Los descriptores tienen diferentes dimensiones');
    }
    
    $dotProduct = 0;
    $norm1 = 0;
    $norm2 = 0;
    
    for ($i = 0; $i < count($descriptor1); $i++) {
        $dotProduct += $descriptor1[$i] * $descriptor2[$i];
        $norm1 += $descriptor1[$i] * $descriptor1[$i];
        $norm2 += $descriptor2[$i] * $descriptor2[$i];
    }
    
    $norm1 = sqrt($norm1);
    $norm2 = sqrt($norm2);
    
    if ($norm1 == 0 || $norm2 == 0) {
        return 0;
    }
    
    return $dotProduct / ($norm1 * $norm2);
}
?>