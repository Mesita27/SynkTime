<?php
require_once __DIR__ . '/../../lib/db.php';
$config = require __DIR__ . '/../../config/biometrics.php';
require_once __DIR__ . '/../../lib/Biometrics/FingerprintClient.php';

header('Content-Type: application/json');

try {
    $employeeId = $_POST['employee_id'] ?? null;
    if (!$employeeId || empty($_FILES['images'])) {
        http_response_code(400);
        echo json_encode(['error' => 'employee_id and images[] files are required']);
        exit;
    }

    $client = new FingerprintClient($config['fingerprint_api']);
    $paths = [];
    foreach ($_FILES['images']['tmp_name'] as $tmp) {
        $paths[] = $tmp;
    }
    $res = $client->enroll($employeeId, $paths);
    $fingerprintId = $res['fingerprintId'] ?? null;

    if (!$fingerprintId) {
        throw new Exception('fingerprintId not returned');
    }

    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO biometric_identity (employee_id, face_subject_id, fingerprint_id, created_at, updated_at)
                           VALUES (?, NULL, ?, NOW(), NOW())
                           ON DUPLICATE KEY UPDATE fingerprint_id = VALUES(fingerprint_id), updated_at = NOW()");
    $stmt->execute([$employeeId, $fingerprintId]);
    $pdo->commit();

    echo json_encode(['ok' => true, 'fingerprint_id' => $fingerprintId]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}