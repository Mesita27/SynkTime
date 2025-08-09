<?php
require_once 'auth/session.php';
requireModuleAccess('empleado'); // Verificar permisos para módulo de empleados
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
    <link rel="stylesheet" href="assets/css/biometric.css">
    <link rel="stylesheet" href="assets/css/employee.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="biometric-enrollment-container">
                <!-- Header Section -->
                <div class="enrollment-header">
                    <h1 class="enrollment-title">
                        <i class="fas fa-fingerprint"></i>
                        Inscripción Biométrica
                    </h1>
                    <p class="enrollment-subtitle">
                        Gestione la inscripción de datos biométricos para empleados
                    </p>
                </div>

                <div class="enrollment-content">
                    <!-- Statistics Section -->
                    <div class="enrollment-stats">
                        <div class="stat-card">
                            <span class="stat-value" id="totalEmployees">0</span>
                            <span class="stat-label">Total Empleados</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-value" id="fingerprintEnrolled">0</span>
                            <span class="stat-label">Huella Inscrita</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-value" id="facialEnrolled">0</span>
                            <span class="stat-label">Facial Inscrito</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-value" id="completionRate">0%</span>
                            <span class="stat-label">Tasa de Completado</span>
                        </div>
                    </div>

                    <!-- Device Status -->
                    <div class="device-status-section">
                        <h3><i class="fas fa-devices"></i> Estado de Dispositivos</h3>
                        <div class="device-status-grid">
                            <div class="camera-status device-status connecting">
                                <i class="fas fa-camera device-status-icon"></i>
                                <span>Detectando cámara...</span>
                            </div>
                            <div class="fingerprint-status device-status connecting">
                                <i class="fas fa-fingerprint device-status-icon"></i>
                                <span>Detectando lector de huellas...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Search Section -->
                    <div class="employee-search">
                        <h3><i class="fas fa-search"></i> Buscar Empleados</h3>
                        <form class="search-form" id="employeeSearchForm">
                            <div class="form-group">
                                <label for="sede_filter">Sede</label>
                                <select id="sede_filter" name="sede" class="form-control">
                                    <option value="">Todas las sedes</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="establecimiento_filter">Establecimiento</label>
                                <select id="establecimiento_filter" name="establecimiento" class="form-control">
                                    <option value="">Todos los establecimientos</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="search_text">Buscar por nombre o código</label>
                                <input type="text" id="search_text" name="search" class="form-control" 
                                       placeholder="Nombre o código del empleado">
                            </div>
                            <div class="form-group">
                                <button type="button" class="btn-primary" onclick="searchEmployees()">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                                <button type="button" class="btn-secondary" onclick="clearSearch()">
                                    <i class="fas fa-times"></i> Limpiar
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Employee List Section -->
                    <div class="employee-list-section">
                        <div class="section-header">
                            <h3><i class="fas fa-users"></i> Lista de Empleados</h3>
                            <button type="button" class="btn-primary" onclick="openEnrollmentModal()">
                                <i class="fas fa-plus"></i> Inscribir Empleado
                            </button>
                        </div>
                        
                        <div class="employee-list" id="employeeList">
                            <div class="loading-state">
                                <div class="loading-spinner"></div>
                                <div class="loading-text">Cargando empleados...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="pagination-container" id="paginationContainer" style="display: none;">
                        <!-- Pagination will be inserted here by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Include enrollment modal -->
            <?php include 'components/biometric_enrollment_modal.php'; ?>
        </main>
    </div>
</div>

<script src="assets/js/layout.js"></script>
<script src="assets/js/biometric.js"></script>
<script src="assets/js/biometric-enrollment.js"></script>

<style>
/* Additional styles for enrollment page */
.device-status-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.device-status-section h3 {
    margin-bottom: 1rem;
    color: var(--text-primary);
    font-size: 1.2rem;
    font-weight: 600;
}

.device-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border);
}

.section-header h3 {
    margin: 0;
    color: var(--text-primary);
    font-size: 1.2rem;
    font-weight: 600;
}

.employee-list-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.loading-state {
    text-align: center;
    padding: 3rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.form-control {
    padding: 0.75rem;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(43, 125, 233, 0.1);
}

.search-form {
    display: grid;
    grid-template-columns: 1fr 1fr 2fr auto;
    gap: 1rem;
    align-items: end;
}

@media (max-width: 768px) {
    .search-form {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .section-header .btn-primary {
        align-self: center;
    }
}
</style>
</body>
</html>