<?php
// Incluir la conexión a la base de datos
require_once 'config/database.php';

/**
 * Obtiene información de la empresa
 * 
 * @param int $empresaId ID de la empresa
 * @return array|null Información de la empresa
 */
function getEmpresaInfo($empresaId) {
    global $conn; // Acceder a la conexión global
    
    try {
        $stmt = $conn->prepare("
            SELECT ID_EMPRESA, NOMBRE, RUC, DIRECCION
            FROM EMPRESA
            WHERE ID_EMPRESA = :empresaId AND ESTADO = 'A'
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener información de empresa: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene las sedes de una empresa
 * 
 * @param int $empresaId ID de la empresa
 * @return array Sedes de la empresa
 */
function getSedesByEmpresa($empresaId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT ID_SEDE, NOMBRE, DIRECCION
            FROM SEDE
            WHERE ID_EMPRESA = :empresaId AND ESTADO = 'A'
            ORDER BY NOMBRE
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener sedes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene los establecimientos de una empresa
 * 
 * @param int $empresaId ID de la empresa
 * @param int|null $sedeId ID de la sede (opcional)
 * @return array Establecimientos
 */
function getEstablecimientosByEmpresa($empresaId, $sedeId = null) {
    global $conn;
    
    try {
        $query = "
            SELECT e.ID_ESTABLECIMIENTO, e.NOMBRE, e.DIRECCION, s.ID_SEDE, s.NOMBRE as SEDE_NOMBRE
            FROM ESTABLECIMIENTO e
            JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
            WHERE s.ID_EMPRESA = :empresaId AND e.ESTADO = 'A'
        ";
        
        if ($sedeId) {
            $query .= " AND s.ID_SEDE = :sedeId";
        }
        
        $query .= " ORDER BY s.NOMBRE, e.NOMBRE";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        
        if ($sedeId) {
            $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener establecimientos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene las estadísticas de asistencia para un establecimiento en una fecha
 * 
 * @param int $establecimientoId ID del establecimiento
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Estadísticas de asistencia
 */
function getEstadisticasAsistencia($nivel, $id, $fecha) {
    global $conn;

    // Construcción del filtro y joins según nivel
    if ($nivel === 'empresa') {
        $where = "s.ID_EMPRESA = :id";
        $join = "JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                 JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE";
    } elseif ($nivel === 'sede') {
        $where = "s.ID_SEDE = :id";
        $join = "JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                 JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE";
    } else { // establecimiento
        $where = "est.ID_ESTABLECIMIENTO = :id";
        $join = "JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO 
                 JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE";
    }

    // Traer todos los empleados activos y su horario vigente ese día
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO, h.HORA_ENTRADA, h.HORA_SALIDA, h.TOLERANCIA
        FROM EMPLEADO e
        $join
        LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
            AND eh.FECHA_DESDE <= :fecha
            AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha)
        LEFT JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE $where
        AND e.ESTADO = 'A' AND e.ACTIVO = 'S'
    ");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Inicializa métricas
    $total_empleados = count($empleados);
    $tempranos = 0;
    $atiempo = 0;
    $tardanzas = 0;
    $faltas = 0;
    $salidas_temprano = 0;
    $salidas_atiempo = 0;
    $salidas_tarde = 0;
    $total_asistencias = 0;
    $total_salidas = 0;
    $horas_trabajadas = 0;

    foreach ($empleados as $emp) {
        // --- ENTRADA ---
        $stmt2 = $conn->prepare("SELECT HORA FROM ASISTENCIA WHERE ID_EMPLEADO = :emp AND FECHA = :fecha AND TIPO='ENTRADA' ORDER BY HORA ASC LIMIT 1");
        $stmt2->execute([':emp' => $emp['ID_EMPLEADO'], ':fecha' => $fecha]);
        $asistencia = $stmt2->fetch(PDO::FETCH_ASSOC);

        if (!$asistencia || !$emp['HORA_ENTRADA']) {
            $faltas++;
        } else {
            $hEntrada = $emp['HORA_ENTRADA'];
            $tolerancia = (int)($emp['TOLERANCIA'] ?? 0);
            $hReal = $asistencia['HORA'];
            $entradaMin = strtotime($fecha . ' ' . $hEntrada);
            $realMin = strtotime($fecha . ' ' . $hReal);

            if ($realMin < $entradaMin) $tempranos++;
            elseif ($realMin <= $entradaMin + $tolerancia * 60) $atiempo++;
            else $tardanzas++;

            $total_asistencias++;
        }

        // --- SALIDA ---
        $stmt3 = $conn->prepare("SELECT HORA FROM ASISTENCIA WHERE ID_EMPLEADO = :emp AND FECHA = :fecha AND TIPO='SALIDA' ORDER BY HORA DESC LIMIT 1");
        $stmt3->execute([':emp' => $emp['ID_EMPLEADO'], ':fecha' => $fecha]);
        $salida = $stmt3->fetch(PDO::FETCH_ASSOC);

        if ($asistencia && $salida && $emp['HORA_SALIDA']) {
            $hSalida = $emp['HORA_SALIDA'];
            $realSalida = $salida['HORA'];
            $salidaMin = strtotime($fecha . ' ' . $hSalida);
            $realSalidaMin = strtotime($fecha . ' ' . $realSalida);
            $tolerancia = (int)($emp['TOLERANCIA'] ?? 0);

            if ($realSalidaMin < $salidaMin - $tolerancia*60) $salidas_temprano++;
            elseif ($realSalidaMin <= $salidaMin + $tolerancia*60) $salidas_atiempo++;
            else $salidas_tarde++;

            // --- Horas trabajadas ---
            $minTr = ($realSalidaMin - $realMin) / 60;
            if ($minTr > 0 && $minTr < 24*60) $horas_trabajadas += $minTr / 60;
            $total_salidas++;
        }
    }

    return [
        'total_empleados'      => $total_empleados,
        'llegadas_temprano'    => $tempranos,
        'llegadas_tiempo'      => $atiempo,
        'llegadas_tarde'       => $tardanzas,
        'faltas'               => $faltas,
        'salidas_temprano'     => $salidas_temprano,
        'salidas_atiempo'      => $salidas_atiempo,
        'salidas_tarde'        => $salidas_tarde,
        'total_asistencias'    => $total_asistencias,
        'total_salidas'        => $total_salidas,
        'horas_trabajadas'     => round($horas_trabajadas, 2)
    ];
}


/**
 * Obtiene datos para el gráfico de asistencias por hora (específico del establecimiento)
 * 
 * @param int $establecimientoId ID del establecimiento
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */

 // SEDE: Entradas por hora en una sede
function getAsistenciasPorHoraSede($sedeId, $fecha) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT SUBSTRING(a.HORA, 1, 2) as hora, COUNT(*) as cantidad
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        WHERE est.ID_SEDE = :sedeId
          AND a.FECHA = :fecha
          AND a.TIPO = 'ENTRADA'
          AND e.ACTIVO = 'S'
        GROUP BY hora
        ORDER BY hora
    ");
    $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = [];
    $data = [];
    foreach ($result as $row) {
        $categories[] = $row['hora'] . ':00';
        $data[] = (int)$row['cantidad'];
    }
    return ['categories' => $categories, 'data' => $data];
}

// ESTABLECIMIENTO: Entradas por hora en un establecimiento
function getAsistenciasPorHoraEstablecimiento($establecimientoId, $fecha) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT SUBSTRING(a.HORA, 1, 2) as hora, COUNT(*) as cantidad
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
          AND a.FECHA = :fecha
          AND a.TIPO = 'ENTRADA'
          AND e.ACTIVO = 'S'
        GROUP BY hora
        ORDER BY hora
    ");
    $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = [];
    $data = [];
    foreach ($result as $row) {
        $categories[] = $row['hora'] . ':00';
        $data[] = (int)$row['cantidad'];
    }
    return ['categories' => $categories, 'data' => $data];
}


/**
 * Obtiene datos para el gráfico de distribución de asistencias (específico del establecimiento)
 * 
 * @param int $establecimientoId ID del establecimiento
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */


// SEDE: [Tempranos, A tiempo, Tardanzas, Faltas]
function getDistribucionAsistenciasSede($sedeId, $fecha) {
    global $conn;
    
    try {
        // Get early arrivals, on-time arrivals, and late arrivals for sede
        $stmt = $conn->prepare("
            SELECT 
                e.ID_EMPLEADO,
                a.HORA as entrada_hora,
                a.TARDANZA,
                h.HORA_ENTRADA,
                h.TOLERANCIA
            FROM EMPLEADO e
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO AND a.FECHA = :fecha AND a.TIPO = 'ENTRADA'
            LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
                AND eh.FECHA_DESDE <= :fecha
                AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha)
            LEFT JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
            WHERE est.ID_SEDE = :sedeId
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        
        $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $llegadas_temprano = 0;
        $llegadas_tiempo = 0;
        $llegadas_tarde = 0;
        $faltas = 0;
        
        foreach ($empleados as $emp) {
            if (!$emp['entrada_hora'] || !$emp['HORA_ENTRADA']) {
                $faltas++;
            } else {
                $hEntrada = $emp['HORA_ENTRADA'];
                $tolerancia = (int)($emp['TOLERANCIA'] ?? 0);
                $hReal = $emp['entrada_hora'];
                $entradaMin = strtotime($fecha . ' ' . $hEntrada);
                $realMin = strtotime($fecha . ' ' . $hReal);
                
                if ($realMin < $entradaMin) {
                    $llegadas_temprano++;
                } elseif ($realMin <= $entradaMin + $tolerancia * 60) {
                    $llegadas_tiempo++;
                } else {
                    $llegadas_tarde++;
                }
            }
        }
        
        return [
            'series' => [
                $llegadas_temprano,
                $llegadas_tiempo,
                $llegadas_tarde,
                $faltas
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener distribución de asistencias de sede: " . $e->getMessage());
        return [
            'series' => [0, 0, 0, 0]
        ];
    }
}

// ESTABLECIMIENTO: [Tempranos, A tiempo, Tardanzas, Faltas]
function getDistribucionAsistenciasEstablecimiento($establecimientoId, $fecha) {
    global $conn;
    
    try {
        // Get early arrivals, on-time arrivals, and late arrivals for establecimiento
        $stmt = $conn->prepare("
            SELECT 
                e.ID_EMPLEADO,
                a.HORA as entrada_hora,
                a.TARDANZA,
                h.HORA_ENTRADA,
                h.TOLERANCIA
            FROM EMPLEADO e
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO AND a.FECHA = :fecha AND a.TIPO = 'ENTRADA'
            LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
                AND eh.FECHA_DESDE <= :fecha
                AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha)
            LEFT JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $llegadas_temprano = 0;
        $llegadas_tiempo = 0;
        $llegadas_tarde = 0;
        $faltas = 0;
        
        foreach ($empleados as $emp) {
            if (!$emp['entrada_hora'] || !$emp['HORA_ENTRADA']) {
                $faltas++;
            } else {
                $hEntrada = $emp['HORA_ENTRADA'];
                $tolerancia = (int)($emp['TOLERANCIA'] ?? 0);
                $hReal = $emp['entrada_hora'];
                $entradaMin = strtotime($fecha . ' ' . $hEntrada);
                $realMin = strtotime($fecha . ' ' . $hReal);
                
                if ($realMin < $entradaMin) {
                    $llegadas_temprano++;
                } elseif ($realMin <= $entradaMin + $tolerancia * 60) {
                    $llegadas_tiempo++;
                } else {
                    $llegadas_tarde++;
                }
            }
        }
        
        return [
            'series' => [
                $llegadas_temprano,
                $llegadas_tiempo,
                $llegadas_tarde,
                $faltas
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener distribución de asistencias de establecimiento: " . $e->getMessage());
        return [
            'series' => [0, 0, 0, 0]
        ];
    }
}

/**
 * Obtiene la actividad reciente de asistencias (específico del establecimiento)
 * 
 * @param int $establecimientoId ID del establecimiento
 * @param string $fecha Fecha en formato Y-m-d
 * @param int $limit Límite de registros
 * @return array Registros de actividad
 */
// ESTABLECIMIENTO: Últimas actividades en un establecimiento
function getActividadRecienteEstablecimiento($establecimientoId, $fecha = null, $limit = 10) {
    global $conn;
    if (!$fecha) $fecha = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT a.ID_ASISTENCIA, e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, a.HORA, a.TIPO, a.TARDANZA, a.OBSERVACION, s.NOMBRE as SEDE_NOMBRE, est.NOMBRE as ESTABLECIMIENTO_NOMBRE
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE est.ID_ESTABLECIMIENTO = :establecimientoId AND a.FECHA = :fecha AND e.ACTIVO = 'S'
        ORDER BY a.ID_ASISTENCIA DESC
        LIMIT :limite
    ");
    $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->bindParam(':limite', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtiene datos para el gráfico de asistencias por hora (nivel empresa)
 * 
 * @param int $empresaId ID de la empresa
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */
function getAsistenciasPorHora($empresaId, $fecha) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT SUBSTRING(a.HORA, 1, 2) as hora, COUNT(*) as cantidad
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresaId
          AND a.FECHA = :fecha
          AND a.TIPO = 'ENTRADA'
          AND e.ACTIVO = 'S'
        GROUP BY hora
        ORDER BY hora
    ");
    $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = [];
    $data = [];
    foreach ($result as $row) {
        $categories[] = $row['hora'] . ':00';
        $data[] = (int)$row['cantidad'];
    }
    return ['categories' => $categories, 'data' => $data];
}


/**
 * Obtiene datos para el gráfico de distribución de asistencias (nivel empresa)
 * 
 * @param int $empresaId ID de la empresa
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */
function getDistribucionAsistencias($empresaId, $fecha) {
    global $conn;
    
    try {
        // Get early arrivals, on-time arrivals, and late arrivals
        $stmt = $conn->prepare("
            SELECT 
                e.ID_EMPLEADO,
                a.HORA as entrada_hora,
                a.TARDANZA,
                h.HORA_ENTRADA,
                h.TOLERANCIA
            FROM EMPLEADO e
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO AND a.FECHA = :fecha AND a.TIPO = 'ENTRADA'
            LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
                AND eh.FECHA_DESDE <= :fecha
                AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha)
            LEFT JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
            WHERE s.ID_EMPRESA = :empresaId
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $llegadas_temprano = 0;
        $llegadas_tiempo = 0;
        $llegadas_tarde = 0;
        $faltas = 0;
        
        foreach ($empleados as $emp) {
            if (!$emp['entrada_hora'] || !$emp['HORA_ENTRADA']) {
                $faltas++;
            } else {
                $hEntrada = $emp['HORA_ENTRADA'];
                $tolerancia = (int)($emp['TOLERANCIA'] ?? 0);
                $hReal = $emp['entrada_hora'];
                $entradaMin = strtotime($fecha . ' ' . $hEntrada);
                $realMin = strtotime($fecha . ' ' . $hReal);
                
                if ($realMin < $entradaMin) {
                    $llegadas_temprano++;
                } elseif ($realMin <= $entradaMin + $tolerancia * 60) {
                    $llegadas_tiempo++;
                } else {
                    $llegadas_tarde++;
                }
            }
        }
        
        return [
            'series' => [
                $llegadas_temprano,
                $llegadas_tiempo,
                $llegadas_tarde,
                $faltas
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener distribución de asistencias: " . $e->getMessage());
        return [
            'series' => [0, 0, 0, 0]
        ];
    }
}

/**
 * Obtiene la actividad reciente de asistencias (nivel empresa)
 * 
 * @param int $empresaId ID de la empresa
 * @param string $fecha Fecha en formato Y-m-d
 * @param int $limit Límite de registros
 * @return array Registros de actividad
 */
// EMPRESA: Últimas actividades en la empresa
function getActividadReciente($empresaId, $fecha = null, $limit = 10) {
    global $conn;
    if (!$fecha) $fecha = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT a.ID_ASISTENCIA, e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, a.HORA, a.TIPO, a.TARDANZA, a.OBSERVACION, s.NOMBRE as SEDE_NOMBRE, est.NOMBRE as ESTABLECIMIENTO_NOMBRE
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresaId AND a.FECHA = :fecha AND e.ACTIVO = 'S'
        ORDER BY a.ID_ASISTENCIA DESC
        LIMIT :limite
    ");
    $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->bindParam(':limite', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// SEDE: Últimas actividades en una sede
function getActividadRecienteSede($sedeId, $fecha = null, $limit = 10) {
    global $conn;
    if (!$fecha) $fecha = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT a.ID_ASISTENCIA, e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, a.HORA, a.TIPO, a.TARDANZA, a.OBSERVACION, s.NOMBRE as SEDE_NOMBRE, est.NOMBRE as ESTABLECIMIENTO_NOMBRE
        FROM ASISTENCIA a
        JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_SEDE = :sedeId AND a.FECHA = :fecha AND e.ACTIVO = 'S'
        ORDER BY a.ID_ASISTENCIA DESC
        LIMIT :limite
    ");
    $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
    $stmt->bindParam(':limite', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Determina si una llegada es tardía según el horario del empleado
 * 
 * @param int $idEmpleado ID del empleado
 * @param string $hora Hora en formato HH:MM
 * @param string $fecha Fecha en formato Y-m-d
 * @return bool True si es tardanza, false si no
 */
function esTardanza($idEmpleado, $hora, $fecha) {
    $horario = getHorarioEmpleado($idEmpleado, $fecha);
    if (!$horario) return false;
    
    // Convertir a minutos para comparación numérica
    $horaEntradaPartes = explode(':', $horario['HORA_ENTRADA']);
    $minutosHoraEntrada = (int)$horaEntradaPartes[0] * 60 + (int)$horaEntradaPartes[1];
    
    $horaLlegadaPartes = explode(':', $hora);
    $minutosHoraLlegada = (int)$horaLlegadaPartes[0] * 60 + (int)$horaLlegadaPartes[1];
    
    // Considerar tolerancia
    $toleranciaMinutos = (int)$horario['TOLERANCIA'];
    
    return $minutosHoraLlegada > ($minutosHoraEntrada + $toleranciaMinutos);
}

/**
 * Obtiene el horario de un empleado para una fecha específica
 * 
 * @param int $idEmpleado ID del empleado
 * @param string $fecha Fecha en formato Y-m-d
 * @return array|null Datos del horario o null si no se encuentra
 */
function getHorarioEmpleado($idEmpleado, $fecha) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT h.*
            FROM EMPLEADO_HORARIO eh
            JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
            WHERE eh.ID_EMPLEADO = :idEmpleado
            AND eh.FECHA_DESDE <= :fecha
            AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha)
            ORDER BY eh.FECHA_DESDE DESC
            LIMIT 1
        ");
        
        $stmt->bindParam(':idEmpleado', $idEmpleado, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado ? $resultado : null;
        
    } catch (PDOException $e) {
        error_log("Error al obtener horario del empleado: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene información del usuario logueado
 * 
 * @param string $username Nombre de usuario
 * @return array|null Información del usuario
 */
function getUsuarioInfo($username) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                u.ID_USUARIO,
                u.USERNAME,
                u.NOMBRE_COMPLETO,
                u.EMAIL,
                u.ROL,
                u.ID_EMPRESA,
                e.NOMBRE as EMPRESA_NOMBRE
            FROM USUARIO u
            JOIN EMPRESA e ON u.ID_EMPRESA = e.ID_EMPRESA
            WHERE u.USERNAME = :username 
            AND u.ESTADO = 'A'
        ");
        
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener información del usuario: " . $e->getMessage());
        return null;
    }
}
?>