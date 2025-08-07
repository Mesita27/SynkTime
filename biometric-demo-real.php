<?php
require_once 'auth/session.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Demo Reconocimiento Biométrico Real | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/biometric.css">
    <style>
        .demo-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .demo-section {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .demo-video {
            width: 100%;
            max-width: 640px;
            height: 480px;
            background: #000;
            border-radius: 8px;
            position: relative;
        }
        .demo-canvas {
            position: absolute;
            top: 0;
            left: 0;
            border-radius: 8px;
        }
        .demo-controls {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .demo-status {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 5px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .demo-results {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="demo-container">
                <h2><i class="fas fa-vial"></i> Demo de Reconocimiento Biométrico Real</h2>
                <p class="text-muted">Prueba las funcionalidades de reconocimiento facial usando Face-api.js</p>
                
                <!-- Sistema de Cámara -->
                <div class="demo-section">
                    <h3><i class="fas fa-camera"></i> Sistema de Cámara</h3>
                    <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 300px;">
                            <video id="demoVideo" class="demo-video" autoplay playsinline></video>
                            <canvas id="demoCanvas" class="demo-canvas" style="display: none;"></canvas>
                        </div>
                        <div style="flex: 1; min-width: 300px;">
                            <h4>Controles</h4>
                            <div class="demo-controls">
                                <button class="btn btn-primary" onclick="startDemoCamera()">
                                    <i class="fas fa-play"></i> Iniciar Cámara
                                </button>
                                <button class="btn btn-danger" onclick="stopDemoCamera()">
                                    <i class="fas fa-stop"></i> Detener Cámara
                                </button>
                                <button class="btn btn-success" onclick="startFaceDetection()">
                                    <i class="fas fa-search"></i> Detectar Rostros
                                </button>
                                <button class="btn btn-warning" onclick="stopFaceDetection()">
                                    <i class="fas fa-pause"></i> Pausar Detección
                                </button>
                            </div>
                            <div id="cameraStatus" class="demo-status">
                                Estado: Cámara no iniciada
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inscripción Facial -->
                <div class="demo-section">
                    <h3><i class="fas fa-user-plus"></i> Inscripción Facial</h3>
                    <div class="demo-controls">
                        <input type="number" id="enrollEmployeeId" placeholder="ID del Empleado" class="form-control" style="max-width: 200px;">
                        <button class="btn btn-primary" onclick="startDemoEnrollment()">
                            <i class="fas fa-fingerprint"></i> Iniciar Inscripción
                        </button>
                        <button class="btn btn-success" onclick="captureDemoEnrollment()">
                            <i class="fas fa-camera"></i> Capturar Rostro
                        </button>
                    </div>
                    <div id="enrollmentStatus" class="demo-status">
                        Estado: Listo para inscripción
                    </div>
                    <div id="enrollmentResults" class="demo-results" style="display: none;"></div>
                </div>

                <!-- Verificación Facial -->
                <div class="demo-section">
                    <h3><i class="fas fa-shield-alt"></i> Verificación Facial</h3>
                    <div class="demo-controls">
                        <input type="number" id="verifyEmployeeId" placeholder="ID del Empleado" class="form-control" style="max-width: 200px;">
                        <button class="btn btn-primary" onclick="startDemoVerification()">
                            <i class="fas fa-search"></i> Cargar Datos
                        </button>
                        <button class="btn btn-success" onclick="verifyDemoFace()">
                            <i class="fas fa-check"></i> Verificar Rostro
                        </button>
                    </div>
                    <div id="verificationStatus" class="demo-status">
                        Estado: Listo para verificación
                    </div>
                    <div id="verificationResults" class="demo-results" style="display: none;"></div>
                </div>

                <!-- Estadísticas -->
                <div class="demo-section">
                    <h3><i class="fas fa-chart-bar"></i> Estadísticas del Sistema</h3>
                    <div id="systemStats" class="demo-results">
                        Cargando estadísticas...
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Face-api.js library -->
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/dist/face-api.min.js"></script>
<script src="assets/js/face-api-models.js"></script>
<script src="assets/js/biometric-real.js"></script>
<script src="assets/js/layout.js"></script>

<script>
// Demo-specific variables
let demoSystem = null;
let isDetecting = false;
let enrollmentData = null;
let storedTemplate = null;

// Initialize demo
document.addEventListener('DOMContentLoaded', async function() {
    updateStatus('cameraStatus', 'Inicializando sistema biométrico...');
    
    try {
        demoSystem = window.realBiometricSystem;
        await demoSystem.initialize();
        updateStatus('cameraStatus', 'Sistema biométrico listo');
        loadSystemStats();
    } catch (error) {
        updateStatus('cameraStatus', `Error: ${error.message}`);
    }
});

// Camera functions
async function startDemoCamera() {
    try {
        demoSystem.setupVideoElement('demoVideo', 'demoCanvas');
        await demoSystem.startCamera();
        updateStatus('cameraStatus', 'Cámara iniciada correctamente');
    } catch (error) {
        updateStatus('cameraStatus', `Error iniciando cámara: ${error.message}`);
    }
}

function stopDemoCamera() {
    demoSystem.stopCamera();
    updateStatus('cameraStatus', 'Cámara detenida');
    stopFaceDetection();
}

function startFaceDetection() {
    if (isDetecting) return;
    
    isDetecting = true;
    demoSystem.startRealTimeDetection((detections) => {
        if (detections && detections.length > 0) {
            updateStatus('cameraStatus', `${detections.length} rostro(s) detectado(s) - Confianza: ${Math.round(detections[0].detection.score * 100)}%`);
        } else {
            updateStatus('cameraStatus', 'Cámara activa - Sin rostros detectados');
        }
    });
}

function stopFaceDetection() {
    isDetecting = false;
    demoSystem.stopRealTimeDetection();
    updateStatus('cameraStatus', 'Detección pausada');
}

// Enrollment functions
async function startDemoEnrollment() {
    const employeeId = document.getElementById('enrollEmployeeId').value;
    if (!employeeId) {
        updateStatus('enrollmentStatus', 'Error: Ingrese un ID de empleado');
        return;
    }
    
    updateStatus('enrollmentStatus', `Preparando inscripción para empleado ${employeeId}...`);
    await startDemoCamera();
    updateStatus('enrollmentStatus', 'Listo para capturar rostros. Presione "Capturar Rostro"');
}

async function captureDemoEnrollment() {
    const employeeId = document.getElementById('enrollEmployeeId').value;
    if (!employeeId) {
        updateStatus('enrollmentStatus', 'Error: Ingrese un ID de empleado');
        return;
    }
    
    try {
        updateStatus('enrollmentStatus', 'Capturando rostros para inscripción...');
        enrollmentData = await demoSystem.enrollFace(employeeId, 3);
        
        updateStatus('enrollmentStatus', 'Rostros capturados. Guardando en servidor...');
        
        const success = await saveDemoEnrollment();
        if (success) {
            updateStatus('enrollmentStatus', 'Inscripción completada exitosamente');
            showResults('enrollmentResults', JSON.stringify(enrollmentData, null, 2));
        }
    } catch (error) {
        updateStatus('enrollmentStatus', `Error en inscripción: ${error.message}`);
    }
}

// Verification functions
async function startDemoVerification() {
    const employeeId = document.getElementById('verifyEmployeeId').value;
    if (!employeeId) {
        updateStatus('verificationStatus', 'Error: Ingrese un ID de empleado');
        return;
    }
    
    try {
        updateStatus('verificationStatus', 'Cargando datos biométricos...');
        const response = await fetch('api/biometric/get-face-template.php', {
            method: 'POST',
            body: new URLSearchParams({ employee_id: employeeId })
        });
        
        const result = await response.json();
        if (result.success) {
            storedTemplate = result.template;
            updateStatus('verificationStatus', `Datos cargados para ${result.employee.name}. Listo para verificar.`);
            await startDemoCamera();
        } else {
            updateStatus('verificationStatus', `Error: ${result.message}`);
        }
    } catch (error) {
        updateStatus('verificationStatus', `Error cargando datos: ${error.message}`);
    }
}

async function verifyDemoFace() {
    if (!storedTemplate) {
        updateStatus('verificationStatus', 'Error: Primero cargue los datos del empleado');
        return;
    }
    
    try {
        updateStatus('verificationStatus', 'Verificando rostro...');
        const result = await demoSystem.verifyFace(storedTemplate);
        
        updateStatus('verificationStatus', `Verificación: ${result.message}`);
        showResults('verificationResults', JSON.stringify(result, null, 2));
    } catch (error) {
        updateStatus('verificationStatus', `Error en verificación: ${error.message}`);
    }
}

// Helper functions
function updateStatus(elementId, message) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = `Estado: ${message}`;
    }
}

function showResults(elementId, data) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = data;
        element.style.display = 'block';
    }
}

async function saveDemoEnrollment() {
    try {
        const formData = new URLSearchParams({
            employee_id: enrollmentData.employeeId,
            face_template: JSON.stringify(enrollmentData.template),
            captures_data: JSON.stringify(enrollmentData.captures)
        });

        const response = await fetch('api/biometric/enroll-facial-real.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        return result.success;
    } catch (error) {
        console.error('Error saving enrollment:', error);
        return false;
    }
}

async function loadSystemStats() {
    try {
        const statsElement = document.getElementById('systemStats');
        statsElement.textContent = `
Sistema: Face-api.js v${faceapi.version || 'Desconocida'}
Modelos cargados: ${window.faceAPILoader.modelsLoaded ? 'Sí' : 'No'}
Cámara disponible: ${navigator.mediaDevices ? 'Sí' : 'No'}
HTTPS: ${location.protocol === 'https:' ? 'Sí' : 'No'}
Navegador: ${navigator.userAgent.split(' ').pop()}
        `;
    } catch (error) {
        document.getElementById('systemStats').textContent = `Error cargando estadísticas: ${error.message}`;
    }
}
</script>
</body>
</html>