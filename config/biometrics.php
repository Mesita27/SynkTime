<?php
/**
 * Biometrics Configuration
 * Configuration for biometric API endpoints and thresholds
 */

// API Endpoints
define('BIOMETRICS_FACE_API_BASE', 'http://localhost:18081');
define('BIOMETRICS_FINGER_API_BASE', 'http://localhost:18082');

// Verification Thresholds
define('FACE_MATCH_THRESHOLD', 0.42);  // ArcFace similarity threshold
define('FINGER_MATCH_THRESHOLD', 40);  // SourceAFIS score threshold

// File Storage Paths
define('ATTENDANCE_UPLOAD_PATH', 'public/uploads/asistencia/');
define('BIOMETRIC_UPLOAD_PATH', 'public/uploads/biometrics/');
define('FINGERPRINT_PLACEHOLDER', 'assets/img/placeholder_fingerprint.png');

// API Timeouts (seconds)
define('BIOMETRIC_API_TIMEOUT', 30);
define('BIOMETRIC_CONNECT_TIMEOUT', 10);

// Photo Settings
define('MAX_PHOTO_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_PHOTO_TYPES', ['image/jpeg', 'image/jpg', 'image/png']);

// Enrollment Settings
define('MIN_FACIAL_IMAGES', 1);
define('MAX_FACIAL_IMAGES', 3);
define('SUPPORTED_FINGER_TYPES', [
    'left_thumb', 'left_index', 'left_middle', 'left_ring', 'left_pinky',
    'right_thumb', 'right_index', 'right_middle', 'right_ring', 'right_pinky'
]);

?>