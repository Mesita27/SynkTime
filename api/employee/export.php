<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="empleados.xls"');
header('Cache-Control: max-age=0');

$empresaId = $_SESSION['id_empresa'] ?? null;
$params = [
    'codigo' => $_GET['codigo'] ?? null,
    'identificacion' => $_GET['identificacion'] ?? null,
    'nombre' => $_GET['nombre'] ?? null,
    'departamento' => $_GET['departamento'] ?? null,
    'sede' => $_GET['sede'] ?? null,
];
$sql = "SELECT e.ID_EMPLEADO as id,
               e.DNI as identificacion,
               e.NOMBRE as nombre,
               e.APELLIDO as apellido,
               e.CORREO as email,
               e.TELEFONO as telefono,
               est.NOMBRE as establecimiento,
               s.NOMBRE as sede,
               e.FECHA_INGRESO as fecha_contratacion,
               e.ESTADO as estado
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :id_empresa";
$bind = [':empresaId' => $empresaId];
if ($params['codigo']) {
    $sql .= " AND e.ID_EMPLEADO = :codigo";
    $bind[':codigo'] = $params['codigo'];
}
if ($params['identificacion']) {
    $sql .= " AND e.DNI LIKE :identificacion";
    $bind[':identificacion'] = '%' . $params['identificacion'] . '%';
}
if ($params['nombre']) {
    $sql .= " AND (e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre)";
    $bind[':nombre'] = '%' . $params['nombre'] . '%';
}
if ($params['departamento']) {
    $sql .= " AND est.NOMBRE LIKE :departamento";
    $bind[':departamento'] = '%' . $params['departamento'] . '%';
}
if ($params['sede']) {
    $sql .= " AND s.NOMBRE LIKE :sede";
    $bind[':sede'] = '%' . $params['sede'] . '%';
}
$sql .= " ORDER BY e.APELLIDO, e.NOMBRE";
$stmt = $conn->prepare($sql);
foreach ($bind as $k => $v) $stmt->bindValue($k, $v);
$stmt->execute();

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Identificación</th><th>Nombre</th><th>Apellido</th><th>Email</th><th>Teléfono</th><th>Establecimiento</th><th>Sede</th><th>Fecha contratación</th><th>Estado</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    foreach ($row as $v) echo "<td>".htmlspecialchars($v)."</td>";
    echo "</tr>";
}
echo "</table>";