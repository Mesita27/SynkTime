<?php
require_once '../auth/session.php';
require_once '../config/database.php';
require_once '../dashboard-controller.php';

// Verificar autenticación
requireAuth();

// Verificar que se recibió el ID de establecimiento
if (!isset($_GET['establecimiento_id'])) {
    echo json_encode(['error' => 'ID de establecimiento no proporcionado']);
    exit;
}

$establecimientoId = (int)$_GET['establecimiento_id'];

// Obtener rango de fechas si se proporciona
$fechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d', strtotime('-1 month'));
$fechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

try {
    // Obtener todas las estadísticas
    $estadisticas = getEstadisticasAsistencia($establecimientoId, $fechaInicio, $fechaFin);
    $asistenciasPorHora = getAsistenciasPorHora($establecimientoId, $fechaInicio, $fechaFin);
    $distribucionAsistencias = getDistribucionAsistencias($establecimientoId, $fechaInicio, $fechaFin);
    $actividadReciente = getActividadReciente($establecimientoId);
    
    echo json_encode([
        'success' => true,
        'estadisticas' => $estadisticas,
        'asistenciasPorHora' => $asistenciasPorHora,
        'distribucionAsistencias' => $distribucionAsistencias,
        'actividadReciente' => $actividadReciente
    ]);
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
    echo json_encode(['error' => 'Error al obtener estadísticas']);
}
?>