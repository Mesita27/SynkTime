<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$session_id = $input['session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Session ID required']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Get session details
    $stmt = $conn->prepare("
        SELECT * FROM biometric_enrollment_sessions WHERE id = ?
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        throw new Exception('Enrollment session not found');
    }
    
    if ($session['session_status'] === 'completed') {
        throw new Exception('Enrollment session already completed');
    }
    
    if ($session['samples_collected'] < $session['required_samples']) {
        throw new Exception('Insufficient samples collected');
    }
    
    // Process the collected samples to create final biometric template
    $sessionData = json_decode($session['session_data'], true);
    $finalTemplate = createBiometricTemplate($sessionData, $session['biometric_type']);
    
    if (!$finalTemplate['success']) {
        throw new Exception('Failed to create biometric template: ' . $finalTemplate['error']);
    }
    
    // Check if employee already has this biometric type enrolled
    $stmt = $conn->prepare("
        SELECT id FROM employee_biometrics 
        WHERE employee_id = ? AND biometric_type = ? AND is_active = TRUE
    ");
    $stmt->execute([$session['employee_id'], $session['biometric_type']]);
    $existingBiometric = $stmt->fetch();
    
    if ($existingBiometric) {
        // Update existing biometric data
        $stmt = $conn->prepare("
            UPDATE employee_biometrics 
            SET biometric_data = ?, 
                quality_score = ?,
                last_updated = CURRENT_TIMESTAMP,
                device_info = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $finalTemplate['template'],
            $finalTemplate['quality'],
            json_encode(['enrollment_session' => $session_id]),
            $existingBiometric['id']
        ]);
        $biometric_id = $existingBiometric['id'];
    } else {
        // Insert new biometric data
        $stmt = $conn->prepare("
            INSERT INTO employee_biometrics 
            (employee_id, biometric_type, biometric_data, quality_score, device_info, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $session['employee_id'],
            $session['biometric_type'],
            $finalTemplate['template'],
            $finalTemplate['quality'],
            json_encode(['enrollment_session' => $session_id]),
            $session['created_by']
        ]);
        $biometric_id = $conn->lastInsertId();
    }
    
    // Update session status
    $stmt = $conn->prepare("
        UPDATE biometric_enrollment_sessions 
        SET session_status = 'completed', completed_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$session_id]);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Biometric enrollment completed successfully',
        'quality_score' => $finalTemplate['quality'],
        'biometric_id' => $biometric_id
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error completing enrollment: ' . $e->getMessage()
    ]);
}

function createBiometricTemplate($samples, $biometricType) {
    try {
        if (empty($samples)) {
            return ['success' => false, 'error' => 'No samples provided'];
        }
        
        if ($biometricType === 'fingerprint') {
            // For fingerprint, we'll use the highest quality sample as the template
            $bestSample = null;
            $bestQuality = 0;
            
            foreach ($samples as $sample) {
                if ($sample['quality'] > $bestQuality) {
                    $bestQuality = $sample['quality'];
                    $bestSample = $sample;
                }
            }
            
            if (!$bestSample) {
                return ['success' => false, 'error' => 'No valid fingerprint samples found'];
            }
            
            return [
                'success' => true,
                'template' => $bestSample['data'],
                'quality' => $bestQuality
            ];
            
        } elseif ($biometricType === 'facial') {
            // For facial recognition, we'll average the descriptors for better accuracy
            $descriptors = [];
            $totalQuality = 0;
            
            foreach ($samples as $sample) {
                // Decrypt and decode the descriptor
                $decrypted = openssl_decrypt(
                    base64_decode($sample['data']), 
                    'AES-256-CBC', 
                    'biometric_key_' . date('Y'), 
                    0, 
                    substr(md5('synktime_biometric'), 0, 16)
                );
                
                $descriptor = json_decode($decrypted, true);
                if (is_array($descriptor) && count($descriptor) === 128) {
                    $descriptors[] = $descriptor;
                    $totalQuality += $sample['quality'];
                }
            }
            
            if (empty($descriptors)) {
                return ['success' => false, 'error' => 'No valid facial descriptors found'];
            }
            
            // Average the descriptors
            $avgDescriptor = array_fill(0, 128, 0);
            foreach ($descriptors as $descriptor) {
                for ($i = 0; $i < 128; $i++) {
                    $avgDescriptor[$i] += $descriptor[$i];
                }
            }
            
            for ($i = 0; $i < 128; $i++) {
                $avgDescriptor[$i] /= count($descriptors);
            }
            
            // Encrypt the averaged descriptor
            $encryptedTemplate = base64_encode(openssl_encrypt(
                json_encode($avgDescriptor), 
                'AES-256-CBC', 
                'biometric_key_' . date('Y'), 
                0, 
                substr(md5('synktime_biometric'), 0, 16)
            ));
            
            $avgQuality = $totalQuality / count($descriptors);
            
            return [
                'success' => true,
                'template' => $encryptedTemplate,
                'quality' => $avgQuality
            ];
        }
        
        return ['success' => false, 'error' => 'Unsupported biometric type'];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Template creation error: ' . $e->getMessage()];
    }
}
?>