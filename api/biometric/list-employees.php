<?php
/**
 * List Employees for Biometric Enrollment API Endpoint
 * Returns employees with their current biometric enrollment status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Verify user session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Get filters from request
    $filters = [];
    $page = 1;
    $perPage = 20;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $filters = $input['filters'] ?? [];
        $page = $input['page'] ?? 1;
        $perPage = $input['per_page'] ?? 20;
    } else {
        $filters['search'] = $_GET['search'] ?? '';
        $filters['sede'] = $_GET['sede'] ?? '';
        $filters['establecimiento'] = $_GET['establecimiento'] ?? '';
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 20;
    }
    
    $result = getEmployeesWithBiometricStatus($conn, $filters, $page, $perPage);
    
    echo json_encode([
        'success' => true,
        'employees' => $result['employees'],
        'totalCount' => $result['totalCount'],
        'totalPages' => $result['totalPages'],
        'currentPage' => $page
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Get employees with their biometric enrollment status
 */
function getEmployeesWithBiometricStatus($conn, $filters, $page, $perPage) {
    $offset = ($page - 1) * $perPage;
    
    // Build base query
    $whereClause = "WHERE e.ACTIVO = 'S'";
    $params = [];
    
    // Apply filters
    if (!empty($filters['search'])) {
        $whereClause .= " AND (e.NOMBRE LIKE ? OR e.APELLIDO LIKE ? OR e.CODIGO LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($filters['sede'])) {
        $whereClause .= " AND s.ID_SEDE = ?";
        $params[] = $filters['sede'];
    }
    
    if (!empty($filters['establecimiento'])) {
        $whereClause .= " AND est.ID_ESTABLECIMIENTO = ?";
        $params[] = $filters['establecimiento'];
    }
    
    // Count total records
    $countQuery = "
        SELECT COUNT(DISTINCT e.ID_EMPLEADO) as total
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        $whereClause
    ";
    
    $stmt = $conn->prepare($countQuery);
    $stmt->execute($params);
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalCount / $perPage);
    
    // Get employees with biometric status
    $query = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.CODIGO,
            e.NOMBRE,
            e.APELLIDO,
            e.EMAIL,
            e.TELEFONO,
            est.NOMBRE as ESTABLECIMIENTO,
            s.NOMBRE as SEDE,
            CASE WHEN fp.ID_EMPLEADO IS NOT NULL THEN 1 ELSE 0 END as hasFingerprint,
            CASE WHEN fc.ID_EMPLEADO IS NOT NULL THEN 1 ELSE 0 END as hasFacial,
            fp.fingerprint_count,
            fp.last_fingerprint_update,
            fc.last_facial_update
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN (
            SELECT ID_EMPLEADO, 
                   COUNT(*) as fingerprint_count,
                   MAX(UPDATED_AT) as last_fingerprint_update
            FROM biometric_data 
            WHERE BIOMETRIC_TYPE = 'fingerprint' AND ACTIVO = 1
            GROUP BY ID_EMPLEADO
        ) fp ON e.ID_EMPLEADO = fp.ID_EMPLEADO
        LEFT JOIN (
            SELECT ID_EMPLEADO,
                   MAX(UPDATED_AT) as last_facial_update
            FROM biometric_data 
            WHERE BIOMETRIC_TYPE = 'facial' AND ACTIVO = 1
            GROUP BY ID_EMPLEADO
        ) fc ON e.ID_EMPLEADO = fc.ID_EMPLEADO
        $whereClause
        ORDER BY e.NOMBRE, e.APELLIDO
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $perPage;
    $params[] = $offset;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process employees data
    foreach ($employees as &$employee) {
        $employee['hasFingerprint'] = (bool)$employee['hasFingerprint'];
        $employee['hasFacial'] = (bool)$employee['hasFacial'];
        $employee['fingerprint_count'] = (int)($employee['fingerprint_count'] ?? 0);
        
        // Format dates
        if ($employee['last_fingerprint_update']) {
            $employee['last_fingerprint_update'] = date('d/m/Y H:i', strtotime($employee['last_fingerprint_update']));
        }
        if ($employee['last_facial_update']) {
            $employee['last_facial_update'] = date('d/m/Y H:i', strtotime($employee['last_facial_update']));
        }
        
        // Calculate enrollment status
        $employee['enrollmentStatus'] = 'none';
        if ($employee['hasFingerprint'] && $employee['hasFacial']) {
            $employee['enrollmentStatus'] = 'complete';
        } elseif ($employee['hasFingerprint'] || $employee['hasFacial']) {
            $employee['enrollmentStatus'] = 'partial';
        }
        
        // Add enrollment percentage
        $enrolledMethods = ($employee['hasFingerprint'] ? 1 : 0) + ($employee['hasFacial'] ? 1 : 0);
        $employee['enrollmentPercentage'] = ($enrolledMethods / 2) * 100;
    }
    
    return [
        'employees' => $employees,
        'totalCount' => $totalCount,
        'totalPages' => $totalPages
    ];
}
?>