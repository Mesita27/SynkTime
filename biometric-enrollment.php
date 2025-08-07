<?php
require_once 'auth/session.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inscripción Biométrica | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/employee.css">
    <link rel="stylesheet" href="assets/css/biometric.css">
    <link rel="stylesheet" href="assets/css/employee-navigation.css">
    <link rel="stylesheet" href="assets/css/biometric-options.css">
    <link rel="stylesheet" href="assets/css/biometric-enrollment-enhanced.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="employee-header">
                <h2 class="page-title"><i class="fas fa-fingerprint"></i> Inscripción Biométrica</h2>
                <div class="employee-actions">
                    <button class="btn-primary" id="btnOpenBiometricEnrollment">
                        <i class="fas fa-user-plus"></i> Inscribir Empleado
                    </button>
                    <button class="btn-secondary" id="btnBiometricReport">
                        <i class="fas fa-chart-bar"></i> Reporte Biométrico
                    </button>
                </div>
            </div>
            
            <!-- Estadísticas biométricas -->
            <div class="biometric-stats">
                <div class="stat-card">
                    <div class="stat-icon fingerprint">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="fingerprint_enrolled_count">0</h3>
                        <p>Empleados con Huella</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon facial">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="facial_enrolled_count">0</h3>
                        <p>Empleados con Patrón Facial</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon complete">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="complete_biometric_count">0</h3>
                        <p>Inscripción Completa</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="pending_enrollment_count">0</h3>
                        <p>Pendientes de Inscripción</p>
                    </div>
                </div>
            </div>
            
            <!-- Resumen de empleados por estado biométrico -->
            <div class="biometric-summary">
                <h3><i class="fas fa-list"></i> Estado de Inscripción por Empleado</h3>
                
                <!-- Navegación por pestañas -->
                <div class="biometric-status-tabs">
                    <button class="status-tab active" onclick="switchBiometricStatusTab('all')" data-tab="all">
                        <i class="fas fa-users"></i> Todos los Empleados
                    </button>
                    <button class="status-tab" onclick="switchBiometricStatusTab('enrolled')" data-tab="enrolled">
                        <i class="fas fa-check-circle"></i> Con Biometría
                    </button>
                    <button class="status-tab" onclick="switchBiometricStatusTab('partial')" data-tab="partial">
                        <i class="fas fa-exclamation-triangle"></i> Parcial
                    </button>
                    <button class="status-tab" onclick="switchBiometricStatusTab('pending')" data-tab="pending">
                        <i class="fas fa-clock"></i> Pendientes
                    </button>
                </div>
                
                <!-- Filtros mejorados con búsqueda AJAX -->
                <div class="biometric-filters enhanced">
                    <div class="filter-group">
                        <label for="filter_search_code">Código Empleado</label>
                        <input type="text" id="filter_search_code" class="form-control" placeholder="Buscar por código">
                    </div>
                    <div class="filter-group">
                        <label for="filter_search_name">Nombre Empleado</label>
                        <input type="text" id="filter_search_name" class="form-control" placeholder="Buscar por nombre">
                    </div>
                    <div class="filter-group">
                        <label for="filter_sede">Sede</label>
                        <select id="filter_sede" class="form-control">
                            <option value="">Todas las sedes</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_establecimiento">Establecimiento</label>
                        <select id="filter_establecimiento" class="form-control">
                            <option value="">Todos los establecimientos</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_status">Estado Biométrico</label>
                        <select id="filter_status" class="form-control">
                            <option value="">Todos</option>
                            <option value="complete">Completo</option>
                            <option value="fingerprint_only">Solo Huella</option>
                            <option value="facial_only">Solo Facial</option>
                            <option value="none">Sin registrar</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="button" class="btn-primary" id="btnApplyFilters">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <button type="button" class="btn-secondary" id="btnClearFilters">
                            <i class="fas fa-redo"></i> Limpiar
                        </button>
                        <button type="button" class="btn-success" id="btnExportBiometric">
                            <i class="fas fa-file-excel"></i> Exportar
                        </button>
                    </div>
                </div>
                
                <!-- Información de resultados y paginación -->
                <div class="biometric-summary-controls">
                    <div class="results-info" id="biometric_results_info">
                        <span>Cargando datos...</span>
                    </div>
                    <div class="view-controls">
                        <label for="biometric_page_size">Mostrar:</label>
                        <select id="biometric_page_size" class="form-control">
                            <option value="10">10 registros</option>
                            <option value="25" selected>25 registros</option>
                            <option value="50">50 registros</option>
                            <option value="100">100 registros</option>
                        </select>
                    </div>
                </div>
                
                <!-- Tabla mejorada de empleados -->
                <div class="biometric-table-container enhanced">
                    <table class="biometric-table enhanced">
                        <thead>
                            <tr>
                                <th>
                                    <input type="checkbox" id="select_all_employees" onclick="toggleSelectAllEmployees()">
                                </th>
                                <th onclick="sortBiometricTable('codigo')" class="sortable">
                                    Código <i class="fas fa-sort"></i>
                                </th>
                                <th onclick="sortBiometricTable('nombre')" class="sortable">
                                    Nombre Completo <i class="fas fa-sort"></i>
                                </th>
                                <th>Establecimiento</th>
                                <th>Sede</th>
                                <th>Estado Huella Patrón</th>
                                <th>Estado Reconocimiento Facial</th>
                                <th>Estado General</th>
                                <th>Última Actividad</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="biometricSummaryTableBody">
                            <tr>
                                <td colspan="10" class="loading-text">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando datos de empleados...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Paginación mejorada -->
                <div class="biometric-pagination" id="biometric_pagination">
                    <!-- Se genera dinámicamente -->
                </div>
                
                <!-- Acciones masivas -->
                <div class="bulk-actions" id="bulk_actions" style="display: none;">
                    <div class="bulk-actions-header">
                        <span id="selected_count">0</span> empleados seleccionados
                    </div>
                    <div class="bulk-actions-buttons">
                        <button type="button" class="btn-primary" onclick="bulkEnrollBiometric()">
                            <i class="fas fa-fingerprint"></i> Inscripción Masiva
                        </button>
                        <button type="button" class="btn-warning" onclick="bulkExportSelected()">
                            <i class="fas fa-download"></i> Exportar Seleccionados
                        </button>
                        <button type="button" class="btn-danger" onclick="bulkResetBiometric()">
                            <i class="fas fa-trash"></i> Resetear Biometría
                        </button>
                    </div>
                </div>
            </div>
            
            <?php include 'components/biometric_enrollment_modal.php'; ?>
        </main>
    </div>
</div>
<script src="assets/js/layout.js"></script>
<script src="assets/js/employee-navigation.js"></script>
<script src="assets/js/biometric.js"></script>
<script src="assets/js/biometric-enrollment-page.js"></script>
</body>
</html>