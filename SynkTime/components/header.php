<?php
// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener información del usuario si está logueado
$nombreUsuario = 'Usuario';
$nombreCompleto = 'Usuario';

if (isset($_SESSION['username'])) {
    $nombreUsuario = $_SESSION['username'];
    
    // Si tenemos el nombre completo en la sesión, usarlo
    if (isset($_SESSION['nombre_completo'])) {
        $nombreCompleto = $_SESSION['nombre_completo'];
    } else {
        // Si no, obtenerlo de la base de datos
        require_once 'dashboard-controller.php';
        $userInfo = getUsuarioInfo($_SESSION['username']);
        if ($userInfo) {
            $nombreCompleto = $userInfo['NOMBRE_COMPLETO'];
            $_SESSION['nombre_completo'] = $nombreCompleto; // Guardar en sesión para próximas consultas
        }
    }
}

// Establecer la zona horaria del servidor a Bogotá
date_default_timezone_set('America/Bogota');
?>
<header class="header">
    <div class="header-left">
        <button class="toggle-sidebar" id="toggleSidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="header-title">
            <?php
                // Detectar título dinámico según el archivo
                $titles = [
                    'dashboard.php' => 'Dashboard',
                    'employees.php' => 'Empleados',
                    'attendance.php' => 'Asistencias',
                    'schedules.php' => 'Horarios',
                    'reports.php' => 'Reportes',
                    'index.php' => 'Inicio'
                ];
                $file = basename($_SERVER['PHP_SELF']);
                echo isset($titles[$file]) ? $titles[$file] : 'SynkTime';
            ?>
        </h1>
    </div>
    <div class="header-right">
        <div class="system-info">
            <div class="datetime-display">
                <i class="fas fa-clock"></i>
                <span id="currentDateTime">Cargando...</span>
                <span class="timezone-label">Bogotá, COL</span>
            </div>
            <div class="user-dropdown">
                <button class="user-info" id="userMenuBtn" type="button">
                    <i class="fas fa-user"></i>
                    <span class="user-name" title="<?php echo htmlspecialchars($nombreCompleto); ?>">
                        <?php echo htmlspecialchars($nombreUsuario); ?>
                    </span>
                    <i class="fas fa-caret-down dropdown-arrow"></i>
                </button>
                <div class="user-menu" id="userMenu">
                    <div class="user-info-details">
                        <div class="user-full-name"><?php echo htmlspecialchars($nombreCompleto); ?></div>
                        <div class="user-role">
                            <?php echo isset($_SESSION['rol']) ? htmlspecialchars($_SESSION['rol']) : 'Usuario'; ?>
                        </div>
                        <div class="user-company">
                            <?php echo isset($_SESSION['empresa_nombre']) ? htmlspecialchars($_SESSION['empresa_nombre']) : ''; ?>
                        </div>
                    </div>
                    <hr class="user-menu-divider">
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
// Función para actualizar la hora de Bogotá en tiempo real usando JavaScript
function updateDateTime() {
    const now = new Date();
    // Opciones para la zona horaria de Bogotá
    const options = {
        timeZone: 'America/Bogota',
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit',
        hour12: false
    };
    // Formatear fecha y hora para Bogotá, CO
    const dateTimeString = now.toLocaleString('es-CO', options).replace(',', '');
    const dateTimeElement = document.getElementById('currentDateTime');
    if (dateTimeElement) {
        dateTimeElement.textContent = dateTimeString;
    }
}

// Actualizar inmediatamente y luego cada segundo
updateDateTime();
setInterval(updateDateTime, 1000);

// Funcionalidad del dropdown de usuario
document.addEventListener('DOMContentLoaded', function() {
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');
    
    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });
        
        // Cerrar el menú al hacer click fuera
        document.addEventListener('click', function(e) {
            if (!userMenuBtn.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        });
    }
});
</script>