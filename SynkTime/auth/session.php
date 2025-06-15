<?php
// Iniciar sesión si no está iniciada
function initSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Verificar si el usuario está autenticado
function isAuthenticated() {
    initSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Obtener información del usuario actual
function getCurrentUser() {
    initSession();
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'nombre' => $_SESSION['nombre_completo'],
        'email' => $_SESSION['email'],
        'rol' => $_SESSION['rol'],
        'empresa' => $_SESSION['id_empresa']
    ];
}

// Requerir autenticación (redirige si no está autenticado)
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: /login.php');
        exit;
    }
    return getCurrentUser();
}

// Verificar si el usuario tiene un rol específico
function hasRole($roles) {
    initSession();
    if (!isAuthenticated()) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['rol'], $roles);
    }
    
    return $_SESSION['rol'] === $roles;
}

// Registrar actividad en la tabla LOG
function logActivity($action, $details = '') {
    if (!isAuthenticated()) {
        return false;
    }
    
    try {
        require_once __DIR__ . '/../config/database.php';
        $conn = getConnection();
        $stmt = $conn->prepare("
            INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE) 
            VALUES (:userId, :action, :details)
        ");
        
        $stmt->bindParam(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':action', $action, PDO::PARAM_STR);
        $stmt->bindParam(':details', $details, PDO::PARAM_STR);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("Error registrando actividad: " . $e->getMessage());
        return false;
    }
}
?>