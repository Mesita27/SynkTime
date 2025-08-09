<?php
/**
 * Face Recognition Service
 * Integrates with InsightFace-REST API for facial recognition operations
 */

class FaceService
{
    private $config;
    private $apiBase;
    private $threshold;
    private $timeout;
    private $connectTimeout;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/biometrics.php';
        $this->apiBase = $this->config['FACE_API_BASE'];
        $this->threshold = $this->config['FACE_MATCH_THRESHOLD'];
        $this->timeout = $this->config['API_TIMEOUT'];
        $this->connectTimeout = $this->config['API_CONNECT_TIMEOUT'];
    }

    /**
     * Enroll facial patterns for an employee
     * 
     * @param int $employeeId Employee ID
     * @param array $images Array of base64 encoded images
     * @return array Result with success status and message
     */
    public function enrollFace($employeeId, $images)
    {
        try {
            if (!$this->isServiceAvailable()) {
                throw new Exception('Servicio de reconocimiento facial no disponible');
            }

            if (empty($images) || !is_array($images)) {
                throw new Exception('Se requiere al menos una imagen facial');
            }

            if (count($images) > $this->config['FACE_MAX_IMAGES']) {
                throw new Exception('Máximo ' . $this->config['FACE_MAX_IMAGES'] . ' imágenes permitidas');
            }

            // Extract embeddings for each image
            $embeddings = [];
            foreach ($images as $index => $imageData) {
                $embedding = $this->extractEmbedding($imageData);
                if ($embedding) {
                    $embeddings[] = [
                        'index' => $index,
                        'embedding' => $embedding,
                        'image_data' => $imageData
                    ];
                }
            }

            if (empty($embeddings)) {
                throw new Exception('No se pudieron extraer características faciales de las imágenes');
            }

            // Store embeddings and return result
            return [
                'success' => true,
                'embeddings' => $embeddings,
                'count' => count($embeddings),
                'message' => 'Patrones faciales extraídos correctamente'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'embeddings' => [],
                'count' => 0
            ];
        }
    }

    /**
     * Verify a face against stored patterns
     * 
     * @param int $employeeId Employee ID
     * @param string $imageData Base64 encoded image
     * @param array $storedEmbeddings Previously stored facial embeddings
     * @return array Verification result with score and match status
     */
    public function verifyFace($employeeId, $imageData, $storedEmbeddings)
    {
        try {
            if (!$this->isServiceAvailable()) {
                throw new Exception('Servicio de reconocimiento facial no disponible');
            }

            if (empty($imageData)) {
                throw new Exception('Imagen requerida para verificación');
            }

            if (empty($storedEmbeddings)) {
                throw new Exception('No hay patrones faciales registrados para este empleado');
            }

            // Extract embedding from current image
            $currentEmbedding = $this->extractEmbedding($imageData);
            if (!$currentEmbedding) {
                throw new Exception('No se pudo extraer características faciales de la imagen');
            }

            // Compare with stored embeddings
            $maxScore = 0;
            $bestMatch = null;

            foreach ($storedEmbeddings as $stored) {
                if (isset($stored['embedding'])) {
                    $score = $this->calculateCosineSimilarity($currentEmbedding, $stored['embedding']);
                    if ($score > $maxScore) {
                        $maxScore = $score;
                        $bestMatch = $stored;
                    }
                }
            }

            $isMatch = $maxScore >= $this->threshold;

            return [
                'success' => true,
                'match' => $isMatch,
                'score' => $maxScore,
                'threshold' => $this->threshold,
                'confidence' => $maxScore * 100,
                'message' => $isMatch ? 'Verificación facial exitosa' : 'Rostro no reconocido'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'match' => false,
                'score' => 0,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Extract facial embedding from image using InsightFace-REST API
     */
    private function extractEmbedding($imageData)
    {
        try {
            // Clean image data
            $imageData = $this->cleanImageData($imageData);

            $payload = [
                'images' => [
                    'data' => [$imageData]
                ],
                'extract_embedding' => true,
                'return_face_data' => true
            ];

            $response = $this->makeApiCall('/extract', $payload);

            if ($response && isset($response['data']) && !empty($response['data'])) {
                $faceData = $response['data'][0];
                if (isset($faceData['embedding'])) {
                    return $faceData['embedding'];
                }
            }

            return null;

        } catch (Exception $e) {
            error_log("Error extracting face embedding: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Calculate cosine similarity between two embeddings
     */
    private function calculateCosineSimilarity($embedding1, $embedding2)
    {
        if (!is_array($embedding1) || !is_array($embedding2)) {
            return 0;
        }

        if (count($embedding1) !== count($embedding2)) {
            return 0;
        }

        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $norm1 += $embedding1[$i] * $embedding1[$i];
            $norm2 += $embedding2[$i] * $embedding2[$i];
        }

        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);

        if ($norm1 == 0 || $norm2 == 0) {
            return 0;
        }

        return $dotProduct / ($norm1 * $norm2);
    }

    /**
     * Clean base64 image data
     */
    private function cleanImageData($imageData)
    {
        // Remove data URL prefix if present
        $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $imageData);
        
        // Replace spaces with plus signs
        $imageData = str_replace(' ', '+', $imageData);
        
        return $imageData;
    }

    /**
     * Make API call to InsightFace-REST service
     */
    private function makeApiCall($endpoint, $data)
    {
        $url = $this->apiBase . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Error de conexión con servicio facial: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Error en servicio facial (HTTP $httpCode)");
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Respuesta inválida del servicio facial");
        }

        return $decoded;
    }

    /**
     * Check if the face recognition service is available
     */
    public function isServiceAvailable()
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiBase . '/docs');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get service status and configuration
     */
    public function getServiceStatus()
    {
        return [
            'service' => 'InsightFace-REST',
            'endpoint' => $this->apiBase,
            'available' => $this->isServiceAvailable(),
            'threshold' => $this->threshold,
            'timeout' => $this->timeout
        ];
    }
}