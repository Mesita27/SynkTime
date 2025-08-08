<?php
/**
 * SynkTime Application Configuration
 * Main configuration file for the application
 */

// Database Configuration (imported from database.php)
require_once __DIR__ . '/database.php';

// Biometric Configuration
require_once __DIR__ . '/biometrics.php';

// Application Settings
define('APP_NAME', 'SynkTime');
define('APP_VERSION', '2.0.0');
define('APP_TIMEZONE', 'America/Bogota');

// Security Settings
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('SESSION_TIMEOUT', 28800); // 8 hours

// File Upload Settings
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('TEMP_DIR', '/tmp');

// Default timezone
date_default_timezone_set(APP_TIMEZONE);

?>