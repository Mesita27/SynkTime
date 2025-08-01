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
    <link rel="stylesheet" href="assets/css/biometric.css">
    
    <!-- Biometric Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/dist/face-api.min.js"></script>
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="biometric-header">
                <h2 class="page-title"><i class="fas fa-fingerprint"></i> Inscripción Biométrica</h2>
                <p class="page-subtitle">Registre datos biométricos para mejorar la seguridad del sistema de asistencias</p>
            </div>
            
            <!-- Employee Selection -->
            <div class="biometric-section">
                <div class="section-header">
                    <h3><i class="fas fa-user-search"></i> Seleccionar Empleado</h3>
                </div>
                
                <div class="employee-search-container">
                    <div class="search-filters">
                        <div class="form-group">
                            <label for="filtro_sede_bio">Sede</label>
                            <select id="filtro_sede_bio" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_establecimiento_bio">Establecimiento</label>
                            <select id="filtro_establecimiento_bio" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label for="codigo_empleado_bio">Código Empleado</label>
                            <input type="text" id="codigo_empleado_bio" class="form-control" placeholder="Ingrese código">
                        </div>
                        <div class="form-group">
                            <button type="button" id="btnBuscarEmpleado" class="btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    
                    <div id="employeeInfo" class="employee-info" style="display: none;">
                        <div class="employee-card">
                            <div class="employee-details">
                                <h4 id="employeeName"></h4>
                                <p><strong>Código:</strong> <span id="employeeCode"></span></p>
                                <p><strong>Establecimiento:</strong> <span id="employeeEstablishment"></span></p>
                                <p><strong>Sede:</strong> <span id="employeeSede"></span></p>
                            </div>
                            <div class="biometric-status">
                                <div class="status-item">
                                    <i class="fas fa-fingerprint"></i>
                                    <span>Huella Digital:</span>
                                    <span id="fingerprintStatus" class="status-badge">No inscrito</span>
                                </div>
                                <div class="status-item">
                                    <i class="fas fa-face-smile"></i>
                                    <span>Reconocimiento Facial:</span>
                                    <span id="facialStatus" class="status-badge">No inscrito</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Biometric Enrollment Section -->
            <div id="biometricEnrollmentSection" class="biometric-section" style="display: none;">
                <div class="section-header">
                    <h3><i class="fas fa-shield-alt"></i> Inscripción de Datos Biométricos</h3>
                </div>
                
                <div class="enrollment-options">
                    <div class="enrollment-card" id="fingerprintCard">
                        <div class="card-header">
                            <i class="fas fa-fingerprint"></i>
                            <h4>Inscripción de Huella Digital</h4>
                        </div>
                        <div class="card-body">
                            <p>Registre múltiples muestras de huella digital para mayor precisión</p>
                            <div class="device-status">
                                <span id="fingerprintDeviceStatus">Detectando dispositivos...</span>
                            </div>
                            <button type="button" id="btnStartFingerprintEnrollment" class="btn-primary" disabled>
                                <i class="fas fa-play"></i> Iniciar Inscripción
                            </button>
                        </div>
                    </div>
                    
                    <div class="enrollment-card" id="facialCard">
                        <div class="card-header">
                            <i class="fas fa-face-smile"></i>
                            <h4>Inscripción de Reconocimiento Facial</h4>
                        </div>
                        <div class="card-body">
                            <p>Capture múltiples ángulos del rostro para reconocimiento preciso</p>
                            <div class="device-status">
                                <span id="facialDeviceStatus">Verificando cámara...</span>
                            </div>
                            <button type="button" id="btnStartFacialEnrollment" class="btn-primary" disabled>
                                <i class="fas fa-play"></i> Iniciar Inscripción
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modals -->
            <?php include 'components/biometric_fingerprint_modal.php'; ?>
            <?php include 'components/biometric_facial_modal.php'; ?>
            
        </main>
    </div>
</div>

<script src="assets/js/layout.js"></script>
<script src="assets/js/biometric-enrollment.js"></script>
</body>
</html>