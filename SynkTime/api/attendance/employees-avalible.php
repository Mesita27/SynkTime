<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
$empresaId = $_SESSION['id_empresa'] ?? null;
$sede = $_GET['sede'] ?? null;
$establecimiento = $_GET['establecimiento'] ?? null;
header('Content-Type: application/json');
if (!$empresaId) {
    echo json_encode(['success' => false, 'data' => []]); exit;
}
$where = ["s.ID_EMPRESA = ?"];
$params = [$empresaId];
if ($sede) { $where[] = "s.ID_SEDE = ?"; $params[] = $sede; }
if ($establecimiento) { $where[] = "est.ID_ESTABLECIMIENTO = ?"; $params[] = $establecimiento; }
$sql = "
SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, est.NOMBRE AS ESTABLECIMIENTO, s.NOMBRE AS SEDE
FROM EMPLEADO e
JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
WHERE e.ESTADO = 'A' AND e.ACTIVO = 'S' AND " . implode(" AND ", $where) . "
AND e.ID_EMPLEADO NOT IN (
    SELECT a.ID_EMPLEADO FROM ASISTENCIA a
    WHERE a.FECHA = CURDATE() AND a.TIPO = 'ENTRADA'
    AND NOT EXISTS (
        SELECT 1 FROM ASISTENCIA s2
        WHERE s2.ID_EMPLEADO = a.ID_EMPLEADO AND s2.FECHA = a.FECHA AND s2.TIPO = 'SALIDA'
    )
)
ORDER BY e.APELLIDO, e.NOMBRE";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);