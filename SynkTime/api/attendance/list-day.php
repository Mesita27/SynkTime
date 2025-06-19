<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

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

$fecha = date('Y-m-d');
$where[] = 'a.FECHA = :fecha';
$params[':fecha'] = $fecha;

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
  MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.TARDANZA END) AS ENTRADA_TARDANZA,
  MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.FOTO END) AS FOTO,
  -- SALIDA
  MIN(CASE WHEN a.TIPO = 'SALIDA' THEN a.HORA END) AS SALIDA_HORA,
  MIN(CASE WHEN a.TIPO = 'SALIDA' THEN a.TARDANZA END) AS SALIDA_TARDANZA,
  -- HORARIO
  h.HORA_ENTRADA,
  h.HORA_SALIDA,
  h.TOLERANCIA
FROM EMPLEADO e
LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO AND a.FECHA = :fecha
JOIN ESTABLECIMIENTO est ON est.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
JOIN SEDE s ON s.ID_SEDE = est.ID_SEDE
LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
    AND eh.FECHA_DESDE <= :fecha AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha)
LEFT JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
WHERE $whereString
GROUP BY e.ID_EMPLEADO, a.FECHA
ORDER BY e.APELLIDO, e.NOMBRE
";

$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();

$data = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // ===== ENTRADA =====
    $estado_entrada = '--';
    $hora_entrada = $row['ENTRADA_HORA'];
    $hora_prog_entrada = $row['HORA_ENTRADA'];
    $tolerancia = (int)($row['TOLERANCIA'] ?? 0);

    if ($hora_entrada && $hora_prog_entrada) {
        $entrada_min = strtotime($row['FECHA'] . ' ' . $hora_prog_entrada);
        $real_min = strtotime($row['FECHA'] . ' ' . $hora_entrada);

        if ($real_min < $entrada_min) {
            $estado_entrada = 'Temprano';
        } elseif ($real_min <= $entrada_min + $tolerancia * 60) {
            $estado_entrada = 'Puntual';
        } else {
            $estado_entrada = 'Tardanza';
        }
    }

    // ===== SALIDA =====
    $estado_salida = '--';
    $hora_salida = $row['SALIDA_HORA'];
    $hora_prog_salida = $row['HORA_SALIDA'];
    if ($hora_salida && $hora_prog_salida) {
        $salida_min = strtotime($row['FECHA'] . ' ' . $hora_prog_salida);
        $real_salida_min = strtotime($row['FECHA'] . ' ' . $hora_salida);

        // Considera tolerancia igual que para entrada/salida
        if ($real_salida_min < $salida_min - $tolerancia * 60) {
            $estado_salida = 'Temprano';
        } elseif ($real_salida_min <= $salida_min + $tolerancia * 60) {
            $estado_salida = 'Puntual';
        } else {
            $estado_salida = 'Tardanza';
        }
    }

    $data[] = [
        'ID_EMPLEADO' => $row['ID_EMPLEADO'],
        'NOMBRE' => $row['NOMBRE'],
        'APELLIDO' => $row['APELLIDO'],
        'establecimiento' => $row['establecimiento'],
        'sede' => $row['sede'],
        'FECHA' => $row['FECHA'],
        'ENTRADA_HORA' => $row['ENTRADA_HORA'],
        'ENTRADA_ESTADO' => $estado_entrada,
        'FOTO' => $row['FOTO'],
        'SALIDA_HORA' => $row['SALIDA_HORA'],
        'SALIDA_ESTADO' => $estado_salida,
    ];
}

echo json_encode(['success' => true, 'data' => $data]);