<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir la conexión a la base de datos y controlador
require_once '../config/database.php';
require_once '../dashboard-controller.php';

// Verificar que se reciba el parámetro establecimiento_id
if (!isset($_GET['establecimiento_id']) || empty($_GET['establecimiento_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID de establecimiento requerido'
    ]);
    exit;
}

$establecimientoId = (int)$_GET['establecimiento_id'];

// Obtener fecha desde parámetros o usar fecha actual
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Formato de fecha inválido. Use YYYY-MM-DD'
    ]);
    exit;
}

try {
    // Obtener ID de empresa del establecimiento
    $stmt = $conn->prepare("
        SELECT s.ID_EMPRESA 
        FROM ESTABLECIMIENTO e
        JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
        WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
    ");
    $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
    $stmt->execute();
    $empresaData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empresaData) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Establecimiento no encontrado'
        ]);
        exit;
    }
    
    $empresaId = $empresaData['ID_EMPRESA'];
    
    // Obtener estadísticas del establecimiento
    $estadisticas = getEstadisticasAsistencia($establecimientoId, $fecha);
    
    // Obtener datos para gráficos (nivel empresa para contexto)
    $asistenciasPorHora = getAsistenciasPorHoraEstablecimiento($establecimientoId, $fecha);
    $distribucionAsistencias = getDistribucionAsistenciasEstablecimiento($establecimientoId, $fecha);
    
    // Obtener actividad reciente del establecimiento
    $actividadReciente = getActividadRecienteEstablecimiento($establecimientoId, $fecha);
    
    echo json_encode([
        'success' => true,
        'establecimiento_id' => $establecimientoId,
        'fecha' => $fecha,
        'estadisticas' => $estadisticas,
        'asistenciasPorHora' => $asistenciasPorHora,
        'distribucionAsistencias' => $distribucionAsistencias,
        'actividadReciente' => $actividadReciente
    ]);
    
} catch (PDOException $e) {
    error_log("Error al obtener estadísticas del dashboard: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>