<?php
require_once __DIR__ . '/../../auth/session.php';
requireModuleAccess('asistencia'); // Verificar permisos para módulo de asistencia
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Asistencia Biométrica | SynkTime</title>
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
                                <i class="fas fa-fingerprint me-2"></i>
                                Registro de Asistencia Biométrica
                            </h2>
                            <a href="../../attendance.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-1"></i>
                                Ver Asistencias
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
                    <!-- Employee Selection -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>
                                    Selección de Empleado
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="employeeSelect" class="form-label">Buscar Empleado</label>
                                    <select id="employeeSelect" class="form-select" style="width: 100%;">
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

                        <!-- Registration Type Selection -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    Tipo de Registro
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-success w-100" data-tipo="ENTRADA">
                                            <i class="fas fa-sign-in-alt me-2"></i>
                                            Entrada
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-warning w-100" data-tipo="SALIDA">
                                            <i class="fas fa-sign-out-alt me-2"></i>
                                            Salida
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="fas fa-info-circle me-1"></i>
                                    El sistema determinará automáticamente el tipo si no se especifica
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Methods -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Métodos de Verificación
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="registrationButtons" style="display: none;">
                                    <div class="d-grid gap-3">
                                        <!-- Facial Recognition -->
                                        <button type="button" class="btn btn-primary btn-lg" data-method="facial">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-face-smile me-3 fs-4"></i>
                                                <div class="text-start">
                                                    <div class="fw-bold">Reconocimiento Facial</div>
                                                    <small class="opacity-75">Verificación automática por cámara</small>
                                                </div>
                                            </div>
                                        </button>

                                        <!-- Fingerprint Verification -->
                                        <button type="button" class="btn btn-success btn-lg" data-method="fingerprint">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-fingerprint me-3 fs-4"></i>
                                                <div class="text-start">
                                                    <div class="fw-bold">Huella Dactilar</div>
                                                    <small class="opacity-75">Verificación por huella digital</small>
                                                </div>
                                            </div>
                                        </button>

                                        <!-- Traditional Registration -->
                                        <button type="button" class="btn btn-secondary btn-lg" data-method="traditional">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-camera me-3 fs-4"></i>
                                                <div class="text-start">
                                                    <div class="fw-bold">Registro Tradicional</div>
                                                    <small class="opacity-75">Solo captura de fotografía</small>
                                                </div>
                                            </div>
                                        </button>
                                    </div>

                                    <!-- Biometric Status Indicators -->
                                    <div class="mt-4">
                                        <h6 class="text-muted mb-2">Estado Biométrico del Empleado</h6>
                                        <div id="biometricStatus" class="row g-2">
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-face-smile me-2 text-muted"></i>
                                                    <small class="text-muted">Rostro: <span id="faceStatus">No verificado</span></small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-fingerprint me-2 text-muted"></i>
                                                    <small class="text-muted">Huella: <span id="fingerprintStatus">No verificado</span></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Instructions when no employee selected -->
                                <div id="noEmployeeMessage" class="text-center text-muted">
                                    <i class="fas fa-arrow-left fs-1 mb-3 opacity-50"></i>
                                    <p>Seleccione un empleado para continuar</p>
                                </div>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-server me-2"></i>
                                    Estado del Sistema
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="status-indicator bg-secondary me-2" id="cameraStatus"></div>
                                            <span>Cámara</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="status-indicator bg-secondary me-2" id="faceServiceStatus"></div>
                                            <span>Servicio Facial</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="status-indicator bg-secondary me-2" id="fingerprintServiceStatus"></div>
                                            <span>Servicio Huella</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex align-items-center">
                                            <div class="status-indicator bg-success me-2"></div>
                                            <span>Base de Datos</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Registrations -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>
                                    Registros Recientes
                                </h5>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshRecentRecords()">
                                    <i class="fas fa-refresh me-1"></i>
                                    Actualizar
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="recentRecords" class="table-responsive">
                                    <p class="text-muted text-center">Cargando registros recientes...</p>
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
<script src="../../assets/js/asistencia.js"></script>

<script>
// Initialize system status checks
document.addEventListener('DOMContentLoaded', () => {
    checkSystemStatus();
    loadRecentRecords();
});

// Check system status
async function checkSystemStatus() {
    // Check camera
    if (CameraManager.isSupported()) {
        document.getElementById('cameraStatus').className = 'status-indicator bg-success me-2';
    } else {
        document.getElementById('cameraStatus').className = 'status-indicator bg-danger me-2';
    }

    // Check biometric services
    try {
        const response = await fetch('../../api/biometric/check-services.php');
        const data = await response.json();
        
        if (data.face_service) {
            document.getElementById('faceServiceStatus').className = 'status-indicator bg-success me-2';
        } else {
            document.getElementById('faceServiceStatus').className = 'status-indicator bg-danger me-2';
        }
        
        if (data.fingerprint_service) {
            document.getElementById('fingerprintServiceStatus').className = 'status-indicator bg-success me-2';
        } else {
            document.getElementById('fingerprintServiceStatus').className = 'status-indicator bg-danger me-2';
        }
    } catch (error) {
        console.warn('Error checking services:', error);
    }
}

// Load recent attendance records
async function loadRecentRecords() {
    try {
        const response = await fetch('../../api/attendance/get-recent.php');
        const data = await response.json();
        
        if (data.success && data.records.length > 0) {
            const tableHtml = `
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Tipo</th>
                            <th>Método</th>
                            <th>Hora</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.records.map(record => `
                            <tr>
                                <td>${record.EMPLEADO_NOMBRE} ${record.EMPLEADO_APELLIDO}</td>
                                <td>
                                    <span class="badge ${record.TIPO === 'ENTRADA' ? 'bg-success' : 'bg-warning'}">
                                        ${record.TIPO}
                                    </span>
                                </td>
                                <td>
                                    <i class="fas ${getMethodIcon(record.VERIFICATION_METHOD)} me-1"></i>
                                    ${getMethodName(record.VERIFICATION_METHOD)}
                                </td>
                                <td>${record.HORA}</td>
                                <td>
                                    ${record.TARDANZA && record.TARDANZA !== 'N' ? 
                                        `<span class="text-warning">${record.TARDANZA} min tarde</span>` : 
                                        '<span class="text-success">A tiempo</span>'
                                    }
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
            document.getElementById('recentRecords').innerHTML = tableHtml;
        } else {
            document.getElementById('recentRecords').innerHTML = '<p class="text-muted text-center">No hay registros recientes</p>';
        }
    } catch (error) {
        document.getElementById('recentRecords').innerHTML = '<p class="text-danger text-center">Error al cargar registros</p>';
    }
}

function getMethodIcon(method) {
    switch (method) {
        case 'facial': return 'fa-face-smile';
        case 'fingerprint': return 'fa-fingerprint';
        case 'traditional': return 'fa-camera';
        default: return 'fa-question';
    }
}

function getMethodName(method) {
    switch (method) {
        case 'facial': return 'Facial';
        case 'fingerprint': return 'Huella';
        case 'traditional': return 'Tradicional';
        default: return 'Desconocido';
    }
}

function refreshRecentRecords() {
    loadRecentRecords();
}
</script>

<style>
.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.btn-lg .fs-4 {
    font-size: 1.5rem !important;
}

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

#registrationButtons .btn {
    transition: all 0.2s ease-in-out;
}

#registrationButtons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.opacity-75 {
    opacity: 0.75;
}

.opacity-50 {
    opacity: 0.5;
}
</style>
</body>
</html>