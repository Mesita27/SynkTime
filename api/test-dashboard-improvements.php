<?php
/**
 * Test endpoint to validate dashboard improvements
 */

header('Content-Type: application/json');

// Include required files
require_once '../config/database.php';
require_once '../dashboard-controller.php';

try {
    $fecha = date('Y-m-d');
    $empresaId = 1; // Test with default company
    
    // Test 1: Statistics calculation
    $estadisticas = getEstadisticasAsistencia('empresa', $empresaId, $fecha);
    
    // Test 2: Distribution data
    $distribucion = getDistribucionAsistencias($empresaId, $fecha);
    
    // Test 3: Hourly attendance
    $porHora = getAsistenciasPorHora($empresaId, $fecha);
    
    // Test 4: Recent activity
    $actividad = getActividadReciente($empresaId, $fecha, 5);
    
    // Test 5: New arrival type calculation function
    $testCases = [
        ['08:00:00', '07:45:00', 15, 'temprano'],
        ['08:00:00', '08:00:00', 15, 'atiempo'],
        ['08:00:00', '08:10:00', 15, 'atiempo'],
        ['08:00:00', '08:20:00', 15, 'tarde'],
    ];
    
    $calculationTests = [];
    foreach ($testCases as $test) {
        $resultado = calcularTipoLlegada($test[0], $test[1], $test[2], $fecha);
        $calculationTests[] = [
            'horaEntrada' => $test[0],
            'horaReal' => $test[1],
            'tolerancia' => $test[2],
            'esperado' => $test[3],
            'resultado' => $resultado,
            'correcto' => $resultado === $test[3]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'fecha_prueba' => $fecha,
        'estadisticas' => $estadisticas,
        'distribucion' => $distribucion,
        'por_hora' => $porHora,
        'actividad_reciente' => $actividad,
        'pruebas_calculo' => $calculationTests
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>