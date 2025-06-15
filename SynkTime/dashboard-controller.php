<?php
// Verificar sesión y permisos
require_once 'auth/session.php';
require_once 'config/database.php';

// Requerir autenticación
$user = requireAuth();

/**
 * Obtiene la información de la empresa del usuario actual
 */
function getEmpresaInfo($userId) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT e.ID_EMPRESA, e.NOMBRE, e.RUC, e.DIRECCION
            FROM EMPRESA e
            INNER JOIN USUARIO u ON e.ID_EMPRESA = u.ID_EMPRESA
            WHERE u.ID_USUARIO = :userId AND e.ESTADO = 'A'
        ");
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo información de empresa: " . $e->getMessage());
        return null;
    }
}

/**
 * Obtiene todas las sedes de una empresa
 */
function getSedesByEmpresa($empresaId) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT ID_SEDE, NOMBRE
            FROM SEDE
            WHERE ID_EMPRESA = :empresaId AND ESTADO = 'A'
            ORDER BY NOMBRE
        ");
        $stmt->bindParam(':empresaId', $empresaId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo sedes: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene todos los establecimientos de una sede
 */
function getEstablecimientosBySede($sedeId) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT ID_ESTABLECIMIENTO, NOMBRE
            FROM ESTABLECIMIENTO
            WHERE ID_SEDE = :sedeId AND ESTADO = 'A'
            ORDER BY NOMBRE
        ");
        $stmt->bindParam(':sedeId', $sedeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo establecimientos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene estadísticas de asistencias para un establecimiento
 */
function getEstadisticasAsistencia($establecimientoId, $fechaInicio = null, $fechaFin = null) {
    // Si no se especifican fechas, usar último mes
    if (!$fechaInicio) {
        $fechaInicio = date('Y-m-d', strtotime('-1 month'));
    }
    if (!$fechaFin) {
        $fechaFin = date('Y-m-d');
    }
    
    try {
        $conn = getConnection();
        
        // Estadísticas básicas
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
                SUM(CASE WHEN a.TARDANZA = 'N' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as llegadas_tiempo,
                SUM(CASE WHEN a.TARDANZA = 'S' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as llegadas_tarde,
                COUNT(DISTINCT CONCAT(e.ID_EMPLEADO, a.FECHA)) as dias_asistencia
            FROM EMPLEADO e
            LEFT JOIN ASISTENCIA a ON e.ID_EMPLEADO = a.ID_EMPLEADO AND a.FECHA BETWEEN :fechaInicio AND :fechaFin
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId AND e.ESTADO = 'A'
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
        $stmt->execute();
        $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular días laborables en el rango de fechas (aproximado)
        $start = new DateTime($fechaInicio);
        $end = new DateTime($fechaFin);
        $interval = $start->diff($end);
        $diasLaborables = min($interval->days + 1, 22); // Asumir máximo 22 días laborables/mes
        
        // Calcular faltas (aproximado)
        $totalPosiblesAsistencias = $estadisticas['total_empleados'] * $diasLaborables;
        $estadisticas['faltas'] = $totalPosiblesAsistencias - $estadisticas['dias_asistencia'];
        if ($estadisticas['faltas'] < 0) $estadisticas['faltas'] = 0;
        
        // Obtener horas trabajadas
        $stmt = $conn->prepare("
            SELECT 
                SUM(
                    TIMESTAMPDIFF(
                        MINUTE,
                        CONCAT(a_entrada.FECHA, ' ', a_entrada.HORA),
                        CONCAT(a_salida.FECHA, ' ', a_salida.HORA)
                    )
                ) / 60 as horas_trabajadas
            FROM ASISTENCIA a_entrada
            INNER JOIN ASISTENCIA a_salida ON 
                a_entrada.ID_EMPLEADO = a_salida.ID_EMPLEADO AND 
                a_entrada.FECHA = a_salida.FECHA AND
                a_entrada.TIPO = 'ENTRADA' AND
                a_salida.TIPO = 'SALIDA'
            INNER JOIN EMPLEADO e ON a_entrada.ID_EMPLEADO = e.ID_EMPLEADO
            WHERE 
                e.ID_ESTABLECIMIENTO = :establecimientoId AND
                a_entrada.FECHA BETWEEN :fechaInicio AND :fechaFin
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
        $stmt->execute();
        $horasTrabajadas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $estadisticas['horas_trabajadas'] = round($horasTrabajadas['horas_trabajadas'] ?? 0, 1);
        
        return $estadisticas;
    } catch (PDOException $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
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
 * Obtiene datos para gráfico de asistencias por hora
 */
function getAsistenciasPorHora($establecimientoId, $fechaInicio = null, $fechaFin = null) {
    if (!$fechaInicio) {
        $fechaInicio = date('Y-m-d', strtotime('-1 month'));
    }
    if (!$fechaFin) {
        $fechaFin = date('Y-m-d');
    }
    
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT 
                SUBSTRING(a.HORA, 1, 2) as hora_entrada,
                COUNT(*) as cantidad
            FROM ASISTENCIA a
            INNER JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            WHERE 
                e.ID_ESTABLECIMIENTO = :establecimientoId AND
                a.FECHA BETWEEN :fechaInicio AND :fechaFin AND
                a.TIPO = 'ENTRADA'
            GROUP BY SUBSTRING(a.HORA, 1, 2)
            ORDER BY hora_entrada
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
        $stmt->execute();
        
        // Crear array con todas las horas del día (6-22)
        $horas = [];
        $cantidades = [];
        
        for ($i = 6; $i <= 22; $i++) {
            $horas[$i] = 0;
        }
        
        // Llenar con datos reales
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hora = (int)$row['hora_entrada'];
            if (isset($horas[$hora])) {
                $horas[$hora] = (int)$row['cantidad'];
            }
        }
        
        // Formatear para ApexCharts
        $labels = [];
        $values = [];
        
        foreach ($horas as $hora => $cantidad) {
            $labels[] = $hora . ':00';
            $values[] = $cantidad;
        }
        
        return [
            'categories' => $labels,
            'data' => $values
        ];
    } catch (PDOException $e) {
        error_log("Error obteniendo asistencias por hora: " . $e->getMessage());
        return [
            'categories' => [],
            'data' => []
        ];
    }
}

/**
 * Obtiene datos para gráfico de distribución de asistencias
 */
function getDistribucionAsistencias($establecimientoId, $fechaInicio = null, $fechaFin = null) {
    if (!$fechaInicio) {
        $fechaInicio = date('Y-m-d', strtotime('-1 month'));
    }
    if (!$fechaFin) {
        $fechaFin = date('Y-m-d');
    }
    
    try {
        $conn = getConnection();
        
        // Obtener a tiempo, tardanzas y total posible
        $stmt = $conn->prepare("
            SELECT 
                COUNT(DISTINCT e.ID_EMPLEADO) as total_empleados,
                SUM(CASE WHEN a.TARDANZA = 'N' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as a_tiempo,
                SUM(CASE WHEN a.TARDANZA = 'S' AND a.TIPO = 'ENTRADA' THEN 1 ELSE 0 END) as tardanzas
            FROM EMPLEADO e
            LEFT JOIN ASISTENCIA a ON 
                e.ID_EMPLEADO = a.ID_EMPLEADO AND 
                a.FECHA BETWEEN :fechaInicio AND :fechaFin
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId AND e.ESTADO = 'A'
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':fechaInicio', $fechaInicio, PDO::PARAM_STR);
        $stmt->bindParam(':fechaFin', $fechaFin, PDO::PARAM_STR);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular días laborables en el rango de fechas (aproximado)
        $start = new DateTime($fechaInicio);
        $end = new DateTime($fechaFin);
        $interval = $start->diff($end);
        $diasLaborables = min($interval->days + 1, 22); // Asumir máximo 22 días laborables/mes
        
        // Calcular total posible de asistencias
        $totalPosible = $resultado['total_empleados'] * $diasLaborables;
        $faltas = $totalPosible - ($resultado['a_tiempo'] + $resultado['tardanzas']);
        if ($faltas < 0) $faltas = 0;
        
        return [
            'series' => [
                (int)$resultado['a_tiempo'],
                (int)$resultado['tardanzas'],
                (int)$faltas
            ]
        ];
    } catch (PDOException $e) {
        error_log("Error obteniendo distribución de asistencias: " . $e->getMessage());
        return [
            'series' => [0, 0, 0]
        ];
    }
}

/**
 * Obtiene actividad reciente (últimas asistencias)
 */
function getActividadReciente($establecimientoId, $limite = 5) {
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("
            SELECT 
                a.ID_ASISTENCIA,
                a.FECHA,
                a.HORA,
                a.TIPO,
                a.TARDANZA,
                e.ID_EMPLEADO,
                e.NOMBRE,
                e.APELLIDO,
                s.NOMBRE as SEDE_NOMBRE,
                est.NOMBRE as ESTABLECIMIENTO_NOMBRE
            FROM ASISTENCIA a
            INNER JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            INNER JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            INNER JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            WHERE e.ID_ESTABLECIMIENTO = :establecimientoId
            ORDER BY a.FECHA DESC, a.HORA DESC
            LIMIT :limite
        ");
        
        $stmt->bindParam(':establecimientoId', $establecimientoId, PDO::PARAM_INT);
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error obteniendo actividad reciente: " . $e->getMessage());
        return [];
    }
}

// Obtener información de la empresa del usuario actual
$empresaInfo = getEmpresaInfo($user['id']);

// Obtener todas las sedes de la empresa
$sedes = [];
if ($empresaInfo) {
    $sedes = getSedesByEmpresa($empresaInfo['ID_EMPRESA']);
}

// Por defecto, seleccionar la primera sede si hay disponibles
$sedeSeleccionada = !empty($sedes) ? $sedes[0]['ID_SEDE'] : null;

// Obtener establecimientos de la primera sede
$establecimientos = [];
if ($sedeSeleccionada) {
    $establecimientos = getEstablecimientosBySede($sedeSeleccionada);
}

// Por defecto, seleccionar el primer establecimiento si hay disponibles
$establecimientoSeleccionado = !empty($establecimientos) ? $establecimientos[0]['ID_ESTABLECIMIENTO'] : null;

// Obtener estadísticas iniciales si hay un establecimiento seleccionado
$estadisticas = null;
$asistenciasPorHora = null;
$distribucionAsistencias = null;
$actividadReciente = null;

if ($establecimientoSeleccionado) {
    $estadisticas = getEstadisticasAsistencia($establecimientoSeleccionado);
    $asistenciasPorHora = getAsistenciasPorHora($establecimientoSeleccionado);
    $distribucionAsistencias = getDistribucionAsistencias($establecimientoSeleccionado);
    $actividadReciente = getActividadReciente($establecimientoSeleccionado);
}
?>