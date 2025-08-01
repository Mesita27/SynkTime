<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/authorization.php';
requireAuth();

header('Content-Type: application/json');

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Verificar permisos de rol
    if (!hasPermission('attendance_current_day')) {
        echo json_encode(['success' => false, 'message' => 'Sin permisos para acceder a asistencias']);
        exit;
    }

    // Establecer zona horaria de Colombia
    date_default_timezone_set('America/Bogota');

    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(50, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    // Aplicar restricciones de fecha según el rol
    $dateFilter = getDateFilter();
    if ($dateFilter === false) {
        echo json_encode(['success' => false, 'message' => 'Sin acceso a los datos']);
        exit;
    }

    // Para usuarios de asistencia, solo mostrar el día actual
    if (isAttendanceUser()) {
        // Obtenemos fecha y hora actual
        $fecha_actual = date('Y-m-d H:i:s');
        $fecha_inicio_dia = date('Y-m-d 00:00:00');
        $fecha_fin_dia = date('Y-m-d 23:59:59');
    } else {
        // Para gerentes, mostrar las últimas 20 horas como antes
        $fecha_actual = date('Y-m-d H:i:s');
        $fecha_20_horas_atras = date('Y-m-d H:i:s', strtotime('-20 hours'));
    }

    // Parámetros de filtro
    $filtros = [
        'codigo' => $_GET['codigo'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null
    ];

    // Construcción de la consulta base
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [
        ':empresa_id' => $empresaId
    ];

    // Aplicar filtro de fecha según el rol
    if (isAttendanceUser()) {
        $where[] = "a.FECHA = :fecha_actual";
        $params[':fecha_actual'] = date('Y-m-d');
    } else {
        $where[] = "CONCAT(a.FECHA, ' ', a.HORA) >= :fecha_20_horas_atras";
        $params[':fecha_20_horas_atras'] = $fecha_20_horas_atras;
    }

    // Aplicar filtros adicionales
    if ($filtros['codigo']) {
        $where[] = "e.ID_EMPLEADO = :codigo";
        $params[':codigo'] = $filtros['codigo'];
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    $whereClause = implode(' AND ', $where);

    // Consulta para contar total de registros
    $countSql = "
        SELECT COUNT(*) as total
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN HORARIO h ON h.ID_HORARIO = a.ID_HORARIO
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
            a.ID_ASISTENCIA as id,
            e.ID_EMPLEADO as codigo_empleado,
            CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_empleado,
            est.NOMBRE as establecimiento,
            s.NOMBRE as sede,
            a.FECHA as fecha,
            a.HORA as hora,
            a.TIPO as tipo,
            a.TARDANZA as tardanza,
            a.OBSERVACION as observacion,
            a.FOTO as foto,
            a.REGISTRO_MANUAL as registro_manual,
            a.ID_HORARIO,
            h.NOMBRE AS HORARIO_NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN HORARIO h ON h.ID_HORARIO = a.ID_HORARIO
        WHERE {$whereClause}
        ORDER BY CONCAT(a.FECHA, ' ', a.HORA) DESC, a.ID_ASISTENCIA DESC
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
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $asistencias,
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
        'message' => 'Error al cargar asistencias: ' . $e->getMessage()
    ]);
}
?>