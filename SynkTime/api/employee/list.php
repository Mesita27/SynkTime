<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

$empresaId = $_SESSION['id_empresa'];
$stmt = $conn->prepare("
    SELECT est.ID_ESTABLECIMIENTO, s.ID_SEDE, s.NOMBRE as nombre_sede
    FROM ESTABLECIMIENTO est
    JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
    WHERE s.ID_EMPRESA = :empresaId AND est.ESTADO = 'A'
");
$stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
$stmt->execute();
$establecimientos = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$establecimientos) {
    echo json_encode(['success' => true, 'data' => []]);
    exit;
}

$filtros = [
    'sede' => $_GET['sede'] ?? null,
    'establecimiento' => $_GET['establecimiento'] ?? null,
    'codigo' => $_GET['codigo'] ?? null,
    'identificacion' => $_GET['identificacion'] ?? null,
    'nombre' => $_GET['nombre'] ?? null,
];

$where = [];
$params = [];
$where[] = "e.ID_ESTABLECIMIENTO IN (" . implode(",", array_fill(0, count($establecimientos), "?")) . ")";
$params = array_merge($params, $establecimientos);

if ($filtros['sede']) {
    $where[] = "s.ID_SEDE = ?";
    $params[] = $filtros['sede'];
}
if ($filtros['establecimiento']) {
    $where[] = "e.ID_ESTABLECIMIENTO = ?";
    $params[] = $filtros['establecimiento'];
}
if ($filtros['codigo']) {
    $where[] = "e.ID_EMPLEADO = ?";
    $params[] = $filtros['codigo'];
}
if ($filtros['identificacion']) {
    $where[] = "e.DNI LIKE ?";
    $params[] = '%' . $filtros['identificacion'] . '%';
}
if ($filtros['nombre']) {
    $where[] = "(e.NOMBRE LIKE ? OR e.APELLIDO LIKE ?)";
    $params[] = '%' . $filtros['nombre'] . '%';
    $params[] = '%' . $filtros['nombre'] . '%';
}

$sql = "SELECT 
            e.ID_EMPLEADO as id,
            e.DNI as identificacion,
            e.NOMBRE as nombre,
            e.APELLIDO as apellido,
            e.CORREO as email,
            est.NOMBRE as establecimiento,
            est.ID_ESTABLECIMIENTO as establecimiento_id,
            s.NOMBRE as sede,
            s.ID_SEDE as sede_id,
            e.FECHA_INGRESO as fecha_contratacion,
            e.ESTADO as estado,
            (SELECT GROUP_CONCAT(h.NOMBRE SEPARATOR ', ') 
             FROM empleado_horario eh
             JOIN horario h ON h.ID_HORARIO = eh.ID_HORARIO
             WHERE eh.ID_EMPLEADO=e.ID_EMPLEADO) as horarios_asignados
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE " . implode(' AND ', $where) . "
        ORDER BY e.APELLIDO, e.NOMBRE";

$stmt = $conn->prepare($sql);
foreach ($params as $i => $val) {
    $stmt->bindValue($i + 1, $val);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $data]);