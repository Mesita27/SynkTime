<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['id_empresa'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$empresaId = $_SESSION['id_empresa'];
$userRole = $_SESSION['rol'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

try {
    $sedeId = $_GET['sede_id'] ?? null;
    $establecimientoId = $_GET['establecimiento_id'] ?? null;
    
    $where = ["e.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];
    
    // Aplicar filtro restrictivo solo para rol ASISTENCIA
    // GERENTE, ADMIN, DUEÑO tienen acceso total a todos los empleados de la empresa
    if ($userRole === 'ASISTENCIA') {
        // Solo para ASISTENCIA: restringir a empleados específicos
        $where[] = "est.ID_ESTABLECIMIENTO IN (
            SELECT DISTINCT e2.ID_ESTABLECIMIENTO 
            FROM EMPLEADO e2 
            JOIN ESTABLECIMIENTO est2 ON e2.ID_ESTABLECIMIENTO = est2.ID_ESTABLECIMIENTO 
            JOIN SEDE s2 ON est2.ID_SEDE = s2.ID_SEDE 
            WHERE s2.ID_EMPRESA = :empresa_id
        )";
    }
    // Para GERENTE, ADMIN, DUEÑO: sin restricciones adicionales (acceso total)
    
    // Filtrar por sede si se especifica
    if ($sedeId) {
        $where[] = "s.ID_SEDE = :sede_id";
        $params[':sede_id'] = $sedeId;
    }
    
    // Filtrar por establecimiento si se especifica
    if ($establecimientoId) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento_id";
        $params[':establecimiento_id'] = $establecimientoId;
    }
    
    $whereClause = implode(' AND ', $where);
    
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            est.NOMBRE AS ESTABLECIMIENTO_NOMBRE,
            s.NOMBRE AS SEDE_NOMBRE
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        JOIN EMPRESA emp ON s.ID_EMPRESA = emp.ID_EMPRESA
        WHERE {$whereClause}
        AND e.ESTADO = 'A'
        ORDER BY e.NOMBRE, e.APELLIDO
    ";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'empleados' => $empleados
    ]);
    
} catch (PDOException $e) {
    error_log("Error en get-empleados.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener empleados: ' . $e->getMessage()
    ]);
}
?>