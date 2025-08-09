<?php
require_once '../../auth/session.php';
requireModuleAccess('asistencia'); // Verificar permisos para módulo de asistencia
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de Asistencia | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/layout.css">
    <link rel="stylesheet" href="../../assets/css/attendance.css">
    <link rel="stylesheet" href="../../assets/css/biometric.css">
</head>
<body>
<div class="app-container">
    <?php include '../../components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include '../../components/header.php'; ?>
        <main class="main-content">
            <div class="attendance-header">
                <h2 class="page-title">
                    <i class="fas fa-clock"></i> Registro de Asistencia
                </h2>
                <div class="page-actions">
                    <button type="button" class="btn-secondary" onclick="window.location.href='../../attendance.php'">
                        <i class="fas fa-list"></i> Ver Asistencias
                    </button>
                </div>
            </div>
            
            <!-- Main Registration Card -->
            <div class="attendance-register-card">
                <div class="card-header">
                    <h3><i class="fas fa-user-check"></i> Registrar Nueva Asistencia</h3>
                </div>
                
                <div class="card-content">
                    <form id="attendanceRegisterForm" class="attendance-form">
                        <!-- Employee Selection -->
                        <div class="form-group">
                            <label for="employeeSelect" class="form-label">
                                <i class="fas fa-user"></i> Empleado
                            </label>
                            <div class="employee-select-wrapper">
                                <input type="text" 
                                       id="employeeSearch" 
                                       class="form-control" 
                                       placeholder="Buscar empleado por nombre, código o DNI..."
                                       autocomplete="off">
                                <input type="hidden" id="selectedEmployeeId">
                                <div id="employeeSearchResults" class="search-results"></div>
                            </div>
                            <div id="selectedEmployeeInfo" class="selected-employee-info" style="display: none;">
                                <div class="employee-card">
                                    <div class="employee-details">
                                        <span class="employee-name"></span>
                                        <span class="employee-code"></span>
                                    </div>
                                    <button type="button" class="btn-clear" onclick="clearEmployeeSelection()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Registration Type -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-clock"></i> Tipo de Registro
                            </label>
                            <div class="registration-type-display" id="registrationTypeDisplay">
                                <span class="type-indicator">
                                    <i class="fas fa-question-circle"></i>
                                    Seleccione un empleado para determinar el tipo
                                </span>
                            </div>
                        </div>
                        
                        <!-- Verification Methods -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-shield-alt"></i> Método de Verificación
                            </label>
                            <div class="verification-methods">
                                <button type="button" 
                                        class="verification-btn fingerprint-btn" 
                                        id="fingerprintBtn"
                                        onclick="openFingerprintModal()"
                                        disabled>
                                    <i class="fas fa-fingerprint"></i>
                                    <span>Huella Dactilar</span>
                                    <small>Verificación biométrica</small>
                                </button>
                                
                                <button type="button" 
                                        class="verification-btn facial-btn" 
                                        id="facialBtn"
                                        onclick="openFacialModal()"
                                        disabled>
                                    <i class="fas fa-user-shield"></i>
                                    <span>Reconocimiento Facial</span>
                                    <small>Verificación biométrica</small>
                                </button>
                                
                                <button type="button" 
                                        class="verification-btn traditional-btn" 
                                        id="traditionalBtn"
                                        onclick="openTraditionalModal()"
                                        disabled>
                                    <i class="fas fa-camera"></i>
                                    <span>Verificación Tradicional</span>
                                    <small>Captura de foto</small>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Status Cards -->
            <div class="status-cards">
                <div class="status-card">
                    <div class="status-icon success">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="status-content">
                        <h4 id="todayAttendances">0</h4>
                        <p>Asistencias Hoy</p>
                    </div>
                </div>
                
                <div class="status-card">
                    <div class="status-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="status-content">
                        <h4 id="lateArrivals">0</h4>
                        <p>Llegadas Tarde</p>
                    </div>
                </div>
                
                <div class="status-card">
                    <div class="status-icon info">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div class="status-content">
                        <h4 id="biometricVerifications">0</h4>
                        <p>Verificaciones Biométricas</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Fingerprint Verification Modal -->
<div id="fingerprintModal" class="modal">
    <div class="modal-content biometric-modal">
        <div class="modal-header">
            <h3><i class="fas fa-fingerprint"></i> Verificación de Huella Dactilar</h3>
            <button type="button" class="modal-close" onclick="closeFingerprintModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="biometric-status" id="fingerprintStatus">
                <div class="status-icon">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <p>Esperando lectura de huella dactilar...</p>
                <div class="loading-spinner"></div>
            </div>
            <div class="fingerprint-scanner">
                <div class="scanner-area">
                    <div class="scanner-frame">
                        <i class="fas fa-fingerprint scanner-icon"></i>
                    </div>
                    <p>Coloque su dedo en el lector</p>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeFingerprintModal()">
                    Cancelar
                </button>
                <button type="button" class="btn-primary" id="simulateFingerprintBtn" onclick="simulateFingerprintScan()">
                    Simular Lectura
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Facial Recognition Modal -->
<div id="facialModal" class="modal">
    <div class="modal-content biometric-modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-shield"></i> Reconocimiento Facial</h3>
            <button type="button" class="modal-close" onclick="closeFacialModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="facial-camera">
                <video id="facialVideo" autoplay playsinline></video>
                <canvas id="facialCanvas" style="display: none;"></canvas>
                <div class="camera-overlay">
                    <div class="face-frame"></div>
                </div>
            </div>
            <div class="biometric-status" id="facialStatus">
                <p>Posicione su rostro en el marco y manténgase inmóvil</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeFacialModal()">
                    Cancelar
                </button>
                <button type="button" class="btn-primary" id="captureFacialBtn" onclick="captureFacialImage()">
                    Verificar Rostro
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Traditional Photo Modal -->
<div id="traditionalModal" class="modal">
    <div class="modal-content biometric-modal">
        <div class="modal-header">
            <h3><i class="fas fa-camera"></i> Captura Tradicional</h3>
            <button type="button" class="modal-close" onclick="closeTraditionalModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="traditional-camera">
                <video id="traditionalVideo" autoplay playsinline></video>
                <canvas id="traditionalCanvas" style="display: none;"></canvas>
            </div>
            <div class="camera-status" id="traditionalStatus">
                <p>Posiciónese frente a la cámara</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeTraditionalModal()">
                    Cancelar
                </button>
                <button type="button" class="btn-primary" id="captureTraditionalBtn" onclick="captureTraditionalPhoto()">
                    Tomar Foto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<!-- Scripts -->
<script src="../../assets/js/camera.js"></script>
<script src="../../assets/js/asistencia.js"></script>

<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    initializeAttendancePage();
    loadTodayStats();
});
</script>

</body>
</html>