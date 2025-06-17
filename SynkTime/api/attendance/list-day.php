<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

// Filtros según tu frontend
$empresaId = $_SESSION['id_empresa'];
$sede = $_GET['sede'] ?? null;
$establecimiento = $_GET['establecimiento'] ?? null;

$where = ['s.ID_EMPRESA = :empresaId'];
$params = [':empresaId' => $empresaId];

if ($sede) {
    $where[] = 's.ID_SEDE = :sede';
    $params[':sede'] = $sede;
}
if ($establecimiento) {
    $where[] = 'est.ID_ESTABLECIMIENTO = :establecimiento';
    $params[':establecimiento'] = $establecimiento;
}

// Solo del día actual, puedes cambiarlo si quieres fechas distintas
$where[] = 'a.FECHA = CURDATE()';

$whereString = implode(' AND ', $where);

$sql = "
SELECT 
  e.ID_EMPLEADO,
  e.NOMBRE,
  e.APELLIDO,
  est.NOMBRE AS establecimiento,
  s.NOMBRE AS sede,
  a.FECHA,
  -- ENTRADA
  MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.HORA END) AS ENTRADA_HORA,
  MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.TARDANZA END) AS ENTRADA_ESTADO,
  MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.FOTO END) AS FOTO,
  -- SALIDA
  MIN(CASE WHEN a.TIPO = 'SALIDA' THEN a.HORA END) AS SALIDA_HORA,
  MIN(CASE WHEN a.TIPO = 'SALIDA' THEN a.TARDANZA END) AS SALIDA_ESTADO
FROM ASISTENCIA a
JOIN EMPLEADO e ON e.ID_EMPLEADO = a.ID_EMPLEADO
JOIN ESTABLECIMIENTO est ON est.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
JOIN SEDE s ON s.ID_SEDE = est.ID_SEDE
WHERE $whereString
GROUP BY a.ID_EMPLEADO, a.FECHA
ORDER BY e.APELLIDO, e.NOMBRE
";

$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'data' => $data]);