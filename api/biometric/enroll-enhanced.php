<?php
/**
 * API endpoint for biometric enrollment using EMPLEADO_BIOMETRICO table
 * Enhanced version supporting multiple biometric types and providers
 */

require_once '../../config/database.php';
require_once '../../auth/session.php';

// Ensure user is authenticated
requireAuth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $id_empleado = $_POST['id_empleado'] ?? null;
    $tipo_biometrico = $_POST['tipo_biometrico'] ?? null;
    $subtipo = $_POST['subtipo'] ?? null;
    $datos_biometricos = $_POST['datos_biometricos'] ?? null;
    $proveedor = $_POST['proveedor_biometrico'] ?? 'SYNKTIME_INTERNO';
    $calidad_muestra = $_POST['calidad_muestra'] ?? 0;

    if (!$id_empleado || !$tipo_biometrico || !$datos_biometricos) {
        throw new Exception('Datos incompletos');
    }

    // Validate employee exists and is active
    $stmt = $conn->prepare("
        SELECT ID_EMPLEADO, NOMBRE, APELLIDO, CODIGO
        FROM empleados 
        WHERE ID_EMPLEADO = ? AND ACTIVO = 1
    ");
    $stmt->execute([$id_empleado]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        throw new Exception('Empleado no encontrado o inactivo');
    }

    // Validate biometric type
    $tipos_validos = ['HUELLA_DIGITAL', 'FACIAL', 'IRIS', 'VOZ'];
    if (!in_array($tipo_biometrico, $tipos_validos)) {
        throw new Exception('Tipo biométrico inválido');
    }

    // Validate subtypes for fingerprints
    if ($tipo_biometrico === 'HUELLA_DIGITAL') {
        $subtipos_validos = [
            'PULGAR_IZQUIERDO', 'INDICE_IZQUIERDO', 'MEDIO_IZQUIERDO', 'ANULAR_IZQUIERDO', 'MENIQUE_IZQUIERDO',
            'PULGAR_DERECHO', 'INDICE_DERECHO', 'MEDIO_DERECHO', 'ANULAR_DERECHO', 'MENIQUE_DERECHO'
        ];
        
        if (!$subtipo || !in_array($subtipo, $subtipos_validos)) {
            throw new Exception('Subtipo de huella inválido');
        }
    }

    // Generate template hash for quick searches
    $template_hash = hash('sha256', $datos_biometricos . $id_empleado . $tipo_biometrico);

    // Check if biometric already exists
    $stmt = $conn->prepare("
        SELECT ID_EMPLEADO_BIOMETRICO 
        FROM EMPLEADO_BIOMETRICO 
        WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = ? AND SUBTIPO = ? AND ACTIVO = 1
    ");
    $stmt->execute([$id_empleado, $tipo_biometrico, $subtipo]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update existing biometric data
        $stmt = $conn->prepare("
            UPDATE EMPLEADO_BIOMETRICO 
            SET DATOS_BIOMETRICOS = ?, 
                TEMPLATE_HASH = ?,
                PROVEEDOR_BIOMETRICO = ?,
                CALIDAD_MUESTRA = ?,
                FECHA_ACTUALIZACION = CURRENT_TIMESTAMP,
                USUARIO_REGISTRO = ?
            WHERE ID_EMPLEADO_BIOMETRICO = ?
        ");
        $stmt->execute([
            $datos_biometricos, 
            $template_hash,
            $proveedor,
            $calidad_muestra,
            $_SESSION['user_id'] ?? null,
            $existing['ID_EMPLEADO_BIOMETRICO']
        ]);
        $action = 'actualizado';
        $id_biometrico = $existing['ID_EMPLEADO_BIOMETRICO'];
    } else {
        // Insert new biometric data
        $stmt = $conn->prepare("
            INSERT INTO EMPLEADO_BIOMETRICO (
                ID_EMPLEADO, 
                TIPO_BIOMETRICO, 
                SUBTIPO,
                DATOS_BIOMETRICOS,
                TEMPLATE_HASH,
                PROVEEDOR_BIOMETRICO,
                CALIDAD_MUESTRA,
                USUARIO_REGISTRO
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id_empleado, 
            $tipo_biometrico, 
            $subtipo,
            $datos_biometricos,
            $template_hash,
            $proveedor,
            $calidad_muestra,
            $_SESSION['user_id'] ?? null
        ]);
        $action = 'registrado';
        $id_biometrico = $conn->lastInsertId();
    }

    // Log enrollment in verification table
    $stmt = $conn->prepare("
        INSERT INTO VERIFICACION_BIOMETRICA (
            ID_EMPLEADO,
            ID_EMPLEADO_BIOMETRICO,
            TIPO_VERIFICACION,
            RESULTADO_VERIFICACION,
            PUNTUACION_COINCIDENCIA,
            METADATA_VERIFICACION,
            IP_ORIGEN
        ) VALUES (?, ?, ?, 1, 100.0, ?, ?)
    ");
    
    $metadata = json_encode([
        'action' => 'enrollment',
        'proveedor' => $proveedor,
        'calidad' => $calidad_muestra,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $stmt->execute([
        $id_empleado,
        $id_biometrico,
        $tipo_biometrico,
        $metadata,
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]);

    echo json_encode([
        'success' => true,
        'message' => ucfirst(strtolower(str_replace('_', ' ', $tipo_biometrico))) . " {$action} correctamente",
        'empleado' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
        'codigo_empleado' => $empleado['CODIGO'],
        'tipo_biometrico' => $tipo_biometrico,
        'subtipo' => $subtipo,
        'action' => $action,
        'id_biometrico' => $id_biometrico
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>