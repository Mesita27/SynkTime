<?php
require_once __DIR__ . '/../../lib/db.php';
$config = require __DIR__ . '/../../config/biometrics.php';

header('Content-Type: application/json');

function save_photo(string $dataUrl, string $dir): string {
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $data = preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl);
    $bin = base64_decode($data);
    $name = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
    $path = rtrim($dir, '/').'/'.$name;
    file_put_contents($path, $bin);
    return $path;
}

// TODO: Ajustar al flujo real de registro de asistencia del sistema.
// Este stub crea una fila de auditoría y deja un hook para que el módulo actual de asistencias registre la marca.
try {
    $employeeId = $_POST['employee_id'] ?? null;
    $channel    = $_POST['channel'] ?? null; // face|fingerprint|photo
    $score      = isset($_POST['score']) ? (float)$_POST['score'] : null;
    $imageData  = $_POST['image'] ?? null; // base64 foto (o null para huella)
    $providerRef= $_POST['provider_ref'] ?? null; // subject_id o fingerprint_id
    if (!$employeeId || !$channel) {
        http_response_code(400);
        echo json_encode(['error' => 'employee_id and channel are required']);
        exit;
    }

    $photosDir = $config['storage']['photos_dir'];
    $imagePath = $imageData ? save_photo($imageData, $photosDir) : null;

    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO biometric_event (employee_id, type, score, image_path, provider_ref, created_at)
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$employeeId, $channel, $score, $imagePath, $providerRef]);

    // Hook: aquí invoca al registro de asistencia real del sistema si existe una función/endpoint.
    // register_attendance_for_employee($employeeId, $channel, $imagePath, $score);

    $pdo->commit();
    echo json_encode(['ok' => true, 'image_path' => $imagePath]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}