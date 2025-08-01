// ===================================================================
// BIOMETRIC-APIS.JS - EXTERNAL API INTEGRATIONS FOR SYNKTIME
// Integrates Face-api.js and WebAuthn for real biometric recognition
// ===================================================================

// Global variables for API management
let faceApiLoaded = false;
let faceApiModels = {
    detection: false,
    landmark: false,
    recognition: false
};

// Face-api.js configuration
const FACE_API_CONFIG = {
    modelPath: 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/model/',
    minConfidence: 0.5,
    recognitionThreshold: 0.6
};

// WebAuthn configuration
const WEBAUTHN_CONFIG = {
    timeout: 60000,
    userVerification: 'required',
    authenticatorAttachment: 'platform' // For built-in biometric sensors
};

// Employee biometric database (in production, this would be server-side)
let enrolledFaces = new Map();
let enrolledFingerprints = new Map();

// ===================================================================
// 1. FACE-API.JS INTEGRATION
// ===================================================================

/**
 * Load Face-api.js library and models
 */
async function loadFaceApiLibrary() {
    if (faceApiLoaded) return true;
    
    try {
        updateFacialStatus('Cargando bibliotecas de reconocimiento facial...');
        
        // Load Face-api.js from CDN
        if (typeof faceapi === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/dist/face-api.min.js';
            script.onload = async () => {
                await loadFaceApiModels();
            };
            script.onerror = () => {
                throw new Error('Failed to load Face-api.js library');
            };
            document.head.appendChild(script);
            
            // Wait for script to load
            await new Promise((resolve, reject) => {
                script.onload = resolve;
                script.onerror = reject;
            });
        }
        
        await loadFaceApiModels();
        return true;
        
    } catch (error) {
        console.error('Error loading Face-api.js:', error);
        updateFacialStatus('Error al cargar bibliotecas de reconocimiento facial');
        return false;
    }
}

/**
 * Load Face-api.js models
 */
async function loadFaceApiModels() {
    try {
        updateFacialStatus('Cargando modelos de reconocimiento facial...');
        
        // Load required models
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(FACE_API_CONFIG.modelPath),
            faceapi.nets.faceLandmark68Net.loadFromUri(FACE_API_CONFIG.modelPath),
            faceapi.nets.faceRecognitionNet.loadFromUri(FACE_API_CONFIG.modelPath)
        ]);
        
        faceApiModels.detection = true;
        faceApiModels.landmark = true;
        faceApiModels.recognition = true;
        faceApiLoaded = true;
        
        updateFacialStatus('Modelos de reconocimiento facial cargados');
        console.log('Face-api.js models loaded successfully');
        
    } catch (error) {
        console.error('Error loading Face-api.js models:', error);
        updateFacialStatus('Error al cargar modelos de reconocimiento');
        throw error;
    }
}

/**
 * Detect faces in video element
 */
async function detectFacesInVideo(videoElement) {
    if (!faceApiLoaded) {
        await loadFaceApiLibrary();
    }
    
    try {
        const detections = await faceapi.detectAllFaces(
            videoElement,
            new faceapi.TinyFaceDetectorOptions()
        ).withFaceLandmarks().withFaceDescriptors();
        
        return detections;
    } catch (error) {
        console.error('Face detection error:', error);
        return [];
    }
}

/**
 * Enroll facial pattern for employee
 */
async function enrollFacialPattern(employeeId, videoElement) {
    try {
        updateFacialEnrollmentStatus('Analizando patrones faciales...');
        
        const detections = await detectFacesInVideo(videoElement);
        
        if (detections.length === 0) {
            throw new Error('No se detectó ningún rostro en la imagen');
        }
        
        if (detections.length > 1) {
            throw new Error('Se detectaron múltiples rostros. Asegúrate de que solo tú estés en la imagen.');
        }
        
        const faceDescriptor = detections[0].descriptor;
        
        // Store facial pattern (in production, this would be encrypted and stored server-side)
        enrolledFaces.set(employeeId, {
            descriptor: faceDescriptor,
            enrollmentDate: new Date(),
            confidence: detections[0].detection.score
        });
        
        updateFacialEnrollmentStatus('Patrón facial registrado exitosamente');
        return faceDescriptor;
        
    } catch (error) {
        console.error('Facial enrollment error:', error);
        updateFacialEnrollmentStatus('Error: ' + error.message);
        throw error;
    }
}

/**
 * Verify facial pattern for employee
 */
async function verifyFacialPattern(employeeId, videoElement) {
    try {
        updateFacialStatus('Analizando rostro...');
        
        const detections = await detectFacesInVideo(videoElement);
        
        if (detections.length === 0) {
            throw new Error('No se detectó ningún rostro');
        }
        
        if (detections.length > 1) {
            throw new Error('Se detectaron múltiples rostros');
        }
        
        const currentDescriptor = detections[0].descriptor;
        const enrolledData = enrolledFaces.get(employeeId);
        
        if (!enrolledData) {
            throw new Error('No hay patrón facial registrado para este empleado');
        }
        
        // Calculate similarity between current and enrolled descriptor
        const distance = faceapi.euclideanDistance(currentDescriptor, enrolledData.descriptor);
        const similarity = 1 - distance;
        
        console.log('Face recognition similarity:', similarity);
        
        if (similarity >= FACE_API_CONFIG.recognitionThreshold) {
            updateFacialStatus('Rostro reconocido correctamente');
            return {
                success: true,
                confidence: similarity,
                message: 'Verificación facial exitosa'
            };
        } else {
            updateFacialStatus('Rostro no reconocido');
            return {
                success: false,
                confidence: similarity,
                message: 'Rostro no coincide con el patrón registrado'
            };
        }
        
    } catch (error) {
        console.error('Facial verification error:', error);
        updateFacialStatus('Error: ' + error.message);
        return {
            success: false,
            confidence: 0,
            message: error.message
        };
    }
}

// ===================================================================
// 2. WEBAUTHN API INTEGRATION
// ===================================================================

/**
 * Check WebAuthn support
 */
function isWebAuthnSupported() {
    return !!(navigator.credentials && 
              navigator.credentials.create && 
              navigator.credentials.get &&
              window.PublicKeyCredential);
}

/**
 * Check if platform authenticator (biometric) is available
 */
async function isPlatformAuthenticatorAvailable() {
    if (!isWebAuthnSupported()) return false;
    
    try {
        const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        return available;
    } catch (error) {
        console.error('Platform authenticator check error:', error);
        return false;
    }
}

/**
 * Create WebAuthn credential for fingerprint enrollment
 */
async function createFingerprintCredential(employeeId, employeeName) {
    if (!isWebAuthnSupported()) {
        throw new Error('WebAuthn no está soportado en este navegador');
    }
    
    try {
        updateFingerprintEnrollmentStatus('Iniciando registro biométrico...');
        
        const challenge = new Uint8Array(32);
        crypto.getRandomValues(challenge);
        
        const credentialCreationOptions = {
            publicKey: {
                challenge: challenge,
                rp: {
                    name: "SynkTime",
                    id: window.location.hostname,
                },
                user: {
                    id: new TextEncoder().encode(employeeId.toString()),
                    name: `employee_${employeeId}`,
                    displayName: employeeName
                },
                pubKeyCredParams: [{
                    type: "public-key",
                    alg: -7 // ES256
                }],
                authenticatorSelection: {
                    authenticatorAttachment: "platform",
                    userVerification: "required"
                },
                timeout: WEBAUTHN_CONFIG.timeout
            }
        };
        
        updateFingerprintEnrollmentStatus('Coloca tu dedo en el sensor biométrico...');
        
        const credential = await navigator.credentials.create(credentialCreationOptions);
        
        if (credential) {
            // Store credential ID (in production, this would be stored server-side)
            enrolledFingerprints.set(employeeId, {
                credentialId: credential.id,
                publicKey: credential.response.publicKey,
                enrollmentDate: new Date()
            });
            
            updateFingerprintEnrollmentStatus('Huella dactilar registrada exitosamente');
            return credential;
        } else {
            throw new Error('No se pudo crear la credencial biométrica');
        }
        
    } catch (error) {
        console.error('Fingerprint enrollment error:', error);
        updateFingerprintEnrollmentStatus('Error: ' + error.message);
        throw error;
    }
}

/**
 * Verify fingerprint using WebAuthn
 */
async function verifyFingerprintCredential(employeeId) {
    if (!isWebAuthnSupported()) {
        throw new Error('WebAuthn no está soportado en este navegador');
    }
    
    try {
        updateFingerprintStatus('Iniciando verificación biométrica...');
        
        const enrolledData = enrolledFingerprints.get(employeeId);
        if (!enrolledData) {
            throw new Error('No hay huella dactilar registrada para este empleado');
        }
        
        const challenge = new Uint8Array(32);
        crypto.getRandomValues(challenge);
        
        const credentialRequestOptions = {
            publicKey: {
                challenge: challenge,
                allowCredentials: [{
                    type: "public-key",
                    id: base64ToArrayBuffer(enrolledData.credentialId)
                }],
                userVerification: "required",
                timeout: WEBAUTHN_CONFIG.timeout
            }
        };
        
        updateFingerprintStatus('Coloca tu dedo en el sensor biométrico...');
        
        const assertion = await navigator.credentials.get(credentialRequestOptions);
        
        if (assertion) {
            updateFingerprintStatus('Huella dactilar verificada correctamente');
            return {
                success: true,
                message: 'Verificación biométrica exitosa'
            };
        } else {
            throw new Error('Verificación fallida');
        }
        
    } catch (error) {
        console.error('Fingerprint verification error:', error);
        updateFingerprintStatus('Error: ' + error.message);
        return {
            success: false,
            message: error.message
        };
    }
}

// ===================================================================
// 3. UTILITY FUNCTIONS
// ===================================================================

/**
 * Convert base64 to ArrayBuffer
 */
function base64ToArrayBuffer(base64) {
    const binaryString = window.atob(base64);
    const bytes = new Uint8Array(binaryString.length);
    for (let i = 0; i < binaryString.length; i++) {
        bytes[i] = binaryString.charCodeAt(i);
    }
    return bytes.buffer;
}

/**
 * Convert ArrayBuffer to base64
 */
function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

/**
 * Enhanced device detection with real API support
 */
async function detectRealBiometricDevices() {
    console.log('Detecting real biometric devices...');
    
    // Check camera availability (existing implementation)
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        biometricDevices.camera = true;
        stream.getTracks().forEach(track => track.stop());
        updateDeviceStatus('facial', true, 'Cámara detectada con reconocimiento facial');
        
        // Pre-load Face-api.js models
        loadFaceApiLibrary().catch(console.error);
        
    } catch (error) {
        biometricDevices.camera = false;
        updateDeviceStatus('facial', false, 'Cámara no disponible');
        console.warn('Camera not available:', error);
    }

    // Check fingerprint/biometric authenticator availability
    try {
        const available = await isPlatformAuthenticatorAvailable();
        biometricDevices.fingerprint = available;
        updateDeviceStatus('fingerprint', available, 
            available ? 'Sensor biométrico detectado' : 'Sensor biométrico no detectado');
    } catch (error) {
        biometricDevices.fingerprint = false;
        updateDeviceStatus('fingerprint', false, 'Sensor biométrico no disponible');
        console.warn('Biometric authenticator not available:', error);
    }
}

/**
 * Initialize real biometric APIs
 */
async function initializeRealBiometricAPIs() {
    console.log('Initializing real biometric APIs...');
    
    // Replace the simulated device detection with real detection
    await detectRealBiometricDevices();
    
    // Initialize Face-api.js if camera is available
    if (biometricDevices.camera) {
        try {
            await loadFaceApiLibrary();
            console.log('Face-api.js initialized successfully');
        } catch (error) {
            console.error('Failed to initialize Face-api.js:', error);
        }
    }
    
    console.log('Real biometric APIs initialized');
}

// ===================================================================
// 4. INTEGRATION WITH EXISTING SYSTEM
// ===================================================================

// Override existing functions to use real APIs
if (typeof window !== 'undefined') {
    // Replace device detection function
    window.detectBiometricDevices = detectRealBiometricDevices;
    
    // Export functions for use in main biometric.js
    window.BiometricAPIs = {
        // Face API functions
        loadFaceApiLibrary,
        enrollFacialPattern,
        verifyFacialPattern,
        
        // WebAuthn functions
        isWebAuthnSupported,
        isPlatformAuthenticatorAvailable,
        createFingerprintCredential,
        verifyFingerprintCredential,
        
        // Utility functions
        detectRealBiometricDevices,
        initializeRealBiometricAPIs
    };
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('biometricVerificationModal') || 
        document.getElementById('biometricEnrollmentModal')) {
        initializeRealBiometricAPIs();
    }
});