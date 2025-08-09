/**
 * SynkTime Biometric System - Main JavaScript Module
 * Handles biometric verification and enrollment functionality
 */

class BiometricSystem {
    constructor() {
        this.currentStream = null;
        this.currentMethod = null;
        this.devices = {
            camera: null,
            fingerprint: null
        };
        this.isProcessing = false;
        this.init();
    }

    /**
     * Initialize the biometric system
     */
    async init() {
        await this.detectDevices();
        this.bindEvents();
        console.log('BiometricSystem initialized');
    }

    /**
     * Detect available biometric devices
     */
    async detectDevices() {
        try {
            // Detect camera devices
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                const devices = await navigator.mediaDevices.enumerateDevices();
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                this.devices.camera = videoDevices.length > 0 ? videoDevices[0] : null;
            }

            // Detect fingerprint devices (simulated for now - can be extended for real hardware)
            this.devices.fingerprint = window.PublicKeyCredential ? 'available' : null;

            this.updateDeviceStatus();
        } catch (error) {
            console.error('Error detecting devices:', error);
        }
    }

    /**
     * Update device status indicators
     */
    updateDeviceStatus() {
        const cameraStatus = document.querySelector('.camera-status');
        const fingerprintStatus = document.querySelector('.fingerprint-status');

        if (cameraStatus) {
            cameraStatus.className = `device-status ${this.devices.camera ? 'connected' : 'disconnected'}`;
            cameraStatus.innerHTML = `
                <i class="fas fa-camera device-status-icon"></i>
                <span>Cámara: ${this.devices.camera ? 'Conectada' : 'No disponible'}</span>
            `;
        }

        if (fingerprintStatus) {
            fingerprintStatus.className = `device-status ${this.devices.fingerprint ? 'connected' : 'disconnected'}`;
            fingerprintStatus.innerHTML = `
                <i class="fas fa-fingerprint device-status-icon"></i>
                <span>Lector de huella: ${this.devices.fingerprint ? 'Disponible' : 'No disponible'}</span>
            `;
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Verification method selection
        document.addEventListener('click', (e) => {
            if (e.target.closest('.verification-method')) {
                this.selectVerificationMethod(e.target.closest('.verification-method'));
            }
            
            if (e.target.closest('.finger-option')) {
                this.selectFinger(e.target.closest('.finger-option'));
            }
        });

        // Modal close events
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('biometric-close') || 
                e.target.classList.contains('biometric-modal')) {
                this.closeBiometricModal();
            }
        });

        // Escape key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeBiometricModal();
            }
        });
    }

    /**
     * Select verification method
     */
    selectVerificationMethod(element) {
        if (element.classList.contains('disabled')) return;

        // Remove previous selections
        document.querySelectorAll('.verification-method').forEach(method => {
            method.classList.remove('selected');
        });

        // Select current method
        element.classList.add('selected');
        this.currentMethod = element.dataset.method;

        // Update UI based on selected method
        this.setupVerificationUI();
    }

    /**
     * Setup verification UI based on selected method
     */
    setupVerificationUI() {
        const processArea = document.querySelector('.biometric-process');
        if (!processArea) return;

        switch (this.currentMethod) {
            case 'facial':
                this.setupFacialRecognition(processArea);
                break;
            case 'fingerprint':
                this.setupFingerprintVerification(processArea);
                break;
            case 'traditional':
                this.setupTraditionalCapture(processArea);
                break;
        }
    }

    /**
     * Setup facial recognition interface
     */
    async setupFacialRecognition(container) {
        container.innerHTML = `
            <div class="biometric-camera-area">
                <video class="biometric-video" autoplay playsinline></video>
                <div class="camera-overlay"></div>
            </div>
            <div class="biometric-status info">
                <i class="fas fa-info-circle"></i>
                Posicione su rostro dentro del círculo y manténgase quieto
            </div>
            <div class="form-actions">
                <button type="button" class="btn-primary" onclick="biometricSystem.startFacialCapture()">
                    <i class="fas fa-camera"></i> Iniciar Captura
                </button>
                <button type="button" class="btn-secondary" onclick="biometricSystem.stopCamera()">
                    <i class="fas fa-stop"></i> Detener
                </button>
            </div>
        `;

        if (this.devices.camera) {
            await this.initializeCamera();
        } else {
            this.showError('No se detectó una cámara disponible');
        }
    }

    /**
     * Setup fingerprint verification interface
     */
    setupFingerprintVerification(container) {
        container.innerHTML = `
            <div class="fingerprint-area">
                <div class="finger-selection">
                    <div class="finger-option" data-finger="thumb">Pulgar</div>
                    <div class="finger-option" data-finger="index">Índice</div>
                    <div class="finger-option" data-finger="middle">Medio</div>
                    <div class="finger-option" data-finger="ring">Anular</div>
                    <div class="finger-option" data-finger="little">Meñique</div>
                </div>
                <div class="fingerprint-scanner">
                    <i class="fas fa-fingerprint fingerprint-icon"></i>
                </div>
                <div class="biometric-status info">
                    <i class="fas fa-info-circle"></i>
                    Seleccione un dedo y colóquelo en el lector
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn-primary" onclick="biometricSystem.startFingerprintScan()" disabled>
                    <i class="fas fa-fingerprint"></i> Escanear Huella
                </button>
            </div>
        `;

        if (!this.devices.fingerprint) {
            this.showError('No se detectó un lector de huellas disponible');
        }
    }

    /**
     * Setup traditional photo capture interface
     */
    async setupTraditionalCapture(container) {
        container.innerHTML = `
            <div class="biometric-camera-area">
                <video class="biometric-video" autoplay playsinline></video>
            </div>
            <div class="biometric-status info">
                <i class="fas fa-info-circle"></i>
                Tome una foto clara del empleado
            </div>
            <div class="form-actions">
                <button type="button" class="btn-primary" onclick="biometricSystem.capturePhoto()">
                    <i class="fas fa-camera"></i> Tomar Foto
                </button>
            </div>
        `;

        if (this.devices.camera) {
            await this.initializeCamera();
        } else {
            this.showError('No se detectó una cámara disponible');
        }
    }

    /**
     * Initialize camera
     */
    async initializeCamera() {
        try {
            this.currentStream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                }
            });

            const video = document.querySelector('.biometric-video');
            if (video) {
                video.srcObject = this.currentStream;
            }
        } catch (error) {
            console.error('Error accessing camera:', error);
            this.showError('Error al acceder a la cámara: ' + error.message);
        }
    }

    /**
     * Start facial recognition capture
     */
    async startFacialCapture() {
        if (this.isProcessing) return;

        this.isProcessing = true;
        this.showStatus('Iniciando reconocimiento facial...', 'info');

        try {
            // Simulate facial recognition processing
            await this.delay(2000);
            
            // Capture the image
            const imageData = this.captureFromVideo();
            if (imageData) {
                await this.processFacialRecognition(imageData);
            }
        } catch (error) {
            console.error('Error in facial capture:', error);
            this.showError('Error en el reconocimiento facial');
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Start fingerprint scanning
     */
    async startFingerprintScan() {
        const selectedFinger = document.querySelector('.finger-option.selected');
        if (!selectedFinger) {
            this.showError('Seleccione un dedo para escanear');
            return;
        }

        if (this.isProcessing) return;

        this.isProcessing = true;
        const scanner = document.querySelector('.fingerprint-scanner');
        scanner.classList.add('scanning');
        
        this.showStatus('Escaneando huella dactilar...', 'info');

        try {
            // Simulate fingerprint scanning
            await this.delay(3000);
            
            // Simulate fingerprint processing
            await this.processFingerprintVerification(selectedFinger.dataset.finger);
        } catch (error) {
            console.error('Error in fingerprint scan:', error);
            this.showError('Error en el escaneo de huella');
        } finally {
            this.isProcessing = false;
            scanner.classList.remove('scanning');
        }
    }

    /**
     * Capture photo from video
     */
    capturePhoto() {
        const imageData = this.captureFromVideo();
        if (imageData) {
            this.processTraditionalPhoto(imageData);
        }
    }

    /**
     * Capture image data from video element
     */
    captureFromVideo() {
        const video = document.querySelector('.biometric-video');
        if (!video) return null;

        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        return canvas.toDataURL('image/jpeg', 0.8);
    }

    /**
     * Process facial recognition data
     */
    async processFacialRecognition(imageData) {
        try {
            const employeeId = this.getCurrentEmployeeId();
            if (!employeeId) {
                this.showError('No se ha seleccionado un empleado');
                return;
            }

            const response = await fetch('/home/runner/work/Synktime/Synktime/api/biometric/verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    method: 'facial',
                    employee_id: employeeId,
                    image_data: imageData
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Reconocimiento facial exitoso');
                this.onVerificationSuccess(result);
            } else {
                this.showError(result.message || 'Reconocimiento facial fallido');
            }
        } catch (error) {
            console.error('Error processing facial recognition:', error);
            this.showError('Error al procesar reconocimiento facial');
        }
    }

    /**
     * Process fingerprint verification
     */
    async processFingerprintVerification(fingerType) {
        try {
            const employeeId = this.getCurrentEmployeeId();
            if (!employeeId) {
                this.showError('No se ha seleccionado un empleado');
                return;
            }

            // Simulate fingerprint data
            const fingerprintData = this.generateSimulatedFingerprintData();

            const response = await fetch('/home/runner/work/Synktime/Synktime/api/biometric/verify.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    method: 'fingerprint',
                    employee_id: employeeId,
                    finger_type: fingerType,
                    fingerprint_data: fingerprintData
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Verificación de huella exitosa');
                this.onVerificationSuccess(result);
            } else {
                this.showError(result.message || 'Verificación de huella fallida');
            }
        } catch (error) {
            console.error('Error processing fingerprint verification:', error);
            this.showError('Error al procesar verificación de huella');
        }
    }

    /**
     * Process traditional photo
     */
    async processTraditionalPhoto(imageData) {
        try {
            this.showSuccess('Foto capturada correctamente');
            this.onVerificationSuccess({
                method: 'traditional',
                image_data: imageData
            });
        } catch (error) {
            console.error('Error processing traditional photo:', error);
            this.showError('Error al procesar la foto');
        }
    }

    /**
     * Handle successful verification
     */
    onVerificationSuccess(result) {
        // Store verification result for attendance registration
        window.biometricVerificationResult = result;
        
        // Close biometric modal and proceed with attendance registration
        this.closeBiometricModal();
        
        // Trigger attendance registration with biometric data
        if (typeof registerAttendanceWithBiometric === 'function') {
            registerAttendanceWithBiometric(result);
        }
    }

    /**
     * Select finger for fingerprint scanning
     */
    selectFinger(element) {
        // Remove previous selections
        document.querySelectorAll('.finger-option').forEach(option => {
            option.classList.remove('selected');
        });

        // Select current finger
        element.classList.add('selected');

        // Enable scan button
        const scanButton = document.querySelector('button[onclick="biometricSystem.startFingerprintScan()"]');
        if (scanButton) {
            scanButton.disabled = false;
        }
    }

    /**
     * Stop camera stream
     */
    stopCamera() {
        if (this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
            this.currentStream = null;
        }
    }

    /**
     * Close biometric modal
     */
    closeBiometricModal() {
        const modal = document.querySelector('.biometric-modal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        this.stopCamera();
        this.currentMethod = null;
        this.isProcessing = false;
    }

    /**
     * Show biometric modal
     */
    showBiometricModal() {
        const modal = document.querySelector('.biometric-modal');
        if (modal) {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }
    }

    /**
     * Get current employee ID from the form
     */
    getCurrentEmployeeId() {
        const employeeSelect = document.getElementById('employee_id');
        return employeeSelect ? employeeSelect.value : null;
    }

    /**
     * Utility functions
     */
    showStatus(message, type = 'info') {
        const statusEl = document.querySelector('.biometric-status');
        if (statusEl) {
            statusEl.className = `biometric-status ${type}`;
            statusEl.innerHTML = `<i class="fas fa-${this.getStatusIcon(type)}"></i> ${message}`;
        }
    }

    showSuccess(message) {
        this.showStatus(message, 'success');
    }

    showError(message) {
        this.showStatus(message, 'error');
    }

    getStatusIcon(type) {
        const icons = {
            info: 'info-circle',
            success: 'check-circle',
            warning: 'exclamation-triangle',
            error: 'times-circle'
        };
        return icons[type] || 'info-circle';
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    generateSimulatedFingerprintData() {
        // Generate a simulated fingerprint template
        const template = Array.from({length: 256}, () => Math.floor(Math.random() * 256));
        return btoa(String.fromCharCode.apply(null, template));
    }
}

// Initialize biometric system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.biometricSystem = new BiometricSystem();
});

// Global functions for backward compatibility
function openBiometricVerification() {
    if (window.biometricSystem) {
        window.biometricSystem.showBiometricModal();
    }
}

function closeBiometricModal() {
    if (window.biometricSystem) {
        window.biometricSystem.closeBiometricModal();
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BiometricSystem;
}