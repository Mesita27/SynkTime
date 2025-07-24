<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

header('Content-Type: application/json');

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    $sede = $_GET['sede'] ?? null;
    $establecimiento = $_GET['establecimiento'] ?? null;
    $codigo = $_GET['codigo'] ?? null; // Añadido para buscar por código

    if (!$empresaId) {
        echo json_encode(['success' => false, 'data' => []]);
        exit;
    }

    $where = ["s.ID_EMPRESA = ?"];
    $params = [$empresaId];

    if ($sede) {
        $where[] = "s.ID_SEDE = ?";
        $params[] = $sede;
    }

    if ($establecimiento) {
        $where[] = "est.ID_ESTABLECIMIENTO = ?";
        $params[] = $establecimiento;
    }

    if ($codigo) {
        $where[] = "e.ID_EMPLEADO = ?";
        $params[] = $codigo;
    }

    // Obtenemos la fecha y hora de hace 8 horas
    $fechaHora8HorasAntes = date('Y-m-d H:i:s', strtotime('-8 hours'));
    $fechaActual = date('Y-m-d');
    $fechaAnterior = date('Y-m-d', strtotime('-1 day'));
    $horaLimite = date('H:i:s', strtotime('-8 hours'));

    // SQL para empleados sin asistencia en las últimas 8 horas
    $sql = "
    SELECT 
        e.ID_EMPLEADO, 
        e.NOMBRE, 
        e.APELLIDO, 
        est.NOMBRE AS ESTABLECIMIENTO, 
        s.NOMBRE AS SEDE
    FROM EMPLEADO e
    JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
    JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
    WHERE e.ESTADO = 'A' AND e.ACTIVO = 'S' AND " . implode(" AND ", $where) . "
    AND NOT EXISTS (
        SELECT 1 FROM ASISTENCIA a
        WHERE a.ID_EMPLEADO = e.ID_EMPLEADO
        AND (
            (a.FECHA = ? AND a.TIPO = 'ENTRADA') OR
            (a.FECHA = ? AND a.HORA >= ? AND a.TIPO = 'ENTRADA')
        )
    )
    ORDER BY e.APELLIDO, e.NOMBRE";

    // Añadimos los parámetros para el filtro de fecha/hora
    $params[] = $fechaActual;    // Para el día actual
    $params[] = $fechaAnterior;  // Para el día anterior
    $params[] = $horaLimite;     // Para la hora límite

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'filter_info' => [
            'current_date' => $fechaActual,
            'previous_date' => $fechaAnterior,
            'time_limit' => $horaLimite,
            'hours_back' => 8
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>