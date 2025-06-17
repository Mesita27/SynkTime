<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Verificar que existe el directorio uploads
$uploads_dir = __DIR__ . '/../../uploads/';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0755, true);
}

// Recoge los datos POST
$id_empleado = $_POST['id_empleado'] ?? null;
$tipo = $_POST['tipo'] ?? null;
$fecha = $_POST['fecha'] ?? date('Y-m-d');
$foto_base64 = $_POST['foto_base64'] ?? null;

// Log para debug (opcional - puedes eliminar después)
error_log("Datos recibidos: ID=" . $id_empleado . ", TIPO=" . $tipo . ", FOTO=" . (empty($foto_base64) ? 'NO' : 'SÍ'));

// Validación de datos obligatorios
if (!$id_empleado || !$tipo) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// ========== REGISTRO DE ENTRADA ==========
if ($tipo === 'ENTRADA') {
    if (!$foto_base64) {
        echo json_encode(['success' => false, 'message' => 'Debe tomar una foto para la entrada.']);
        exit;
    }
    
    // Guardar la foto en disco
    $filename = 'entrada_' . $id_empleado . '_' . date('Ymd_His') . '.jpg';
    $save_path = $uploads_dir . $filename;
    
    // Limpiar el base64
    $foto_base64_clean = preg_replace('#^data:image/\w+;base64,#i', '', $foto_base64);
    $img_data = base64_decode($foto_base64_clean);
    
    if ($img_data === false) {
        echo json_encode(['success' => false, 'message' => 'Formato de imagen inválido.']);
        exit;
    }
    
    if (file_put_contents($save_path, $img_data) === false) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la foto. Verificar permisos.']);
        exit;
    }

    // Obtener día de la semana (1=Lunes ... 7=Domingo)
    $dia_semana = date('N', strtotime($fecha));

    // Buscar horario asignado, que esté vigente en esa fecha y cubra ese día de semana
    $sqlHorario = "
        SELECT h.HORA_ENTRADA
        FROM empleado_horario eh
        JOIN horario h ON h.ID_HORARIO = eh.ID_HORARIO
        JOIN horario_dia hd ON hd.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = ?
          AND hd.ID_DIA = ?
          AND eh.FECHA_DESDE <= ?
          AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= ?)
        LIMIT 1
    ";
    $stmt = $conn->prepare($sqlHorario);
    $stmt->execute([$id_empleado, $dia_semana, $fecha, $fecha]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'No se encontró un horario asignado para el empleado en ese día.']);
        exit;
    }

    $hora_entrada_horario = $row['HORA_ENTRADA']; // formato HH:MM

    // Hora real de llegada (servidor)
    $hora_llegada = date('H:i');

    // Calcular diferencia en minutos
    $ts_horario = strtotime($fecha . ' ' . $hora_entrada_horario);
    $ts_llegada = strtotime($fecha . ' ' . $hora_llegada);
    $diferencia_min = ($ts_llegada - $ts_horario) / 60;

    // Estado según criterios
    if ($diferencia_min < -10) {
        $tardanza = "Temprano";
    } elseif ($diferencia_min >= -10 && $diferencia_min <= 10) {
        $tardanza = "A tiempo";
    } else {
        $tardanza = "Tardía";
    }

    // Verificar el nombre exacto de la tabla y columnas
    $sql = "INSERT INTO asistencia 
        (ID_EMPLEADO, FECHA, TIPO, HORA, TARDANZA, OBSERVACION, FOTO, REGISTRO_MANUAL)
        VALUES (?, ?, 'ENTRADA', ?, ?, NULL, ?, 'N')";
    
    try {
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute([$id_empleado, $fecha, $hora_llegada, $tardanza, $filename]);
        
        if ($ok) {
            echo json_encode([
                'success' => true, 
                'message' => 'Entrada registrada correctamente',
                'foto_guardada' => $filename,
                'tardanza' => $tardanza
            ]);
        } else {
            // Si falla el insert, eliminar la foto
            if (file_exists($save_path)) {
                unlink($save_path);
            }
            echo json_encode(['success' => false, 'message' => 'No se pudo registrar la entrada en la base de datos.']);
        }
    } catch (PDOException $e) {
        // Si falla el insert, eliminar la foto
        if (file_exists($save_path)) {
            unlink($save_path);
        }
        error_log("Error SQL: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
    exit;
}

// ========== REGISTRO DE SALIDA ==========
if ($tipo === 'SALIDA') {
    // Validar que no exista ya salida para ese día
    $sql_check = "SELECT COUNT(*) FROM asistencia WHERE ID_EMPLEADO = ? AND FECHA = ? AND TIPO = 'SALIDA'";
    $stmt = $conn->prepare($sql_check);
    $stmt->execute([$id_empleado, $fecha]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una salida registrada para este empleado en esta fecha.']);
        exit;
    }

    // Variable para almacenar el nombre del archivo de foto (puede ser null)
    $filename = null;
    
    // Si se proporciona foto para la salida, procesarla
    if ($foto_base64) {
        $filename = 'salida_' . $id_empleado . '_' . date('Ymd_His') . '.jpg';
        $save_path = $uploads_dir . $filename;
        $foto_base64_clean = preg_replace('#^data:image/\w+;base64,#i', '', $foto_base64);
        $img_data = base64_decode($foto_base64_clean);
        
        if ($img_data === false || file_put_contents($save_path, $img_data) === false) {
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar la foto de salida.']);
            exit;
        }
    }

    // Buscar horario asignado, que esté vigente y cubra ese día de semana
    $dia_semana = date('N', strtotime($fecha));
    $sqlHorario = "
        SELECT h.HORA_SALIDA
        FROM empleado_horario eh
        JOIN horario h ON h.ID_HORARIO = eh.ID_HORARIO
        JOIN horario_dia hd ON hd.ID_HORARIO = h.ID_HORARIO
        WHERE eh.ID_EMPLEADO = ?
          AND hd.ID_DIA = ?
          AND eh.FECHA_DESDE <= ?
          AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= ?)
        LIMIT 1
    ";
    $stmt = $conn->prepare($sqlHorario);
    $stmt->execute([$id_empleado, $dia_semana, $fecha, $fecha]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'No se encontró un horario asignado para el empleado en ese día.']);
        exit;
    }

    $hora_salida_horario = $row['HORA_SALIDA']; // formato HH:MM

    // Hora real de salida (servidor)
    $hora_salida = date('H:i');

    // Calcular diferencia en minutos
    $ts_horario = strtotime($fecha . ' ' . $hora_salida_horario);
    $ts_salida = strtotime($fecha . ' ' . $hora_salida);
    $diferencia_min = ($ts_salida - $ts_horario) / 60;

    // Estado para salida: Temprano = más de 10 min antes, A tiempo = ±10 min, Tardío = más de 10 min después
    if ($diferencia_min < -10) {
        $tardanza = "Temprano";
    } elseif ($diferencia_min >= -10 && $diferencia_min <= 10) {
        $tardanza = "A tiempo";
    } else {
        $tardanza = "Tardía";
    }

    // Inserta la asistencia (salida) CON FOTO (puede ser NULL)
    $sql = "INSERT INTO asistencia 
        (ID_EMPLEADO, FECHA, TIPO, HORA, TARDANZA, OBSERVACION, FOTO, REGISTRO_MANUAL)
        VALUES (?, ?, 'SALIDA', ?, ?, NULL, ?, 'N')";
    
    try {
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute([$id_empleado, $fecha, $hora_salida, $tardanza, $filename]);
        
        if ($ok) {
            $response = ['success' => true, 'message' => 'Salida registrada correctamente'];
            if ($filename) {
                $response['foto_guardada'] = $filename;
            }
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo registrar la salida.']);
        }
    } catch (PDOException $e) {
        error_log("Error SQL: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
    exit;
}

// Si el tipo no es válido
echo json_encode(['success' => false, 'message' => 'Tipo de asistencia no válido.']);
exit;