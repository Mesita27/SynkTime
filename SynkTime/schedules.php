<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horarios | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
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
            <div class="schedule-header">
                <h2 class="page-title"><i class="fas fa-clock"></i> Horarios</h2>
                <div class="schedule-actions">
                    <button class="btn-primary" id="btnAddSchedule"><i class="fas fa-plus"></i> Registrar horario</button>
                    <button class="btn-secondary" id="btnExportXLS"><i class="fas fa-file-excel"></i> Exportar .xls</button>
                </div>
            </div>
            <div class="schedule-table-container">
                <form id="scheduleQueryForm" class="schedule-query-form" autocomplete="off" style="margin-bottom: 1.5rem;">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="q_empleado">Empleado</label>
                            <input type="text" id="q_empleado" name="empleado" placeholder="Nombre o Apellido">
                        </div>
                        <div class="form-group">
                            <label for="q_departamento">Departamento</label>
                            <input type="text" id="q_departamento" name="departamento" placeholder="Departamento">
                        </div>
                        <div class="form-group">
                            <label for="q_sede">Sede</label>
                            <input type="text" id="q_sede" name="sede" placeholder="Sede">
                        </div>
                        <div class="form-group">
                            <label for="q_dia">Día</label>
                            <select id="q_dia" name="dia">
                                <option value="">Todos</option>
                                <option value="Lunes">Lunes</option>
                                <option value="Martes">Martes</option>
                                <option value="Miércoles">Miércoles</option>
                                <option value="Jueves">Jueves</option>
                                <option value="Viernes">Viernes</option>
                                <option value="Sábado">Sábado</option>
                                <option value="Domingo">Domingo</option>
                            </select>
                        </div>
                        <div class="form-group query-btns">
                            <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Consultar</button>
                            <button type="button" class="btn-secondary"><i class="fas fa-redo"></i> Limpiar</button>
                        </div>
                    </div>
                </form>
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Sede</th>
                            <th>Día</th>
                            <th>Hora entrada</th>
                            <th>Hora salida</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody">
                        <!-- Las filas de horarios se insertan dinámicamente con JS -->
                    </tbody>
                </table>
            </div>
            <?php include 'components/schedule_modals.php'; ?>
        </main>
    </div>
</div>
<!-- SheetJS para exportar a XLS -->
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script src="assets/js/layout.js"></script>
<script src="assets/js/schedule.js"></script>
</body>
</html>