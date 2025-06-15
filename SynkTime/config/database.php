<?php
// Configuración de la conexión a la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'synktime');
define('DB_USER', 'root'); // Cambiar por tu usuario de BD
define('DB_PASS', '');     // Cambiar por tu contraseña

// Función para obtener conexión a la base de datos
function getConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS
        );
        
        // Configurar PDO para lanzar excepciones en caso de errores
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        error_log("Error de conexión a la BD: " . $e->getMessage());
        die("Error: No se pudo conectar a la base de datos.");
    }
}
?>