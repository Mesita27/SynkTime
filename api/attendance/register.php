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
$fecha = date('Y-m-d'); // Fecha actual

// Validación de datos obligatorios
if (!$id_empleado) {
    echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
    exit;
}

// Procesar la imagen si existe
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
    
    // Generar nombre de archivo único
    $filename = uniqid('att_') . '_' . date('Ymd_His') . '.jpg';
    $save_path = $uploads_dir . $filename;
    
    if (file_put_contents($save_path, $img_data) === false) {
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la foto. Verificar permisos.']);
        exit;
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
    // Si ya hay entrada y salida para este horario, no se incluye en disponibles
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

// Registrar la asistencia
$sql = "INSERT INTO ASISTENCIA 
    (ID_EMPLEADO, FECHA, TIPO, HORA, TARDANZA, OBSERVACION, FOTO, REGISTRO_MANUAL, ID_HORARIO)
    VALUES (?, ?, ?, ?, ?, NULL, ?, 'N', ?)";

try {
    $stmt = $conn->prepare($sql);
    $ok = $stmt->execute([
        $id_empleado, 
        $fecha, 
        $tipoRegistro, 
        $hora_actual, 
        $tardanza, 
        $filename,
        $horarioSeleccionado['ID_HORARIO']
    ]);
    
    if ($ok) {
        echo json_encode([
            'success' => true, 
            'message' => $tipoRegistro === 'ENTRADA' ? 'Entrada registrada correctamente' : 'Salida registrada correctamente',
            'tipo' => $tipoRegistro,
            'horario' => $horarioSeleccionado['NOMBRE_HORARIO'],
            'foto_guardada' => $filename,
            'tardanza' => $tardanza
        ]);
    } else {
        if ($filename && file_exists($uploads_dir . $filename)) {
            unlink($uploads_dir . $filename);
        }
        echo json_encode(['success' => false, 'message' => 'Error al registrar la asistencia.']);
    }
} catch (PDOException $e) {
    if ($filename && file_exists($uploads_dir . $filename)) {
        unlink($uploads_dir . $filename);
    }
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>