<?php
require_once '../../auth/session.php';
requireAuth();
require_once '../../config/database.php';

$empresaId = $_SESSION['id_empresa'];

// 1. Obtener todos los IDs de establecimientos de la empresa
$stmt = $conn->prepare("
    SELECT est.ID_ESTABLECIMIENTO, s.ID_SEDE, s.NOMBRE as nombre_sede
    FROM ESTABLECIMIENTO est
    JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
    WHERE s.ID_EMPRESA = :empresaId AND est.ESTADO = 'A'
");
$stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
$stmt->execute();
$establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$idsEstablecimientos = array_column($establecimientos, 'ID_ESTABLECIMIENTO');

// Si no hay establecimientos se retorna vacío
if (!$idsEstablecimientos) {
    echo json_encode(['success' => true, 'horarios' => []]);
    exit;
}

// Filtros GET
$filtros = [
    'id_horario' => $_GET['id_horario'] ?? null,
    'nombre' => $_GET['nombre'] ?? null,
    'establecimiento' => $_GET['establecimiento'] ?? null,
    'hora_entrada' => $_GET['hora_entrada'] ?? null,
    'hora_salida' => $_GET['hora_salida'] ?? null,
    'dia' => $_GET['dia'] ?? null
];

$where = [];
$params = [];

$where[] = "h.ID_ESTABLECIMIENTO IN (" . implode(",", array_fill(0, count($idsEstablecimientos), "?")) . ")";
$params = array_merge($params, $idsEstablecimientos);

if ($filtros['id_horario']) {
    $where[] = "h.ID_HORARIO = ?";
    $params[] = $filtros['id_horario'];
}
if ($filtros['nombre']) {
    $where[] = "h.NOMBRE LIKE ?";
    $params[] = '%' . $filtros['nombre'] . '%';
}
if ($filtros['establecimiento']) {
    $where[] = "h.ID_ESTABLECIMIENTO = ?";
    $params[] = $filtros['establecimiento'];
}
if ($filtros['hora_entrada']) {
    $where[] = "h.HORA_ENTRADA = ?";
    $params[] = $filtros['hora_entrada'];
}
if ($filtros['hora_salida']) {
    $where[] = "h.HORA_SALIDA = ?";
    $params[] = $filtros['hora_salida'];
}
if ($filtros['dia']) {
    $where[] = "hd.ID_DIA = ?";
    $params[] = $filtros['dia'];
}

$sql = "
SELECT 
    h.ID_HORARIO,
    h.NOMBRE,
    h.HORA_ENTRADA,
    h.HORA_SALIDA,
    h.ID_ESTABLECIMIENTO,
    est.NOMBRE AS ESTABLECIMIENTO,
    s.ID_SEDE,
    s.NOMBRE AS SEDE
FROM HORARIO h
INNER JOIN ESTABLECIMIENTO est ON est.ID_ESTABLECIMIENTO = h.ID_ESTABLECIMIENTO
INNER JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
" . (!empty($filtros['dia']) ? "INNER JOIN HORARIO_DIA hd ON hd.ID_HORARIO = h.ID_HORARIO" : "") . "
WHERE " . implode(' AND ', $where) . "
ORDER BY h.ID_HORARIO DESC
";

$stmt = $conn->prepare($sql);
foreach ($params as $i => $val) {
    $stmt->bindValue($i + 1, $val);
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener días para cada horario
foreach ($rows as &$row) {
    $stmt2 = $conn->prepare("
        SELECT d.ID_DIA, d.NOMBRE
        FROM HORARIO_DIA hd
        JOIN DIA_SEMANA d ON d.ID_DIA = hd.ID_DIA
        WHERE hd.ID_HORARIO = ?
        ORDER BY d.ID_DIA
    ");
    $stmt2->execute([$row['ID_HORARIO']]);
    $dias = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $row['DIAS'] = array_column($dias, 'NOMBRE');
    $row['DIAS_ID'] = array_column($dias, 'ID_DIA');
}
unset($row);

echo json_encode(['success'=>true, 'horarios'=>$rows]);