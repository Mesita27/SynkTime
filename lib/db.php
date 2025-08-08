<?php
// Intenta reutilizar la conexión existente del proyecto, si no existe crea una nueva.
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    // Intentar usar la conexión existente del proyecto
    if (file_exists(__DIR__ . '/../config/database.php')) {
        require_once __DIR__ . '/../config/database.php';
        if (isset($conn) && $conn instanceof PDO) {
            $pdo = $conn;
            return $pdo;
        }
    }

    // Fallback: crear nueva conexión
    $host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
    $db   = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'synktime');
    $user = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
    $pass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}