<?php
function http_post_json(string $url, array $payload, array $headers = [], int $timeout = 15): array {
    $ch = curl_init($url);
    $headers = array_merge(['Content-Type: application/json'], $headers);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => $timeout,
    ]);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("HTTP POST error: $err");
    }
    curl_close($ch);
    $data = json_decode($raw, true);
    return ['status' => $status, 'data' => $data, 'raw' => $raw];
}

function http_post_multipart(string $url, array $fields, array $headers = [], int $timeout = 20): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_TIMEOUT => $timeout,
    ]);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("HTTP POST multipart error: $err");
    }
    curl_close($ch);
    $data = json_decode($raw, true);
    return ['status' => $status, 'data' => $data, 'raw' => $raw];
}