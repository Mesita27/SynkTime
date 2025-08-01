<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/holidays-helper.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['id_empresa'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$empresaId = $_SESSION['id_empresa'];

try {
    // Establecer zona horaria de Colombia
    date_default_timezone_set('America/Bogota');
    
    // Parámetros de filtro
    $filtros = [
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'empleados' => $_GET['empleados'] ?? [], // Support array of employee IDs
        'fechaDesde' => $_GET['fechaDesde'] ?? date('Y-m-d'),
        'fechaHasta' => $_GET['fechaHasta'] ?? date('Y-m-d')
    ];

    // Construir consulta base
    $where = ["emp.ID_EMPRESA = ?"];
    $params = [$empresaId];

    // Aplicar filtros de fecha
    if ($filtros['fechaDesde']) {
        $where[] = "a_fecha.FECHA >= ?";
        $params[] = $filtros['fechaDesde'];
    }

    if ($filtros['fechaHasta']) {
        $where[] = "a_fecha.FECHA <= ?";
        $params[] = $filtros['fechaHasta'];
    }

    // Filtros adicionales
    if (!empty($filtros['empleados']) && is_array($filtros['empleados'])) {
        $empleadosPlaceholders = implode(',', array_fill(0, count($filtros['empleados']), '?'));
        $where[] = "e.ID_EMPLEADO IN ($empleadosPlaceholders)";
        foreach ($filtros['empleados'] as $empleadoId) {
            $params[] = $empleadoId;
        }
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = ?";
        $params[] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "est.ID_ESTABLECIMIENTO = ?";
        $params[] = $filtros['establecimiento'];
    }

    $whereClause = implode(' AND ', $where);

    // Consulta principal para obtener asistencias
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE AS ESTABLECIMIENTO,
            s.NOMBRE AS SEDE,
            a_fecha.FECHA,
            h.ID_HORARIO,
            h.NOMBRE AS HORARIO_NOMBRE,
            h.HORA_ENTRADA AS HORA_ENTRADA_PROGRAMADA,
            h.HORA_SALIDA AS HORA_SALIDA_PROGRAMADA,
            h.TOLERANCIA,
            
            -- Entrada
            entrada.ID_ASISTENCIA AS ENTRADA_ID,
            entrada.HORA AS ENTRADA_HORA,
            entrada.TARDANZA AS ENTRADA_TARDANZA,
            entrada.OBSERVACION as OBSERVACION,
            
            -- Salida
            salida.ID_ASISTENCIA AS SALIDA_ID,
            salida.HORA AS SALIDA_HORA,
            salida.TARDANZA AS SALIDA_TARDANZA
            
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA

        -- Subconsulta para obtener fechas únicas de asistencia
        JOIN (
            SELECT DISTINCT a.ID_EMPLEADO, a.FECHA, a.ID_HORARIO
            FROM ASISTENCIA a
        ) AS a_fecha ON e.ID_EMPLEADO = a_fecha.ID_EMPLEADO

        -- Unión con HORARIO a través del ID_HORARIO en la asistencia
        LEFT JOIN HORARIO h ON h.ID_HORARIO = a_fecha.ID_HORARIO

        -- Subconsulta para obtener la entrada más reciente
        LEFT JOIN (
            SELECT a_entrada.ID_ASISTENCIA, a_entrada.ID_EMPLEADO, a_entrada.FECHA, a_entrada.ID_HORARIO, 
                a_entrada.HORA, a_entrada.TARDANZA, a_entrada.OBSERVACION
            FROM ASISTENCIA a_entrada
            WHERE a_entrada.TIPO = 'ENTRADA'
            AND NOT EXISTS (
                SELECT 1 FROM ASISTENCIA a2
                WHERE a2.ID_EMPLEADO = a_entrada.ID_EMPLEADO
                AND a2.FECHA = a_entrada.FECHA
                AND a2.ID_HORARIO = a_entrada.ID_HORARIO
                AND a2.TIPO = 'ENTRADA'
                AND a2.ID_ASISTENCIA > a_entrada.ID_ASISTENCIA
            )
        ) AS entrada ON e.ID_EMPLEADO = entrada.ID_EMPLEADO 
                    AND a_fecha.FECHA = entrada.FECHA
                    AND a_fecha.ID_HORARIO = entrada.ID_HORARIO

        -- Subconsulta para obtener la salida más reciente
        LEFT JOIN (
            SELECT a_salida.ID_ASISTENCIA, a_salida.ID_EMPLEADO, a_salida.FECHA, a_salida.ID_HORARIO, 
                a_salida.HORA, a_salida.TARDANZA
            FROM ASISTENCIA a_salida
            WHERE a_salida.TIPO = 'SALIDA'
            AND NOT EXISTS (
                SELECT 1 FROM ASISTENCIA a2
                WHERE a2.ID_EMPLEADO = a_salida.ID_EMPLEADO
                AND a2.FECHA = a_salida.FECHA
                AND a2.ID_HORARIO = a_salida.ID_HORARIO
                AND a2.TIPO = 'SALIDA'
                AND a2.ID_ASISTENCIA > a_salida.ID_ASISTENCIA
            )
        ) AS salida ON e.ID_EMPLEADO = salida.ID_EMPLEADO 
                    AND a_fecha.FECHA = salida.FECHA
                    AND a_fecha.ID_HORARIO = salida.ID_HORARIO

        WHERE {$whereClause}
        GROUP BY e.ID_EMPLEADO, a_fecha.FECHA, a_fecha.ID_HORARIO
        ORDER BY a_fecha.FECHA DESC, e.NOMBRE, e.APELLIDO
    ";

    $stmt = $conn->prepare($sql);
    
    // Bind parameters by position
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
    
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesamos para calcular las horas trabajadas y clasificaciones
    $result = [];
    $stats = [
        'total' => 0,
        'regular' => 0,
        'extra' => 0,
        'dominicales' => 0,
        'festivos' => 0
    ];
    
    // Obtener festivos para el rango de fechas
    $festivosHelper = new HolidaysHelper();
    $festivos = $festivosHelper->getFestivosRango($filtros['fechaDesde'], $filtros['fechaHasta']);
    
    foreach ($asistencias as $registro) {
        $fecha = $registro['FECHA'];
        $diaSemana = date('w', strtotime($fecha));
        $esDomingo = ($diaSemana == 0);
        $esFestivo = in_array($fecha, $festivos) || $festivosHelper->esDiaCivico($fecha);
        
        // Calcular horas trabajadas si hay entrada y salida
        $horasRegulares = 0;
        $horasExtras = 0;
        $horasDominicales = 0;
        $horasFestivos = 0;
        $totalHoras = 0;
        
        if ($registro['ENTRADA_HORA'] && $registro['SALIDA_HORA']) {
            $entradaTs = strtotime($fecha . ' ' . $registro['ENTRADA_HORA']);
            $salidaTs = strtotime($fecha . ' ' . $registro['SALIDA_HORA']);
            
            // Solo calcular si la salida es posterior a la entrada
            if ($salidaTs > $entradaTs) {
                $horasTrabajadas = ($salidaTs - $entradaTs) / 3600;
                
                // Clasificar las horas según el tipo de día y horario
                if ($esFestivo) {
                    $horasFestivos = round($horasTrabajadas, 2);
                } elseif ($esDomingo) {
                    $horasDominicales = round($horasTrabajadas, 2);
                } else {
                    // Calcular horas regulares vs extras basado en el horario programado
                    $horasRegularesProgramadas = 8; // Por defecto 8 horas
                    
                    if ($registro['HORA_ENTRADA_PROGRAMADA'] && $registro['HORA_SALIDA_PROGRAMADA']) {
                        $entradaProgramadaTs = strtotime($fecha . ' ' . $registro['HORA_ENTRADA_PROGRAMADA']);
                        $salidaProgramadaTs = strtotime($fecha . ' ' . $registro['HORA_SALIDA_PROGRAMADA']);
                        $horasRegularesProgramadas = ($salidaProgramadaTs - $entradaProgramadaTs) / 3600;
                    }
                    
                    if ($horasTrabajadas <= $horasRegularesProgramadas) {
                        $horasRegulares = round($horasTrabajadas, 2);
                    } else {
                        $horasRegulares = round($horasRegularesProgramadas, 2);
                        $horasExtras = round($horasTrabajadas - $horasRegularesProgramadas, 2);
                    }
                }
                
                $totalHoras = $horasRegulares + $horasExtras + $horasDominicales + $horasFestivos;
            }
        }
        
        // Añadir clasificaciones al registro
        $registro['HORAS_REGULARES'] = $horasRegulares;
        $registro['HORAS_EXTRAS'] = $horasExtras;
        $registro['HORAS_DOMINICALES'] = $horasDominicales;
        $registro['HORAS_FESTIVOS'] = $horasFestivos;
        $registro['TOTAL_HORAS'] = round($totalHoras, 2);
        $registro['ES_FESTIVO'] = $esFestivo ? 'S' : 'N';
        $registro['ES_DOMINGO'] = $esDomingo ? 'S' : 'N';
        $registro['OBSERVACIONES'] = $registro['OBSERVACION'] ?? '';
        
        // Actualizar estadísticas
        $stats['total'] += $totalHoras;
        $stats['regular'] += $horasRegulares;
        $stats['extra'] += $horasExtras;
        $stats['dominicales'] += $horasDominicales;
        $stats['festivos'] += $horasFestivos;
        
        $result[] = $registro;
    }
    
    // Redondear estadísticas
    foreach ($stats as $key => $value) {
        $stats[$key] = round($value, 2);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'horas' => $result,
        'stats' => $stats,
        'total_registros' => count($result)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get-horas.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener las horas trabajadas: ' . $e->getMessage()
    ]);
}
?>