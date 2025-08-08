<?php
require_once __DIR__ . '/../../lib/db.php';
$config = require __DIR__ . '/../../config/biometrics.php';
require_once __DIR__ . '/../../lib/Biometrics/FaceClient.php';

header('Content-Type: application/json');

try {
    $image = $_POST['image'] ?? null; // base64 (data:image/png;base64,...)
    if (!$image) {
        http_response_code(400);
        echo json_encode(['error' => 'image base64 required']);
        exit;
    }
    $face = new FaceClient($config['face_api']);
    $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $image);
    $res = $face->recognize($base64, 3);

    // Normaliza resultado: lista de candidatos con subject y score
    $candidates = [];
    if (!empty($res['result'][0]['subjects'])) {
        foreach ($res['result'][0]['subjects'] as $s) {
            $candidates[] = [
                'subject' => $s['subject'] ?? null,
                'score' => $s['similarity'] ?? $s['similarity_score'] ?? null
            ];
        }
    }

    echo json_encode(['candidates' => $candidates, 'min_score' => $config['face_api']['min_score']]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}