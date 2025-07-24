<?php
require_once __DIR__ . '/auth/session.php';
requireAuth();
?>
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
    <style>
    /* Mejora visual para el formulario de búsqueda, igual que empleados */
    .attendance-query-box {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 14px 2px rgba(43,125,233,0.07);
        padding: 1.5rem 1.2rem 1.2rem 1.2rem;
        margin-bottom: 1.3rem;
        display: flex;
        justify-content: flex-start;
        align-items: flex-end;
        gap: 1rem;
    }
    .attendance-query-form .query-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1.2rem;
        align-items: flex-end;
    }
    .attendance-query-form .form-group {
        display: flex;
        flex-direction: column;
        min-width: 130px;
    }
    .attendance-query-form .form-group label {
        color: var(--primary, #2B7DE9);
        font-weight: 500;
        margin-bottom: 0.2em;
        font-size: 0.98rem;
    }
    .attendance-query-form .form-group input,
    .attendance-query-form .form-group select {
        padding: 0.55rem 0.7rem;
        border: 1px solid var(--border, #E2E8F0);
        border-radius: 7px;
        font-size: 1rem;
        background: #f8fafc;
        width: 100%;
    }
    .attendance-query-form .form-group input:focus,
    .attendance-query-form .form-group select:focus {
        border: 1.5px solid var(--primary, #2B7DE9);
        background: #fff;
    }
    .attendance-query-form .query-btns {
        display: flex;
        gap: 0.6rem;
        align-items: center;
        margin-top: 1.2rem;
    }
    @media (max-width: 950px) {
        .attendance-query-form .query-row {
            flex-direction: column;
            gap: 0.7rem;
            align-items: stretch;
        }
        .attendance-query-form .form-group { min-width: unset; }
        .attendance-query-box { flex-direction: column; gap: 0.5rem; }
    }
    /* Ajustes para popups: */
    .modal-content-md { max-width: 700px !important; }
    .modal-content-sm { max-width: 400px !important; }
    .modal-close {
        position: absolute; right: 18px; top: 18px; background: none; border: none;
        font-size: 1.35rem; color: #8a8a8a; cursor: pointer;
    }
    .employee-popup-header { display: flex; align-items: center; gap: 1.5em; margin-bottom: 1em; }
    .employee-popup-header i { font-size: 2.2rem; color: #2B7DE9; background: #e6f0ff; border-radius: 50%; padding: 0.5em; }
    .employee-popup-details b { color: #2B7DE9; margin-right: 0.4em; }
    .reports-table .btn-icon { padding: 0.45em 0.6em; font-size: 1.1rem; border-radius: 7px; }
    .reports-table .btn-icon.btn-main { background: #e6f0ff; color: #2B7DE9; border: none; }
    .reports-table .btn-icon.btn-main:hover { background: #dbeafe; color: #1E5EBB; }
    .reports-table .btn-icon.btn-info { background: #e3fcec; color: #38b2ac; }
    .reports-table .btn-icon.btn-info:hover { background: #c6f6d5; color: #25896e; }
    </style>
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
            <section class="reports-filters" style="margin-bottom: 0.4rem;">
                <div class="filter-buttons">
                    <button type="button" class="btn-primary" id="btnToday"><i class="fas fa-calendar-day"></i> Día actual</button>
                    <button type="button" class="btn-primary" id="btnWeek"><i class="fas fa-calendar-week"></i> Semana actual</button>
                    <button type="button" class="btn-primary" id="btnMonth"><i class="fas fa-calendar"></i> Mes actual</button>
                </div>
                <form id="customRangeForm" class="custom-range-form" autocomplete="off" style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="margin-right:0.6em;">Rango personalizado:</label>
                    <input type="date" id="customStart" name="desde" style="width:120px;">
                    <span style="margin:0 0.3em;">a</span>
                    <input type="date" id="customEnd" name="hasta" style="width:120px;">
                    <button type="submit" class="btn-secondary" id="btnConsultar"><i class="fas fa-search"></i> Consultar</button>
                    <button type="button" class="btn-secondary" id="btnLimpiar"><i class="fas fa-redo"></i> Limpiar</button>
                </form>
                <button class="btn-success" id="btnExportXLS"><i class="fas fa-file-excel"></i> Exportar a .xls</button>
            </section>
            <div class="attendance-query-box">
                <form id="attendanceQueryForm" class="attendance-query-form" autocomplete="off" style="width:100%;">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="q_codigo">Código</label>
                            <input type="text" id="q_codigo" name="codigo" placeholder="Código empleado">
                        </div>
                        <div class="form-group">
                            <label for="q_nombre">Nombre</label>
                            <input type="text" id="q_nombre" name="nombre" placeholder="Nombre o apellido">
                        </div>
                        <div class="form-group">
                            <label for="q_sede">Sede</label>
                            <select id="q_sede" name="sede"><option value="">Todas</option></select>
                        </div>
                        <div class="form-group">
                            <label for="q_establecimiento">Establecimiento</label>
                            <select id="q_establecimiento" name="establecimiento"><option value="">Todos</option></select>
                        </div>
                        <div class="form-group">
                            <label for="q_estado">Estado Entrada</label>
                            <select id="q_estado" name="estado">
                                <option value="">Todos</option>
                                <option value="Puntual">Puntual</option>
                                <option value="Tardanza">Tardanza</option>
                                <option value="Temprano">Temprano</option>
                            </select>
                        </div>
                        <div class="form-group query-btns" style="margin-top:0;">
                            <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Consultar</button>
                            <button type="button" class="btn-secondary" id="btnClearAttendanceQuery"><i class="fas fa-redo"></i> Limpiar</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="reports-table-container">
                <table id="tablaReportes" class="reports-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Sede</th>
                            <th>Establecimiento</th>
                            <th>Fecha</th>
                            <th>Hora entrada</th>
                            <th>Estado entrada</th>
                            <th>Observación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="reportsTableBody">
                        <!-- Las filas se llenan dinámicamente por JS -->
                    </tbody>
                </table>
            </div>
            <?php include 'components/reports_modals.php'; ?>
        </main>
    </div>
</div>
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<script src="assets/js/layout.js"></script>
<script src="assets/js/reports.js"></script>
</body>
</html>