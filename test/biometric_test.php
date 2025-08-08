<?php
// Simple test for biometric API structure

// Test configuration
echo "Testing biometric configuration...\n";
$config = require __DIR__ . '/../config/biometrics.php';
if (isset($config['face_api']) && isset($config['fingerprint_api'])) {
    echo "✓ Configuration loaded successfully\n";
} else {
    echo "✗ Configuration incomplete\n";
}

// Test HTTP utilities
echo "\nTesting HTTP utilities...\n";
require_once __DIR__ . '/../lib/http.php';
if (function_exists('http_post_json') && function_exists('http_post_multipart')) {
    echo "✓ HTTP functions available\n";
} else {
    echo "✗ HTTP functions missing\n";
}

// Test biometric clients (without actual API calls)
echo "\nTesting biometric clients...\n";
require_once __DIR__ . '/../lib/Biometrics/FaceClient.php';
require_once __DIR__ . '/../lib/Biometrics/FingerprintClient.php';

try {
    $faceClient = new FaceClient($config['face_api']);
    echo "✓ FaceClient created successfully\n";
} catch (Exception $e) {
    echo "✗ FaceClient error: " . $e->getMessage() . "\n";
}

try {
    $fpClient = new FingerprintClient($config['fingerprint_api']);
    echo "✓ FingerprintClient created successfully\n";
} catch (Exception $e) {
    echo "✗ FingerprintClient error: " . $e->getMessage() . "\n";
}

// Test file structure
echo "\nTesting file structure...\n";
$requiredFiles = [
    '../api/biometrics/enroll_face.php',
    '../api/biometrics/enroll_fingerprint.php',
    '../api/biometrics/recognize_face.php',
    '../api/biometrics/identify_fingerprint.php',
    '../api/biometrics/mark_attendance.php',
    '../api/biometrics/employee_status.php',
    '../views/biometrics/attendance.php',
    '../views/biometrics/enroll.php',
    '../public/js/biometrics.js',
    '../public/images/placeholders/fingerprint_placeholder.svg',
    '../lib/biometric_integration.php',
    '../biometric-enrollment.php',
    '../INTEGRATION_GUIDE.md'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

// Test database migration script
echo "\nTesting database migration...\n";
$migrationFile = __DIR__ . '/../scripts/db/migrations/20250808_biometrics.sql';
if (file_exists($migrationFile)) {
    $sql = file_get_contents($migrationFile);
    if (strpos($sql, 'biometric_identity') !== false && strpos($sql, 'biometric_event') !== false) {
        echo "✓ Migration script contains required tables\n";
    } else {
        echo "✗ Migration script incomplete\n";
    }
} else {
    echo "✗ Migration script missing\n";
}

echo "\nBiometric module test completed!\n";