<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../dashboard-controller.php';

session_start();
$empresaId = $_SESSION['id_empresa'] ?? null;

if (!$empresaId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
    exit;
}

// Parámetros para los filtros
$establecimientoId = !empty($_GET['establecimiento_id']) ? intval($_GET['establecimiento_id']) : null;
$sedeId = !empty($_GET['sede_id']) ? intval($_GET['sede_id']) : null;
$fecha = isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');
$limit = max(5, min(50, intval($_GET['limit'] ?? 10)));

try {
    // Lógica jerárquica: establecimiento > sede > empresa
    $where = [];
    $params = [
        ':fecha' => $fecha,
        ':limite' => $limit
    ];

    if ($establecimientoId) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento_id";
        $params[':establecimiento_id'] = $establecimientoId;
    } elseif ($sedeId) {
        $where[] = "est.ID_SEDE = :sede_id";
        $params[':sede_id'] = $sedeId;
    } else {
        $where[] = "s.ID_EMPRESA = :empresa_id";
        $params[':empresa_id'] = $empresaId;
    }

    $whereClause = implode(' AND ', $where);

    // Consulta específica para obtener solo las ENTRADAS del día
    $sql = "
        SELECT 
            a.ID_ASISTENCIA, 
            e.ID_EMPLEADO, 
            e.NOMBRE, 
            e.APELLIDO, 
            a.HORA, 
            a.TIPO, 
            a.TARDANZA, 
            a.OBSERVACION, 
            s.NOMBRE as SEDE_NOMBRE, 
            est.NOMBRE as ESTABLECIMIENTO_NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
            AND a.FECHA BETWEEN eh.FECHA_DESDE AND IFNULL(eh.FECHA_HASTA, '9999-12-31')
        LEFT JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE {$whereClause}
          AND a.FECHA = :fecha 
          AND a.TIPO = 'ENTRADA'
          AND e.ACTIVO = 'S'
        ORDER BY a.HORA DESC, a.ID_ASISTENCIA DESC
        LIMIT :limite
    ";

    $stmt = $conn->prepare($sql);
    
    // Bind parámetros
    foreach ($params as $key => $value) {
        if ($key === ':limite') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    
    $stmt->execute();
    $entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener estadísticas para las métricas del dashboard (mantener lógica original)
    if ($establecimientoId) {
        $estadisticas = getEstadisticasAsistencia('establecimiento', $establecimientoId, $fecha);
    } elseif ($sedeId) {
        $estadisticas = getEstadisticasAsistencia('sede', $sedeId, $fecha);
    } else {
        $estadisticas = getEstadisticasAsistencia('empresa', $empresaId, $fecha);
    }

    echo json_encode([
        'success' => true,
        'fecha' => $fecha,
        'entradas' => $entradas,
        'estadisticas' => $estadisticas,
        'total_entradas' => count($entradas)
    ]);

} catch (Exception $e) {
    error_log("Error en get-dashboard-entries.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>