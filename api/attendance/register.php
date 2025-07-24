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
        SELECT H.HORA_ENTRADA, H.TOLERANCIA
        FROM EMPLEADO_HORARIO EH
        JOIN HORARIO H ON H.ID_HORARIO = EH.ID_HORARIO
        JOIN HORARIO_DIA HD ON HD.ID_HORARIO = H.ID_HORARIO
        WHERE EH.ID_EMPLEADO = ?
          AND HD.ID_DIA = ?
          AND EH.FECHA_DESDE <= ?
          AND (EH.FECHA_HASTA IS NULL OR EH.FECHA_HASTA >= ?)
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
    $tolerancia = (int)($row['TOLERANCIA'] ?? 0);

    // Hora real de llegada (servidor)
    $hora_llegada = date('H:i');

    // Calcular diferencia en minutos
    $ts_horario = strtotime($fecha . ' ' . $hora_entrada_horario);
    $ts_llegada = strtotime($fecha . ' ' . $hora_llegada);
    $diferencia_min = ($ts_llegada - $ts_horario) / 60;

    // Estado según criterios de tolerancia real
    if ($ts_llegada < $ts_horario) {
        $tardanza = "N"; // Temprano (pero no cuenta como tardanza)
    } elseif ($ts_llegada <= $ts_horario + $tolerancia * 60) {
        $tardanza = "N"; // Puntual
    } else {
        $tardanza = "S"; // Tardanza
    }

    $sql = "INSERT INTO ASISTENCIA 
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
            if (file_exists($save_path)) unlink($save_path);
            echo json_encode(['success' => false, 'message' => 'No se pudo registrar la entrada en la base de datos.']);
        }
    } catch (PDOException $e) {
        if (file_exists($save_path)) unlink($save_path);
        error_log("Error SQL: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
    exit;
}

// ========== REGISTRO DE SALIDA ==========
if ($tipo === 'SALIDA') {
    // Validar que no exista ya salida para ese día
    $sql_check = "SELECT COUNT(*) FROM ASISTENCIA WHERE ID_EMPLEADO = ? AND FECHA = ? AND TIPO = 'SALIDA'";
    $stmt = $conn->prepare($sql_check);
    $stmt->execute([$id_empleado, $fecha]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya existe una salida registrada para este empleado en esta fecha.']);
        exit;
    }

    $filename = null;
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
        SELECT H.HORA_SALIDA, H.TOLERANCIA
        FROM EMPLEADO_HORARIO EH
        JOIN HORARIO H ON H.ID_HORARIO = EH.ID_HORARIO
        JOIN HORARIO_DIA HD ON HD.ID_HORARIO = H.ID_HORARIO
        WHERE EH.ID_EMPLEADO = ?
          AND HD.ID_DIA = ?
          AND EH.FECHA_DESDE <= ?
          AND (EH.FECHA_HASTA IS NULL OR EH.FECHA_HASTA >= ?)
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
    $tolerancia = (int)($row['TOLERANCIA'] ?? 0);

    // Hora real de salida (servidor)
    $hora_salida = date('H:i');

    // Calcular diferencia en minutos
    $ts_horario = strtotime($fecha . ' ' . $hora_salida_horario);
    $ts_salida = strtotime($fecha . ' ' . $hora_salida);
    $diferencia_min = ($ts_salida - $ts_horario) / 60;

    // Estado salida: Temprano, Puntual, Tarde con tolerancia
    if ($ts_salida < $ts_horario - $tolerancia * 60) {
        $tardanza = "S"; // salida muy temprano
    } elseif ($ts_salida <= $ts_horario + $tolerancia * 60) {
        $tardanza = "N"; // salida a tiempo
    } else {
        $tardanza = "S"; // salida tarde
    }

    $sql = "INSERT INTO ASISTENCIA 
        (ID_EMPLEADO, FECHA, TIPO, HORA, TARDANZA, OBSERVACION, FOTO, REGISTRO_MANUAL)
        VALUES (?, ?, 'SALIDA', ?, ?, NULL, ?, 'N')";
    
    try {
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute([$id_empleado, $fecha, $hora_salida, $tardanza, $filename]);
        if ($ok) {
            $response = ['success' => true, 'message' => 'Salida registrada correctamente'];
            if ($filename) $response['foto_guardada'] = $filename;
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