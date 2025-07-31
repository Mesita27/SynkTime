<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="assets/img/synktime-logo.png" alt="SynkTime">
            <span class="logo-text">SynkTime</span>
        </div>
    </div>
    
    <nav class="nav-menu">
        <div class="nav-section">
            <div class="nav-section-title">Principal</div>
            <ul class="nav-items">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'dashboard.php') echo ' active'; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">Gestión</div>
            <ul class="nav-items">
                <li class="nav-item">
                    <a href="employee.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'employee.php') echo ' active'; ?>">
                        <i class="fas fa-users"></i>
                        <span>Empleados</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'attendance.php') echo ' active'; ?>">
                        <i class="fas fa-clock"></i>
                        <span>Asistencias</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="schedules.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'schedules.php') echo ' active'; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Horarios</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'reports.php') echo ' active'; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Reportes</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>