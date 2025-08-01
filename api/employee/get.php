<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

header('Content-Type: application/json');

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Verificar que se proporcionó un ID de empleado
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado no proporcionado']);
        exit;
    }

    $id_empleado = intval($_GET['id']);

    // Consultar datos del empleado
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            e.CORREO,
            e.TELEFONO,
            e.FECHA_INGRESO,
            e.ESTADO,
            e.ACTIVO,
            e.FACIAL_RECOGNITION_ENABLED,
            e.FINGERPRINT_ENABLED,
            est.NOMBRE AS ESTABLECIMIENTO,
            est.ID_ESTABLECIMIENTO,
            s.NOMBRE AS SEDE,
            s.ID_SEDE
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :id_empleado
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id_empleado', $id_empleado);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empleado) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }

    // Consultar horarios asignados al empleado
    $sqlHorarios = "
        SELECT 
            eh.ID_HORARIO,
            h.NOMBRE,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA,
            eh.FECHA_DESDE,
            eh.FECHA_HASTA
        FROM EMPLEADO_HORARIO eh
        JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = :id_empleado
        ORDER BY eh.FECHA_DESDE DESC
    ";
    
    $stmtHorarios = $conn->prepare($sqlHorarios);
    $stmtHorarios->bindValue(':id_empleado', $id_empleado);
    $stmtHorarios->execute();
    
    $horarios = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear la respuesta
    $empleado['HORARIOS'] = $horarios;
    
    // Traducir estado según el valor almacenado
    switch ($empleado['ESTADO']) {
        case 'A':
            $empleado['ESTADO_TEXTO'] = 'Activo';
            break;
        case 'I':
            $empleado['ESTADO_TEXTO'] = 'Inactivo';
            break;
        default:
            $empleado['ESTADO_TEXTO'] = 'Desconocido';
    }
    
    echo json_encode([
        'success' => true,
        'data' => $empleado
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar datos del empleado: ' . $e->getMessage()
    ]);
}
?>