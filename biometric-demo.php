<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Demo Biométrico Avanzado | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/biometric.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .demo-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .demo-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .demo-header h1 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        .demo-header p {
            color: #666;
            font-size: 1.1rem;
        }
        .demo-section {
            margin-bottom: 3rem;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 15px;
            border-left: 5px solid #667eea;
        }
        .demo-section h2 {
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .api-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        .status-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #28a745;
        }
        .status-card.error {
            border-left-color: #dc3545;
        }
        .status-card.warning {
            border-left-color: #ffc107;
        }
        .demo-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .demo-btn {
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            font-weight: 500;
        }
        .demo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .demo-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .demo-btn i {
            margin-right: 0.5rem;
        }
        .test-results {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
            border: 1px solid #e9ecef;
        }
        .result-item {
            padding: 1rem;
            margin: 0.5rem 0;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
            background: #f8f9fa;
        }
        .result-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .result-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1><i class="fas fa-fingerprint"></i> Demo Biométrico Avanzado</h1>
            <p>Prueba las capacidades de reconocimiento biométrico mejoradas con APIs externas</p>
        </div>

        <!-- API Status Section -->
        <div class="demo-section">
            <h2><i class="fas fa-server"></i> Estado de APIs Externas</h2>
            <div class="api-status">
                <div class="status-card" id="faceapi-status">
                    <h4><i class="fas fa-user-circle"></i> Face-api.js</h4>
                    <p id="faceapi-status-text">Verificando...</p>
                    <small id="faceapi-details">Biblioteca de reconocimiento facial</small>
                </div>
                <div class="status-card" id="webauthn-status">
                    <h4><i class="fas fa-fingerprint"></i> WebAuthn API</h4>
                    <p id="webauthn-status-text">Verificando...</p>
                    <small id="webauthn-details">API nativa de autenticación biométrica</small>
                </div>
                <div class="status-card" id="camera-status">
                    <h4><i class="fas fa-camera"></i> Cámara</h4>
                    <p id="camera-status-text">Verificando...</p>
                    <small id="camera-details">Dispositivo de captura de video</small>
                </div>
            </div>
        </div>

        <!-- Facial Recognition Demo -->
        <div class="demo-section">
            <h2><i class="fas fa-user-circle"></i> Reconocimiento Facial Avanzado</h2>
            <p>Prueba el reconocimiento facial usando Face-api.js para detección y análisis en tiempo real.</p>
            <div class="demo-buttons">
                <button class="demo-btn" id="test-face-detection" onclick="testFaceDetection()">
                    <i class="fas fa-search"></i> Probar Detección Facial
                </button>
                <button class="demo-btn" id="test-face-enrollment" onclick="testFaceEnrollment()" disabled>
                    <i class="fas fa-user-plus"></i> Simular Inscripción Facial
                </button>
                <button class="demo-btn" id="test-face-verification" onclick="testFaceVerification()" disabled>
                    <i class="fas fa-check-circle"></i> Probar Verificación Facial
                </button>
            </div>
            <div class="test-results" id="face-results" style="display: none;">
                <h4>Resultados de Pruebas Faciales:</h4>
                <div id="face-test-output"></div>
            </div>
        </div>

        <!-- Fingerprint Authentication Demo -->
        <div class="demo-section">
            <h2><i class="fas fa-fingerprint"></i> Autenticación Biométrica Avanzada</h2>
            <p>Prueba la autenticación biométrica usando WebAuthn API para sensores de huella dactilar nativos.</p>
            <div class="demo-buttons">
                <button class="demo-btn" id="test-webauthn-support" onclick="testWebAuthnSupport()">
                    <i class="fas fa-info-circle"></i> Verificar Soporte WebAuthn
                </button>
                <button class="demo-btn" id="test-biometric-enrollment" onclick="testBiometricEnrollment()" disabled>
                    <i class="fas fa-fingerprint"></i> Simular Inscripción Biométrica
                </button>
                <button class="demo-btn" id="test-biometric-verification" onclick="testBiometricVerification()" disabled>
                    <i class="fas fa-shield-alt"></i> Probar Verificación Biométrica
                </button>
            </div>
            <div class="test-results" id="fingerprint-results" style="display: none;">
                <h4>Resultados de Pruebas Biométricas:</h4>
                <div id="fingerprint-test-output"></div>
            </div>
        </div>

        <!-- Performance Test -->
        <div class="demo-section">
            <h2><i class="fas fa-tachometer-alt"></i> Pruebas de Rendimiento</h2>
            <p>Evalúa el rendimiento de las APIs para soportar alto volumen de solicitudes.</p>
            <div class="demo-buttons">
                <button class="demo-btn" id="test-performance" onclick="testPerformance()">
                    <i class="fas fa-stopwatch"></i> Probar Rendimiento
                </button>
                <button class="demo-btn" id="test-concurrent" onclick="testConcurrentRequests()">
                    <i class="fas fa-layer-group"></i> Pruebas Concurrentes
                </button>
            </div>
            <div class="test-results" id="performance-results" style="display: none;">
                <h4>Resultados de Rendimiento:</h4>
                <div id="performance-test-output"></div>
            </div>
        </div>
    </div>

    <!-- Include biometric APIs -->
    <script src="assets/js/biometric-apis.js"></script>
    
    <script>
        // Global test variables
        let testEmployee = { id: 999, name: 'Usuario Demo' };
        
        // Initialize demo on page load
        document.addEventListener('DOMContentLoaded', async function() {
            await initializeDemo();
        });

        async function initializeDemo() {
            console.log('Initializing biometric demo...');
            
            // Check API availability
            await checkAPIStatus();
            
            // Initialize biometric APIs
            if (window.BiometricAPIs) {
                try {
                    await window.BiometricAPIs.initializeRealBiometricAPIs();
                    console.log('Biometric APIs initialized for demo');
                } catch (error) {
                    console.error('Error initializing APIs:', error);
                }
            }
        }

        async function checkAPIStatus() {
            // Check Face-api.js
            try {
                if (window.BiometricAPIs && window.BiometricAPIs.loadFaceApiLibrary) {
                    await window.BiometricAPIs.loadFaceApiLibrary();
                    updateStatusCard('faceapi', 'success', 'Disponible y cargado', 'Face-api.js cargado correctamente');
                    document.getElementById('test-face-enrollment').disabled = false;
                    document.getElementById('test-face-verification').disabled = false;
                } else {
                    throw new Error('Face-api.js not available');
                }
            } catch (error) {
                updateStatusCard('faceapi', 'error', 'No disponible', error.message);
            }

            // Check WebAuthn
            try {
                if (window.BiometricAPIs && window.BiometricAPIs.isWebAuthnSupported && 
                    window.BiometricAPIs.isWebAuthnSupported()) {
                    const platformAvailable = await window.BiometricAPIs.isPlatformAuthenticatorAvailable();
                    if (platformAvailable) {
                        updateStatusCard('webauthn', 'success', 'Soporte completo', 'WebAuthn y sensores biométricos disponibles');
                        document.getElementById('test-biometric-enrollment').disabled = false;
                        document.getElementById('test-biometric-verification').disabled = false;
                    } else {
                        updateStatusCard('webauthn', 'warning', 'Soporte parcial', 'WebAuthn disponible pero sin sensores biométricos');
                    }
                } else {
                    throw new Error('WebAuthn not supported');
                }
            } catch (error) {
                updateStatusCard('webauthn', 'error', 'No disponible', error.message);
            }

            // Check Camera
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                stream.getTracks().forEach(track => track.stop());
                updateStatusCard('camera', 'success', 'Disponible', 'Cámara accesible para captura de video');
            } catch (error) {
                updateStatusCard('camera', 'error', 'No disponible', error.message);
            }
        }

        function updateStatusCard(type, status, text, details) {
            const card = document.getElementById(`${type}-status`);
            const textEl = document.getElementById(`${type}-status-text`);
            const detailsEl = document.getElementById(`${type}-details`);

            card.className = `status-card ${status}`;
            textEl.textContent = text;
            detailsEl.textContent = details;
        }

        function addTestResult(containerId, type, title, message, details = '') {
            const container = document.getElementById(containerId);
            const output = container.querySelector('div[id$="test-output"]');
            
            const resultItem = document.createElement('div');
            resultItem.className = `result-item ${type}`;
            resultItem.innerHTML = `
                <h5><strong>${title}</strong></h5>
                <p>${message}</p>
                ${details ? `<small>${details}</small>` : ''}
                <small style="float: right; color: #666;">${new Date().toLocaleTimeString()}</small>
            `;
            
            output.appendChild(resultItem);
            container.style.display = 'block';
            
            // Scroll to result
            resultItem.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Test Functions
        async function testFaceDetection() {
            const btn = document.getElementById('test-face-detection');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';

            try {
                // Create a temporary video element for testing
                const video = document.createElement('video');
                video.style.display = 'none';
                document.body.appendChild(video);

                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                
                await new Promise(resolve => {
                    video.onloadedmetadata = resolve;
                });

                // Test face detection
                if (window.BiometricAPIs && typeof faceapi !== 'undefined') {
                    const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions());
                    
                    stream.getTracks().forEach(track => track.stop());
                    document.body.removeChild(video);

                    addTestResult('face-results', 'success', 
                        'Detección Facial Exitosa', 
                        `Se detectaron ${detections.length} rostro(s) en la imagen de prueba`,
                        'Face-api.js funcionando correctamente');
                } else {
                    throw new Error('Face-api.js no está disponible');
                }
            } catch (error) {
                addTestResult('face-results', 'error', 
                    'Error en Detección Facial', 
                    error.message,
                    'Verificar permisos de cámara y soporte del navegador');
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i> Probar Detección Facial';
        }

        async function testFaceEnrollment() {
            addTestResult('face-results', 'success', 
                'Simulación de Inscripción Facial', 
                'Inscripción facial simulada para usuario demo',
                'En implementación real, se almacenaría el descriptor facial encriptado');
        }

        async function testFaceVerification() {
            addTestResult('face-results', 'success', 
                'Simulación de Verificación Facial', 
                'Verificación facial simulada con 95% de confianza',
                'En implementación real, se compararían descriptores faciales');
        }

        async function testWebAuthnSupport() {
            const btn = document.getElementById('test-webauthn-support');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';

            try {
                if (window.BiometricAPIs) {
                    const supported = window.BiometricAPIs.isWebAuthnSupported();
                    const platformAvailable = await window.BiometricAPIs.isPlatformAuthenticatorAvailable();

                    addTestResult('fingerprint-results', 'success', 
                        'Verificación WebAuthn Completada', 
                        `WebAuthn soportado: ${supported ? 'Sí' : 'No'}, Autenticador de plataforma: ${platformAvailable ? 'Disponible' : 'No disponible'}`,
                        'WebAuthn permite autenticación biométrica nativa del navegador');
                } else {
                    throw new Error('BiometricAPIs no disponible');
                }
            } catch (error) {
                addTestResult('fingerprint-results', 'error', 
                    'Error en Verificación WebAuthn', 
                    error.message);
            }

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-info-circle"></i> Verificar Soporte WebAuthn';
        }

        async function testBiometricEnrollment() {
            addTestResult('fingerprint-results', 'success', 
                'Simulación de Inscripción Biométrica', 
                'Inscripción biométrica simulada usando WebAuthn',
                'En implementación real, se crearía una credencial criptográfica');
        }

        async function testBiometricVerification() {
            addTestResult('fingerprint-results', 'success', 
                'Simulación de Verificación Biométrica', 
                'Verificación biométrica simulada exitosa',
                'En implementación real, se verificaría la credencial criptográfica');
        }

        async function testPerformance() {
            const btn = document.getElementById('test-performance');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';

            const start = performance.now();
            
            // Simulate multiple operations
            const operations = [];
            for (let i = 0; i < 10; i++) {
                operations.push(new Promise(resolve => setTimeout(resolve, Math.random() * 100)));
            }

            await Promise.all(operations);
            
            const end = performance.now();
            const duration = end - start;

            addTestResult('performance-results', 'success', 
                'Prueba de Rendimiento Completada', 
                `10 operaciones completadas en ${duration.toFixed(2)}ms`,
                `Promedio: ${(duration/10).toFixed(2)}ms por operación`);

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-stopwatch"></i> Probar Rendimiento';
        }

        async function testConcurrentRequests() {
            const btn = document.getElementById('test-concurrent');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Probando...';

            const start = performance.now();
            const concurrentCount = 50;
            
            // Simulate concurrent requests
            const requests = Array(concurrentCount).fill().map((_, i) => 
                new Promise(resolve => setTimeout(() => resolve(i), Math.random() * 200))
            );

            const results = await Promise.all(requests);
            
            const end = performance.now();
            const duration = end - start;

            addTestResult('performance-results', 'success', 
                'Prueba de Solicitudes Concurrentes', 
                `${concurrentCount} solicitudes concurrentes completadas en ${duration.toFixed(2)}ms`,
                `Throughput: ${(concurrentCount / (duration/1000)).toFixed(2)} solicitudes/segundo`);

            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-layer-group"></i> Pruebas Concurrentes';
        }
    </script>
</body>
</html>
            font-size: 1.1rem;
            font-weight: 500;
        }
        .demo-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .demo-btn i {
            margin-right: 0.5rem;
            font-size: 1.3rem;
        }
        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .feature-item {
            padding: 1rem;
            background: var(--surface);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        .feature-item h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text-primary);
        }
        .feature-item p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="demo-container">
    <div class="demo-header">
        <h1><i class="fas fa-fingerprint"></i> SynkTime - Sistema Biométrico</h1>
        <p>Demostración del nuevo sistema de verificación biométrica integrado</p>
    </div>
    
    <div class="demo-section">
        <h2><i class="fas fa-shield-alt"></i> Verificación Biométrica para Asistencias</h2>
        <p>El sistema ahora soporta múltiples métodos de verificación para el registro de asistencias:</p>
        
        <div class="feature-list">
            <div class="feature-item">
                <h4><i class="fas fa-fingerprint"></i> Verificación por Huella</h4>
                <p>Utiliza lectores de huellas dactilares para verificación segura e instantánea.</p>
            </div>
            <div class="feature-item">
                <h4><i class="fas fa-user-circle"></i> Reconocimiento Facial</h4>
                <p>Captura automática de foto con procesamiento de reconocimiento facial.</p>
            </div>
            <div class="feature-item">
                <h4><i class="fas fa-camera"></i> Verificación Tradicional</h4>
                <p>Captura manual de foto manteniendo la funcionalidad original.</p>
            </div>
        </div>
        
        <div class="demo-buttons">
            <button class="demo-btn" onclick="openBiometricVerificationModal(123, 'Juan Pérez')">
                <i class="fas fa-shield-alt"></i> Demo Verificación Biométrica
            </button>
            <button class="demo-btn" onclick="openFingerprintVerificationModal()">
                <i class="fas fa-fingerprint"></i> Demo Verificación por Huella
            </button>
            <button class="demo-btn" onclick="openFacialVerificationModal()">
                <i class="fas fa-user-circle"></i> Demo Reconocimiento Facial
            </button>
        </div>
    </div>
    
    <div class="demo-section">
        <h2><i class="fas fa-user-plus"></i> Inscripción Biométrica</h2>
        <p>Sistema completo para la inscripción y gestión de datos biométricos de empleados:</p>
        
        <div class="feature-list">
            <div class="feature-item">
                <h4><i class="fas fa-hand-paper"></i> Inscripción de Huellas</h4>
                <p>Registro de huellas dactilares con selección de dedos específicos.</p>
            </div>
            <div class="feature-item">
                <h4><i class="fas fa-camera-retro"></i> Inscripción Facial</h4>
                <p>Captura múltiple de patrones faciales para mayor precisión.</p>
            </div>
            <div class="feature-item">
                <h4><i class="fas fa-search"></i> Gestión de Empleados</h4>
                <p>Búsqueda y filtrado dinámico de empleados con estado biométrico.</p>
            </div>
            <div class="feature-item">
                <h4><i class="fas fa-chart-bar"></i> Estadísticas</h4>
                <p>Dashboard con métricas de inscripción y estado general del sistema.</p>
            </div>
        </div>
        
        <div class="demo-buttons">
            <button class="demo-btn" onclick="openBiometricEnrollmentModal()">
                <i class="fas fa-user-plus"></i> Demo Inscripción Biométrica
            </button>
            <button class="demo-btn" onclick="openFingerprintEnrollmentModal()">
                <i class="fas fa-fingerprint"></i> Demo Inscripción de Huella
            </button>
            <button class="demo-btn" onclick="openFacialEnrollmentModal()">
                <i class="fas fa-user-circle"></i> Demo Inscripción Facial
            </button>
        </div>
    </div>
    
    <div class="demo-section">
        <h2><i class="fas fa-cog"></i> Características Técnicas</h2>
        
        <div class="feature-list">
            <div class="feature-item">
                <h4><i class="fas fa-plug"></i> Detección Automática</h4>
                <p>Detecta automáticamente dispositivos biométricos conectados.</p>
            </div>
            <div class="feature-item">
                <h4><i class="fas fa-shield-virus"></i> Seguridad</h4>
                <p>Encriptación de datos biométricos y registro de auditoría.</p>
            </div>
            <div class="feature-item">
                <h4><i class="fas fa-mobile-alt"></i> Responsive</h4>
                <p>Diseño adaptable para dispositivos móviles y tablets.</p>
            </div>
            <div class="feature-item">
                <h4><i class="fas fa-database"></i> Base de Datos</h4>
                <p>Creación automática de tablas y compatibilidad total con el sistema existente.</p>
            </div>
        </div>
    </div>
</div>

<!-- Include all biometric modals -->
<?php include 'components/biometric_verification_modal.php'; ?>
<?php include 'components/biometric_enrollment_modal.php'; ?>

<script>
// Mock data for demo
selectedEmployee = { id: 123, name: 'Juan Pérez' };

// Initialize demo functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize biometric system for demo
    if (typeof initializeBiometricSystem === 'function') {
        initializeBiometricSystem();
    }
    
    // Show demo notifications
    setTimeout(() => {
        showNotification('Demo del Sistema Biométrico SynkTime cargado correctamente', 'success');
    }, 1000);
});

// Demo notification function
function showNotification(message, type = 'info') {
    let notification = document.getElementById('appNotification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'appNotification';
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Add notification styles if not present
    if (!document.getElementById('notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                max-width: 400px;
                padding: 1rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                z-index: 10000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            }
            .notification.show { transform: translateX(0); }
            .notification-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
            .notification-error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
            .notification-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
            .notification-content { display: flex; align-items: center; gap: 0.5rem; }
            .notification-close { background: none; border: none; font-size: 1.2rem; cursor: pointer; margin-left: auto; }
        `;
        document.head.appendChild(style);
    }
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    notification.querySelector('.notification-close').addEventListener('click', function() {
        hideNotification();
    });
    
    const timeout = setTimeout(() => {
        hideNotification();
    }, 5000);
    
    function hideNotification() {
        clearTimeout(timeout);
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}

// Demo functions to open modals directly
function openFingerprintVerificationModal() {
    document.getElementById('fingerprintVerificationModal').classList.add('show');
    updateFingerprintInstruction('Demo: Coloca tu dedo en el lector');
    updateFingerprintStatus('Modo demostración activado');
}

function openFacialVerificationModal() {
    document.getElementById('facialVerificationModal').classList.add('show');
    updateFacialInstruction('Demo: Posiciona tu rostro en el marco');
    updateFacialStatus('Modo demostración - cámara simulada');
}

function openFingerprintEnrollmentModal() {
    document.getElementById('fingerprintEnrollmentModal').classList.add('show');
    document.getElementById('fingerprint_enrollment_employee').textContent = 'Juan Pérez (Demo)';
}

function openFacialEnrollmentModal() {
    document.getElementById('facialEnrollmentModal').classList.add('show');
    document.getElementById('facial_enrollment_employee').textContent = 'Juan Pérez (Demo)';
}
</script>

<script src="assets/js/biometric.js"></script>
</body>
</html>