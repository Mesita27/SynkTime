<?php
/**
 * Biometric Verification Service
 * Handles comparison of biometric data for authentication
 */

class BiometricVerificationService {
    
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Verify fingerprint against stored data
     */
    public function verifyFingerprint($employee_id, $fingerprint_data, $finger_type = null) {
        try {
            // Get stored fingerprint data for the employee
            $sql = "
                SELECT bd.BIOMETRIC_DATA, bd.FINGER_TYPE 
                FROM biometric_data bd 
                WHERE bd.ID_EMPLEADO = ? 
                AND bd.BIOMETRIC_TYPE = 'fingerprint' 
                AND bd.ACTIVO = 1
            ";
            
            $params = [$employee_id];
            
            if ($finger_type) {
                $sql .= " AND bd.FINGER_TYPE = ?";
                $params[] = $finger_type;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $stored_prints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($stored_prints)) {
                return [
                    'success' => false,
                    'message' => 'No hay huellas registradas para este empleado',
                    'confidence' => 0
                ];
            }
            
            // Compare with each stored fingerprint
            $best_match = 0;
            $matched_finger = null;
            
            foreach ($stored_prints as $stored_print) {
                $similarity = $this->compareFingerprintData(
                    $fingerprint_data, 
                    $stored_print['BIOMETRIC_DATA']
                );
                
                if ($similarity > $best_match) {
                    $best_match = $similarity;
                    $matched_finger = $stored_print['FINGER_TYPE'];
                }
            }
            
            // Threshold for fingerprint verification (70% similarity)
            $threshold = 0.70;
            $verified = $best_match >= $threshold;
            
            return [
                'success' => $verified,
                'confidence' => $best_match,
                'matched_finger' => $matched_finger,
                'message' => $verified ? 
                    'Huella verificada correctamente' : 
                    'Huella no reconocida (confianza: ' . round($best_match * 100, 1) . '%)'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar huella: ' . $e->getMessage(),
                'confidence' => 0
            ];
        }
    }
    
    /**
     * Verify facial recognition against stored data
     */
    public function verifyFacial($employee_id, $image_data) {
        try {
            // Get stored facial data for the employee
            $stmt = $this->conn->prepare("
                SELECT bd.BIOMETRIC_DATA 
                FROM biometric_data bd 
                WHERE bd.ID_EMPLEADO = ? 
                AND bd.BIOMETRIC_TYPE = 'facial' 
                AND bd.ACTIVO = 1
            ");
            $stmt->execute([$employee_id]);
            $stored_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$stored_data) {
                return [
                    'success' => false,
                    'message' => 'No hay patrón facial registrado para este empleado',
                    'confidence' => 0
                ];
            }
            
            $facial_info = json_decode($stored_data['BIOMETRIC_DATA'], true);
            if (!$facial_info || !isset($facial_info['images'])) {
                return [
                    'success' => false,
                    'message' => 'Datos faciales corruptos',
                    'confidence' => 0
                ];
            }
            
            // Compare with stored facial images
            $best_match = 0;
            $upload_dir = '../../uploads/facial/';
            
            foreach ($facial_info['images'] as $stored_image_filename) {
                $stored_image_path = $upload_dir . $stored_image_filename;
                
                if (file_exists($stored_image_path)) {
                    $similarity = $this->compareFacialData($image_data, $stored_image_path);
                    $best_match = max($best_match, $similarity);
                }
            }
            
            // Threshold for facial verification (75% similarity)
            $threshold = 0.75;
            $verified = $best_match >= $threshold;
            
            return [
                'success' => $verified,
                'confidence' => $best_match,
                'message' => $verified ? 
                    'Rostro verificado correctamente' : 
                    'Rostro no reconocido (confianza: ' . round($best_match * 100, 1) . '%)'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar rostro: ' . $e->getMessage(),
                'confidence' => 0
            ];
        }
    }
    
    /**
     * Compare two fingerprint data strings
     * This is a simplified comparison - in production, use proper minutiae comparison
     */
    private function compareFingerprintData($data1, $data2) {
        // For demonstration, we'll use a basic comparison
        // In production, this should use proper fingerprint minutiae comparison algorithms
        
        if (empty($data1) || empty($data2)) {
            return 0;
        }
        
        // Remove data URI prefixes if present
        $clean_data1 = $this->cleanBiometricData($data1);
        $clean_data2 = $this->cleanBiometricData($data2);
        
        // Basic similarity using Levenshtein distance
        $max_len = max(strlen($clean_data1), strlen($clean_data2));
        if ($max_len === 0) return 0;
        
        $distance = levenshtein(
            substr($clean_data1, 0, 1000), // Limit length for performance
            substr($clean_data2, 0, 1000)
        );
        
        $similarity = 1 - ($distance / min(1000, $max_len));
        
        // Add some randomization to simulate real-world variation
        $randomFactor = 0.9 + (mt_rand() / mt_getrandmax()) * 0.1; // 90-100%
        
        return max(0, min(1, $similarity * $randomFactor));
    }
    
    /**
     * Compare facial images using basic image analysis
     */
    private function compareFacialData($new_image_data, $stored_image_path) {
        try {
            // Save the new image temporarily for comparison
            $temp_dir = '../../uploads/temp/';
            if (!file_exists($temp_dir)) {
                mkdir($temp_dir, 0777, true);
            }
            
            $temp_filename = 'temp_' . uniqid() . '.jpg';
            $temp_path = $temp_dir . $temp_filename;
            
            // Clean and save the image
            $clean_data = $this->cleanBiometricData($new_image_data);
            $image_binary = base64_decode($clean_data);
            
            if ($image_binary === false) {
                return 0;
            }
            
            file_put_contents($temp_path, $image_binary);
            
            // Perform image comparison
            $similarity = $this->compareImages($temp_path, $stored_image_path);
            
            // Clean up temporary file
            if (file_exists($temp_path)) {
                unlink($temp_path);
            }
            
            return $similarity;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Compare two images using basic analysis
     */
    private function compareImages($image1_path, $image2_path) {
        try {
            // Basic image comparison using file hash
            // In production, use proper facial recognition algorithms
            
            if (!file_exists($image1_path) || !file_exists($image2_path)) {
                return 0;
            }
            
            // Get image dimensions and basic properties
            $info1 = getimagesize($image1_path);
            $info2 = getimagesize($image2_path);
            
            if (!$info1 || !$info2) {
                return 0;
            }
            
            // Basic file hash comparison
            $hash1 = hash_file('md5', $image1_path);
            $hash2 = hash_file('md5', $image2_path);
            
            if ($hash1 === $hash2) {
                return 1.0; // Exact match
            }
            
            // Simulate facial recognition analysis
            // In production, integrate with facial recognition APIs
            $base_similarity = 0.6 + (mt_rand() / mt_getrandmax()) * 0.3; // 60-90%
            
            // Factor in image sizes (similar sizes = higher similarity)
            $size_factor = 1 - abs($info1[0] * $info1[1] - $info2[0] * $info2[1]) / 
                          max($info1[0] * $info1[1], $info2[0] * $info2[1]) * 0.2;
            
            return max(0, min(1, $base_similarity * $size_factor));
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Clean biometric data by removing data URI prefixes
     */
    private function cleanBiometricData($data) {
        // Remove common data URI prefixes
        $prefixes = [
            'data:image/jpeg;base64,',
            'data:image/png;base64,',
            'data:application/octet-stream;base64,',
            'data:text/plain;base64,'
        ];
        
        foreach ($prefixes as $prefix) {
            if (strpos($data, $prefix) === 0) {
                return substr($data, strlen($prefix));
            }
        }
        
        return $data;
    }
    
    /**
     * Log verification attempt
     */
    public function logVerificationAttempt($employee_id, $method, $success, $confidence = null) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO biometric_logs (
                    ID_EMPLEADO,
                    VERIFICATION_METHOD,
                    VERIFICATION_SUCCESS,
                    FECHA,
                    HORA,
                    CREATED_AT
                ) VALUES (?, ?, ?, CURDATE(), CURTIME(), NOW())
            ");
            
            return $stmt->execute([
                $employee_id,
                $method,
                $success ? 1 : 0
            ]);
            
        } catch (Exception $e) {
            return false;
        }
    }
}
?>