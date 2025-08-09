<?php
/**
 * Biometric Statistics API Endpoint
 * Provides enrollment statistics and metrics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Verify user session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $stats = getBiometricStatistics($conn);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Get comprehensive biometric statistics
 */
function getBiometricStatistics($conn) {
    $stats = [];
    
    // Total employees
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM empleado WHERE ACTIVO = 'S'");
    $stmt->execute();
    $stats['totalEmployees'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Fingerprint enrollments
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT ID_EMPLEADO) as count 
        FROM biometric_data 
        WHERE BIOMETRIC_TYPE = 'fingerprint' AND ACTIVO = 1
    ");
    $stmt->execute();
    $stats['fingerprintEnrolled'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Facial enrollments
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT ID_EMPLEADO) as count 
        FROM biometric_data 
        WHERE BIOMETRIC_TYPE = 'facial' AND ACTIVO = 1
    ");
    $stmt->execute();
    $stats['facialEnrolled'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Employees with any biometric enrollment
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT ID_EMPLEADO) as count 
        FROM biometric_data 
        WHERE ACTIVO = 1
    ");
    $stmt->execute();
    $stats['anyBiometricEnrolled'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Completion rate calculation
    if ($stats['totalEmployees'] > 0) {
        $stats['completionRate'] = round(($stats['anyBiometricEnrolled'] / $stats['totalEmployees']) * 100, 2);
    } else {
        $stats['completionRate'] = 0;
    }
    
    // Recent enrollments (last 30 days)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM biometric_logs 
        WHERE OPERATION_TYPE = 'enrollment' 
        AND VERIFICATION_SUCCESS = 1 
        AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $stats['recentEnrollments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Verification attempts today
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM biometric_logs 
        WHERE OPERATION_TYPE = 'verification' 
        AND FECHA = CURDATE()
    ");
    $stmt->execute();
    $stats['verificationAttemptsToday'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Successful verifications today
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM biometric_logs 
        WHERE OPERATION_TYPE = 'verification' 
        AND VERIFICATION_SUCCESS = 1 
        AND FECHA = CURDATE()
    ");
    $stmt->execute();
    $stats['successfulVerificationsToday'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Success rate today
    if ($stats['verificationAttemptsToday'] > 0) {
        $stats['successRateToday'] = round(($stats['successfulVerificationsToday'] / $stats['verificationAttemptsToday']) * 100, 2);
    } else {
        $stats['successRateToday'] = 0;
    }
    
    // Enrollment breakdown by sede
    $stmt = $conn->prepare("
        SELECT s.NOMBRE as sede_name, 
               COUNT(DISTINCT bd.ID_EMPLEADO) as enrolled_count,
               COUNT(DISTINCT e.ID_EMPLEADO) as total_count
        FROM sede s
        LEFT JOIN establecimiento est ON s.ID_SEDE = est.ID_SEDE
        LEFT JOIN empleado e ON est.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO AND e.ACTIVO = 'S'
        LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO AND bd.ACTIVO = 1
        GROUP BY s.ID_SEDE, s.NOMBRE
        ORDER BY s.NOMBRE
    ");
    $stmt->execute();
    $stats['enrollmentBySede'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Method usage breakdown (last 30 days)
    $stmt = $conn->prepare("
        SELECT VERIFICATION_METHOD as method, COUNT(*) as count
        FROM biometric_logs 
        WHERE OPERATION_TYPE = 'verification' 
        AND VERIFICATION_SUCCESS = 1 
        AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY VERIFICATION_METHOD
        ORDER BY count DESC
    ");
    $stmt->execute();
    $stats['methodUsage'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent enrollment activity (last 7 days)
    $stmt = $conn->prepare("
        SELECT DATE(CREATED_AT) as date, COUNT(*) as enrollments
        FROM biometric_logs 
        WHERE OPERATION_TYPE = 'enrollment' 
        AND VERIFICATION_SUCCESS = 1 
        AND CREATED_AT >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(CREATED_AT)
        ORDER BY date ASC
    ");
    $stmt->execute();
    $stats['recentEnrollmentActivity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fingerprint breakdown by finger type
    $stmt = $conn->prepare("
        SELECT FINGER_TYPE as finger, COUNT(*) as count
        FROM biometric_data 
        WHERE BIOMETRIC_TYPE = 'fingerprint' AND ACTIVO = 1
        GROUP BY FINGER_TYPE
        ORDER BY count DESC
    ");
    $stmt->execute();
    $stats['fingerprintBreakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}
?>