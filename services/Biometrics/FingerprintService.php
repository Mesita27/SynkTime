<?php
/**
 * Fingerprint Recognition Service
 * Integrates with SourceAFIS HTTP API for fingerprint recognition operations
 */

class FingerprintService
{
    private $config;
    private $apiBase;
    private $threshold;
    private $timeout;
    private $connectTimeout;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/biometrics.php';
        $this->apiBase = $this->config['FINGER_API_BASE'];
        $this->threshold = $this->config['FINGER_MATCH_THRESHOLD'];
        $this->timeout = $this->config['API_TIMEOUT'];
        $this->connectTimeout = $this->config['API_CONNECT_TIMEOUT'];
    }

    /**
     * Enroll fingerprint template for an employee
     * 
     * @param int $employeeId Employee ID
     * @param string $fingerType Type of finger (thumb_right, index_left, etc.)
     * @param string $imageData Base64 encoded fingerprint image
     * @return array Result with success status and template data
     */
    public function enrollFingerprint($employeeId, $fingerType, $imageData)
    {
        try {
            if (!$this->isServiceAvailable()) {
                throw new Exception('Servicio de huella dactilar no disponible');
            }

            if (empty($imageData)) {
                throw new Exception('Imagen de huella requerida');
            }

            if (!$this->isValidFingerType($fingerType)) {
                throw new Exception('Tipo de dedo inválido');
            }

            // Generate fingerprint template
            $template = $this->generateTemplate($imageData);
            if (!$template) {
                throw new Exception('No se pudo generar template de huella dactilar');
            }

            // Validate template quality
            if (!$this->validateTemplateQuality($template)) {
                throw new Exception('Calidad de huella insuficiente, intente nuevamente');
            }

            return [
                'success' => true,
                'template' => $template,
                'finger_type' => $fingerType,
                'quality_score' => $this->getTemplateQuality($template),
                'message' => 'Template de huella generado correctamente'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'template' => null
            ];
        }
    }

    /**
     * Verify fingerprint against stored templates
     * 
     * @param int $employeeId Employee ID
     * @param string $imageData Base64 encoded fingerprint image
     * @param array $storedTemplates Previously stored fingerprint templates
     * @return array Verification result with score and match status
     */
    public function verifyFingerprint($employeeId, $imageData, $storedTemplates)
    {
        try {
            if (!$this->isServiceAvailable()) {
                throw new Exception('Servicio de huella dactilar no disponible');
            }

            if (empty($imageData)) {
                throw new Exception('Imagen de huella requerida para verificación');
            }

            if (empty($storedTemplates)) {
                throw new Exception('No hay templates de huella registrados para este empleado');
            }

            // Generate template from current image
            $currentTemplate = $this->generateTemplate($imageData);
            if (!$currentTemplate) {
                throw new Exception('No se pudo procesar la huella dactilar');
            }

            // Compare with stored templates
            $maxScore = 0;
            $bestMatch = null;

            foreach ($storedTemplates as $stored) {
                if (isset($stored['template'])) {
                    $score = $this->compareTemplates($currentTemplate, $stored['template']);
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
                'confidence' => min(100, ($maxScore / $this->threshold) * 100),
                'finger_type' => $bestMatch['finger_type'] ?? null,
                'message' => $isMatch ? 'Verificación de huella exitosa' : 'Huella no reconocida'
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
     * Generate fingerprint template using SourceAFIS API
     */
    private function generateTemplate($imageData)
    {
        try {
            // Clean image data
            $imageData = $this->cleanImageData($imageData);

            $payload = [
                'image' => $imageData,
                'return_quality' => true
            ];

            $response = $this->makeApiCall('/template', $payload);

            if ($response && isset($response['template'])) {
                return $response['template'];
            }

            return null;

        } catch (Exception $e) {
            error_log("Error generating fingerprint template: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compare two fingerprint templates using SourceAFIS API
     */
    private function compareTemplates($template1, $template2)
    {
        try {
            $payload = [
                'probe' => $template1,
                'candidate' => $template2
            ];

            $response = $this->makeApiCall('/verify', $payload);

            if ($response && isset($response['score'])) {
                return floatval($response['score']);
            }

            return 0;

        } catch (Exception $e) {
            error_log("Error comparing fingerprint templates: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Validate fingerprint template quality
     */
    private function validateTemplateQuality($template)
    {
        if (empty($template)) {
            return false;
        }

        // Basic template validation
        // In a real implementation, you might check template size, minutiae count, etc.
        return strlen($template) > 100; // Minimum template size check
    }

    /**
     * Get template quality score (0-100)
     */
    private function getTemplateQuality($template)
    {
        if (empty($template)) {
            return 0;
        }

        // Simplified quality scoring based on template length
        // In production, this would use actual quality metrics from SourceAFIS
        $templateLength = strlen($template);
        
        if ($templateLength < 200) return 30;
        if ($templateLength < 500) return 60;
        if ($templateLength < 1000) return 80;
        
        return 95;
    }

    /**
     * Validate finger type
     */
    private function isValidFingerType($fingerType)
    {
        $validTypes = array_keys($this->config['FINGER_TYPES']);
        return in_array($fingerType, $validTypes);
    }

    /**
     * Get available finger types
     */
    public function getFingerTypes()
    {
        return $this->config['FINGER_TYPES'];
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
     * Make API call to SourceAFIS HTTP service
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
            throw new Exception("Error de conexión con servicio de huella: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Error en servicio de huella (HTTP $httpCode)");
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Respuesta inválida del servicio de huella");
        }

        return $decoded;
    }

    /**
     * Check if the fingerprint service is available
     */
    public function isServiceAvailable()
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiBase . '/');
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
            'service' => 'SourceAFIS HTTP',
            'endpoint' => $this->apiBase,
            'available' => $this->isServiceAvailable(),
            'threshold' => $this->threshold,
            'timeout' => $this->timeout,
            'supported_fingers' => $this->getFingerTypes()
        ];
    }

    /**
     * Get fingerprint statistics for an employee
     */
    public function getEmployeeFingerprintStats($employeeId, $biometricDataRepository)
    {
        try {
            $fingerprints = $biometricDataRepository->getByEmployee($employeeId, 'fingerprint');
            
            $stats = [
                'total_enrolled' => count($fingerprints),
                'by_finger_type' => [],
                'enrollment_dates' => []
            ];

            foreach ($fingerprints as $fp) {
                $fingerType = $fp['FINGER_TYPE'];
                if (!isset($stats['by_finger_type'][$fingerType])) {
                    $stats['by_finger_type'][$fingerType] = 0;
                }
                $stats['by_finger_type'][$fingerType]++;
                $stats['enrollment_dates'][] = $fp['CREATED_AT'];
            }

            return $stats;

        } catch (Exception $e) {
            return [
                'total_enrolled' => 0,
                'by_finger_type' => [],
                'enrollment_dates' => [],
                'error' => $e->getMessage()
            ];
        }
    }
}