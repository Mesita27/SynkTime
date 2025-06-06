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
            <?php include 'components/attendance_query.php'; ?>
            <div class="attendance-table-container">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Departamento</th>
                            <th>Sede</th>
                            <th>Fecha</th>
                            <th>Hora entrada</th>
                            <th>Hora salida</th>
                            <th>Estado entrada</th>
                            <th>Estado salida</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <!-- JS: aquí se cargan las asistencias -->
                    </tbody>
                </table>
            </div>
            <?php include 'components/attendance_modals.php'; ?>
        </main>
    </div>
</div>
<!-- IMPORTANTE: primero employee.js, luego attendance.js -->
<script src="assets/js/employee.js"></script>
<script src="assets/js/attendance.js"></script>
<script src="assets/js/layout.js"></script>
</body>
</html>