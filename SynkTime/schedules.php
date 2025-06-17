<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horarios | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/schedule.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <!-- HORARIOS -->
            <div class="schedule-header">
                <h2 class="page-title"><i class="fas fa-clock"></i> Horarios</h2>
                <div class="schedule-actions">
                    <button class="btn-primary" id="btnAddSchedule"><i class="fas fa-plus"></i> Registrar horario</button>
                    <button class="btn-secondary" id="btnExportXLS"><i class="fas fa-file-excel"></i> Exportar .xls</button>
                </div>
            </div>
            <div class="schedule-table-container">
                <?php include 'components/schedule_search_form.php'; ?>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Sede</th>
                            <th>Establecimiento</th>
                            <th>Días</th>
                            <th>Hora entrada</th>
                            <th>Hora salida</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody"></tbody>
                </table>
            </div>
            <?php include 'components/schedule_modals.php'; ?>

            <!-- VINCULAR EMPLEADOS Y HORARIOS -->
            <div class="schedule-header" style="margin-top: 3rem;">
                <h2 class="page-title"><i class="fas fa-link"></i> Vinculación empleados y horarios</h2>
            </div>
            <div class="schedule-table-container">
                <?php include 'components/schedule_employee_linker_form.php'; ?>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Identificación</th>
                            <th>Sede</th>
                            <th>Establecimiento</th>
                            <th>Horarios asignados</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="employeeScheduleTableBody"></tbody>
                </table>
            </div>
            <?php include 'components/schedule_employee_link_modals.php'; ?>
        </main>
    </div>
</div>
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script src="assets/js/layout.js"></script>
<script src="assets/js/schedule.js"></script>
<script src="assets/js/schedule-employee-link.js"></script>
</body>
</html>