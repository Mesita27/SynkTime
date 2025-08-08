<?php
require_once __DIR__ . '/../http.php';

class FingerprintClient {
    private string $baseUrl;

    public function __construct(array $cfg) {
        $this->baseUrl = rtrim($cfg['base_url'] ?? '', '/');
    }

    public function enroll(string $employeeId, array $images): array {
        $url = $this->baseUrl . '/enroll';
        $fields = ['employeeId' => $employeeId];
        foreach ($images as $i => $pathOrBlob) {
            $fields["images[$i]"] = curl_file_create($pathOrBlob, 'image/png', "finger_$i.png");
        }
        $res = http_post_multipart($url, $fields);
        if ($res['status'] >= 400) {
            throw new Exception('Fingerprint enroll failed: ' . $res['raw']);
        }
        return $res['data'] ?? [];
    }

    public function identify(string $imagePath, int $limit = 3): array {
        $url = $this->baseUrl . '/identify';
        $fields = ['image' => curl_file_create($imagePath, 'image/png', 'scan.png'), 'limit' => $limit];
        $res = http_post_multipart($url, $fields);
        if ($res['status'] >= 400) {
            throw new Exception('Fingerprint identify failed: ' . $res['raw']);
        }
        return $res['data'] ?? [];
    }

    public function verify(string $imagePath, string $fingerprintId): array {
        $url = $this->baseUrl . '/verify';
        $fields = [
            'image' => curl_file_create($imagePath, 'image/png', 'scan.png'),
            'fingerprintId' => $fingerprintId
        ];
        $res = http_post_multipart($url, $fields);
        if ($res['status'] >= 400) {
            throw new Exception('Fingerprint verify failed: ' . $res['raw']);
        }
        return $res['data'] ?? [];
    }
}