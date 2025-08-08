<?php
require_once __DIR__ . '/../../lib/db.php';
$config = require __DIR__ . '/../../config/biometrics.php';
require_once __DIR__ . '/../../lib/Biometrics/FaceClient.php';

header('Content-Type: application/json');

try {
    $employeeId = $_POST['employee_id'] ?? null;
    $images = $_POST['images'] ?? []; // array de base64 (data:image/png;base64,xxx)
    if (!$employeeId || empty($images)) {
        http_response_code(400);
        echo json_encode(['error' => 'employee_id and images[] are required']);
        exit;
    }

    $face = new FaceClient($config['face_api']);
    $subjectId = 'emp_' . $employeeId;

    $face->ensureSubject($subjectId);
    foreach ($images as $img) {
        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $img);
        $face->addFaceExample($subjectId, $base64);
    }

    // Guardar/actualizar mapping
    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO biometric_identity (employee_id, face_subject_id, fingerprint_id, created_at, updated_at)
                           VALUES (?, ?, NULL, NOW(), NOW())
                           ON DUPLICATE KEY UPDATE face_subject_id = VALUES(face_subject_id), updated_at = NOW()");
    $stmt->execute([$employeeId, $subjectId]);
    $pdo->commit();

    echo json_encode(['ok' => true, 'subject_id' => $subjectId]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}