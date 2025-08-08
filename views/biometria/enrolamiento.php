<?php
require_once __DIR__ . '/../../auth/session.php';
requireModuleAccess('asistencia'); // Verificar permisos para módulo de asistencia
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
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Select2 for employee search -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="../../assets/css/biometric.css">
</head>
<body>
<div class="app-container">
    <?php include '../../components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include '../../components/header.php'; ?>
        <main class="main-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-header d-flex justify-content-between align-items-center mb-4">
                            <h2 class="page-title">
                                <i class="fas fa-user-plus me-2"></i>
                                Inscripción Biométrica
                            </h2>
                            <a href="../asistencia/index.php" class="btn btn-primary">
                                <i class="fas fa-fingerprint me-1"></i>
                                Registrar Asistencia
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Alert Container -->
                <div id="alertContainer" class="row">
                    <div class="col-12"></div>
                </div>

                <!-- Loading Indicator -->
                <div id="loadingIndicator" class="row" style="display: none;">
                    <div class="col-12">
                        <div class="alert alert-info"></div>
                    </div>
                </div>

                <div class="row">
                    <!-- Employee Selection and Info -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-search me-2"></i>
                                    Selección de Empleado
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="enrollmentEmployeeSelect" class="form-label">Buscar Empleado</label>
                                    <select id="enrollmentEmployeeSelect" class="form-select" style="width: 100%;">
                                        <option value="">Seleccione un empleado...</option>
                                    </select>
                                    <div class="form-text">
                                        Busque por nombre, apellido o DNI
                                    </div>
                                </div>

                                <!-- Employee Info -->
                                <div id="employeeInfo"></div>
                            </div>
                        </div>

                        <!-- Enrollment Statistics -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Estadísticas del Sistema
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="enrollmentStats">
                                    <p class="text-muted text-center">Cargando estadísticas...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Actions -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    Métodos de Inscripción
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-3">
                                    <!-- Facial Enrollment -->
                                    <button type="button" class="btn btn-primary btn-lg" data-enrollment-type="facial">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-face-smile me-3 fs-4"></i>
                                            <div class="text-start">
                                                <div class="fw-bold">Inscripción Facial</div>
                                                <small class="opacity-75">Registrar patrones faciales</small>
                                            </div>
                                        </div>
                                    </button>

                                    <!-- Fingerprint Enrollment -->
                                    <button type="button" class="btn btn-success btn-lg" data-enrollment-type="fingerprint">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-fingerprint me-3 fs-4"></i>
                                            <div class="text-start">
                                                <div class="fw-bold">Inscripción de Huella</div>
                                                <small class="opacity-75">Registrar huellas dactilares</small>
                                            </div>
                                        </div>
                                    </button>
                                </div>

                                <!-- Requirements -->
                                <div class="mt-4">
                                    <h6 class="text-muted mb-2">Requisitos del Sistema</h6>
                                    <div class="small">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-camera me-2 text-muted"></i>
                                            <span>Cámara web funcional</span>
                                            <i class="fas fa-check text-success ms-auto" id="cameraCheck"></i>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="fas fa-server me-2 text-muted"></i>
                                            <span>Servicios biométricos</span>
                                            <i class="fas fa-question text-warning ms-auto" id="serviceCheck"></i>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-shield-alt me-2 text-muted"></i>
                                            <span>Conexión segura (HTTPS)</span>
                                            <i class="fas fa-info text-info ms-auto" id="httpsCheck"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-tools me-2"></i>
                                    Acciones Rápidas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="checkSystemStatus()">
                                        <i class="fas fa-stethoscope me-1"></i>
                                        Verificar Sistema
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="viewEnrollmentGuide()">
                                        <i class="fas fa-book me-1"></i>
                                        Guía de Uso
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="refreshEmployeeList()">
                                        <i class="fas fa-refresh me-1"></i>
                                        Actualizar Lista
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biometric Data Management -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-database me-2"></i>
                                    Datos Biométricos
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="biometricDataList">
                                    <p class="text-muted text-center">
                                        <i class="fas fa-user-slash fs-2 mb-2 opacity-50 d-block"></i>
                                        Seleccione un empleado para ver sus datos biométricos
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrollment History -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>
                                    Historial de Inscripciones
                                </h5>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="loadEnrollmentHistory('today')">
                                        Hoy
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" onclick="loadEnrollmentHistory('week')">
                                        Semana
                                    </button>
                                    <button type="button" class="btn btn-outline-primary active" onclick="loadEnrollmentHistory('month')">
                                        Mes
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="enrollmentHistory" class="table-responsive">
                                    <p class="text-muted text-center">Cargando historial...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Help and Documentation -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    Consejos para una Buena Inscripción
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="small">
                                    <div class="mb-2">
                                        <strong>Inscripción Facial:</strong>
                                        <ul class="mb-0 mt-1">
                                            <li>Asegure buena iluminación</li>
                                            <li>Mantenga el rostro centrado</li>
                                            <li>Capture desde diferentes ángulos</li>
                                            <li>Evite sombras en el rostro</li>
                                        </ul>
                                    </div>
                                    <div>
                                        <strong>Inscripción de Huella:</strong>
                                        <ul class="mb-0 mt-1">
                                            <li>Limpie el dedo antes de capturar</li>
                                            <li>Presione firmemente en el scanner</li>
                                            <li>Mantenga el dedo inmóvil</li>
                                            <li>Use diferentes dedos para mayor seguridad</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Notas Importantes
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="small">
                                    <ul class="mb-0">
                                        <li>Los datos biométricos son encriptados y seguros</li>
                                        <li>Cada empleado puede tener múltiples métodos registrados</li>
                                        <li>La inscripción es requerida antes del primer uso</li>
                                        <li>Los datos pueden ser actualizados en cualquier momento</li>
                                        <li>Se recomienda inscribir al menos 2 dedos diferentes</li>
                                        <li>El sistema mantiene logs de todas las operaciones</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<!-- Biometric System Scripts -->
<script src="../../assets/js/camera.js"></script>
<script src="../../assets/js/biometria_enroll.js"></script>

<script>
// Initialize system when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    checkSystemRequirements();
    loadEnrollmentStats();
    loadEnrollmentHistory('month');
});

// Check system requirements
function checkSystemRequirements() {
    // Check camera
    const cameraCheck = document.getElementById('cameraCheck');
    if (CameraManager.isSupported()) {
        cameraCheck.className = 'fas fa-check text-success ms-auto';
    } else {
        cameraCheck.className = 'fas fa-times text-danger ms-auto';
    }

    // Check HTTPS
    const httpsCheck = document.getElementById('httpsCheck');
    if (location.protocol === 'https:') {
        httpsCheck.className = 'fas fa-check text-success ms-auto';
    } else {
        httpsCheck.className = 'fas fa-exclamation text-warning ms-auto';
    }
}

// Check system status
async function checkSystemStatus() {
    try {
        const response = await fetch('../../api/biometric/check-services.php');
        const data = await response.json();
        
        const serviceCheck = document.getElementById('serviceCheck');
        if (data.face_service && data.fingerprint_service) {
            serviceCheck.className = 'fas fa-check text-success ms-auto';
        } else if (data.face_service || data.fingerprint_service) {
            serviceCheck.className = 'fas fa-exclamation text-warning ms-auto';
        } else {
            serviceCheck.className = 'fas fa-times text-danger ms-auto';
        }

        // Show status message
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-info alert-dismissible fade show';
        alertDiv.innerHTML = `
            <strong>Estado del Sistema:</strong><br>
            Servicio Facial: ${data.face_service ? '✅ Disponible' : '❌ No disponible'}<br>
            Servicio de Huella: ${data.fingerprint_service ? '✅ Disponible' : '❌ No disponible'}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.getElementById('alertContainer').appendChild(alertDiv);

    } catch (error) {
        const serviceCheck = document.getElementById('serviceCheck');
        serviceCheck.className = 'fas fa-times text-danger ms-auto';
    }
}

// Load enrollment statistics
async function loadEnrollmentStats() {
    try {
        const response = await fetch('../../api/biometric/get-enrollment-stats.php');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.stats;
            document.getElementById('enrollmentStats').innerHTML = `
                <div class="row g-2 text-center">
                    <div class="col-6">
                        <div class="bg-primary bg-opacity-10 rounded p-2">
                            <div class="h5 mb-0 text-primary">${stats.total_employees || 0}</div>
                            <small class="text-muted">Empleados</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-success bg-opacity-10 rounded p-2">
                            <div class="h5 mb-0 text-success">${stats.enrolled_employees || 0}</div>
                            <small class="text-muted">Inscritos</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-info bg-opacity-10 rounded p-2">
                            <div class="h5 mb-0 text-info">${stats.facial_enrollments || 0}</div>
                            <small class="text-muted">Rostros</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-warning bg-opacity-10 rounded p-2">
                            <div class="h5 mb-0 text-warning">${stats.fingerprint_enrollments || 0}</div>
                            <small class="text-muted">Huellas</small>
                        </div>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        document.getElementById('enrollmentStats').innerHTML = '<p class="text-danger small">Error al cargar estadísticas</p>';
    }
}

// Load enrollment history
async function loadEnrollmentHistory(period) {
    try {
        const response = await fetch(`../../api/biometric/get-enrollment-history.php?period=${period}`);
        const data = await response.json();
        
        if (data.success && data.history.length > 0) {
            const tableHtml = `
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Tipo</th>
                            <th>Detalles</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.history.map(record => `
                            <tr>
                                <td>${record.EMPLEADO_NOMBRE} ${record.EMPLEADO_APELLIDO}</td>
                                <td>
                                    <i class="fas ${record.BIOMETRIC_TYPE === 'facial' ? 'fa-face-smile' : 'fa-fingerprint'} me-1"></i>
                                    ${record.BIOMETRIC_TYPE === 'facial' ? 'Facial' : 'Huella'}
                                </td>
                                <td>
                                    ${record.FINGER_TYPE ? getFingerTypeName(record.FINGER_TYPE) : ''}
                                </td>
                                <td>${new Date(record.CREATED_AT).toLocaleString()}</td>
                                <td>
                                    <span class="badge ${record.ACTIVO ? 'bg-success' : 'bg-secondary'}">
                                        ${record.ACTIVO ? 'Activo' : 'Inactivo'}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('enrollmentHistory').innerHTML = tableHtml;
        } else {
            document.getElementById('enrollmentHistory').innerHTML = '<p class="text-muted text-center">No hay registros de inscripción</p>';
        }
        
        // Update active button
        document.querySelectorAll('.btn-group .btn').forEach(btn => btn.classList.remove('active'));
        event?.target?.classList.add('active');
        
    } catch (error) {
        document.getElementById('enrollmentHistory').innerHTML = '<p class="text-danger text-center">Error al cargar historial</p>';
    }
}

function getFingerTypeName(type) {
    const names = {
        'thumb_right': 'Pulgar Derecho',
        'thumb_left': 'Pulgar Izquierdo',
        'index_right': 'Índice Derecho',
        'index_left': 'Índice Izquierdo',
        'middle_right': 'Medio Derecho',
        'middle_left': 'Medio Izquierdo',
        'ring_right': 'Anular Derecho',
        'ring_left': 'Anular Izquierdo',
        'pinky_right': 'Meñique Derecho',
        'pinky_left': 'Meñique Izquierdo'
    };
    return names[type] || type;
}

function viewEnrollmentGuide() {
    window.open('../../docs/USO_BIOMETRIA.txt', '_blank');
}

function refreshEmployeeList() {
    // Trigger refresh of employee selector
    $('#enrollmentEmployeeSelect').val(null).trigger('change');
    location.reload();
}
</script>

<style>
.page-header {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 1rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.btn-lg {
    padding: 1rem;
    text-align: left;
}

.btn-lg .fs-4 {
    font-size: 1.5rem !important;
}

.opacity-75 {
    opacity: 0.75;
}

.opacity-50 {
    opacity: 0.5;
}

[data-enrollment-type] {
    transition: all 0.2s ease-in-out;
}

[data-enrollment-type]:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.bg-opacity-10 {
    background-color: rgba(var(--bs-primary-rgb), 0.1) !important;
}
</style>
</body>
</html>