<?php
require_once __DIR__ . '/../../lib/db.php';
$config = require __DIR__ . '/../../config/biometrics.php';
require_once __DIR__ . '/../../lib/Biometrics/FingerprintClient.php';

header('Content-Type: application/json');

try {
    if (empty($_FILES['image'])) {
        http_response_code(400);
        echo json_encode(['error' => 'image file required']);
        exit;
    }
    $tmp = $_FILES['image']['tmp_name'];
    $client = new FingerprintClient($config['fingerprint_api']);
    $res = $client->identify($tmp, 3);

    $candidates = $res['candidates'] ?? [];
    echo json_encode(['candidates' => $candidates, 'min_score' => $config['fingerprint_api']['min_score']]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}