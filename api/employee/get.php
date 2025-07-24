<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

$id = $_GET['id'] ?? '';
if (!$id) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("SELECT 
    e.ID_EMPLEADO as id,
    e.DNI as identificacion,
    e.NOMBRE as nombre,
    e.APELLIDO as apellido,
    e.CORREO as email,
    e.TELEFONO as telefono,
    est.NOMBRE as departamento,
    s.NOMBRE as sede,
    e.ID_ESTABLECIMIENTO,
    s.ID_SEDE,
    e.FECHA_INGRESO as fecha_contratacion,
    e.ESTADO as estado
 FROM EMPLEADO e
 JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
 JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
 WHERE e.ID_EMPLEADO = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => !!$data, 'data' => $data]);