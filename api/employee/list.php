<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/authorization.php';
requireAuth();

header('Content-Type: application/json');

// Verificar permisos - Solo gerentes pueden gestionar empleados
if (!hasPermission('employee_management')) {
    echo json_encode(['success' => false, 'message' => 'Sin permisos para acceder a datos de empleados']);
    exit;
}

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(50, intval($_GET['limit'] ?? 10))); // Entre 10 y 50
    $offset = ($page - 1) * $limit;

    // Parámetros de filtro
    $filtros = [
        'codigo' => $_GET['codigo'] ?? null,
        'identificacion' => $_GET['identificacion'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'estado' => $_GET['estado'] ?? null
    ];

    // Construcción de la consulta
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    if ($filtros['codigo']) {
        $where[] = "e.ID_EMPLEADO = :codigo";
        $params[':codigo'] = $filtros['codigo'];
    }

    if ($filtros['identificacion']) {
        $where[] = "e.DNI LIKE :identificacion";
        $params[':identificacion'] = '%' . $filtros['identificacion'] . '%';
    }

    if ($filtros['nombre']) {
        $where[] = "(e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre)";
        $params[':nombre'] = '%' . $filtros['nombre'] . '%';
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    if ($filtros['estado']) {
        $where[] = "e.ESTADO = :estado";
        $params[':estado'] = $filtros['estado'];
    } else {
        $where[] = "e.ACTIVO = 'S'"; // Solo activos por defecto
    }

    $whereClause = implode(' AND ', $where);

    // Consulta para contar total de registros
    $countSql = "
        SELECT COUNT(*) as total
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE {$whereClause}
    ";

    $countStmt = $conn->prepare($countSql);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Consulta principal con paginación
    $sql = "
        SELECT 
            e.ID_EMPLEADO as id,
            e.DNI as identificacion,
            e.NOMBRE as nombre,
            e.APELLIDO as apellido,
            e.CORREO as email,
            e.TELEFONO as telefono,
            est.NOMBRE as establecimiento,
            est.ID_ESTABLECIMIENTO as establecimiento_id,
            s.NOMBRE as sede,
            s.ID_SEDE as sede_id,
            e.FECHA_INGRESO as fecha_contratacion,
            e.ESTADO as estado,
            e.FACIAL_RECOGNITION_ENABLED,
            e.FINGERPRINT_ENABLED
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE {$whereClause}
        ORDER BY e.APELLIDO, e.NOMBRE
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $conn->prepare($sql);
    
    // Bind parámetros de filtro
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind parámetros de paginación
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $empleados,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'limit' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ],
        'filters_applied' => array_filter($filtros)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar empleados: ' . $e->getMessage()
    ]);
}
?>