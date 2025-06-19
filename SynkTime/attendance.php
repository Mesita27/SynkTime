<?php
require_once 'auth/session.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencias | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/attendance.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="attendance-header">
                <h2 class="page-title"><i class="fas fa-calendar-check"></i> Asistencias</h2>
                <!-- Botón para abrir el modal de registrar asistencia -->
                <button type="button" class="btn-primary" onclick="openAttendanceRegisterModal()" style="margin-left:1rem;">
                    <i class="fas fa-plus"></i> Registrar Asistencia
                </button>
            </div>
            <!-- Filtros de sede y establecimiento y búsqueda por código -->
            <div class="attendance-filters" style="display:flex;gap:1em;flex-wrap:wrap;margin-bottom:1em;">
                <div>
                    <label for="filtro_sede">Sede:</label>
                    <select id="filtro_sede"></select>
                </div>
                <div>
                    <label for="filtro_establecimiento">Establecimiento:</label>
                    <select id="filtro_establecimiento"></select>
                </div>
                <div style="align-self:flex-end;">
                    <input type="text" id="codigoBusqueda" class="filter-input" placeholder="Buscar por código de empleado...">
                    <button id="btnBuscarCodigo" class="btn-primary" style="margin-left:.5em;">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
            <!-- Tabla de asistencias -->
            <div class="attendance-table-container">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Establecimiento</th>
                            <th>Sede</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Estado</th>
                            <th>Foto</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <!-- JS: aquí se cargan las asistencias -->
                    </tbody>
                </table>
            </div>
            <?php include 'components/attendance_register_modal.php'; ?>
            <?php include 'components/attendance_photo_modal.php'; ?>
        </main>
    </div>
</div>
<script src="assets/js/attendance.js"></script>
<script src="assets/js/layout.js"></script>
</body>
</html>