<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Demo Biométrico | SynkTime</title>
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
            background: var(--surface);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
        }
        .demo-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .demo-header h1 {
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .demo-section {
            margin-bottom: 3rem;
            padding: 2rem;
            background: var(--background);
            border-radius: var(--border-radius);
        }
        .demo-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .demo-btn {
            padding: 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: all 0.3s ease;
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