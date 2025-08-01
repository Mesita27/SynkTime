<?php
require_once 'auth/session.php';
require_once 'auth/authorization.php';
requirePageAccess();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Asistencia | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/reports_query.css">
    <link rel="stylesheet" href="assets/css/reports_modals.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="reports-header">
                <h2 class="page-title"><i class="fas fa-file-alt"></i> Reportes de Asistencia</h2>
                <div class="reports-actions">
                    <button class="btn-primary" id="btnExportarXLS">
                        <i class="fas fa-file-excel"></i> Exportar a XLS
                    </button>
                </div>
            </div>
            
            <!-- Filtros rápidos -->
            <div class="quick-filters">
                <button id="btnDiaActual" class="btn-filter">
                    <i class="fas fa-calendar-day"></i> Día actual
                </button>
                <button id="btnSemanaActual" class="btn-filter">
                    <i class="fas fa-calendar-week"></i> Semana actual
                </button>
                <button id="btnMesActual" class="btn-filter">
                    <i class="fas fa-calendar-alt"></i> Mes actual
                </button>
            </div>
            
            <!-- Formulario de búsqueda -->
            <div class="reports-query-box">
                <form class="reports-query-form" id="reportsQueryForm">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="filtroCodigo">Código</label>
                            <input type="text" id="filtroCodigo" placeholder="Código de empleado">
                        </div>
                        <div class="form-group">
                            <label for="filtroNombre">Nombre</label>
                            <input type="text" id="filtroNombre" placeholder="Nombre o apellido">
                        </div>
                        <div class="form-group">
                            <label for="filtroSede">Sede</label>
                            <select id="filtroSede">
                                <option value="Todas">Todas</option>
                                <!-- Se cargará con JavaScript -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtroEstablecimiento">Establecimiento</label>
                            <select id="filtroEstablecimiento">
                                <option value="Todos">Todos</option>
                                <!-- Se cargará con JavaScript -->
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="filtroEstadoEntrada">Estado Entrada</label>
                            <select id="filtroEstadoEntrada">
                                <option value="Todos">Todos</option>
                                <option value="A Tiempo">A Tiempo</option>
                                <option value="Temprano">Temprano</option>
                                <option value="Tardanza">Tardanza</option>
                                <option value="Ausente">Ausente</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="query-row">
                        <div class="form-group">
                            <label for="fechaDesde">Fecha desde</label>
                            <input type="date" id="fechaDesde">
                        </div>
                        <div class="form-group">
                            <label for="fechaHasta">Fecha hasta</label>
                            <input type="date" id="fechaHasta">
                        </div>
                        <div class="query-btns">
                            <button type="button" id="btnConsultarReporte" class="btn-primary">
                                <i class="fas fa-search"></i> Consultar
                            </button>
                            <button type="button" id="btnLimpiarReporte" class="btn-secondary">
                                <i class="fas fa-redo"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Tabla de reportes -->
            <div class="reports-table-container">
                <table class="reports-table" id="reportsTable">
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
                    <tbody id="reporteTableBody">
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px;">
                                <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="pagination-container">
                    <div class="pagination-info" id="paginationInfo">
                        Mostrando 0-0 de 0 registros
                    </div>
                    <div class="pagination-controls" id="paginationControls">
                        <!-- Controles de paginación -->
                    </div>
                </div>
            </div>
            
            <!-- Incluir modales de reportes -->
            <?php include 'components/reports_modals.php'; ?>
        </main>
    </div>
</div>

<!-- Scripts -->
<script src="assets/js/layout.js"></script>
<script src="assets/js/reports_modals.js"></script>
<script src="assets/js/reports.js"></script>
</body>
</html>