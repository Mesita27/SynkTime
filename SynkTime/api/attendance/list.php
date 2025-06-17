<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$empresaId = $_SESSION['id_empresa'];
$params = [];
$where = [];

// JOINs para obtener datos de empleado, sede, establecimiento
$sql = "SELECT a.ID_ASISTENCIA, a.ID_EMPLEADO, a.FECHA, a.TIPO, a.HORA, a.TARDANZA, a.OBSERVACION,
    e.ID_EMPLEADO as codigo, e.NOMBRE, e.APELLIDO, est.NOMBRE as establecimiento, s.NOMBRE as sede
FROM ASISTENCIA a
JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
WHERE s.ID_EMPRESA = ? AND a.TIPO='ENTRADA'";
$params[] = $empresaId;

// Filtros
if (!empty($_GET['codigo'])) {
    $sql .= " AND e.ID_EMPLEADO = ?";
    $params[] = $_GET['codigo'];
}
if (!empty($_GET['nombre'])) {
    $sql .= " AND (e.NOMBRE LIKE ? OR e.APELLIDO LIKE ?)";
    $params[] = '%' . $_GET['nombre'] . '%';
    $params[] = '%' . $_GET['nombre'] . '%';
}
if (!empty($_GET['sede'])) {
    $sql .= " AND s.ID_SEDE = ?";
    $params[] = $_GET['sede'];
}
if (!empty($_GET['establecimiento'])) {
    $sql .= " AND est.ID_ESTABLECIMIENTO = ?";
    $params[] = $_GET['establecimiento'];
}
if (!empty($_GET['estado'])) {
    $sql .= " AND (CASE WHEN a.TARDANZA='S' THEN 'Tardanza' WHEN a.HORA<'08:00' THEN 'Temprano' ELSE 'Puntual' END) = ?";
    $params[] = $_GET['estado'];
}
if (!empty($_GET['fecha_desde']) && !empty($_GET['fecha_hasta'])) {
    $sql .= " AND a.FECHA BETWEEN ? AND ?";
    $params[] = $_GET['fecha_desde'];
    $params[] = $_GET['fecha_hasta'];
}

$sql .= " ORDER BY a.FECHA DESC, a.HORA ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Calcular estado de entrada
    $estado = ($row['TARDANZA'] === 'S') ? 'Tardanza' : (($row['HORA'] < '08:00') ? 'Temprano' : 'Puntual');
    $data[] = [
        'id' => $row['ID_ASISTENCIA'],
        'codigo' => $row['codigo'],
        'nombre' => $row['NOMBRE'] . ' ' . $row['APELLIDO'],
        'sede' => $row['sede'],
        'establecimiento' => $row['establecimiento'],
        'fecha' => $row['FECHA'],
        'hora_entrada' => $row['HORA'],
        'estado_entrada' => $estado,
        'observacion' => $row['OBSERVACION']
    ];
}
echo json_encode(['success' => true, 'data' => $data]);