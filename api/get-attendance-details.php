<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Configurar zona horaria para Colombia
date_default_timezone_set('America/Bogota');

require_once '../config/database.php';

// Iniciar sesión
session_start();
$empresaId = $_SESSION['id_empresa'] ?? 1; // Valor por defecto 1 si no hay sesión

try {
    // Obtener los parámetros de la solicitud
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
    $establecimientoId = !empty($_GET['establecimiento_id']) ? intval($_GET['establecimiento_id']) : null;
    $sedeId = !empty($_GET['sede_id']) ? intval($_GET['sede_id']) : null;
    
    // Fecha - usar la fecha enviada o la fecha actual en Colombia
    $fecha = isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha']) 
        ? $_GET['fecha'] 
        : date('Y-m-d'); // Ya usa la zona horaria de Colombia gracias al date_default_timezone_set
    
    // Registrar para depuración
    error_log("API get-attendance-details.php - Parámetros recibidos: tipo=$tipo, fecha=$fecha, empresa=$empresaId");
    
    // Validar tipo
    $tiposValidos = ['temprano', 'aTiempo', 'tarde', 'faltas'];
    if (!in_array($tipo, $tiposValidos)) {
        throw new Exception('Tipo de asistencia inválido');
    }
    
    // Construir condición WHERE según filtros
    $whereConditions = [];
    $params = [':fecha' => $fecha];
    
    if ($establecimientoId) {
        $whereConditions[] = "E.ID_ESTABLECIMIENTO = :establecimiento_id";
        $params[':establecimiento_id'] = $establecimientoId;
    } elseif ($sedeId) {
        $whereConditions[] = "EST.ID_SEDE = :sede_id";
        $params[':sede_id'] = $sedeId;
    } else {
        $whereConditions[] = "S.ID_EMPRESA = :empresa_id";
        $params[':empresa_id'] = $empresaId;
    }
    
    // Construir la cláusula WHERE
    $whereClause = implode(" AND ", $whereConditions);
    
    // Obtener el nombre de la ubicación (empresa, sede o establecimiento)
    $locationName = "Todas las ubicaciones";
    if ($establecimientoId) {
        $queryLoc = "SELECT NOMBRE FROM ESTABLECIMIENTO WHERE ID_ESTABLECIMIENTO = :id";
        $stmtLoc = $conn->prepare($queryLoc);
        $stmtLoc->bindValue(':id', $establecimientoId);
        $stmtLoc->execute();
        $locationName = $stmtLoc->fetchColumn() ?: $locationName;
    } elseif ($sedeId) {
        $queryLoc = "SELECT NOMBRE FROM SEDE WHERE ID_SEDE = :id";
        $stmtLoc = $conn->prepare($queryLoc);
        $stmtLoc->bindValue(':id', $sedeId);
        $stmtLoc->execute();
        $locationName = $stmtLoc->fetchColumn() ?: $locationName;
    } else {
        $queryLoc = "SELECT NOMBRE FROM EMPRESA WHERE ID_EMPRESA = :id";
        $stmtLoc = $conn->prepare($queryLoc);
        $stmtLoc->bindValue(':id', $empresaId);
        $stmtLoc->execute();
        $locationName = $stmtLoc->fetchColumn() ?: $locationName;
    }
    
    // Dependiendo del tipo, construir la consulta apropiada
    if ($tipo === 'faltas') {
        // Consulta para faltas: Empleados activos que no tienen registro de entrada para la fecha
        $query = "
            SELECT 
                E.ID_EMPLEADO AS CODIGO,
                E.NOMBRE,
                E.APELLIDO,
                S.NOMBRE AS SEDE,
                EST.NOMBRE AS ESTABLECIMIENTO,
                H.NOMBRE AS HORARIO_NOMBRE,
                H.HORA_ENTRADA
            FROM EMPLEADO E
            INNER JOIN ESTABLECIMIENTO EST ON E.ID_ESTABLECIMIENTO = EST.ID_ESTABLECIMIENTO
            INNER JOIN SEDE S ON EST.ID_SEDE = S.ID_SEDE
            LEFT JOIN EMPLEADO_HORARIO EH ON E.ID_EMPLEADO = EH.ID_EMPLEADO AND :fecha BETWEEN EH.FECHA_DESDE AND IFNULL(EH.FECHA_HASTA, '9999-12-31')
            LEFT JOIN HORARIO H ON EH.ID_HORARIO = H.ID_HORARIO
            LEFT JOIN ASISTENCIA A ON E.ID_EMPLEADO = A.ID_EMPLEADO AND A.FECHA = :fecha AND A.TIPO = 'ENTRADA'
            WHERE E.ACTIVO = 'S'
            AND $whereClause
            AND A.ID_ASISTENCIA IS NULL  -- No registró asistencia
            ORDER BY E.NOMBRE, E.APELLIDO
        ";
    } else {
        // Para los otros tipos (temprano, a tiempo, tarde) - basado únicamente en registros ENTRADA
        // Usar ID_HORARIO de la tabla ASISTENCIA en lugar del horario del empleado
        $tipoCondition = '';
        
        if ($tipo === 'temprano') {
            // Llegó temprano: antes de la hora de entrada
            $tipoCondition = "AND CAST(A.HORA AS TIME) < CAST(H.HORA_ENTRADA AS TIME)";
        } else if ($tipo === 'aTiempo') {
            // Llegó a tiempo: en el horario programado (con tolerancia)
            $tipoCondition = "AND CAST(A.HORA AS TIME) >= CAST(H.HORA_ENTRADA AS TIME) 
                              AND CAST(A.HORA AS TIME) <= ADDTIME(CAST(H.HORA_ENTRADA AS TIME), SEC_TO_TIME(IFNULL(H.TOLERANCIA, 0) * 60))";
        } else if ($tipo === 'tarde') {
            // Llegó tarde: después de la hora de entrada más tolerancia
            $tipoCondition = "AND CAST(A.HORA AS TIME) > ADDTIME(CAST(H.HORA_ENTRADA AS TIME), SEC_TO_TIME(IFNULL(H.TOLERANCIA, 0) * 60))";
        }
        
        $query = "
            SELECT 
                E.ID_EMPLEADO AS CODIGO,
                E.NOMBRE,
                E.APELLIDO,
                S.NOMBRE AS SEDE,
                EST.NOMBRE AS ESTABLECIMIENTO,
                A.HORA AS ENTRADA_HORA,
                H.HORA_ENTRADA AS HORARIO_ENTRADA,
                H.TOLERANCIA,
                TIME_TO_SEC(TIMEDIFF(H.HORA_ENTRADA, A.HORA))/60 AS MINUTOS_DIFERENCIA
            FROM ASISTENCIA A
            INNER JOIN EMPLEADO E ON A.ID_EMPLEADO = E.ID_EMPLEADO
            INNER JOIN ESTABLECIMIENTO EST ON E.ID_ESTABLECIMIENTO = EST.ID_ESTABLECIMIENTO
            INNER JOIN SEDE S ON EST.ID_SEDE = S.ID_SEDE
            LEFT JOIN HORARIO H ON A.ID_HORARIO = H.ID_HORARIO
            WHERE A.FECHA = :fecha
            AND A.TIPO = 'ENTRADA'
            AND $whereClause
            $tipoCondition
            ORDER BY E.NOMBRE, E.APELLIDO
        ";
    }
    
    // Registrar la consulta para depuración
    error_log("Query: $query");
    
    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
        error_log("Param $key: $val");
    }
    $stmt->execute();
    
    // Obtener resultados
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Registrar resultados para depuración
    error_log("Resultados encontrados: " . count($data));
    
    // Devolver resultados
    echo json_encode([
        'success' => true,
        'data' => $data,
        'tipo' => $tipo,
        'fecha' => $fecha,
        'locationName' => $locationName
    ]);
    
} catch (Exception $e) {
    // Registrar el error
    error_log("Error en get-attendance-details.php: " . $e->getMessage());
    
    // Devolver respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>