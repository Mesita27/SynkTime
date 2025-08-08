<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../auth/session.php';

// Verificar autenticación
requireAuth();

header('Content-Type: application/json');

try {
    $currentUser = getCurrentUser();
    if (!$currentUser || !$currentUser['id_empresa']) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }
    
    $empresaId = $currentUser['id_empresa'];
    $userRole = $currentUser['rol'];
    
    // Establecer zona horaria de Colombia
    date_default_timezone_set('America/Bogota');

    // Parámetros de paginación
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(10, min(50, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    // Filtros
    $filtros = [
        'codigo' => $_GET['codigo'] ?? '',
        'sede' => $_GET['sede'] ?? '',
        'establecimiento' => $_GET['establecimiento'] ?? ''
    ];

    // Construir WHERE clause básico
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    // Para rol ASISTENCIA, restringir a solo día actual
    if ($userRole === 'ASISTENCIA') {
        $fecha_inicio_dia = date('Y-m-d 00:00:00');
        $fecha_fin_dia = date('Y-m-d 23:59:59');
        $where[] = "CONCAT(a.FECHA, ' ', a.HORA) >= :fecha_inicio_dia";
        $where[] = "CONCAT(a.FECHA, ' ', a.HORA) <= :fecha_fin_dia";
        $params[':fecha_inicio_dia'] = $fecha_inicio_dia;
        $params[':fecha_fin_dia'] = $fecha_fin_dia;
    } else {
        // Para otros roles, mostrar las últimas 20 horas
        $fecha_20_horas_atras = date('Y-m-d H:i:s', strtotime('-20 hours'));
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
            h.TOLERANCIA,
            a.VERIFICATION_METHOD as verification_method_legacy,
            a.METODO_VERIFICACION as metodo_verificacion,
            a.ID_VERIFICACION_BIOMETRICA,
            vb.PUNTUACION_COINCIDENCIA,
            vb.FOTO_VERIFICACION,
            vb.RESULTADO_VERIFICACION
        FROM asistencias a
        JOIN empleados e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN horarios h ON h.ID_HORARIO = a.ID_HORARIO
        LEFT JOIN VERIFICACION_BIOMETRICA vb ON a.ID_VERIFICACION_BIOMETRICA = vb.ID_VERIFICACION
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
