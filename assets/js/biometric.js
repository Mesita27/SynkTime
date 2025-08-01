
// ===================================================================
// BIOMETRIC.JS - Biometric Enrollment Functionality
// ===================================================================

// Global variables for biometric enrollment
let facialStream = null;
let biometricEmployeeId = null;
let capturedFacialData = null;
let capturedFingerprintData = null;

// ===================================================================
// 1. INITIALIZATION AND EVENT LISTENERS
// ===================================================================

document.addEventListener('DOMContentLoaded', function() {
    setupBiometricEventListeners();
});

function setupBiometricEventListeners() {
    // Biometric enrollment button
    const biometricBtn = document.getElementById('biometricEnrollmentBtn');
    if (biometricBtn) {
        biometricBtn.addEventListener('click', function() {
            const employeeId = document.getElementById('id_empleado').value;
            if (employeeId) {
                openBiometricModal(employeeId);
            } else {
                showBiometricError('Debe guardar el empleado antes de configurar datos biométricos.');
            }
        });
    }

    // Modal close listeners
    const closeBiometricBtn = document.getElementById('closeBiometricModal');
    const cancelBiometricBtn = document.getElementById('cancelBiometricModal');
    const biometricModal = document.getElementById('biometricEnrollmentModal');
    
    if (closeBiometricBtn) closeBiometricBtn.addEventListener('click', closeBiometricModal);
    if (cancelBiometricBtn) cancelBiometricBtn.addEventListener('click', closeBiometricModal);
    if (biometricModal) {
        biometricModal.addEventListener('mousedown', function(e) {
            if (e.target === this) closeBiometricModal();
        });
    }

    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            switchBiometricTab(tab);
        });
    });

    // Facial recognition buttons
    const startFacialCamera = document.getElementById('startFacialCamera');
    const captureFacialBtn = document.getElementById('captureFacialBtn');
    const saveFacialBtn = document.getElementById('saveFacialBtn');
    
    if (startFacialCamera) startFacialCamera.addEventListener('click', initializeFacialCamera);
    if (captureFacialBtn) captureFacialBtn.addEventListener('click', captureFacialImage);
    if (saveFacialBtn) saveFacialBtn.addEventListener('click', saveFacialData);

    // Fingerprint buttons
    const startFingerprintBtn = document.getElementById('startFingerprintBtn');
    const captureFingerprintBtn = document.getElementById('captureFingerprintBtn');
    const saveFingerprintBtn = document.getElementById('saveFingerprintBtn');
    
    if (startFingerprintBtn) startFingerprintBtn.addEventListener('click', initializeFingerprintReader);
    if (captureFingerprintBtn) captureFingerprintBtn.addEventListener('click', captureFingerprintData);
    if (saveFingerprintBtn) saveFingerprintBtn.addEventListener('click', saveFingerprintData);

    // Complete biometric registration
    const completeBiometricBtn = document.getElementById('completeBiometricBtn');
    if (completeBiometricBtn) completeBiometricBtn.addEventListener('click', completeBiometricRegistration);
}

// ===================================================================
// 2. MODAL MANAGEMENT
// ===================================================================

function openBiometricModal(employeeId) {
    biometricEmployeeId = employeeId;
    document.getElementById('biometricEmployeeId').value = employeeId;
    
    const modal = document.getElementById('biometricEnrollmentModal');
    if (modal) {
        modal.classList.add('show');
        
        // Check if employee should default to facial recognition (IDs 1, 2, 3)
        if (['1', '2', '3'].includes(employeeId)) {
            switchBiometricTab('facial');
            showBiometricInfo('Este empleado tiene habilitado el reconocimiento facial por defecto.', 'info');
        } else {
            switchBiometricTab('facial'); // Default to facial for all
        }
        
        loadExistingBiometricData(employeeId);
    }
}

function closeBiometricModal() {
    const modal = document.getElementById('biometricEnrollmentModal');
    if (modal) {
        modal.classList.remove('show');
    }
    
    // Clean up camera stream
    if (facialStream) {
        facialStream.getTracks().forEach(track => track.stop());
        facialStream = null;
    }
    
    // Reset state
    resetBiometricForm();
}

function switchBiometricTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tab + 'Tab').classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + 'Content').classList.add('active');
}

// ===================================================================
// 3. FACIAL RECOGNITION FUNCTIONALITY
// ===================================================================

async function initializeFacialCamera() {
    try {
        const video = document.getElementById('facialVideo');
        
        // Request camera access
        facialStream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            }
        });
        
        video.srcObject = facialStream;
        
        // Enable capture button when video is ready
        video.addEventListener('loadedmetadata', function() {
            document.getElementById('captureFacialBtn').disabled = false;
            document.getElementById('startFacialCamera').disabled = true;
            showBiometricStatus('facial', 'Cámara iniciada. Posicione su rostro y haga clic en "Capturar Rostro".', 'info');
        });
        
    } catch (error) {
        console.error('Error accessing camera:', error);
        showBiometricStatus('facial', 'Error al acceder a la cámara: ' + error.message, 'error');
    }
}

function captureFacialImage() {
    const video = document.getElementById('facialVideo');
    const canvas = document.getElementById('facialCanvas');
    const context = canvas.getContext('2d');
    
    // Draw video frame to canvas
    context.drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Convert to base64
    capturedFacialData = canvas.toDataURL('image/jpeg', 0.8);
    
    // Show preview
    const preview = document.getElementById('facialPreview');
    preview.innerHTML = `<img src="${capturedFacialData}" alt="Imagen capturada" style="max-width: 200px; border-radius: 8px;">`;
    
    // Enable save button
    document.getElementById('saveFacialBtn').disabled = false;
    
    showBiometricStatus('facial', 'Imagen facial capturada exitosamente. Haga clic en "Guardar" para almacenar.', 'success');
}

async function saveFacialData() {
    if (!capturedFacialData || !biometricEmployeeId) {
        showBiometricStatus('facial', 'No hay datos faciales para guardar.', 'error');
        return;
    }
    
    try {
        showBiometricStatus('facial', 'Guardando datos faciales...', 'info');
        
        const response = await fetch('api/employee/save-biometric.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                employee_id: biometricEmployeeId,
                type: 'facial',
                data: capturedFacialData
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showBiometricStatus('facial', 'Datos faciales guardados exitosamente.', 'success');
            updateBiometricSummary('facial', true);
            
            // Stop camera stream
            if (facialStream) {
                facialStream.getTracks().forEach(track => track.stop());
                facialStream = null;
            }
            
            // Reset camera UI
            document.getElementById('startFacialCamera').disabled = false;
            document.getElementById('captureFacialBtn').disabled = true;
            document.getElementById('saveFacialBtn').disabled = true;
            
        } else {
            showBiometricStatus('facial', result.message || 'Error al guardar datos faciales.', 'error');
        }
        
    } catch (error) {
        console.error('Error saving facial data:', error);
        showBiometricStatus('facial', 'Error de conexión al guardar datos faciales.', 'error');
    }
}

// ===================================================================
// 4. FINGERPRINT FUNCTIONALITY
// ===================================================================

function initializeFingerprintReader() {
    const simulator = document.querySelector('.fingerprint-simulator');
    const progressBar = document.getElementById('fingerprintProgress');
    
    simulator.classList.add('active');
    document.getElementById('startFingerprintBtn').disabled = true;
    
    showBiometricStatus('fingerprint', 'Simulando lectura de huella dactilar...', 'info');
    
    // Simulate fingerprint reading progress
    let progress = 0;
    const interval = setInterval(() => {
        progress += 10;
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
            clearInterval(interval);
            document.getElementById('captureFingerprintBtn').disabled = false;
            showBiometricStatus('fingerprint', 'Lector listo. Haga clic en "Capturar Huella".', 'success');
        }
    }, 200);
}

function captureFingerprintData() {
    // Simulate fingerprint capture
    showBiometricStatus('fingerprint', 'Capturando datos de huella dactilar...', 'info');
    
    setTimeout(() => {
        // Generate simulated fingerprint data
        capturedFingerprintData = generateSimulatedFingerprintData();
        
        // Show preview
        const preview = document.getElementById('fingerprintPreview');
        preview.innerHTML = `
            <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <i class="fas fa-fingerprint" style="font-size: 48px; color: #28a745;"></i>
                <p>Huella dactilar capturada exitosamente</p>
                <small>Datos encriptados: ${capturedFingerprintData.substring(0, 20)}...</small>
            </div>
        `;
        
        document.getElementById('saveFingerprintBtn').disabled = false;
        showBiometricStatus('fingerprint', 'Huella dactilar capturada exitosamente.', 'success');
        
    }, 1500);
}

async function saveFingerprintData() {
    if (!capturedFingerprintData || !biometricEmployeeId) {
        showBiometricStatus('fingerprint', 'No hay datos de huella para guardar.', 'error');
        return;
    }
    
    try {
        showBiometricStatus('fingerprint', 'Guardando datos de huella dactilar...', 'info');
        
        const response = await fetch('api/employee/save-biometric.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                employee_id: biometricEmployeeId,
                type: 'fingerprint',
                data: capturedFingerprintData
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showBiometricStatus('fingerprint', 'Datos de huella guardados exitosamente.', 'success');
            updateBiometricSummary('fingerprint', true);
            
            // Reset fingerprint UI
            const simulator = document.querySelector('.fingerprint-simulator');
            simulator.classList.remove('active');
            document.getElementById('fingerprintProgress').style.width = '0%';
            document.getElementById('startFingerprintBtn').disabled = false;
            document.getElementById('captureFingerprintBtn').disabled = true;
            document.getElementById('saveFingerprintBtn').disabled = true;
            
        } else {
            showBiometricStatus('fingerprint', result.message || 'Error al guardar datos de huella.', 'error');
        }
        
    } catch (error) {
        console.error('Error saving fingerprint data:', error);
        showBiometricStatus('fingerprint', 'Error de conexión al guardar datos de huella.', 'error');
    }
}

// ===================================================================
// 5. UTILITY FUNCTIONS
// ===================================================================

function generateSimulatedFingerprintData() {
    // Generate a simulated fingerprint hash
    const timestamp = Date.now();
    const random = Math.random().toString(36).substring(7);
    const employeeId = biometricEmployeeId;
    
    // Create a hash-like string for simulation
    return btoa(`fingerprint_${employeeId}_${timestamp}_${random}`);
}

function showBiometricStatus(type, message, statusType) {
    const statusElement = document.getElementById(type + 'Status');
    if (statusElement) {
        statusElement.className = `biometric-status ${statusType}`;
        statusElement.textContent = message;
        statusElement.style.display = 'block';
        
        // Auto-hide info messages after 5 seconds
        if (statusType === 'info') {
            setTimeout(() => {
                statusElement.style.display = 'none';
            }, 5000);
        }
    }
}

function showBiometricError(message) {
    const errorDiv = document.getElementById('biometricFormError');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }
}

function updateBiometricSummary(type, isRegistered) {
    const statusElement = document.getElementById(type + 'StatusSummary') || 
                         document.querySelector(`#biometricSummary .status-item:has(i.fa-${type === 'facial' ? 'face-smile' : 'fingerprint'})`);
    
    if (statusElement) {
        const statusText = statusElement.querySelector('.status-text');
        if (statusText) {
            statusText.textContent = isRegistered ? 'Registrado' : 'No registrado';
        }
        
        if (isRegistered) {
            statusElement.classList.add('success');
        } else {
            statusElement.classList.remove('success');
        }
    }
}

async function loadExistingBiometricData(employeeId) {
    try {
        const response = await fetch(`api/employee/get-biometric.php?id=${employeeId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            
            // Update summary based on existing data
            updateBiometricSummary('facial', data.facial_recognition_enabled === 'Y');
            updateBiometricSummary('fingerprint', data.fingerprint_enabled === 'Y');
            
            if (data.facial_recognition_enabled === 'Y') {
                showBiometricStatus('facial', 'Reconocimiento facial ya configurado para este empleado.', 'success');
            }
            
            if (data.fingerprint_enabled === 'Y') {
                showBiometricStatus('fingerprint', 'Huella dactilar ya configurada para este empleado.', 'success');
            }
        }
        
    } catch (error) {
        console.error('Error loading biometric data:', error);
    }
}

function resetBiometricForm() {
    // Reset all form elements
    capturedFacialData = null;
    capturedFingerprintData = null;
    biometricEmployeeId = null;
    
    // Reset UI elements
    document.getElementById('facialPreview').innerHTML = '';
    document.getElementById('fingerprintPreview').innerHTML = '';
    
    // Reset button states
    document.getElementById('startFacialCamera').disabled = false;
    document.getElementById('captureFacialBtn').disabled = true;
    document.getElementById('saveFacialBtn').disabled = true;
    document.getElementById('startFingerprintBtn').disabled = false;
    document.getElementById('captureFingerprintBtn').disabled = true;
    document.getElementById('saveFingerprintBtn').disabled = true;
    
    // Hide status messages
    document.querySelectorAll('.biometric-status').forEach(status => {
        status.style.display = 'none';
    });
    
    // Reset summary
    updateBiometricSummary('facial', false);
    updateBiometricSummary('fingerprint', false);
}

async function completeBiometricRegistration() {
    // Close the modal and optionally refresh employee data
    closeBiometricModal();
    
    // Reload employee table to reflect biometric status changes
    if (typeof loadEmployees === 'function') {
        loadEmployees();
    }
    
    alert('Registro biométrico completado exitosamente.');
}

// ===================================================================
// 6. INTEGRATION WITH EMPLOYEE MODULE
// ===================================================================

// Function to show/hide biometric button based on employee mode
function toggleBiometricButton(mode, employeeData = null) {
    const biometricBtn = document.getElementById('biometricEnrollmentBtn');
    if (biometricBtn) {
        if (mode === 'editar' && employeeData && employeeData.id) {
            biometricBtn.style.display = 'inline-block';
        } else if (mode === 'crear') {
            biometricBtn.style.display = 'none';
        } else {
            biometricBtn.style.display = 'none';
        }
    }
}

// Export functions for use in employee.js
window.toggleBiometricButton = toggleBiometricButton;
window.openBiometricModal = openBiometricModal;
=======
// ===========================================================================
// BIOMETRIC RECOGNITION SYSTEM JAVASCRIPT
// SynkTime - Attendance Management System
// ===========================================================================

// Global variables for biometric system
let biometricConfig = {
    fingerprintEnabled: true,
    facialEnabled: true,
    fingerprintConfidenceThreshold: 80.0,
    facialConfidenceThreshold: 85.0,
    maxVerificationAttempts: 3,
    verificationTimeout: 30000
};

let currentEmployee = null;
let verificationAttempts = 0;
let facialVideo = null;
let facialCanvas = null;
let facialContext = null;
let faceApiLoaded = false;

// ===========================================================================
// INITIALIZATION AND CONFIGURATION
// ===========================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Load biometric configuration from server
    loadBiometricConfig();
    
    // Initialize face-api.js if facial recognition is enabled
    if (biometricConfig.facialEnabled) {
        loadFaceApiLibrary();
    }
    
    // Check WebAuthn support for fingerprint
    if (biometricConfig.fingerprintEnabled) {
        checkWebAuthnSupport();
    }
});

async function loadBiometricConfig() {
    try {
        const response = await fetch('api/biometric/config.php');
        const data = await response.json();
        if (data.success) {
            biometricConfig = { ...biometricConfig, ...data.config };
        }
    } catch (error) {
        console.error('Error loading biometric config:', error);
    }
}

async function loadFaceApiLibrary() {
    try {
        // Load face-api.js from CDN
        if (!window.faceapi) {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js';
            script.onload = async () => {
                await initializeFaceApi();
            };
            document.head.appendChild(script);
        } else {
            await initializeFaceApi();
        }
    } catch (error) {
        console.error('Error loading face-api.js:', error);
        biometricConfig.facialEnabled = false;
    }
}

async function initializeFaceApi() {
    try {
        // Load face detection models
        await faceapi.nets.tinyFaceDetector.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights');
        await faceapi.nets.faceLandmark68Net.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights');
        await faceapi.nets.faceRecognitionNet.loadFromUri('https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights');
        
        faceApiLoaded = true;
        console.log('Face-API.js loaded successfully');
    } catch (error) {
        console.error('Error initializing face-api.js:', error);
        biometricConfig.facialEnabled = false;
    }
}

function checkWebAuthnSupport() {
    if (!window.PublicKeyCredential) {
        console.warn('WebAuthn not supported, fingerprint recognition disabled');
        biometricConfig.fingerprintEnabled = false;
        return false;
    }
    return true;
}

// ===========================================================================
// BIOMETRIC SELECTION MODAL
// ===========================================================================

function openBiometricSelectionModal(employee) {
    currentEmployee = employee;
    verificationAttempts = 0;
    
    // Update employee information display
    updateEmployeeInfoDisplay(employee);
    
    // Check biometric availability for employee
    checkEmployeeBiometricAvailability(employee.id);
    
    // Show modal
    document.getElementById('biometricSelectionModal').style.display = 'flex';
    
    // Close attendance register modal
    closeAttendanceRegisterModal();
}

function closeBiometricSelectionModal() {
    document.getElementById('biometricSelectionModal').style.display = 'none';
    currentEmployee = null;
    
    // Reset option selections
    document.querySelectorAll('.biometric-option').forEach(option => {
        option.classList.remove('selected', 'disabled');
    });
}

function updateEmployeeInfoDisplay(employee) {
    const employeeInfo = document.getElementById('biometricEmployeeInfo');
    
    document.getElementById('biometric_empleado_nombre').textContent = employee.nombre;
    document.getElementById('biometric_empleado_codigo').textContent = employee.codigo;
    document.getElementById('biometric_empleado_establecimiento').textContent = employee.establecimiento;
    document.getElementById('biometric_empleado_sede').textContent = employee.sede;
    
    employeeInfo.style.display = 'block';
}

async function checkEmployeeBiometricAvailability(employeeId) {
    try {
        const response = await fetch(`api/biometric/check-availability.php?employee_id=${employeeId}`);
        const data = await response.json();
        
        // Update fingerprint option
        const fingerprintOption = document.getElementById('fingerprintOption');
        const fingerprintStatus = document.getElementById('fingerprintStatus');
        
        if (data.fingerprint_available && biometricConfig.fingerprintEnabled) {
            fingerprintStatus.innerHTML = '<i class="fas fa-check-circle"></i> Disponible';
            fingerprintStatus.className = 'biometric-status available';
        } else {
            fingerprintOption.classList.add('disabled');
            fingerprintStatus.innerHTML = '<i class="fas fa-times-circle"></i> No disponible';
            fingerprintStatus.className = 'biometric-status unavailable';
        }
        
        // Update facial option
        const facialOption = document.getElementById('facialOption');
        const facialStatus = document.getElementById('facialStatus');
        
        if (data.facial_available && biometricConfig.facialEnabled && faceApiLoaded) {
            facialStatus.innerHTML = '<i class="fas fa-check-circle"></i> Disponible';
            facialStatus.className = 'biometric-status available';
        } else {
            facialOption.classList.add('disabled');
            facialStatus.innerHTML = '<i class="fas fa-times-circle"></i> No disponible';
            facialStatus.className = 'biometric-status unavailable';
        }
        
        // Show fallback option if no biometric methods available
        const hasAvailableMethod = (data.fingerprint_available && biometricConfig.fingerprintEnabled) || 
                                  (data.facial_available && biometricConfig.facialEnabled && faceApiLoaded);
        
        document.getElementById('fallbackBtn').style.display = hasAvailableMethod ? 'none' : 'inline-block';
        
    } catch (error) {
        console.error('Error checking biometric availability:', error);
        showErrorNotification('Error al verificar disponibilidad biométrica');
    }
}

function selectBiometricMethod(method) {
    // Remove previous selections
    document.querySelectorAll('.biometric-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Check if method is available
    const option = document.getElementById(method + 'Option');
    if (option.classList.contains('disabled')) {
        return;
    }
    
    // Select current method
    option.classList.add('selected');
    
    // Start verification based on method
    setTimeout(() => {
        if (method === 'fingerprint') {
            openFingerprintVerificationModal();
        } else if (method === 'facial') {
            openFacialVerificationModal();
        }
    }, 500);
}

function useFallbackMethod() {
    closeBiometricSelectionModal();
    
    // Use traditional photo capture method
    showAttendancePhotoModal(currentEmployee);
}

// ===========================================================================
// FINGERPRINT VERIFICATION
// ===========================================================================

function openFingerprintVerificationModal() {
    closeBiometricSelectionModal();
    document.getElementById('fingerprintVerificationModal').style.display = 'flex';
    
    // Reset UI state
    resetFingerprintUI();
}

function closeFingerprintModal() {
    document.getElementById('fingerprintVerificationModal').style.display = 'none';
    resetFingerprintUI();
}

function resetFingerprintUI() {
    const scanner = document.getElementById('fingerprintScanner');
    const progress = document.getElementById('fingerprintProgress');
    const result = document.getElementById('fingerprintResult');
    const retryBtn = document.getElementById('retryFingerprintBtn');
    const startBtn = document.getElementById('startFingerprintBtn');
    
    scanner.className = 'fingerprint-scanner';
    progress.style.display = 'none';
    result.style.display = 'none';
    retryBtn.style.display = 'none';
    startBtn.style.display = 'inline-block';
    
    document.getElementById('fingerprintScannerStatus').innerHTML = 
        '<span class="status-text">Esperando huella digital...</span>';
}

async function startFingerprintVerification() {
    if (verificationAttempts >= biometricConfig.maxVerificationAttempts) {
        showFingerprintError('Demasiados intentos fallidos. Intente más tarde.');
        return;
    }
    
    const scanner = document.getElementById('fingerprintScanner');
    const progress = document.getElementById('fingerprintProgress');
    const startBtn = document.getElementById('startFingerprintBtn');
    
    try {
        // Update UI to scanning state
        scanner.classList.add('active');
        progress.style.display = 'block';
        startBtn.style.display = 'none';
        
        document.getElementById('fingerprintScannerStatus').innerHTML = 
            '<span class="status-text">Detectando huella digital...</span>';
        
        // Animate progress
        animateProgress('fingerprintProgressFill', 3000);
        
        // Use WebAuthn for fingerprint verification
        const credential = await navigator.credentials.create({
            publicKey: {
                challenge: new Uint8Array(32),
                rp: {
                    name: "SynkTime",
                    id: window.location.hostname,
                },
                user: {
                    id: new TextEncoder().encode(currentEmployee.id.toString()),
                    name: currentEmployee.codigo,
                    displayName: currentEmployee.nombre,
                },
                pubKeyCredParams: [{alg: -7, type: "public-key"}],
                authenticatorSelection: {
                    authenticatorAttachment: "platform",
                    userVerification: "required"
                },
                timeout: biometricConfig.verificationTimeout,
                attestation: "direct"
            }
        });
        
        // Send fingerprint data to server for verification
        const verificationResult = await verifyFingerprintWithServer(credential);
        
        if (verificationResult.success) {
            showFingerprintSuccess();
            
            // Register attendance after successful verification
            setTimeout(() => {
                registerAttendanceWithBiometric('fingerprint', verificationResult);
            }, 1500);
        } else {
            throw new Error(verificationResult.message || 'Verificación fallida');
        }
        
    } catch (error) {
        console.error('Fingerprint verification error:', error);
        verificationAttempts++;
        showFingerprintError(error.message || 'Error en la verificación de huella digital');
    }
}

async function verifyFingerprintWithServer(credential) {
    const formData = new FormData();
    formData.append('employee_id', currentEmployee.id);
    formData.append('credential_data', JSON.stringify({
        id: credential.id,
        rawId: Array.from(new Uint8Array(credential.rawId)),
        response: {
            attestationObject: Array.from(new Uint8Array(credential.response.attestationObject)),
            clientDataJSON: Array.from(new Uint8Array(credential.response.clientDataJSON))
        },
        type: credential.type
    }));
    
    const response = await fetch('api/biometric/verify-fingerprint.php', {
        method: 'POST',
        body: formData
    });
    
    return await response.json();
}

function showFingerprintSuccess() {
    const scanner = document.getElementById('fingerprintScanner');
    const progress = document.getElementById('fingerprintProgress');
    const result = document.getElementById('fingerprintResult');
    
    scanner.classList.remove('active');
    scanner.classList.add('success');
    progress.style.display = 'none';
    
    document.getElementById('fingerprintScannerStatus').innerHTML = 
        '<span class="status-text">Verificación exitosa</span>';
    
    document.getElementById('fingerprintResultIcon').innerHTML = '<i class="fas fa-check-circle"></i>';
    document.getElementById('fingerprintResultMessage').textContent = 'Huella digital verificada correctamente';
    result.className = 'verification-result success';
    result.style.display = 'block';
}

function showFingerprintError(message) {
    const scanner = document.getElementById('fingerprintScanner');
    const progress = document.getElementById('fingerprintProgress');
    const result = document.getElementById('fingerprintResult');
    const retryBtn = document.getElementById('retryFingerprintBtn');
    const startBtn = document.getElementById('startFingerprintBtn');
    
    scanner.classList.remove('active');
    scanner.classList.add('error');
    progress.style.display = 'none';
    
    document.getElementById('fingerprintScannerStatus').innerHTML = 
        '<span class="status-text">Error en verificación</span>';
    
    document.getElementById('fingerprintResultIcon').innerHTML = '<i class="fas fa-times-circle"></i>';
    document.getElementById('fingerprintResultMessage').textContent = message;
    result.className = 'verification-result error';
    result.style.display = 'block';
    
    if (verificationAttempts < biometricConfig.maxVerificationAttempts) {
        retryBtn.style.display = 'inline-block';
    }
    startBtn.style.display = 'none';
}

function retryFingerprintVerification() {
    resetFingerprintUI();
    startFingerprintVerification();
}

// ===========================================================================
// FACIAL RECOGNITION
// ===========================================================================

function openFacialVerificationModal() {
    closeBiometricSelectionModal();
    document.getElementById('facialVerificationModal').style.display = 'flex';
    
    // Reset UI state
    resetFacialUI();
}

function closeFacialModal() {
    document.getElementById('facialVerificationModal').style.display = 'none';
    resetFacialUI();
    
    // Stop camera if active
    if (facialVideo && facialVideo.srcObject) {
        facialVideo.srcObject.getTracks().forEach(track => track.stop());
    }
}

function resetFacialUI() {
    const container = document.getElementById('facialCameraContainer');
    const progress = document.getElementById('facialProgress');
    const result = document.getElementById('facialResult');
    const retryBtn = document.getElementById('retryFacialBtn');
    const startBtn = document.getElementById('startFacialBtn');
    const scanningLine = document.getElementById('facialScanningLine');
    
    container.classList.remove('active');
    progress.style.display = 'none';
    result.style.display = 'none';
    retryBtn.style.display = 'none';
    startBtn.style.display = 'inline-block';
    scanningLine.style.display = 'none';
    
    document.getElementById('facialCameraStatus').innerHTML = 
        '<i class="fas fa-video"></i><span>Preparando cámara...</span>';
}

async function startFacialVerification() {
    if (verificationAttempts >= biometricConfig.maxVerificationAttempts) {
        showFacialError('Demasiados intentos fallidos. Intente más tarde.');
        return;
    }
    
    if (!faceApiLoaded) {
        showFacialError('Sistema de reconocimiento facial no disponible');
        return;
    }
    
    try {
        // Initialize camera
        await initializeCamera();
        
        const container = document.getElementById('facialCameraContainer');
        const startBtn = document.getElementById('startFacialBtn');
        const scanningLine = document.getElementById('facialScanningLine');
        
        container.classList.add('active');
        startBtn.style.display = 'none';
        scanningLine.style.display = 'block';
        
        document.getElementById('facialCameraStatus').innerHTML = 
            '<i class="fas fa-eye"></i><span>Analizando rostro...</span>';
        
        // Start face detection and recognition
        setTimeout(async () => {
            await performFacialRecognition();
        }, 1000);
        
    } catch (error) {
        console.error('Facial verification error:', error);
        verificationAttempts++;
        showFacialError(error.message || 'Error al acceder a la cámara');
    }
}

async function initializeCamera() {
    facialVideo = document.getElementById('facialVideo');
    facialCanvas = document.getElementById('facialCanvas');
    facialContext = facialCanvas.getContext('2d');
    
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: {
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'user'
            }
        });
        
        facialVideo.srcObject = stream;
        facialVideo.style.display = 'block';
        
        return new Promise((resolve) => {
            facialVideo.onloadedmetadata = () => {
                facialCanvas.width = facialVideo.videoWidth;
                facialCanvas.height = facialVideo.videoHeight;
                facialCanvas.style.display = 'block';
                resolve();
            };
        });
    } catch (error) {
        throw new Error('No se pudo acceder a la cámara');
    }
}

async function performFacialRecognition() {
    try {
        const progress = document.getElementById('facialProgress');
        progress.style.display = 'block';
        
        // Animate progress
        animateProgress('facialProgressFill', 4000);
        
        // Detect faces in video
        const detections = await faceapi.detectAllFaces(
            facialVideo,
            new faceapi.TinyFaceDetectorOptions()
        ).withFaceLandmarks().withFaceDescriptors();
        
        if (detections.length === 0) {
            throw new Error('No se detectó ningún rostro. Asegúrese de estar frente a la cámara.');
        }
        
        if (detections.length > 1) {
            throw new Error('Se detectaron múltiples rostros. Asegúrese de que solo una persona esté frente a la cámara.');
        }
        
        // Get face descriptor for comparison
        const faceDescriptor = detections[0].descriptor;
        
        // Send face data to server for verification
        const verificationResult = await verifyFacialWithServer(faceDescriptor);
        
        if (verificationResult.success) {
            // Capture photo for attendance record
            await captureFacePhoto();
            
            showFacialSuccess();
            
            // Register attendance after successful verification
            setTimeout(() => {
                registerAttendanceWithBiometric('facial', verificationResult);
            }, 1500);
        } else {
            throw new Error(verificationResult.message || 'Rostro no reconocido');
        }
        
    } catch (error) {
        console.error('Face recognition error:', error);
        verificationAttempts++;
        showFacialError(error.message || 'Error en el reconocimiento facial');
    }
}

async function verifyFacialWithServer(faceDescriptor) {
    const formData = new FormData();
    formData.append('employee_id', currentEmployee.id);
    formData.append('face_descriptor', JSON.stringify(Array.from(faceDescriptor)));
    
    const response = await fetch('api/biometric/verify-facial.php', {
        method: 'POST',
        body: formData
    });
    
    return await response.json();
}

async function captureFacePhoto() {
    // Draw current video frame to canvas
    facialContext.drawImage(facialVideo, 0, 0, facialCanvas.width, facialCanvas.height);
    
    // Convert canvas to base64 image
    const imageData = facialCanvas.toDataURL('image/jpeg', 0.8);
    
    // Store for attendance registration
    imageBase64 = imageData;
}

function showFacialSuccess() {
    const progress = document.getElementById('facialProgress');
    const result = document.getElementById('facialResult');
    const scanningLine = document.getElementById('facialScanningLine');
    
    progress.style.display = 'none';
    scanningLine.style.display = 'none';
    
    document.getElementById('facialCameraStatus').innerHTML = 
        '<i class="fas fa-check"></i><span>Verificación exitosa</span>';
    
    document.getElementById('facialResultIcon').innerHTML = '<i class="fas fa-check-circle"></i>';
    document.getElementById('facialResultMessage').textContent = 'Rostro verificado correctamente';
    result.className = 'verification-result success';
    result.style.display = 'block';
}

function showFacialError(message) {
    const progress = document.getElementById('facialProgress');
    const result = document.getElementById('facialResult');
    const retryBtn = document.getElementById('retryFacialBtn');
    const startBtn = document.getElementById('startFacialBtn');
    const scanningLine = document.getElementById('facialScanningLine');
    
    progress.style.display = 'none';
    scanningLine.style.display = 'none';
    
    document.getElementById('facialCameraStatus').innerHTML = 
        '<i class="fas fa-times"></i><span>Error en verificación</span>';
    
    document.getElementById('facialResultIcon').innerHTML = '<i class="fas fa-times-circle"></i>';
    document.getElementById('facialResultMessage').textContent = message;
    result.className = 'verification-result error';
    result.style.display = 'block';
    
    if (verificationAttempts < biometricConfig.maxVerificationAttempts) {
        retryBtn.style.display = 'inline-block';
    }
    startBtn.style.display = 'none';
}

function retryFacialVerification() {
    resetFacialUI();
    startFacialVerification();
}

// ===========================================================================
// ATTENDANCE REGISTRATION WITH BIOMETRIC
// ===========================================================================

async function registerAttendanceWithBiometric(biometricType, verificationResult) {
    try {
        const formData = new FormData();
        formData.append('id_empleado', currentEmployee.id);
        formData.append('biometric_type', biometricType);
        formData.append('verification_data', JSON.stringify(verificationResult));
        
        if (imageBase64) {
            formData.append('image_data', imageBase64);
        }
        
        const response = await fetch('api/attendance/register-biometric.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccessNotification(`Asistencia registrada exitosamente mediante ${biometricType === 'fingerprint' ? 'huella digital' : 'reconocimiento facial'}`);
            
            // Close modals
            closeFingerprintModal();
            closeFacialModal();
            
            // Refresh attendance list
            loadAttendanceDay();
            
        } else {
            throw new Error(result.message || 'Error al registrar asistencia');
        }
        
    } catch (error) {
        console.error('Error registering attendance:', error);
        showErrorNotification('Error al registrar asistencia: ' + error.message);
    }
}

// ===========================================================================
// UTILITY FUNCTIONS
// ===========================================================================

function animateProgress(elementId, duration) {
    const progressFill = document.getElementById(elementId);
    let startTime = null;
    
    function animate(currentTime) {
        if (!startTime) startTime = currentTime;
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        progressFill.style.width = (progress * 100) + '%';
        
        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    }
    
    requestAnimationFrame(animate);
}

function showSuccessNotification(message) {
    // Use existing notification system if available
    if (typeof showNotification === 'function') {
        showNotification(message, 'success');
    } else {
        alert(message);
    }
}

function showErrorNotification(message) {
    // Use existing notification system if available
    if (typeof showNotification === 'function') {
        showNotification(message, 'error');
    } else {
        alert(message);
    }
}

// ===========================================================================
// INTEGRATION WITH EXISTING ATTENDANCE SYSTEM
// ===========================================================================

// Override the existing attendance registration to use biometric selection
function showAttendanceWithBiometric(employee) {
    // Store employee data
    empleadoSeleccionado = employee;
    
    // Open biometric selection modal
    openBiometricSelectionModal(employee);
}

// Fallback to original photo modal if biometric not available
function showAttendancePhotoModal(employee) {
    // Use existing photo modal functionality
    empleadoSeleccionado = employee;
    openPhotoModal();
}

