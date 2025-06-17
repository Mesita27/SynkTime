<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$id = $_GET['id'] ?? '';
if (!$id) {
    echo json_encode(['success'=>false]);
    exit;
}

$stmt = $conn->prepare("SELECT a.ID_ASISTENCIA, a.FECHA, a.HORA, a.TARDANZA, a.OBSERVACION, a.FOTO,
    e.ID_EMPLEADO as codigo, e.NOMBRE, e.APELLIDO, est.NOMBRE as establecimiento, s.NOMBRE as sede
FROM ASISTENCIA a
JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
WHERE a.ID_ASISTENCIA = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) { echo json_encode(['success'=>false]); exit; }

$estado = ($row['TARDANZA'] === 'S') ? 'Tardanza' : (($row['HORA'] < '08:00') ? 'Temprano' : 'Puntual');

// Construir la URL de la foto si existe
$foto_url = null;
if (!empty($row['FOTO'])) {
    // Verificar si el archivo existe
    $foto_path = __DIR__ . '/../../uploads/' . $row['FOTO'];
    if (file_exists($foto_path)) {
        $foto_url = 'uploads/' . $row['FOTO'];
    }
}

echo json_encode(['success'=>true, 'data'=>[
    'codigo' => $row['codigo'],
    'nombre' => $row['NOMBRE'].' '.$row['APELLIDO'],
    'sede' => $row['sede'],
    'establecimiento' => $row['establecimiento'],
    'fecha' => $row['FECHA'],
    'hora_entrada' => $row['HORA'],
    'estado_entrada' => $estado,
    'observacion' => $row['OBSERVACION'],
    'foto' => $foto_url
]]);