<?php
/**
 * Test file to verify biometric flow improvements
 * This file can be accessed to test the functionality
 */

require_once 'auth/session.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test Biometric Flow | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/attendance.css">
    <link rel="stylesheet" href="assets/css/biometric.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="attendance-header">
                <h2 class="page-title"><i class="fas fa-vial"></i> Test Biometric Flow</h2>
                <p>Este es un archivo de prueba para verificar las mejoras del sistema biométrico.</p>
            </div>
            
            <div class="test-section">
                <h3>Pruebas de Funcionalidad</h3>
                <div class="test-buttons">
                    <button class="btn-primary" onclick="testAttendanceModal()">
                        <i class="fas fa-calendar-check"></i> Probar Modal de Asistencia
                    </button>
                    <button class="btn-secondary" onclick="testBiometricEnrollment()">
                        <i class="fas fa-fingerprint"></i> Probar Inscripción Biométrica
                    </button>
                    <button class="btn-info" onclick="testQuickFilters()">
                        <i class="fas fa-filter"></i> Probar Filtros Rápidos
                    </button>
                </div>
            </div>
            
            <div class="test-results">
                <h3>Resultados de Pruebas</h3>
                <div id="testResults" class="test-output">
                    <p>Haga clic en los botones para probar la funcionalidad.</p>
                </div>
            </div>
            
            <?php include 'components/attendance_register_modal.php'; ?>
            <?php include 'components/biometric_enrollment_modal.php'; ?>
        </main>
    </div>
</div>

<script src="assets/js/layout.js"></script>
<script src="assets/js/attendance.js"></script>
<script src="assets/js/biometric.js"></script>
<script src="assets/js/biometric-enrollment-page.js"></script>

<script>
function testAttendanceModal() {
    logTest('Abriendo modal de registro de asistencia...');
    openAttendanceRegisterModal();
    logTest('Modal abierto. Verificar que los botones rápidos de filtro biométrico funcionan.');
}

function testBiometricEnrollment() {
    logTest('Abriendo modal de inscripción biométrica...');
    openBiometricEnrollmentModal();
    logTest('Modal abierto. Verificar navegación responsive y funcionalidad.');
}

function testQuickFilters() {
    logTest('Probando filtros rápidos...');
    if (typeof setQuickBiometricFilter === 'function') {
        setQuickBiometricFilter('partial');
        logTest('Filtro rápido establecido a "parcial". Verificar que solo muestra empleados con biometría parcial.');
    } else {
        logTest('ERROR: Función setQuickBiometricFilter no encontrada.');
    }
}

function logTest(message) {
    const resultsDiv = document.getElementById('testResults');
    const timestamp = new Date().toLocaleTimeString();
    resultsDiv.innerHTML += `<p><strong>[${timestamp}]</strong> ${message}</p>`;
    resultsDiv.scrollTop = resultsDiv.scrollHeight;
}

// Auto-initialize test
document.addEventListener('DOMContentLoaded', function() {
    logTest('Sistema de pruebas inicializado.');
    logTest('Las mejoras incluyen:');
    logTest('- Botones rápidos para filtrar empleados por estado biométrico');
    logTest('- Modales responsivos con barras de navegación');
    logTest('- Flujo completo de inscripción y verificación biométrica');
    logTest('- Mejor manejo de errores y retroalimentación del usuario');
});
</script>

<style>
.test-section, .test-results {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.test-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.test-output {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    height: 200px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 0.9rem;
}

.test-output p {
    margin: 0.25rem 0;
    line-height: 1.4;
}
</style>
</body>
</html>