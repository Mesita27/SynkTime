<?php
/**
 * API endpoint for biometric enrollment summary
 * Returns detailed enrollment status for all employees
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

try {
    $sede = $_GET['sede'] ?? '';
    $establecimiento = $_GET['establecimiento'] ?? '';
    $status = $_GET['status'] ?? '';

    // Build the query
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE as ESTABLECIMIENTO,
            s.NOMBRE as SEDE,
            MAX(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 1 THEN 1 ELSE 0 END) as has_fingerprint,
            MAX(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' AND bd.ACTIVO = 1 THEN 1 ELSE 0 END) as has_facial,
            COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 1 THEN 1 END) as fingerprint_count,
            COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' AND bd.ACTIVO = 1 THEN 1 END) as facial_count
        FROM empleados e
        LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO
        WHERE e.ACTIVO = 1
    ";

    $params = [];

    // Add filters
    if (!empty($sede)) {
        $sql .= " AND s.ID_SEDE = ?";
        $params[] = $sede;
    }

    if (!empty($establecimiento)) {
        $sql .= " AND est.ID_ESTABLECIMIENTO = ?";
        $params[] = $establecimiento;
    }

    $sql .= " GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, est.NOMBRE, s.NOMBRE";

    // Add status filter after grouping
    if (!empty($status)) {
        switch ($status) {
            case 'complete':
                $sql .= " HAVING has_fingerprint = 1 AND has_facial = 1";
                break;
            case 'partial':
                $sql .= " HAVING (has_fingerprint = 1 AND has_facial = 0) OR (has_fingerprint = 0 AND has_facial = 1)";
                break;
            case 'none':
                $sql .= " HAVING has_fingerprint = 0 AND has_facial = 0";
                break;
        }
    }

    $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert boolean values to proper boolean type
    foreach ($employees as &$employee) {
        $employee['has_fingerprint'] = (bool) $employee['has_fingerprint'];
        $employee['has_facial'] = (bool) $employee['has_facial'];
        $employee['fingerprint_count'] = intval($employee['fingerprint_count']);
        $employee['facial_count'] = intval($employee['facial_count']);
    }

    echo json_encode([
        'success' => true,
        'data' => $employees,
        'total' => count($employees)
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