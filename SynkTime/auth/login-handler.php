<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';

// Iniciar sesión
initSession();

// Verificar si es una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inicializar respuesta
    $response = [
        'success' => false,
        'message' => 'Error desconocido'
    ];
    
    // Verificar si se recibieron los datos necesarios
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        try {
            $conn = getConnection();
            
            // Consultar usuario por nombre de usuario
            $stmt = $conn->prepare("
                SELECT u.*, e.NOMBRE AS EMPRESA_NOMBRE
                FROM USUARIO u
                INNER JOIN EMPRESA e ON u.ID_EMPRESA = e.ID_EMPRESA
                WHERE u.USERNAME = :username AND u.ESTADO = 'A'
            ");
            
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar si el usuario existe y la contraseña coincide
            if ($user && $password === $user['CONTRASENA']) {
                // Guardar datos en sesión
                $_SESSION['user_id'] = $user['ID_USUARIO'];
                $_SESSION['username'] = $user['USERNAME'];
                $_SESSION['nombre_completo'] = $user['NOMBRE_COMPLETO'];
                $_SESSION['email'] = $user['EMAIL'];
                $_SESSION['rol'] = $user['ROL'];
                $_SESSION['id_empresa'] = $user['ID_EMPRESA'];
                $_SESSION['empresa_nombre'] = $user['EMPRESA_NOMBRE'];
                
                // Registrar login exitoso
                logActivity('LOGIN_EXITOSO', 'Inicio de sesión desde IP: ' . $_SERVER['REMOTE_ADDR']);
                
                // Preparar respuesta exitosa
                $response['success'] = true;
                $response['message'] = 'Login exitoso';
                $response['redirect'] = 'dashboard.php';
            } else {
                // Registrar intento fallido
                if ($user) {
                    // Si el usuario existe pero la contraseña es incorrecta
                    $userId = $user['ID_USUARIO'];
                    $conn->prepare("
                        INSERT INTO LOG (ID_USUARIO, ACCION, DETALLE)
                        VALUES (:userId, 'INTENTO_LOGIN_FALLIDO', 'Contraseña incorrecta')
                    ")->execute(['userId' => $userId]);
                }
                
                // Preparar respuesta de error
                $response['message'] = 'Usuario o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            $response['message'] = 'Error interno del servidor';
        }
    } else {
        $response['message'] = 'Datos incompletos';
    }
    
    // Devolver respuesta como JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Si no es POST, redirigir a la página de login
header('Location: ../login.php');
exit;
?>