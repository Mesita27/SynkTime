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
                <span id="currentDateTime">2025-05-10 01:42:47</span>
                <span class="utc-label">UTC</span>
            </div>
            <div class="user-dropdown">
                <button class="user-info" id="userMenuBtn" type="button">
                    <i class="fas fa-user"></i>
                    <span class="user-name">
                        <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuario'; ?>
                    </span>
                    <i class="fas fa-caret-down dropdown-arrow"></i>
                </button>
                <div class="user-menu" id="userMenu">
                    <a href="/SynkTime/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                          Cerrar sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>