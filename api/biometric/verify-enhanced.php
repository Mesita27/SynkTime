<?php
/**
 * Enhanced biometric verification API
 * Supports facial and fingerprint verification with photo storage
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
    $tipo_verificacion = $_POST['tipo_verificacion'] ?? null;
    $datos_verificacion = $_POST['datos_verificacion'] ?? null;
    $foto_verificacion = $_POST['foto_verificacion'] ?? null; // Base64 image data

    if (!$id_empleado || !$tipo_verificacion) {
        throw new Exception('Datos incompletos');
    }

    // Get employee information
    $stmt = $conn->prepare("
        SELECT e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.CODIGO, e.ID_ESTABLECIMIENTO, 
               est.NOMBRE as ESTABLECIMIENTO, est.ID_SEDE,
               s.NOMBRE as SEDE
        FROM empleados e
        LEFT JOIN establecimientos est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN sedes s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = ? AND e.ACTIVO = 1
    ");
    $stmt->execute([$id_empleado]);
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        throw new Exception('Empleado no encontrado o inactivo');
    }

    // Initialize verification results
    $resultado_verificacion = 0;
    $puntuacion_coincidencia = 0.0;
    $tiempo_verificacion = 0;
    $foto_nombre = null;
    $id_empleado_biometrico = null;

    $inicio_verificacion = microtime(true);

    // Process verification based on type
    switch ($tipo_verificacion) {
        case 'FACIAL':
            $resultado = verificarFacial($conn, $id_empleado, $datos_verificacion);
            $resultado_verificacion = $resultado['success'] ? 1 : 0;
            $puntuacion_coincidencia = $resultado['confidence'];
            $id_empleado_biometrico = $resultado['id_biometrico'] ?? null;
            
            // Save verification photo for facial recognition
            if ($foto_verificacion) {
                $foto_nombre = guardarFotoVerificacion($foto_verificacion, $id_empleado, 'FACIAL');
            }
            break;

        case 'HUELLA_DIGITAL':
            $resultado = verificarHuella($conn, $id_empleado, $datos_verificacion);
            $resultado_verificacion = $resultado['success'] ? 1 : 0;
            $puntuacion_coincidencia = $resultado['confidence'];
            $id_empleado_biometrico = $resultado['id_biometrico'] ?? null;
            break;

        case 'TRADICIONAL':
            $resultado_verificacion = 1; // Always successful for traditional
            $puntuacion_coincidencia = 100.0;
            
            // Save photo for traditional verification
            if ($foto_verificacion) {
                $foto_nombre = guardarFotoVerificacion($foto_verificacion, $id_empleado, 'TRADICIONAL');
            }
            break;

        default:
            throw new Exception('Tipo de verificación no válido');
    }

    $tiempo_verificacion = round((microtime(true) - $inicio_verificacion) * 1000); // ms

    // Create verification log
    $stmt = $conn->prepare("
        INSERT INTO VERIFICACION_BIOMETRICA (
            ID_EMPLEADO,
            ID_EMPLEADO_BIOMETRICO,
            TIPO_VERIFICACION,
            RESULTADO_VERIFICACION,
            PUNTUACION_COINCIDENCIA,
            TIEMPO_VERIFICACION,
            FOTO_VERIFICACION,
            METADATA_VERIFICACION,
            IP_ORIGEN,
            USER_AGENT
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $metadata = json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'usuario' => $_SESSION['username'] ?? 'unknown',
        'datos_recibidos' => !empty($datos_verificacion),
        'foto_guardada' => !empty($foto_nombre)
    ]);
    
    $stmt->execute([
        $id_empleado,
        $id_empleado_biometrico,
        $tipo_verificacion,
        $resultado_verificacion,
        $puntuacion_coincidencia,
        $tiempo_verificacion,
        $foto_nombre,
        $metadata,
        $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);

    $id_verificacion = $conn->lastInsertId();

    // Return verification result
    echo json_encode([
        'success' => $resultado_verificacion == 1,
        'message' => $resultado_verificacion == 1 ? 'Verificación exitosa' : 'Verificación fallida',
        'empleado' => $empleado['NOMBRE'] . ' ' . $empleado['APELLIDO'],
        'codigo_empleado' => $empleado['CODIGO'],
        'tipo_verificacion' => $tipo_verificacion,
        'puntuacion_coincidencia' => $puntuacion_coincidencia,
        'tiempo_verificacion' => $tiempo_verificacion,
        'foto_verificacion' => $foto_nombre,
        'id_verificacion' => $id_verificacion,
        'establecimiento' => $empleado['ESTABLECIMIENTO'],
        'sede' => $empleado['SEDE']
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

/**
 * Verify facial biometric data
 */
function verificarFacial($conn, $id_empleado, $datos_verificacion) {
    try {
        // Get enrolled facial data for employee
        $stmt = $conn->prepare("
            SELECT ID_EMPLEADO_BIOMETRICO, DATOS_BIOMETRICOS, CALIDAD_MUESTRA
            FROM EMPLEADO_BIOMETRICO 
            WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = 'FACIAL' AND ACTIVO = 1
            ORDER BY CALIDAD_MUESTRA DESC
            LIMIT 1
        ");
        $stmt->execute([$id_empleado]);
        $biometric_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$biometric_data) {
            return ['success' => false, 'confidence' => 0.0, 'message' => 'No hay datos faciales registrados'];
        }

        // Simulate facial recognition comparison
        // In real implementation, use actual facial recognition algorithms
        $confidence = simularComparacionFacial($datos_verificacion, $biometric_data['DATOS_BIOMETRICOS']);
        
        return [
            'success' => $confidence >= 75.0, // 75% minimum confidence
            'confidence' => $confidence,
            'id_biometrico' => $biometric_data['ID_EMPLEADO_BIOMETRICO']
        ];

    } catch (Exception $e) {
        return ['success' => false, 'confidence' => 0.0, 'message' => $e->getMessage()];
    }
}

/**
 * Verify fingerprint biometric data
 */
function verificarHuella($conn, $id_empleado, $datos_verificacion) {
    try {
        // Get enrolled fingerprint data for employee
        $stmt = $conn->prepare("
            SELECT ID_EMPLEADO_BIOMETRICO, DATOS_BIOMETRICOS, SUBTIPO, CALIDAD_MUESTRA
            FROM EMPLEADO_BIOMETRICO 
            WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = 'HUELLA_DIGITAL' AND ACTIVO = 1
            ORDER BY CALIDAD_MUESTRA DESC
        ");
        $stmt->execute([$id_empleado]);
        $biometric_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($biometric_data)) {
            return ['success' => false, 'confidence' => 0.0, 'message' => 'No hay huellas registradas'];
        }

        $best_match = null;
        $best_confidence = 0.0;

        // Compare against all enrolled fingerprints
        foreach ($biometric_data as $finger) {
            $confidence = simularComparacionHuella($datos_verificacion, $finger['DATOS_BIOMETRICOS']);
            
            if ($confidence > $best_confidence) {
                $best_confidence = $confidence;
                $best_match = $finger;
            }
        }

        return [
            'success' => $best_confidence >= 80.0, // 80% minimum confidence for fingerprints
            'confidence' => $best_confidence,
            'id_biometrico' => $best_match['ID_EMPLEADO_BIOMETRICO'] ?? null
        ];

    } catch (Exception $e) {
        return ['success' => false, 'confidence' => 0.0, 'message' => $e->getMessage()];
    }
}

/**
 * Simulate facial recognition comparison
 */
function simularComparacionFacial($datos_nuevos, $datos_guardados) {
    // In real implementation, use actual facial recognition algorithms
    // For demo purposes, return a random confidence between 70-95%
    return rand(70, 95) + (rand(0, 99) / 100);
}

/**
 * Simulate fingerprint comparison
 */
function simularComparacionHuella($datos_nuevos, $datos_guardados) {
    // In real implementation, use actual fingerprint matching algorithms
    // For demo purposes, return a random confidence between 75-98%
    return rand(75, 98) + (rand(0, 99) / 100);
}

/**
 * Save verification photo
 */
function guardarFotoVerificacion($foto_base64, $id_empleado, $tipo) {
    $upload_dir = '../../uploads/verificacion/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Remove data URL prefix
    $foto_base64 = preg_replace('/^data:image\/(jpeg|jpg|png);base64,/', '', $foto_base64);
    $foto_base64 = str_replace(' ', '+', $foto_base64);
    $image_binary = base64_decode($foto_base64);
    
    if ($image_binary === false) {
        throw new Exception('Datos de foto inválidos');
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "verificacion_{$tipo}_{$id_empleado}_{$timestamp}.jpg";
    $filepath = $upload_dir . $filename;
    
    if (file_put_contents($filepath, $image_binary) === false) {
        throw new Exception('Error al guardar la foto de verificación');
    }
    
    return $filename;
}
?>