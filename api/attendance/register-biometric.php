<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Verificar que existe el directorio uploads
$uploads_dir = __DIR__ . '/../../uploads/';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Recoge los datos POST
$id_empleado = $_POST['id_empleado'] ?? null;
$biometric_type = $_POST['biometric_type'] ?? null;
$verification_data = $_POST['verification_data'] ?? null;
$fecha = date('Y-m-d'); // Fecha actual

// Validación de datos obligatorios
if (!$id_empleado || !$biometric_type || !$verification_data) {
    echo json_encode(['success' => false, 'message' => 'Datos de verificación biométrica requeridos']);
    exit;
}

try {
    // Parse verification data
    $verificationInfo = json_decode($verification_data, true);
    if (!$verificationInfo || !$verificationInfo['success']) {
        throw new Exception('Verificación biométrica inválida o fallida');
    }
    
    // Procesar la imagen si existe (especialmente para reconocimiento facial)
    $foto_base64 = $_POST['image_data'] ?? null;
    $filename = null;
    
    if ($foto_base64) {
        // Limpiar el base64
        $foto_base64_clean = preg_replace('#^data:image/\w+;base64,#i', '', $foto_base64);
        $img_data = base64_decode($foto_base64_clean);
        
        if ($img_data === false) {
            echo json_encode(['success' => false, 'message' => 'Formato de imagen inválido.']);
            exit;
        }
        
        // Generar nombre de archivo único con indicador biométrico
        $biometric_prefix = ($biometric_type === 'FINGERPRINT') ? 'fp_' : 'face_';
        $filename = uniqid($biometric_prefix . 'att_') . '_' . date('Ymd_His') . '.jpg';
        $save_path = $uploads_dir . $filename;
        
        if (file_put_contents($save_path, $img_data) === false) {
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar la foto. Verificar permisos.']);
            exit;
        }
    } elseif ($biometric_type === 'FINGERPRINT') {
        // Para huella digital, usar imagen placeholder
        $filename = 'fingerprint_placeholder.jpg';
        $placeholder_path = $uploads_dir . $filename;
        
        // Crear imagen placeholder si no existe
        if (!file_exists($placeholder_path)) {
            createFingerprintPlaceholder($placeholder_path);
        }
    }
    
    // Obtener día de la semana (1=Lunes ... 7=Domingo)
    $dia_semana = date('N', strtotime($fecha));
    $hora_actual = date('H:i:s');
    
    // Buscar todos los horarios asignados al empleado para este día
    $sqlHorarios = "
        SELECT H.ID_HORARIO, H.NOMBRE AS NOMBRE_HORARIO, H.HORA_ENTRADA, H.HORA_SALIDA, H.TOLERANCIA
        FROM EMPLEADO_HORARIO EH
        JOIN HORARIO H ON H.ID_HORARIO = EH.ID_HORARIO
        JOIN HORARIO_DIA HD ON HD.ID_HORARIO = H.ID_HORARIO
        WHERE EH.ID_EMPLEADO = ?
          AND HD.ID_DIA = ?
          AND EH.FECHA_DESDE <= ?
          AND (EH.FECHA_HASTA IS NULL OR EH.FECHA_HASTA >= ?)
        ORDER BY H.HORA_ENTRADA
    ";
    $stmt = $conn->prepare($sqlHorarios);
    $stmt->execute([$id_empleado, $dia_semana, $fecha, $fecha]);
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($horarios)) {
        echo json_encode(['success' => false, 'message' => 'No se encontraron horarios asignados para el empleado en este día.']);
        exit;
    }
    
    // Verificar registros existentes para cada horario
    $horariosDisponibles = [];
    foreach ($horarios as $horario) {
        // Verificar registros existentes para este horario específico
        $sqlVerificar = "
            SELECT TIPO FROM ASISTENCIA 
            WHERE ID_EMPLEADO = ? 
            AND FECHA = ? 
            AND ID_HORARIO = ?
            ORDER BY HORA DESC
            LIMIT 1
        ";
        $stmt = $conn->prepare($sqlVerificar);
        $stmt->execute([$id_empleado, $fecha, $horario['ID_HORARIO']]);
        $ultimoRegistro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ultimoRegistro) {
            // No hay registros, se puede registrar entrada
            $horario['PROXIMO_REGISTRO'] = 'ENTRADA';
            $horariosDisponibles[] = $horario;
        } else if ($ultimoRegistro['TIPO'] === 'ENTRADA') {
            // Ya hay entrada, se puede registrar salida
            $horario['PROXIMO_REGISTRO'] = 'SALIDA';
            $horariosDisponibles[] = $horario;
        }
    }
    
    if (empty($horariosDisponibles)) {
        echo json_encode(['success' => false, 'message' => 'Ya se han registrado todas las entradas y salidas para los horarios de hoy.']);
        exit;
    }
    
    // Determinar cuál es el horario más cercano a la hora actual
    $horarioSeleccionado = null;
    $menorDiferencia = PHP_INT_MAX;
    $tipoRegistro = null;
    
    foreach ($horariosDisponibles as $horario) {
        $ts_actual = strtotime($fecha . ' ' . $hora_actual);
        
        if ($horario['PROXIMO_REGISTRO'] === 'ENTRADA') {
            $ts_horario = strtotime($fecha . ' ' . $horario['HORA_ENTRADA']);
            $diferencia = abs($ts_actual - $ts_horario);
            
            if ($diferencia < $menorDiferencia) {
                $menorDiferencia = $diferencia;
                $horarioSeleccionado = $horario;
                $tipoRegistro = 'ENTRADA';
            }
        } else { // SALIDA
            $ts_horario = strtotime($fecha . ' ' . $horario['HORA_SALIDA']);
            $diferencia = abs($ts_actual - $ts_horario);
            
            if ($diferencia < $menorDiferencia) {
                $menorDiferencia = $diferencia;
                $horarioSeleccionado = $horario;
                $tipoRegistro = 'SALIDA';
            }
        }
    }
    
    // Calcular si hay tardanza según el tipo de registro
    $tardanza = "N";
    if ($tipoRegistro === 'ENTRADA') {
        $ts_entrada = strtotime($fecha . ' ' . $horarioSeleccionado['HORA_ENTRADA']);
        $ts_actual = strtotime($fecha . ' ' . $hora_actual);
        $tolerancia = (int)($horarioSeleccionado['TOLERANCIA'] ?? 0);
        
        if ($ts_actual < $ts_entrada) {
            $tardanza = "N"; // Temprano (no es tardanza)
        } elseif ($ts_actual <= $ts_entrada + $tolerancia * 60) {
            $tardanza = "N"; // Puntual (dentro de la tolerancia)
        } else {
            $tardanza = "S"; // Tardanza
        }
    } else if ($tipoRegistro === 'SALIDA') {
        $ts_salida = strtotime($fecha . ' ' . $horarioSeleccionado['HORA_SALIDA']);
        $ts_actual = strtotime($fecha . ' ' . $hora_actual);
        $tolerancia = (int)($horarioSeleccionado['TOLERANCIA'] ?? 0);
        
        if ($ts_actual < $ts_salida - $tolerancia * 60) {
            $tardanza = "S"; // Salida muy temprana
        } else {
            $tardanza = "N"; // Salida a tiempo o tarde
        }
    }
    
    // Preparar observación con información biométrica
    $biometric_method = ($biometric_type === 'FINGERPRINT') ? 'Huella Digital' : 'Reconocimiento Facial';
    $confidence = $verificationInfo['confidence'] ?? 0;
    $observacion = "Verificado con {$biometric_method} (Confianza: {$confidence}%)";
    
    // Registrar la asistencia
    $sql = "INSERT INTO ASISTENCIA 
        (ID_EMPLEADO, FECHA, TIPO, HORA, TARDANZA, OBSERVACION, FOTO, REGISTRO_MANUAL, ID_HORARIO)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'N', ?)";
    
    $stmt = $conn->prepare($sql);
    $ok = $stmt->execute([
        $id_empleado, 
        $fecha, 
        $tipoRegistro, 
        $hora_actual, 
        $tardanza, 
        $observacion,
        $filename,
        $horarioSeleccionado['ID_HORARIO']
    ]);
    
    if ($ok) {
        $attendance_id = $conn->lastInsertId();
        
        // Actualizar el log de verificación biométrica con el ID de asistencia
        $sqlUpdateLog = "UPDATE BIOMETRIC_VERIFICATION_LOG 
                         SET ID_ASISTENCIA = ? 
                         WHERE ID_EMPLEADO = ? AND TIPO_BIOMETRICO = ? AND RESULTADO = 'SUCCESS'
                         ORDER BY FECHA_VERIFICACION DESC LIMIT 1";
        $stmtLog = $conn->prepare($sqlUpdateLog);
        $stmtLog->execute([$attendance_id, $id_empleado, $biometric_type]);
        
        echo json_encode([
            'success' => true, 
            'message' => $tipoRegistro === 'ENTRADA' ? 
                "Entrada registrada correctamente con {$biometric_method}" : 
                "Salida registrada correctamente con {$biometric_method}",
            'tipo' => $tipoRegistro,
            'horario' => $horarioSeleccionado['NOMBRE_HORARIO'],
            'foto_guardada' => $filename,
            'tardanza' => $tardanza,
            'biometric_type' => $biometric_type,
            'confidence' => $confidence,
            'verification_method' => $biometric_method
        ]);
    } else {
        if ($filename && file_exists($uploads_dir . $filename) && $filename !== 'fingerprint_placeholder.jpg') {
            unlink($uploads_dir . $filename);
        }
        echo json_encode(['success' => false, 'message' => 'Error al registrar la asistencia.']);
    }
    
} catch (PDOException $e) {
    if (isset($filename) && file_exists($uploads_dir . $filename) && $filename !== 'fingerprint_placeholder.jpg') {
        unlink($uploads_dir . $filename);
    }
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    if (isset($filename) && file_exists($uploads_dir . $filename) && $filename !== 'fingerprint_placeholder.jpg') {
        unlink($uploads_dir . $filename);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function createFingerprintPlaceholder($filepath) {
    // Create a simple fingerprint placeholder image
    $width = 200;
    $height = 200;
    $image = imagecreate($width, $height);
    
    // Colors
    $background = imagecolorallocate($image, 240, 240, 240);
    $fingerprint_color = imagecolorallocate($image, 0, 123, 255);
    $text_color = imagecolorallocate($image, 102, 102, 102);
    
    // Fill background
    imagefill($image, 0, 0, $background);
    
    // Draw fingerprint icon (simplified)
    $center_x = $width / 2;
    $center_y = $height / 2;
    
    // Draw concentric arcs to represent fingerprint
    for ($i = 20; $i < 80; $i += 15) {
        imagearc($image, $center_x, $center_y, $i, $i, 0, 360, $fingerprint_color);
    }
    
    // Add text
    $text = "HUELLA DIGITAL";
    $font_size = 3;
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_x = ($width - $text_width) / 2;
    $text_y = $height - 30;
    imagestring($image, $font_size, $text_x, $text_y, $text, $text_color);
    
    // Save as JPEG
    imagejpeg($image, $filepath, 80);
    imagedestroy($image);
}
?>