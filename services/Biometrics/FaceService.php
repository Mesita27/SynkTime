<?php
/**
 * FaceService
 * Service for facial recognition using InsightFace-REST API
 */

require_once __DIR__ . '/../../config/biometrics.php';

class FaceService {
    private $apiBase;
    private $timeout;
    private $connectTimeout;
    
    public function __construct() {
        $this->apiBase = BIOMETRICS_FACE_API_BASE;
        $this->timeout = BIOMETRIC_API_TIMEOUT;
        $this->connectTimeout = BIOMETRIC_CONNECT_TIMEOUT;
    }
    
    /**
     * Test API connectivity
     */
    public function testConnection() {
        try {
            $response = $this->makeRequest('/info', 'GET');
            return isset($response['status']) || isset($response['version']);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Detect faces in image
     */
    public function detectFaces($imageData) {
        $data = [
            'images' => [$this->encodeImage($imageData)]
        ];
        
        return $this->makeRequest('/detect', 'POST', $data);
    }
    
    /**
     * Extract face embedding
     */
    public function extractEmbedding($imageData) {
        $data = [
            'images' => [$this->encodeImage($imageData)]
        ];
        
        $response = $this->makeRequest('/embed', 'POST', $data);
        
        if (isset($response[0]['embedding'])) {
            return [
                'embedding' => $response[0]['embedding'],
                'model' => $response[0]['model'] ?? 'arcface',
                'l2norm' => $response[0]['l2norm'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        throw new Exception('No se pudo extraer el embedding facial');
    }
    
    /**
     * Compare two face embeddings
     */
    public function compareFaces($embedding1, $embedding2) {
        $data = [
            'embeddings' => [$embedding1, $embedding2]
        ];
        
        $response = $this->makeRequest('/match', 'POST', $data);
        
        if (isset($response['similarity'])) {
            return [
                'similarity' => $response['similarity'],
                'is_match' => $response['similarity'] >= FACE_MATCH_THRESHOLD,
                'threshold' => FACE_MATCH_THRESHOLD
            ];
        }
        
        throw new Exception('No se pudo comparar los rostros');
    }
    
    /**
     * Verify face against stored embedding
     */
    public function verifyFace($imageData, $storedEmbedding) {
        try {
            $current = $this->extractEmbedding($imageData);
            return $this->compareFaces($current['embedding'], $storedEmbedding);
        } catch (Exception $e) {
            throw new Exception('Error en verificación facial: ' . $e->getMessage());
        }
    }
    
    /**
     * Enroll multiple face images and create composite embedding
     */
    public function enrollMultipleFaces($imageDataArray) {
        $embeddings = [];
        
        foreach ($imageDataArray as $imageData) {
            $embedding = $this->extractEmbedding($imageData);
            $embeddings[] = $embedding;
        }
        
        // For multiple faces, we can average embeddings or store them separately
        // For now, we'll store all embeddings and use the first one for matching
        return [
            'primary_embedding' => $embeddings[0]['embedding'],
            'all_embeddings' => $embeddings,
            'count' => count($embeddings),
            'model' => $embeddings[0]['model'],
            'enrolled_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Encode image data for API
     */
    private function encodeImage($imageData) {
        if (strpos($imageData, 'data:image') === 0) {
            // Remove data URL prefix
            $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $imageData);
        }
        
        return $imageData;
    }
    
    /**
     * Make HTTP request to InsightFace API
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->apiBase . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error de conexión con API facial: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("Error en API facial (HTTP {$httpCode}): {$response}");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Respuesta inválida de API facial");
        }
        
        return $decoded;
    }
    
    /**
     * Get API status and model information
     */
    public function getStatus() {
        try {
            return $this->makeRequest('/info', 'GET');
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'available' => false
            ];
        }
    }
}
?>