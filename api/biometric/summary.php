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
    // Get filter parameters
    $sede = $_GET['sede'] ?? '';
    $establecimiento = $_GET['establecimiento'] ?? '';
    $status = $_GET['status'] ?? '';

    // Create biometric_data table if it doesn't exist
    $create_table_sql = "
        CREATE TABLE IF NOT EXISTS biometric_data (
            ID INT AUTO_INCREMENT PRIMARY KEY,
            ID_EMPLEADO INT NOT NULL,
            BIOMETRIC_TYPE ENUM('fingerprint', 'facial') NOT NULL,
            FINGER_TYPE VARCHAR(20),
            BIOMETRIC_DATA LONGTEXT,
            CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ACTIVO TINYINT(1) DEFAULT 1,
            FOREIGN KEY (ID_EMPLEADO) REFERENCES EMPLEADO(ID_EMPLEADO),
            UNIQUE KEY unique_employee_finger (ID_EMPLEADO, FINGER_TYPE)
        )
    ";
    $conn->exec($create_table_sql);

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
        FROM EMPLEADO e
        LEFT JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
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