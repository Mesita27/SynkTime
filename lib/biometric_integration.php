<?php
/*
 * Sample integration file showing how to connect biometric verification
 * with the existing SynkTime attendance registration system.
 * 
 * This file demonstrates the integration pattern but should be adapted
 * to your specific attendance registration workflow.
 */

require_once __DIR__ . '/../lib/db.php';

/**
 * Enhanced attendance registration with biometric verification support
 */
function register_attendance_with_biometric($employeeId, $verification_method = 'traditional', $biometric_event_id = null) {
    $pdo = db();
    $pdo->beginTransaction();
    
    try {
        // 1. Register attendance using existing system logic
        // (Replace this with your actual attendance registration code)
        $attendanceData = [
            'employee_id' => $employeeId,
            'date' => date('Y-m-d'),
            'type' => 'ENTRADA', // or your attendance type logic
            'time' => date('H:i'),
            'verification_method' => $verification_method,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Insert into your attendance table (adjust table/field names as needed)
        $stmt = $pdo->prepare("INSERT INTO asistencia (ID_EMPLEADO, FECHA, TIPO, HORA, VERIFICATION_METHOD, CREATED_AT) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $attendanceData['employee_id'],
            $attendanceData['date'], 
            $attendanceData['type'],
            $attendanceData['time'],
            $attendanceData['verification_method'],
            $attendanceData['created_at']
        ]);
        
        $attendanceId = $pdo->lastInsertId();
        
        // 2. Link biometric event to attendance record if provided
        if ($biometric_event_id) {
            $stmt = $pdo->prepare("UPDATE biometric_event SET attendance_id = ? WHERE id = ?");
            $stmt->execute([$attendanceId, $biometric_event_id]);
        }
        
        $pdo->commit();
        return $attendanceId;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Get employee biometric enrollment status
 */
function get_employee_biometric_status($employeeId) {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT face_subject_id, fingerprint_id, created_at, updated_at 
                           FROM biometric_identity WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $result = $stmt->fetch();
    
    if (!$result) {
        return [
            'enrolled' => false,
            'face_enrolled' => false,
            'fingerprint_enrolled' => false
        ];
    }
    
    return [
        'enrolled' => true,
        'face_enrolled' => !empty($result['face_subject_id']),
        'fingerprint_enrolled' => !empty($result['fingerprint_id']),
        'enrollment_date' => $result['created_at'],
        'last_update' => $result['updated_at']
    ];
}

/**
 * Get biometric attendance statistics for an employee
 */
function get_employee_biometric_stats($employeeId, $days = 30) {
    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT 
            type,
            COUNT(*) as count,
            AVG(score) as avg_score
        FROM biometric_event 
        WHERE employee_id = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY type
    ");
    $stmt->execute([$employeeId, $days]);
    
    $stats = [];
    while ($row = $stmt->fetch()) {
        $stats[$row['type']] = [
            'count' => (int)$row['count'],
            'avg_score' => $row['avg_score'] ? round((float)$row['avg_score'], 3) : null
        ];
    }
    
    return $stats;
}

/**
 * Enhanced version of the mark_attendance.php logic with better integration
 */
function process_biometric_attendance($employeeId, $channel, $score = null, $imageData = null, $providerRef = null) {
    $config = require __DIR__ . '/../config/biometrics.php';
    $pdo = db();
    
    try {
        $pdo->beginTransaction();
        
        // 1. Save photo if provided
        $imagePath = null;
        if ($imageData) {
            $photosDir = $config['storage']['photos_dir'];
            if (!is_dir($photosDir)) mkdir($photosDir, 0775, true);
            
            $data = preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
            $bin = base64_decode($data);
            $name = 'photo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.png';
            $imagePath = rtrim($photosDir, '/') . '/' . $name;
            file_put_contents($imagePath, $bin);
        }
        
        // 2. Record biometric event
        $stmt = $pdo->prepare("INSERT INTO biometric_event (employee_id, type, score, image_path, provider_ref, created_at)
                               VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$employeeId, $channel, $score, $imagePath, $providerRef]);
        $biometric_event_id = $pdo->lastInsertId();
        
        // 3. Register attendance with biometric linkage
        $attendanceId = register_attendance_with_biometric($employeeId, $channel, $biometric_event_id);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'attendance_id' => $attendanceId,
            'biometric_event_id' => $biometric_event_id,
            'image_path' => $imagePath
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}