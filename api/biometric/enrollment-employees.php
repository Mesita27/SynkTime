<?php
/**
 * API endpoint for loading employees for biometric enrollment
 * Returns employees with their current biometric enrollment status
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

try {
    // Get filter parameters
    $sede = $_GET['sede'] ?? '';
    $establecimiento = $_GET['establecimiento'] ?? '';
    $codigo = $_GET['codigo'] ?? '';

    // Initialize biometric schema if needed
    require_once '../../init-biometric-schema.php';
    initializeBiometricSchema($conn);

    // Build the query
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE as ESTABLECIMIENTO,
            est.ID_ESTABLECIMIENTO,
            s.NOMBRE as SEDE,
            s.ID_SEDE,
            MAX(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 1 THEN 1 ELSE 0 END) as has_fingerprint,
            MAX(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' AND bd.ACTIVO = 1 THEN 1 ELSE 0 END) as has_facial,
            COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 1 THEN 1 END) as fingerprint_count,
            COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' AND bd.ACTIVO = 1 THEN 1 END) as facial_count
        FROM EMPLEADO e
        LEFT JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO
        WHERE e.ACTIVO = 'S'
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

    if (!empty($codigo)) {
        $sql .= " AND e.ID_EMPLEADO = ?";
        $params[] = $codigo;
    }

    $sql .= " GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, est.NOMBRE, est.ID_ESTABLECIMIENTO, s.NOMBRE, s.ID_SEDE";
    $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process results
    foreach ($employees as &$employee) {
        $employee['has_fingerprint'] = (bool) $employee['has_fingerprint'];
        $employee['has_facial'] = (bool) $employee['has_facial'];
        $employee['fingerprint_count'] = intval($employee['fingerprint_count']);
        $employee['facial_count'] = intval($employee['facial_count']);
        
        // Determine enrollment status
        if ($employee['has_fingerprint'] && $employee['has_facial']) {
            $employee['enrollment_status'] = 'complete';
            $employee['enrollment_status_text'] = 'Completo';
        } elseif ($employee['has_fingerprint'] || $employee['has_facial']) {
            $employee['enrollment_status'] = 'partial';
            $employee['enrollment_status_text'] = 'Parcial';
        } else {
            $employee['enrollment_status'] = 'none';
            $employee['enrollment_status_text'] = 'Pendiente';
        }
        
        // Build biometric status details
        $status_details = [];
        if ($employee['has_fingerprint']) {
            $status_details[] = "Huella (" . $employee['fingerprint_count'] . ")";
        }
        if ($employee['has_facial']) {
            $status_details[] = "Facial";
        }
        
        $employee['biometric_details'] = !empty($status_details) ? implode(', ', $status_details) : 'Sin datos';
    }

    echo json_encode([
        'success' => true,
        'data' => $employees,
        'total' => count($employees),
        'filters' => [
            'sede' => $sede,
            'establecimiento' => $establecimiento,
            'codigo' => $codigo
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