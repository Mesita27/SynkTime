<?php
require_once __DIR__ . '/../../config/database.php';
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
    
    // Parámetros de filtro
    $filtros = [
        'codigo' => $_GET['codigo'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'estado_entrada' => $_GET['estado_entrada'] ?? null,
        'fecha_desde' => $_GET['fecha_desde'] ?? null,
        'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        'tipo_reporte' => $_GET['tipo_reporte'] ?? null
    ];

    // Construir consulta base
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    // Aplicar filtros de fecha
    if ($filtros['fecha_desde']) {
        $where[] = "a_fecha.FECHA >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fecha_desde'];
    }

    if ($filtros['fecha_hasta']) {
        $where[] = "a_fecha.FECHA <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }

    // Filtros para reporte del día, semana o mes actual
    if ($filtros['tipo_reporte']) {
        switch ($filtros['tipo_reporte']) {
            case 'dia':
                $where[] = "a_fecha.FECHA = CURDATE()";
                break;
            case 'semana':
                $where[] = "YEARWEEK(a_fecha.FECHA, 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'mes':
                $where[] = "YEAR(a_fecha.FECHA) = YEAR(CURDATE()) AND MONTH(a_fecha.FECHA) = MONTH(CURDATE())";
                break;
        }
    }

    // Filtros adicionales
    if ($filtros['codigo']) {
        $where[] = "e.ID_EMPLEADO = :codigo";
        $params[':codigo'] = $filtros['codigo'];
    }

    if ($filtros['nombre']) {
        $where[] = "(e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre)";
        $params[':nombre'] = '%' . $filtros['nombre'] . '%';
    }

    if ($filtros['sede'] && $filtros['sede'] !== 'Todas') {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento'] && $filtros['establecimiento'] !== 'Todos') {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    // Aplicar filtro por estado_entrada si se proporciona
    if ($filtros['estado_entrada'] && $filtros['estado_entrada'] !== 'Todos') {
        // Este filtro se aplicará después en PHP ya que el estado se calcula, no está almacenado
        $filtro_estado = $filtros['estado_entrada'];
    } else {
        $filtro_estado = null;
    }

    $whereClause = implode(' AND ', $where);

    // Consulta principal (sin paginación)
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE AS establecimiento,
            s.NOMBRE AS sede,
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
        ORDER BY a_fecha.FECHA DESC, h.HORA_ENTRADA ASC
    ";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesamos para calcular los estados y aplicar filtro si es necesario
    $result = [];
    
    foreach ($asistencias as $registro) {
        // Calculamos el estado de la entrada
        $estado_entrada = 'Ausente';
        if ($registro['ENTRADA_HORA'] && $registro['HORA_ENTRADA_PROGRAMADA']) {
            $ts_entrada_programada = strtotime($registro['FECHA'] . ' ' . $registro['HORA_ENTRADA_PROGRAMADA']);
            $ts_entrada_real = strtotime($registro['FECHA'] . ' ' . $registro['ENTRADA_HORA']);
            $tolerancia = (int)($registro['TOLERANCIA'] ?? 0);
            
            if ($ts_entrada_real < $ts_entrada_programada) {
                $estado_entrada = 'Temprano';
            } elseif ($ts_entrada_real <= $ts_entrada_programada + $tolerancia * 60) {
                $estado_entrada = 'A Tiempo';
            } else {
                $estado_entrada = 'Tardanza';
            }
        } elseif ($registro['ENTRADA_HORA']) {
            $estado_entrada = 'Presente'; // Si hay registro de entrada pero no hay horario programado
        }
        
        // Calculamos el estado de la salida
        $estado_salida = '--';
        if ($registro['SALIDA_HORA'] && $registro['HORA_SALIDA_PROGRAMADA']) {
            $ts_salida_programada = strtotime($registro['FECHA'] . ' ' . $registro['HORA_SALIDA_PROGRAMADA']);
            $ts_salida_real = strtotime($registro['FECHA'] . ' ' . $registro['SALIDA_HORA']);
            $tolerancia = (int)($registro['TOLERANCIA'] ?? 0);
            
            if ($ts_salida_real < $ts_salida_programada - $tolerancia * 60) {
                $estado_salida = 'Temprano';
            } else {
                $estado_salida = 'Normal';
            }
        } elseif ($registro['SALIDA_HORA']) {
            $estado_salida = 'Registrada';
        }
        
        // Calcular horas trabajadas si hay entrada y salida
        $horas_trabajadas = null;
        if ($registro['ENTRADA_HORA'] && $registro['SALIDA_HORA']) {
            $ts_entrada = strtotime($registro['FECHA'] . ' ' . $registro['ENTRADA_HORA']);
            $ts_salida = strtotime($registro['FECHA'] . ' ' . $registro['SALIDA_HORA']);
            
            // Solo calcular si la salida es posterior a la entrada
            if ($ts_salida > $ts_entrada) {
                $horas_trabajadas = round(($ts_salida - $ts_entrada) / 3600, 2);
            }
        }
        
        // Añadir estados al registro
        $registro['ENTRADA_ESTADO'] = $estado_entrada;
        $registro['SALIDA_ESTADO'] = $estado_salida;
        $registro['HORAS_TRABAJADAS'] = $horas_trabajadas;
        
        // Filtrar por estado si es necesario
        if ($filtro_estado && $estado_entrada !== $filtro_estado) {
            continue; // Saltar este registro si no coincide con el filtro de estado
        }
        
        // Formatear fecha para mostrar
        $fecha_formateada = date('d/m/Y', strtotime($registro['FECHA']));
        $registro['FECHA_FORMATEADA'] = $fecha_formateada;
        
        $result[] = $registro;
    }
    
    // Generar nombre del archivo
    $fechaActual = date('Y-m-d_H-i-s');
    $fileName = "Reporte_Asistencia_{$fechaActual}.xls";

    // Establecer cabeceras para descarga
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    // Crear documento Excel (formato XML simple compatible con Excel)
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<Worksheet ss:Name="Reporte de Asistencia">';
    echo '<Table>';
    
    // Encabezados de columna
    echo '<Row>';
    echo '<Cell><Data ss:Type="String">Código</Data></Cell>';
    echo '<Cell><Data ss:Type="String">DNI</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Nombre</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Apellido</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Sede</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Establecimiento</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Fecha</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Hora Entrada</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Hora Salida</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Estado Entrada</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Estado Salida</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Horas Trabajadas</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Horario Asignado</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Hora Entrada (Horario)</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Hora Salida (Horario)</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Observación</Data></Cell>';
    echo '</Row>';
    
    // Datos
    foreach ($result as $asistencia) {
        echo '<Row>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['ID_EMPLEADO'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['DNI'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['NOMBRE'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['APELLIDO'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['sede'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['establecimiento'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['FECHA_FORMATEADA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['ENTRADA_HORA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['SALIDA_HORA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['ENTRADA_ESTADO'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['SALIDA_ESTADO'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['HORAS_TRABAJADAS'] ? $asistencia['HORAS_TRABAJADAS'].' h' : '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['HORARIO_NOMBRE'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['HORA_ENTRADA_PROGRAMADA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['HORA_SALIDA_PROGRAMADA'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($asistencia['OBSERVACION'] ?? '') . '</Data></Cell>';
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