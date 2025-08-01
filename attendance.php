<?php
require_once 'auth/session.php';
require_once 'auth/authorization.php';
requirePageAccess(); // Esto reemplaza requireAuth() y agrega verificación de rol
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
    <link rel="stylesheet" href="assets/css/pagination.css">
    <link rel="stylesheet" href="assets/css/attendance-pagination.css">
    <link rel="stylesheet" href="assets/css/biometric.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="attendance-header">
                <h2 class="page-title">
                    <i class="fas fa-calendar-check"></i> 
                    <?php echo isAttendanceUser() ? 'Registro de Asistencia - Hoy' : 'Asistencias'; ?>
                </h2>
                <button type="button" class="btn-primary" onclick="openAttendanceRegisterModal()">
                    <i class="fas fa-plus"></i> Registrar Asistencia
                </button>
            </div>
            
            <?php if (isOwnerManager()): ?>
            <!-- Formulario de búsqueda estilizado - Solo para gerentes -->
            <div class="attendance-query-box">
                <form id="filtrosForm" class="attendance-query-form" autocomplete="off">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="filtro_sede">Sede</label>
                            <select id="filtro_sede" name="sede" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_establecimiento">Establecimiento</label>
                            <select id="filtro_establecimiento" name="establecimiento" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label for="codigoBusqueda">Código Empleado</label>
                            <input type="text" id="codigoBusqueda" name="codigo" class="form-control" placeholder="Ingrese código">
                        </div>
                        <div class="form-group query-btns">
                            <button type="button" id="btnBuscarCodigo" class="btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <button type="button" id="btnLimpiar" class="btn-secondary">
                                <i class="fas fa-redo"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <?php elseif (isAttendanceUser()): ?>
            <!-- Información para usuarios de asistencia -->
            <div class="attendance-info-box">
                <div class="info-message">
                    <i class="fas fa-info-circle"></i>
                    <span>Mostrando únicamente las asistencias registradas el día de hoy: <?php echo date('d/m/Y'); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Los controles de paginación se insertan aquí automáticamente -->
            
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
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <!-- JS: aquí se cargan las asistencias -->
                    </tbody>
                </table>
            </div>
            
            <?php include 'components/attendance_register_modal.php'; ?>
            <?php include 'components/attendance_photo_modal.php'; ?>
            <?php include 'components/attendance_observation_modal.php'; ?>
            <?php include 'components/biometric_modals.php'; ?>
        </main>
    </div>
</div>
<script>
// Variables globales para el rol del usuario
window.userRole = '<?php echo $_SESSION['rol'] ?? ''; ?>';
window.isOwnerManager = <?php echo isOwnerManager() ? 'true' : 'false'; ?>;
window.isAttendanceUser = <?php echo isAttendanceUser() ? 'true' : 'false'; ?>;
</script>
<script src="assets/js/layout.js"></script>
<script src="assets/js/attendance.js"></script>
</body>
</div>
<script src="assets/js/layout.js"></script>
<script src="assets/js/attendance.js"></script>
<script src="assets/js/biometric.js"></script>
</body>
</html>