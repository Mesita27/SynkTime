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
function getEstadisticasAsistencia($establecimientoId, $fecha) {
    global $conn;
    
    try {
        // Consulta para obtener las estadísticas básicas
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
                SUM(CASE WHEN a.TARDANZA = 'N' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as llegadas_tiempo,
                SUM(CASE WHEN a.TARDANZA = 'S' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as llegadas_tarde
            FROM EMPLEADO e
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO AND a.FECHA = :fecha
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId 
            AND e.ESTADO = 'A' 
            AND e.ACTIVO = 'S'
        ");
        
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->execute();
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Consulta para calcular las faltas (empleados sin registro de entrada en la fecha)
        $stmt = $conn->prepare("
            SELECT COUNT(e.ID_EMPLEADO) as faltas
            FROM EMPLEADO e
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO 
                AND a.FECHA = :fecha 
                AND a.TIPO = 'ENTRADA'
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId 
            AND e.ESTADO = 'A' 
            AND e.ACTIVO = 'S'
            AND a.ID_ASISTENCIA IS NULL
        ");
        
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->execute();
        $faltasResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $estadisticas['faltas'] = $faltasResult['faltas'];
        
        // Calcular horas trabajadas (total)
        $stmt = $conn->prepare("
            SELECT 
                SUM(
                    TIMESTAMPDIFF(
                        MINUTE,
                        CONCAT(entrada.FECHA, ' ', entrada.HORA),
                        CONCAT(salida.FECHA, ' ', salida.HORA)
                    ) / 60
                ) as horas_trabajadas
            FROM EMPLEADO e
            JOIN ASISTENCIA entrada ON e.ID_EMPLEADO = entrada.ID_EMPLEADO 
                AND entrada.FECHA = :fecha 
                AND entrada.TIPO = 'ENTRADA'
            JOIN ASISTENCIA salida ON e.ID_EMPLEADO = salida.ID_EMPLEADO 
                AND salida.FECHA = entrada.FECHA 
                AND salida.TIPO = 'SALIDA'
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->execute();
        $horasResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $estadisticas['horas_trabajadas'] = round($horasResult['horas_trabajadas'] ?? 0);
        
        return $estadisticas;
        
    } catch (PDOException $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        return [
            'total_empleados' => 0,
            'llegadas_tiempo' => 0,
            'llegadas_tarde' => 0,
            'faltas' => 0,
            'horas_trabajadas' => 0
        ];
    }
}

/**
 * Obtiene datos para el gráfico de asistencias por hora (específico del establecimiento)
 * 
 * @param int $establecimientoId ID del establecimiento
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */
function getAsistenciasPorHoraEstablecimiento($establecimientoId, $fecha) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                SUBSTRING(a.HORA, 1, 2) as hora,
                COUNT(*) as cantidad
            FROM ASISTENCIA a
            JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
            AND a.FECHA = :fecha
            AND a.TIPO = 'ENTRADA'
            AND e.ACTIVO = 'S'
            GROUP BY SUBSTRING(a.HORA, 1, 2)
            ORDER BY hora
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Preparar el formato para el gráfico
        $categories = [];
        $data = [];
        
        foreach ($resultado as $fila) {
            $categories[] = $fila['hora'] . ':00';
            $data[] = (int)$fila['cantidad'];
        }
        
        return [
            'categories' => $categories,
            'data' => $data
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener asistencias por hora del establecimiento: " . $e->getMessage());
        return [
            'categories' => [],
            'data' => []
        ];
    }
}

/**
 * Obtiene datos para el gráfico de distribución de asistencias (específico del establecimiento)
 * 
 * @param int $establecimientoId ID del establecimiento
 * @param string $fecha Fecha en formato Y-m-d
 * @return array Datos para el gráfico
 */
function getDistribucionAsistenciasEstablecimiento($establecimientoId, $fecha) {
    global $conn;
    
    try {
        // Consulta para obtener el total de empleados, asistencias a tiempo y tardanzas
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
                SUM(CASE WHEN a.TARDANZA = 'N' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as llegadas_tiempo,
                SUM(CASE WHEN a.TARDANZA = 'S' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as llegadas_tarde
            FROM EMPLEADO e
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO AND a.FECHA = :fecha
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Consulta para calcular las faltas (empleados sin registro de entrada en la fecha)
        $stmt = $conn->prepare("
            SELECT COUNT(e.ID_EMPLEADO) as faltas
            FROM EMPLEADO e
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO 
                AND a.FECHA = :fecha 
                AND a.TIPO = 'ENTRADA'
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId 
            AND e.ESTADO = 'A' 
            AND e.ACTIVO = 'S'
            AND a.ID_ASISTENCIA IS NULL
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $faltasResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'series' => [
                (int)$resultado['llegadas_tiempo'],
                (int)$resultado['llegadas_tarde'],
                (int)$faltasResult['faltas']
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener distribución de asistencias del establecimiento: " . $e->getMessage());
        return [
            'series' => [0, 0, 0]
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
function getActividadRecienteEstablecimiento($establecimientoId, $fecha = null, $limit = 10) {
    global $conn;
    
    if (!$fecha) {
        $fecha = date('Y-m-d');
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                a.ID_ASISTENCIA,
                e.ID_EMPLEADO,
                e.NOMBRE,
                e.APELLIDO,
                a.HORA,
                a.TIPO,
                a.TARDANZA,
                a.OBSERVACION,
                s.NOMBRE as SEDE_NOMBRE,
                est.NOMBRE as ESTABLECIMIENTO_NOMBRE
            FROM ASISTENCIA a
            JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
            AND a.FECHA = :fecha
            AND e.ACTIVO = 'S'
            ORDER BY a.ID_ASISTENCIA DESC
            LIMIT :limite
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':limite', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener actividad reciente del establecimiento: " . $e->getMessage());
        return [];
    }
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
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                SUBSTRING(a.HORA, 1, 2) as hora,
                COUNT(*) as cantidad
            FROM ASISTENCIA a
            JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            WHERE s.ID_EMPRESA = :empresaId
            AND a.FECHA = :fecha
            AND a.TIPO = 'ENTRADA'
            AND e.ACTIVO = 'S'
            GROUP BY SUBSTRING(a.HORA, 1, 2)
            ORDER BY hora
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Preparar el formato para el gráfico
        $categories = [];
        $data = [];
        
        foreach ($resultado as $fila) {
            $categories[] = $fila['hora'] . ':00';
            $data[] = (int)$fila['cantidad'];
        }
        
        return [
            'categories' => $categories,
            'data' => $data
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener asistencias por hora: " . $e->getMessage());
        return [
            'categories' => [],
            'data' => []
        ];
    }
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
        // Consulta para obtener el total de empleados, asistencias a tiempo y tardanzas
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
                SUM(CASE WHEN a.TARDANZA = 'N' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as llegadas_tiempo,
                SUM(CASE WHEN a.TARDANZA = 'S' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as llegadas_tarde
            FROM EMPLEADO e
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO AND a.FECHA = :fecha
            WHERE s.ID_EMPRESA = :empresaId
            AND e.ESTADO = 'A'
            AND e.ACTIVO = 'S'
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Consulta para calcular las faltas (empleados sin registro de entrada en la fecha)
        $stmt = $conn->prepare("
            SELECT COUNT(e.ID_EMPLEADO) as faltas
            FROM EMPLEADO e
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO 
                AND a.FECHA = :fecha 
                AND a.TIPO = 'ENTRADA'
            WHERE s.ID_EMPRESA = :empresaId 
            AND e.ESTADO = 'A' 
            AND e.ACTIVO = 'S'
            AND a.ID_ASISTENCIA IS NULL
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->execute();
        $faltasResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'series' => [
                (int)$resultado['llegadas_tiempo'],
                (int)$resultado['llegadas_tarde'],
                (int)$faltasResult['faltas']
            ]
        ];
        
    } catch (PDOException $e) {
        error_log("Error al obtener distribución de asistencias: " . $e->getMessage());
        return [
            'series' => [0, 0, 0]
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
function getActividadReciente($empresaId, $fecha = null, $limit = 10) {
    global $conn;
    
    if (!$fecha) {
        $fecha = date('Y-m-d');
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                a.ID_ASISTENCIA,
                e.ID_EMPLEADO,
                e.NOMBRE,
                e.APELLIDO,
                a.HORA,
                a.TIPO,
                a.TARDANZA,
                a.OBSERVACION,
                s.NOMBRE as SEDE_NOMBRE
            FROM ASISTENCIA a
            JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            WHERE s.ID_EMPRESA = :empresaId
            AND a.FECHA = :fecha
            AND e.ACTIVO = 'S'
            ORDER BY a.ID_ASISTENCIA DESC
            LIMIT :limite
        ");
        
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':limite', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener actividad reciente: " . $e->getMessage());
        return [];
    }
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