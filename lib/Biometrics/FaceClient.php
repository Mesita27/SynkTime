<?php
require_once __DIR__ . '/../http.php';

class FaceClient {
    private string $baseUrl;
    private ?string $apiKey;
    private ?string $serviceTag;

    public function __construct(array $cfg) {
        $this->baseUrl = rtrim($cfg['base_url'] ?? '', '/');
        $this->apiKey = $cfg['api_key'] ?? null;
        $this->serviceTag = $cfg['service_tag'] ?? null;
    }

    private function headers(): array {
        $headers = [];
        if (!empty($this->apiKey)) {
            $headers[] = 'x-api-key: ' . $this->apiKey;
        }
        if (!empty($this->serviceTag)) {
            $headers[] = 'x-service-tag: ' . $this->serviceTag; // opcional si tu proxy/servicio lo usa
        }
        return $headers;
    }

    // Crea/asegura un subject (empleado)
    public function ensureSubject(string $subjectId): bool {
        $url = $this->baseUrl . '/api/v1/subjects';
        $res = http_post_json($url, ['subject' => $subjectId], $this->headers());
        return $res['status'] === 200 || $res['status'] === 201 || $res['status'] === 409;
    }

    // Añade ejemplo de rostro al subject
    public function addFaceExample(string $subjectId, string $imageBase64): array {
        $url = $this->baseUrl . '/api/v1/subjects/' . rawurlencode($subjectId) . '/faces';
        $res = http_post_json($url, ['image_base64' => $imageBase64], $this->headers());
        if ($res['status'] >= 400) {
            throw new Exception('Face add example failed: ' . $res['raw']);
        }
        return $res['data'] ?? [];
    }

    // Reconocer rostro 1:N
    public function recognize(string $imageBase64, int $limit = 3): array {
        $url = $this->baseUrl . '/api/v1/recognition/recognize';
        $payload = [
            'image_base64' => $imageBase64,
            'limit' => $limit,
        ];
        $res = http_post_json($url, $payload, $this->headers());
        if ($res['status'] >= 400) {
            throw new Exception('Face recognize failed: ' . $res['raw']);
        }
        return $res['data'] ?? [];
    }
}