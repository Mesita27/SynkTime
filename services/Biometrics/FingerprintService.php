<?php
/**
 * FingerprintService
 * Service for fingerprint recognition using SourceAFIS HTTP wrapper
 */

require_once __DIR__ . '/../../config/biometrics.php';

class FingerprintService {
    private $apiBase;
    private $timeout;
    private $connectTimeout;
    
    public function __construct() {
        $this->apiBase = BIOMETRICS_FINGER_API_BASE;
        $this->timeout = BIOMETRIC_API_TIMEOUT;
        $this->connectTimeout = BIOMETRIC_CONNECT_TIMEOUT;
    }
    
    /**
     * Test API connectivity
     */
    public function testConnection() {
        try {
            $response = $this->makeRequest('/health', 'GET');
            return isset($response['status']) && $response['status'] === 'UP';
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Extract fingerprint template from image
     */
    public function extractTemplate($imageData) {
        $data = [
            'image' => $this->encodeImage($imageData),
            'format' => 'base64'
        ];
        
        $response = $this->makeRequest('/template', 'POST', $data);
        
        if (isset($response['template'])) {
            return [
                'template' => $response['template'],
                'quality' => $response['quality'] ?? null,
                'minutiae_count' => $response['minutiae_count'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        throw new Exception('No se pudo extraer el template de la huella');
    }
    
    /**
     * Verify fingerprint against stored template
     */
    public function verifyFingerprint($imageData, $storedTemplate) {
        $candidate = $this->extractTemplate($imageData);
        
        $data = [
            'probe' => $candidate['template'],
            'candidate' => $storedTemplate
        ];
        
        $response = $this->makeRequest('/verify', 'POST', $data);
        
        if (isset($response['score'])) {
            return [
                'score' => $response['score'],
                'is_match' => $response['score'] >= FINGER_MATCH_THRESHOLD,
                'threshold' => FINGER_MATCH_THRESHOLD,
                'quality' => $candidate['quality'] ?? null
            ];
        }
        
        throw new Exception('No se pudo verificar la huella dactilar');
    }
    
    /**
     * Enroll fingerprint and return template
     */
    public function enrollFingerprint($imageData, $fingerType) {
        try {
            $template = $this->extractTemplate($imageData);
            
            return [
                'template' => $template['template'],
                'finger_type' => $fingerType,
                'quality' => $template['quality'],
                'minutiae_count' => $template['minutiae_count'],
                'enrolled_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            throw new Exception('Error en inscripción de huella: ' . $e->getMessage());
        }
    }
    
    /**
     * Identify fingerprint against multiple stored templates
     */
    public function identifyFingerprint($imageData, $templates) {
        $probe = $this->extractTemplate($imageData);
        
        $data = [
            'probe' => $probe['template'],
            'candidates' => $templates
        ];
        
        $response = $this->makeRequest('/identify', 'POST', $data);
        
        if (isset($response['matches'])) {
            // Return best match above threshold
            $bestMatch = null;
            foreach ($response['matches'] as $match) {
                if ($match['score'] >= FINGER_MATCH_THRESHOLD) {
                    if (!$bestMatch || $match['score'] > $bestMatch['score']) {
                        $bestMatch = $match;
                    }
                }
            }
            
            return $bestMatch;
        }
        
        return null;
    }
    
    /**
     * Validate fingerprint image quality
     */
    public function validateQuality($imageData) {
        try {
            $template = $this->extractTemplate($imageData);
            
            $quality = $template['quality'] ?? 0;
            $minutiae = $template['minutiae_count'] ?? 0;
            
            return [
                'valid' => $quality >= 50 && $minutiae >= 10,
                'quality' => $quality,
                'minutiae_count' => $minutiae,
                'recommendations' => $this->getQualityRecommendations($quality, $minutiae)
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'recommendations' => ['Verifique que la imagen sea una huella dactilar válida']
            ];
        }
    }
    
    /**
     * Get quality improvement recommendations
     */
    private function getQualityRecommendations($quality, $minutiae) {
        $recommendations = [];
        
        if ($quality < 50) {
            $recommendations[] = 'Mejore la calidad de la imagen - limpie el sensor';
        }
        
        if ($minutiae < 10) {
            $recommendations[] = 'Presione más firmemente en el sensor';
        }
        
        if ($quality < 30) {
            $recommendations[] = 'La imagen está muy borrosa - intente nuevamente';
        }
        
        return $recommendations;
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
     * Make HTTP request to SourceAFIS API
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
            throw new Exception("Error de conexión con API de huellas: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("Error en API de huellas (HTTP {$httpCode}): {$response}");
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Respuesta inválida de API de huellas");
        }
        
        return $decoded;
    }
    
    /**
     * Get API status and version information
     */
    public function getStatus() {
        try {
            return $this->makeRequest('/health', 'GET');
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