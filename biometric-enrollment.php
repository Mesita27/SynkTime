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
            
            <!-- Sistema biométrico - Estado de servicios -->
            <div class="biometric-system-status">
                <h3><i class="fas fa-cogs"></i> Estado del Sistema Biométrico</h3>
                <div class="system-status-cards">
                    <div class="status-card" id="facial_api_status">
                        <div class="status-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="status-content">
                            <h4>Reconocimiento Facial</h4>
                            <p class="status-text">Verificando...</p>
                            <small class="provider-text"></small>
                        </div>
                        <div class="status-indicator">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </div>
                    <div class="status-card" id="fingerprint_api_status">
                        <div class="status-icon">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                        <div class="status-content">
                            <h4>Reconocimiento de Huellas</h4>
                            <p class="status-text">Verificando...</p>
                            <small class="provider-text"></small>
                        </div>
                        <div class="status-indicator">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </div>
                    <div class="status-card" id="system_capabilities_status">
                        <div class="status-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="status-content">
                            <h4>Capacidades del Sistema</h4>
                            <p class="status-text">Verificando...</p>
                            <small class="provider-text"></small>
                        </div>
                        <div class="status-indicator">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </div>
                </div>
                <div class="system-recommendations" id="system_recommendations" style="display: none;">
                    <h4><i class="fas fa-lightbulb"></i> Recomendaciones</h4>
                    <div class="recommendations-list" id="recommendations_list">
                        <!-- Recommendations will be loaded here -->
                    </div>
                </div>
            </div>
            
            <!-- Resumen de empleados por estado biométrico -->
            <div class="biometric-summary">
                <h3><i class="fas fa-list"></i> Estado de Inscripción por Empleado</h3>
                
                <!-- Filtros -->
                <div class="biometric-filters">
                    <div class="filter-group">
                        <label for="filter_sede">Sede:</label>
                        <select id="filter_sede" class="form-control">
                            <option value="">Todas las sedes</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_establecimiento">Establecimiento:</label>
                        <select id="filter_establecimiento" class="form-control">
                            <option value="">Todos los establecimientos</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_status">Estado:</label>
                        <select id="filter_status" class="form-control">
                            <option value="">Todos</option>
                            <option value="complete">Completo</option>
                            <option value="partial">Parcial</option>
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
                    </div>
                </div>
                
                <!-- Tabla de empleados -->
                <div class="biometric-table-container">
                    <table class="biometric-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Establecimiento</th>
                                <th>Sede</th>
                                <th>Huella Dactilar</th>
                                <th>Patrón Facial</th>
                                <th>Estado General</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="biometricSummaryTableBody">
                            <tr>
                                <td colspan="8" class="loading-text">
                                    <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php include 'components/biometric_enrollment_modal.php'; ?>
        </main>
    </div>
</div>
<script src="assets/js/layout.js"></script>
<script src="assets/js/biometric.js"></script>
<script src="assets/js/biometric-enrollment-page.js"></script>
</body>
</html>