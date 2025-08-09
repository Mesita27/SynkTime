<?php
/**
 * Configuración del Sistema Biométrico SynkTime
 * Configuration for SynkTime Biometric System
 */

return [
    // API URLs for biometric services
    'FACE_API_BASE' => 'http://localhost:18081', // InsightFace-REST
    'FINGER_API_BASE' => 'http://localhost:18082', // SourceAFIS HTTP
    
    // Matching thresholds
    'FACE_MATCH_THRESHOLD' => 0.42,
    'FINGER_MATCH_THRESHOLD' => 40,
    
    // Upload directories
    'UPLOAD_DIRS' => [
        'asistencia' => 'uploads/asistencia/',
        'biometrics' => 'uploads/biometrics/'
    ],
    
    // Asset paths
    'PLACEHOLDER_FINGERPRINT' => 'assets/img/placeholder_fingerprint.png',
    
    // API timeouts (in seconds)
    'API_TIMEOUT' => 30,
    'API_CONNECT_TIMEOUT' => 10,
    
    // Image processing settings
    'MAX_IMAGE_SIZE' => 5 * 1024 * 1024, // 5MB
    'ALLOWED_IMAGE_TYPES' => ['image/jpeg', 'image/png', 'image/webp'],
    'JPEG_QUALITY' => 85,
    
    // Face recognition settings
    'FACE_MAX_IMAGES' => 5, // Maximum images for enrollment
    'FACE_MIN_IMAGES' => 1, // Minimum images for enrollment
    
    // Fingerprint settings
    'FINGER_TYPES' => [
        'thumb_right' => 'Pulgar Derecho',
        'thumb_left' => 'Pulgar Izquierdo',
        'index_right' => 'Índice Derecho',
        'index_left' => 'Índice Izquierdo',
        'middle_right' => 'Medio Derecho',
        'middle_left' => 'Medio Izquierdo',
        'ring_right' => 'Anular Derecho',
        'ring_left' => 'Anular Izquierdo',
        'pinky_right' => 'Meñique Derecho',
        'pinky_left' => 'Meñique Izquierdo'
    ],
    
    // Security settings
    'ENABLE_CSRF_PROTECTION' => true,
    'MAX_LOGIN_ATTEMPTS' => 5,
    'LOGIN_ATTEMPT_TIMEOUT' => 900, // 15 minutes
    
    // Logging
    'ENABLE_BIOMETRIC_LOGGING' => true,
    'LOG_RETENTION_DAYS' => 90,
    
    // Feature flags
    'FEATURES' => [
        'face_enrollment' => true,
        'face_verification' => true,
        'fingerprint_enrollment' => true,
        'fingerprint_verification' => true,
        'traditional_capture' => true,
        'multi_device_support' => true
    ]
];