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