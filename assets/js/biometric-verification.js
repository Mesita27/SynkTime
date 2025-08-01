// =================================================================
// BIOMETRIC VERIFICATION FOR ATTENDANCE
// Integrates with existing attendance system
// =================================================================

class BiometricVerification {
    constructor() {
        this.currentEmployee = null;
        this.verificationType = null;
        this.verificationStream = null;
        this.verificationAttempts = 0;
        this.maxAttempts = 3;
        this.faceApiLoaded = false;
        this.attendanceCallback = null;
        
        this.init();
    }
    
    async init() {
        // Load face-api models if not already loaded
        if (typeof faceapi !== 'undefined' && !this.faceApiLoaded) {
            await this.loadFaceApiModels();
        }
        
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Fingerprint verification
        document.getElementById('btnStartFingerprintVerification')?.addEventListener('click', () => {
            this.performFingerprintVerification();
        });
        
        // Facial verification
        document.getElementById('btnStartFacialVerification')?.addEventListener('click', () => {
            this.performFacialVerification();
        });
        
        // Retry verification
        document.getElementById('btnRetryVerification')?.addEventListener('click', () => {
            this.retryVerification();
        });
        
        // Proceed with verified attendance
        document.getElementById('btnProceedWithVerification')?.addEventListener('click', () => {
            this.proceedWithAttendance();
        });
    }
    
    async loadFaceApiModels() {
        try {
            const modelPath = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model/';
            
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
                faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
                faceapi.nets.faceRecognitionNet.loadFromUri(modelPath)
            ]);
            
            this.faceApiLoaded = true;
            console.log('Face-api models loaded for verification');
        } catch (error) {
            console.error('Error loading face-api models:', error);
        }
    }
    
    async startVerification(employee, callback) {
        this.currentEmployee = employee;
        this.attendanceCallback = callback;
        this.verificationAttempts = 0;
        
        // Load employee biometric status
        await this.loadBiometricMethods();
        
        // Show verification modal
        this.showModal('biometricVerificationModal');
        this.showVerificationStep('verificationMethodSelection');
        
        // Update employee info
        document.getElementById('verificationEmployeeName').textContent = employee.nombre;
        document.getElementById('verificationEmployeeCode').textContent = employee.codigo;
    }
    
    async loadBiometricMethods() {
        try {
            const response = await fetch(`api/biometric/status.php?employee_id=${this.currentEmployee.id}`);
            const status = await response.json();
            
            // Show/hide verification methods based on enrollment
            const fingerprintCard = document.getElementById('fingerprintVerificationCard');
            const facialCard = document.getElementById('facialVerificationCard');
            
            if (status.fingerprint_enrolled) {
                fingerprintCard.style.display = 'block';
            } else {
                fingerprintCard.style.display = 'none';
            }
            
            if (status.facial_enrolled) {
                facialCard.style.display = 'block';
            } else {
                facialCard.style.display = 'none';
            }
            
        } catch (error) {
            console.error('Error loading biometric methods:', error);
            // Show traditional photo as fallback
        }
    }
    
    startFingerprintVerification() {
        this.verificationType = 'fingerprint';
        this.showVerificationStep('fingerprintVerificationStep');
        document.getElementById('fingerprintVerificationStatus').textContent = 'Coloque el dedo sobre el sensor';
    }
    
    async startFacialVerification() {
        this.verificationType = 'facial';
        this.showVerificationStep('facialVerificationStep');
        
        try {
            // Start camera
            this.verificationStream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });
            
            const video = document.getElementById('verificationVideo');
            video.srcObject = this.verificationStream;
            
            // Start face detection when video loads
            video.addEventListener('loadedmetadata', () => {
                this.startVerificationFaceDetection();
            });
            
        } catch (error) {
            console.error('Error starting camera for verification:', error);
            this.showVerificationError('Error al acceder a la cámara');
        }
    }
    
    async startVerificationFaceDetection() {
        if (!this.faceApiLoaded) {
            document.getElementById('facialVerificationStatus').textContent = 'Cargando modelos de reconocimiento...';
            return;
        }
        
        const video = document.getElementById('verificationVideo');
        const detectionBox = document.getElementById('verificationFaceBox');
        
        const detectFaces = async () => {
            try {
                const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions());
                
                if (detections.length > 0) {
                    const detection = detections[0];
                    const box = detection.box;
                    
                    // Show detection box
                    detectionBox.style.display = 'block';
                    detectionBox.style.left = `${box.x}px`;
                    detectionBox.style.top = `${box.y}px`;
                    detectionBox.style.width = `${box.width}px`;
                    detectionBox.style.height = `${box.height}px`;
                    
                    // Check if face is well positioned
                    const isWellPositioned = this.isFaceWellPositioned(box, video);
                    document.getElementById('btnStartFacialVerification').disabled = !isWellPositioned;
                    
                    if (isWellPositioned) {
                        document.getElementById('facialVerificationStatus').textContent = 'Rostro detectado - Listo para verificar';
                        detectionBox.style.borderColor = '#10b981';
                    } else {
                        document.getElementById('facialVerificationStatus').textContent = 'Ajuste la posición del rostro';
                        detectionBox.style.borderColor = '#f59e0b';
                    }
                } else {
                    detectionBox.style.display = 'none';
                    document.getElementById('btnStartFacialVerification').disabled = true;
                    document.getElementById('facialVerificationStatus').textContent = 'Posicione su rostro en el centro';
                }
                
                // Continue detection
                if (this.verificationStream && this.verificationStream.active) {
                    requestAnimationFrame(detectFaces);
                }
            } catch (error) {
                console.error('Error in verification face detection:', error);
            }
        };
        
        detectFaces();
    }
    
    isFaceWellPositioned(faceBox, video) {
        const videoRect = video.getBoundingClientRect();
        const centerX = videoRect.width / 2;
        const centerY = videoRect.height / 2;
        
        const faceCenterX = faceBox.x + faceBox.width / 2;
        const faceCenterY = faceBox.y + faceBox.height / 2;
        
        const isCentered = Math.abs(faceCenterX - centerX) < 50 && Math.abs(faceCenterY - centerY) < 50;
        const isProperSize = faceBox.width > 100 && faceBox.height > 100;
        
        return isCentered && isProperSize;
    }
    
    async performFingerprintVerification() {
        try {
            document.getElementById('fingerprintVerificationProgress').style.display = 'block';
            document.getElementById('fingerprintVerificationStatus').textContent = 'Verificando huella digital...';
            
            // Simulate fingerprint capture and verification
            const verificationData = await this.simulateFingerprintVerification();
            
            const response = await fetch('api/biometric/verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    employee_id: this.currentEmployee.id,
                    biometric_type: 'fingerprint',
                    verification_data: verificationData
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.verified) {
                this.showVerificationSuccess(result.confidence, 'Huella Digital');
            } else {
                this.handleVerificationFailure(result.message || 'Huella digital no reconocida');
            }
            
        } catch (error) {
            console.error('Error in fingerprint verification:', error);
            this.handleVerificationFailure('Error en la verificación de huella digital');
        } finally {
            document.getElementById('fingerprintVerificationProgress').style.display = 'none';
        }
    }
    
    async simulateFingerprintVerification() {
        // Simulate fingerprint scanning delay
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Return simulated fingerprint data
        return {
            template: btoa(Math.random().toString(36).substr(2, 100)),
            timestamp: Date.now()
        };
    }
    
    async performFacialVerification() {
        try {
            document.getElementById('facialVerificationProgress').style.display = 'block';
            document.getElementById('facialVerificationStatus').textContent = 'Analizando características faciales...';
            
            const video = document.getElementById('verificationVideo');
            const canvas = document.getElementById('verificationCanvas');
            const ctx = canvas.getContext('2d');
            
            // Capture current frame
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0);
            
            // Extract face descriptor
            const faceDescriptor = await this.extractVerificationFaceDescriptor(video);
            
            if (!faceDescriptor) {
                throw new Error('No se pudieron extraer características faciales');
            }
            
            const response = await fetch('api/biometric/verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    employee_id: this.currentEmployee.id,
                    biometric_type: 'facial',
                    verification_data: {
                        descriptor: Array.from(faceDescriptor),
                        image: canvas.toDataURL('image/jpeg', 0.8),
                        timestamp: Date.now()
                    }
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.verified) {
                this.showVerificationSuccess(result.confidence, 'Reconocimiento Facial');
            } else {
                this.handleVerificationFailure(result.message || 'Rostro no reconocido');
            }
            
        } catch (error) {
            console.error('Error in facial verification:', error);
            this.handleVerificationFailure('Error en el reconocimiento facial');
        } finally {
            document.getElementById('facialVerificationProgress').style.display = 'none';
        }
    }
    
    async extractVerificationFaceDescriptor(video) {
        if (!this.faceApiLoaded) return null;
        
        try {
            const detection = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            return detection ? detection.descriptor : null;
        } catch (error) {
            console.error('Error extracting face descriptor for verification:', error);
            return null;
        }
    }
    
    showVerificationSuccess(confidence, method) {
        document.getElementById('verificationConfidence').textContent = Math.round(confidence);
        document.getElementById('verificationMethodUsed').textContent = method;
        this.showVerificationStep('verificationSuccess');
        
        // Stop video stream
        this.stopVerificationStream();
    }
    
    handleVerificationFailure(message) {
        this.verificationAttempts++;
        
        document.getElementById('verificationErrorMessage').textContent = message;
        document.getElementById('verificationAttemptsLeft').textContent = this.maxAttempts - this.verificationAttempts;
        
        if (this.verificationAttempts >= this.maxAttempts) {
            // Hide retry button after max attempts
            document.getElementById('btnRetryVerification').style.display = 'none';
        }
        
        this.showVerificationStep('verificationFailed');
        this.stopVerificationStream();
    }
    
    retryVerification() {
        if (this.verificationAttempts < this.maxAttempts) {
            this.showVerificationStep('verificationMethodSelection');
        }
    }
    
    proceedWithAttendance() {
        // Close verification modal
        this.closeModal('biometricVerificationModal');
        
        // Call the attendance callback with verification success
        if (this.attendanceCallback) {
            this.attendanceCallback(this.currentEmployee, {
                verified: true,
                method: this.verificationType,
                confidence: document.getElementById('verificationConfidence').textContent
            });
        }
    }
    
    useTraditionalPhoto() {
        // Close verification modal and proceed with traditional photo capture
        this.closeModal('biometricVerificationModal');
        
        if (this.attendanceCallback) {
            this.attendanceCallback(this.currentEmployee, {
                verified: false,
                method: 'traditional_photo',
                useTraditionalPhoto: true
            });
        }
    }
    
    backToMethodSelection() {
        this.stopVerificationStream();
        this.showVerificationStep('verificationMethodSelection');
    }
    
    showVerificationStep(stepId) {
        const steps = [
            'verificationMethodSelection', 
            'fingerprintVerificationStep', 
            'facialVerificationStep',
            'verificationSuccess', 
            'verificationFailed'
        ];
        
        steps.forEach(step => {
            const element = document.getElementById(step);
            if (element) {
                element.style.display = step === stepId ? 'block' : 'none';
            }
        });
    }
    
    showVerificationError(message) {
        this.showVerificationStep('verificationFailed');
        document.getElementById('verificationErrorMessage').textContent = message;
    }
    
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    }
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
        
        this.stopVerificationStream();
    }
    
    stopVerificationStream() {
        if (this.verificationStream) {
            this.verificationStream.getTracks().forEach(track => track.stop());
            this.verificationStream = null;
        }
    }
}

// Global functions for modal interactions
function startFingerprintVerification() {
    biometricVerification.startFingerprintVerification();
}

function startFacialVerification() {
    biometricVerification.startFacialVerification();
}

function useTraditionalPhoto() {
    biometricVerification.useTraditionalPhoto();
}

function backToMethodSelection() {
    biometricVerification.backToMethodSelection();
}

function closeBiometricVerificationModal() {
    biometricVerification.closeModal('biometricVerificationModal');
}

// Initialize biometric verification
let biometricVerification;
document.addEventListener('DOMContentLoaded', function() {
    biometricVerification = new BiometricVerification();
});