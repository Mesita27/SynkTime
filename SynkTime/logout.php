<?php
// Iniciar sesión
session_start();

// Registrar cierre de sesión si es posible
if (isset($_SESSION['user_id'])) {
    try {
        require_once 'config/database.php';
        
        // Solo intentar registrar si podemos obtener conexión
        $conn = @getConnection(); // El @ suprime errores
        
        if ($conn) {
            $stmt = $conn->prepare("
                INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) 
                VALUES (:userId, 'LOGOUT', :details)
            ");
            
            $stmt->bindParam(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
            $details = 'Cierre de sesión desde IP: ' . $_SERVER['REMOTE_ADDR'];
            $stmt->bindParam(':details', $details, PDO::PARAM_STR);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Ignorar errores durante el logout
    }
}

// Limpiar y destruir sesión
$_SESSION = array();
session_destroy();

// Redirigir al login
header('Location: login.php');
exit;
?>