<?php
require_once '../../auth/session.php';
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
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="../../assets/css/employee.css">
    <link rel="stylesheet" href="../../assets/css/biometric.css">
</head>
<body>
<div class="app-container">
    <?php include '../../components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include '../../components/header.php'; ?>
        <main class="main-content">
            <div class="enrollment-header">
                <h2 class="page-title">
                    <i class="fas fa-fingerprint"></i> Inscripción Biométrica
                </h2>
                <div class="page-actions">
                    <button type="button" class="btn-primary" id="btnOpenEnrollment">
                        <i class="fas fa-user-plus"></i> Inscribir Empleado
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='../../reports.php'">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </button>
                </div>
            </div>
            
            <!-- Enrollment Statistics -->
            <div class="biometric-stats">
                <div class="stat-card fingerprint">
                    <div class="stat-icon">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="fingerprintEnrolledCount">0</h3>
                        <p>Empleados con Huella</p>
                        <span class="stat-percentage" id="fingerprintPercentage">0%</span>
                    </div>
                </div>
                
                <div class="stat-card facial">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="facialEnrolledCount">0</h3>
                        <p>Empleados con Rostro</p>
                        <span class="stat-percentage" id="facialPercentage">0%</span>
                    </div>
                </div>
                
                <div class="stat-card complete">
                    <div class="stat-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="completeEnrollmentCount">0</h3>
                        <p>Inscripción Completa</p>
                        <span class="stat-percentage" id="completePercentage">0%</span>
                    </div>
                </div>
                
                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="pendingEnrollmentCount">0</h3>
                        <p>Pendientes</p>
                        <span class="stat-percentage" id="pendingPercentage">0%</span>
                    </div>
                </div>
            </div>
            
            <!-- Employee Search and Filters -->
            <div class="enrollment-filters">
                <div class="filter-card">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="filterSede">Sede</label>
                            <select id="filterSede" class="form-control">
                                <option value="">Todas las sedes</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="filterEstablecimiento">Establecimiento</label>
                            <select id="filterEstablecimiento" class="form-control">
                                <option value="">Todos los establecimientos</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="filterEnrollmentStatus">Estado de Inscripción</label>
                            <select id="filterEnrollmentStatus" class="form-control">
                                <option value="">Todos</option>
                                <option value="complete">Completa</option>
                                <option value="partial">Parcial</option>
                                <option value="none">Sin inscribir</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="employeeNameSearch">Buscar Empleado</label>
                            <input type="text" id="employeeNameSearch" class="form-control" 
                                   placeholder="Nombre, código o DNI...">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="button" class="btn-primary" onclick="loadEmployeeList()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <button type="button" class="btn-secondary" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Employee List -->
            <div class="enrollment-list">
                <div class="list-header">
                    <h3>Empleados</h3>
                    <div class="list-actions">
                        <button type="button" class="btn-outline" onclick="exportEnrollmentReport()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </div>
                
                <div class="employee-table-container">
                    <table class="employee-table" id="employeeTable">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Código</th>
                                <th>Establecimiento</th>
                                <th>Huella</th>
                                <th>Rostro</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <tr class="no-data">
                                <td colspan="7">
                                    <div class="no-data-message">
                                        <i class="fas fa-users"></i>
                                        <p>No se encontraron empleados</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-pagination" id="tablePagination" style="display: none;">
                    <!-- Pagination controls will be added here -->
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Enrollment Modal -->
<div id="enrollmentModal" class="modal">
    <div class="modal-content enrollment-modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Inscripción Biométrica</h3>
            <button type="button" class="modal-close" onclick="closeEnrollmentModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Employee Selection -->
            <div class="enrollment-step active" id="stepEmployeeSelection">
                <h4>Seleccionar Empleado</h4>
                <div class="employee-search">
                    <input type="text" id="modalEmployeeSearch" class="form-control" 
                           placeholder="Buscar empleado..." autocomplete="off">
                    <div id="modalEmployeeResults" class="search-results"></div>
                </div>
                <div id="selectedEmployeeCard" class="selected-employee-card" style="display: none;">
                    <!-- Selected employee info will be displayed here -->
                </div>
            </div>
            
            <!-- Enrollment Type Selection -->
            <div class="enrollment-step" id="stepTypeSelection">
                <h4>Tipo de Inscripción</h4>
                <div class="enrollment-types">
                    <button type="button" class="enrollment-type-btn" onclick="selectEnrollmentType('fingerprint')">
                        <i class="fas fa-fingerprint"></i>
                        <span>Huella Dactilar</span>
                        <small>Registrar huellas dactilares</small>
                    </button>
                    <button type="button" class="enrollment-type-btn" onclick="selectEnrollmentType('facial')">
                        <i class="fas fa-user-shield"></i>
                        <span>Reconocimiento Facial</span>
                        <small>Registrar patrón facial</small>
                    </button>
                </div>
            </div>
            
            <!-- Fingerprint Enrollment -->
            <div class="enrollment-step" id="stepFingerprintEnrollment">
                <h4>Inscripción de Huella Dactilar</h4>
                <div class="finger-selection">
                    <div class="hand-diagram">
                        <div class="hand left-hand">
                            <button class="finger-btn" data-finger="left_thumb"><span>Pulgar</span></button>
                            <button class="finger-btn" data-finger="left_index"><span>Índice</span></button>
                            <button class="finger-btn" data-finger="left_middle"><span>Medio</span></button>
                            <button class="finger-btn" data-finger="left_ring"><span>Anular</span></button>
                            <button class="finger-btn" data-finger="left_pinky"><span>Meñique</span></button>
                        </div>
                        <div class="hand right-hand">
                            <button class="finger-btn" data-finger="right_thumb"><span>Pulgar</span></button>
                            <button class="finger-btn" data-finger="right_index"><span>Índice</span></button>
                            <button class="finger-btn" data-finger="right_middle"><span>Medio</span></button>
                            <button class="finger-btn" data-finger="right_ring"><span>Anular</span></button>
                            <button class="finger-btn" data-finger="right_pinky"><span>Meñique</span></button>
                        </div>
                    </div>
                </div>
                <div class="fingerprint-capture" id="fingerprintCapture" style="display: none;">
                    <div class="capture-area">
                        <div class="scanner-frame">
                            <i class="fas fa-fingerprint scanner-icon"></i>
                        </div>
                        <p>Coloque el dedo seleccionado en el lector</p>
                        <button type="button" class="btn-primary" onclick="simulateFingerprintCapture()">
                            Simular Captura
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Facial Enrollment -->
            <div class="enrollment-step" id="stepFacialEnrollment">
                <h4>Inscripción de Reconocimiento Facial</h4>
                <div class="facial-capture">
                    <video id="enrollmentVideo" autoplay playsinline></video>
                    <canvas id="enrollmentCanvas" style="display: none;"></canvas>
                    <div class="camera-overlay">
                        <div class="face-frame"></div>
                    </div>
                </div>
                <div class="capture-progress">
                    <p>Imágenes capturadas: <span id="capturedCount">0</span>/3</p>
                    <div class="progress-bar">
                        <div class="progress-fill" id="captureProgress"></div>
                    </div>
                </div>
                <div class="capture-instructions">
                    <p>Mantenga su rostro centrado y presione "Capturar" para cada imagen</p>
                </div>
            </div>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeEnrollmentModal()">
                Cancelar
            </button>
            <button type="button" class="btn-outline" id="prevStepBtn" onclick="previousStep()" style="display: none;">
                <i class="fas fa-arrow-left"></i> Anterior
            </button>
            <button type="button" class="btn-primary" id="nextStepBtn" onclick="nextStep()" disabled>
                Siguiente <i class="fas fa-arrow-right"></i>
            </button>
            <button type="button" class="btn-primary" id="enrollBtn" onclick="performEnrollment()" style="display: none;">
                <i class="fas fa-save"></i> Inscribir
            </button>
            <button type="button" class="btn-primary" id="captureFacialEnrollBtn" onclick="captureFacialForEnrollment()" style="display: none;">
                <i class="fas fa-camera"></i> Capturar
            </button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<!-- Scripts -->
<script src="../../assets/js/camera.js"></script>
<script src="../../assets/js/biometria_enroll.js"></script>

<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeEnrollmentPage();
    loadEnrollmentStats();
    loadEmployeeList();
    loadSedesAndEstablecimientos();
});
</script>

</body>
</html>