<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$codigo = $_GET['codigo'] ?? '';
$sede = $_GET['sede'] ?? '';
$establecimiento = $_GET['establecimiento'] ?? '';

if (empty($codigo) && empty($sede) && empty($establecimiento)) {
    echo json_encode(['success' => false, 'message' => 'At least one search criteria required']);
    exit;
}

try {
    $conditions = [];
    $params = [];
    
    $sql = "
        SELECT 
            e.id,
            e.codigo,
            e.nombre,
            est.nombre as establecimiento,
            s.nombre as sede
        FROM employees e
        LEFT JOIN establecimientos est ON e.establecimiento_id = est.id
        LEFT JOIN sedes s ON est.sede_id = s.id
        WHERE 1=1
    ";
    
    if (!empty($codigo)) {
        $sql .= " AND e.codigo = ?";
        $params[] = $codigo;
    }
    
    if (!empty($sede)) {
        $sql .= " AND s.id = ?";
        $params[] = $sede;
    }
    
    if (!empty($establecimiento)) {
        $sql .= " AND est.id = ?";
        $params[] = $establecimiento;
    }
    
    $sql .= " LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($employee) {
        echo json_encode([
            'success' => true,
            'employee' => $employee
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error searching employee: ' . $e->getMessage()
    ]);
}
?>