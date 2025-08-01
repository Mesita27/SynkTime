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

    // Read JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
        exit;
    }

    $employeeId = $input['employee_id'] ?? null;
    $type = $input['type'] ?? null; // 'facial' or 'fingerprint'
    $data = $input['data'] ?? null;

    if (!$employeeId || !$type || !$data) {
        echo json_encode(['success' => false, 'message' => 'Faltan parámetros requeridos']);
        exit;
    }

    // Verify employee belongs to the company
    $checkSql = "
        SELECT e.ID_EMPLEADO 
        FROM EMPLEADO e
        JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :employee_id AND s.ID_EMPRESA = :empresa_id
    ";
    
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindValue(':employee_id', $employeeId);
    $checkStmt->bindValue(':empresa_id', $empresaId);
    $checkStmt->execute();
    
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }

    // Prepare update based on type
    if ($type === 'facial') {
        $sql = "
            UPDATE EMPLEADO 
            SET FACIAL_RECOGNITION_ENABLED = 'Y',
                FACIAL_DATA = :data,
                BIOMETRIC_UPDATED_AT = NOW(),
                BIOMETRIC_UPDATED_BY = :updated_by
            WHERE ID_EMPLEADO = :employee_id
        ";
    } elseif ($type === 'fingerprint') {
        $sql = "
            UPDATE EMPLEADO 
            SET FINGERPRINT_ENABLED = 'Y',
                FINGERPRINT_DATA = :data,
                BIOMETRIC_UPDATED_AT = NOW(),
                BIOMETRIC_UPDATED_BY = :updated_by
            WHERE ID_EMPLEADO = :employee_id
        ";
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo de biométrico no válido']);
        exit;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':data', $data);
    $stmt->bindValue(':employee_id', $employeeId);
    $stmt->bindValue(':updated_by', $_SESSION['user_id'] ?? null);
    
    if ($stmt->execute()) {
        // Log the biometric enrollment action
        $logSql = "
            INSERT INTO BIOMETRIC_LOG (ID_EMPLEADO, TYPE, ACTION, CREATED_AT, CREATED_BY)
            VALUES (:employee_id, :type, 'ENROLLED', NOW(), :created_by)
        ";
        
        try {
            $logStmt = $conn->prepare($logSql);
            $logStmt->bindValue(':employee_id', $employeeId);
            $logStmt->bindValue(':type', strtoupper($type));
            $logStmt->bindValue(':created_by', $_SESSION['user_id'] ?? null);
            $logStmt->execute();
        } catch (Exception $e) {
            // Log creation failed, but biometric save succeeded
            error_log("Failed to create biometric log: " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Datos biométricos guardados exitosamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar datos biométricos'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>