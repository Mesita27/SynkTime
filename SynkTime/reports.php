<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/reports.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="reports-header">
                <h2 class="page-title"><i class="fas fa-file-alt"></i> Reportes de Asistencia</h2>
            </div>
            <section class="reports-filters">
                <div class="filter-buttons">
                    <button type="button" class="btn-primary" id="btnToday"><i class="fas fa-calendar-day"></i> Día actual</button>
                    <button type="button" class="btn-primary" id="btnWeek"><i class="fas fa-calendar-week"></i> Semana actual</button>
                    <button type="button" class="btn-primary" id="btnMonth"><i class="fas fa-calendar"></i> Mes actual</button>
                </div>
                <form id="customRangeForm" class="custom-range-form" autocomplete="off" style="display: flex; align-items: center; gap: 0.5rem;">
                    <label>Rango personalizado:</label>
                    <input type="date" id="customStart" required>
                    <span>a</span>
                    <input type="date" id="customEnd" required>
                    <button type="submit" class="btn-secondary" id="btnConsultar"><i class="fas fa-search"></i> Consultar</button>
                    <button type="button" class="btn-secondary" id="btnLimpiar"><i class="fas fa-redo"></i> Limpiar</button>
                </form>
                <button class="btn-success" id="btnExportXLS"><i class="fas fa-file-excel"></i> Exportar a .xls</button>
            </section>
            <div class="reports-table-container">
                <table id="tablaReportes" class="reports-table">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Sede</th>
                            <th>Fecha</th>
                            <th>Hora entrada</th>
                            <th>Hora salida</th>
                            <th>Estado entrada</th>
                            <th>Dispositivo</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTableBody">
                        <!-- Las filas se llenan dinámicamente por JS -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script src="assets/js/layout.js"></script>
<script src="assets/js/reports.js"></script>
</body>
</html>