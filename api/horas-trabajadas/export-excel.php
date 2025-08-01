<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/holidays-helper.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['id_empresa'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Acceso no autorizado');
}

$empresaId = $_SESSION['id_empresa'];

try {
    // Establecer zona horaria de Colombia
    date_default_timezone_set('America/Bogota');
    
    // Parámetros de filtro (iguales que en get-horas.php)
    $filtros = [
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'empleado' => $_GET['empleado'] ?? null,
        'fechaDesde' => $_GET['fechaDesde'] ?? date('Y-m-d'),
        'fechaHasta' => $_GET['fechaHasta'] ?? date('Y-m-d')
    ];

    // Construir consulta base (reutilizar lógica de get-horas.php)
    $where = ["emp.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    // Aplicar filtros de fecha
    if ($filtros['fechaDesde']) {
        $where[] = "a_fecha.FECHA >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fechaDesde'];
    }

    if ($filtros['fechaHasta']) {
        $where[] = "a_fecha.FECHA <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fechaHasta'];
    }

    // Filtros adicionales
    if ($filtros['empleado']) {
        $where[] = "e.ID_EMPLEADO = :empleado";
        $params[':empleado'] = $filtros['empleado'];
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    $whereClause = implode(' AND ', $where);

    // Consulta principal (misma que get-horas.php)
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
            
            entrada.ID_ASISTENCIA AS ENTRADA_ID,
            entrada.HORA AS ENTRADA_HORA,
            entrada.TARDANZA AS ENTRADA_TARDANZA,
            entrada.OBSERVACION as OBSERVACION,
            
            salida.ID_ASISTENCIA AS SALIDA_ID,
            salida.HORA AS SALIDA_HORA,
            salida.TARDANZA AS SALIDA_TARDANZA
            
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA

        JOIN (
            SELECT DISTINCT a.ID_EMPLEADO, a.FECHA, a.ID_HORARIO
            FROM ASISTENCIA a
        ) AS a_fecha ON e.ID_EMPLEADO = a_fecha.ID_EMPLEADO

        LEFT JOIN HORARIO h ON h.ID_HORARIO = a_fecha.ID_HORARIO

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
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesamos para calcular las horas trabajadas (mismo cálculo que get-horas.php)
    $result = [];
    $festivosHelper = new HolidaysHelper();
    $festivos = $festivosHelper->getFestivosRango($filtros['fechaDesde'], $filtros['fechaHasta']);
    
    foreach ($asistencias as $registro) {
        $fecha = $registro['FECHA'];
        $diaSemana = date('w', strtotime($fecha));
        $esDomingo = ($diaSemana == 0);
        $esFestivo = in_array($fecha, $festivos) || $festivosHelper->esDiaCivico($fecha);
        
        $horasRegulares = 0;
        $horasExtras = 0;
        $horasDominicales = 0;
        $horasFestivos = 0;
        $totalHoras = 0;
        
        if ($registro['ENTRADA_HORA'] && $registro['SALIDA_HORA']) {
            $entradaTs = strtotime($fecha . ' ' . $registro['ENTRADA_HORA']);
            $salidaTs = strtotime($fecha . ' ' . $registro['SALIDA_HORA']);
            
            if ($salidaTs > $entradaTs) {
                $horasTrabajadas = ($salidaTs - $entradaTs) / 3600;
                
                if ($esFestivo) {
                    $horasFestivos = round($horasTrabajadas, 2);
                } elseif ($esDomingo) {
                    $horasDominicales = round($horasTrabajadas, 2);
                } else {
                    $horasRegularesProgramadas = 8;
                    
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
        
        $registro['HORAS_REGULARES'] = $horasRegulares;
        $registro['HORAS_EXTRAS'] = $horasExtras;
        $registro['HORAS_DOMINICALES'] = $horasDominicales;
        $registro['HORAS_FESTIVOS'] = $horasFestivos;
        $registro['TOTAL_HORAS'] = round($totalHoras, 2);
        $registro['ES_FESTIVO'] = $esFestivo ? 'Sí' : 'No';
        $registro['ES_DOMINGO'] = $esDomingo ? 'Sí' : 'No';
        
        // Formatear fecha para mostrar
        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        $registro['FECHA_FORMATEADA'] = $fecha_formateada;
        
        // Obtener día de la semana
        $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $registro['DIA_SEMANA'] = $diasSemana[date('w', strtotime($fecha))];
        
        $result[] = $registro;
    }
    
    // Generar nombre del archivo
    $fechaActual = date('Y-m-d_H-i-s');
    $fileName = "Horas_Trabajadas_{$fechaActual}.xls";

    // Establecer cabeceras para descarga
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    // Crear documento Excel (formato XML simple compatible con Excel)
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<Worksheet ss:Name="Horas Trabajadas">';
    echo '<Table>';
    
    // Encabezados de columna
    echo '<Row>';
    echo '<Cell><Data ss:Type="String">Código Empleado</Data></Cell>';
    echo '<Cell><Data ss:Type="String">DNI</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Nombre</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Apellido</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Sede</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Establecimiento</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Fecha</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Día de Semana</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Es Domingo</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Es Festivo</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Hora Entrada</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Hora Salida</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Horas Regulares</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Horas Extras</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Horas Dominicales</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Horas Festivos</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Total Horas</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Horario Asignado</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Entrada Programada</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Salida Programada</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Observaciones</Data></Cell>';
    echo '</Row>';
    
    // Datos
    foreach ($result as $registro) {
        echo '<Row>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['ID_EMPLEADO'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['DNI'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['NOMBRE'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['APELLIDO'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['SEDE'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['ESTABLECIMIENTO'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['FECHA_FORMATEADA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['DIA_SEMANA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['ES_DOMINGO'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['ES_FESTIVO'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['ENTRADA_HORA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['SALIDA_HORA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . htmlspecialchars($registro['HORAS_REGULARES'] ?? '0') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . htmlspecialchars($registro['HORAS_EXTRAS'] ?? '0') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . htmlspecialchars($registro['HORAS_DOMINICALES'] ?? '0') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . htmlspecialchars($registro['HORAS_FESTIVOS'] ?? '0') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . htmlspecialchars($registro['TOTAL_HORAS'] ?? '0') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['HORARIO_NOMBRE'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['HORA_ENTRADA_PROGRAMADA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['HORA_SALIDA_PROGRAMADA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($registro['OBSERVACION'] ?? '') . '</Data></Cell>';
        echo '</Row>';
    }
    
    echo '</Table>';
    echo '</Worksheet>';
    echo '</Workbook>';
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: text/plain');
    echo 'Error al generar el reporte: ' . $e->getMessage();
}
?>