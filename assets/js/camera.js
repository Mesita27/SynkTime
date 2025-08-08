/**
 * Camera Utilities for SynkTime Biometric System
 * Provides camera access, image capture, and processing functionality
 */

class CameraManager {
    constructor() {
        this.stream = null;
        this.video = null;
        this.canvas = null;
        this.context = null;
        this.isInitialized = false;
    }

    /**
     * Initialize camera with specified video element
     */
    async initialize(videoElement, canvasElement = null) {
        try {
            this.video = videoElement;
            this.canvas = canvasElement || document.createElement('canvas');
            this.context = this.canvas.getContext('2d');

            // Check if camera is available
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('Cámara no disponible en este navegador');
            }

            // Request camera access
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                    facingMode: 'user'
                }
            });

            this.video.srcObject = this.stream;
            this.video.play();

            this.isInitialized = true;
            return true;

        } catch (error) {
            console.error('Error initializing camera:', error);
            throw new Error(`Error al inicializar cámara: ${error.message}`);
        }
    }

    /**
     * Capture image from video stream
     */
    captureImage(format = 'jpeg', quality = 0.8) {
        if (!this.isInitialized || !this.video) {
            throw new Error('Cámara no inicializada');
        }

        // Set canvas size to video dimensions
        this.canvas.width = this.video.videoWidth;
        this.canvas.height = this.video.videoHeight;

        // Draw current video frame to canvas
        this.context.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);

        // Convert to base64
        const mimeType = `image/${format}`;
        return this.canvas.toDataURL(mimeType, quality);
    }

    /**
     * Capture multiple images with delay
     */
    async captureMultiple(count = 3, delay = 1000, format = 'jpeg', quality = 0.8) {
        const images = [];
        
        for (let i = 0; i < count; i++) {
            if (i > 0) {
                await this.delay(delay);
            }
            
            const image = this.captureImage(format, quality);
            images.push(image);
        }

        return images;
    }

    /**
     * Stop camera stream
     */
    stop() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        if (this.video) {
            this.video.srcObject = null;
        }

        this.isInitialized = false;
    }

    /**
     * Check if camera is supported
     */
    static isSupported() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }

    /**
     * Get available cameras
     */
    static async getAvailableCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            return devices.filter(device => device.kind === 'videoinput');
        } catch (error) {
            console.error('Error getting cameras:', error);
            return [];
        }
    }

    /**
     * Utility delay function
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * Resize image to specified dimensions
     */
    resizeImage(imageDataUrl, maxWidth = 800, maxHeight = 600, quality = 0.8) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                // Calculate new dimensions
                let { width, height } = img;
                
                if (width > height) {
                    if (width > maxWidth) {
                        height = (height * maxWidth) / width;
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = (width * maxHeight) / height;
                        height = maxHeight;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                // Draw and convert
                ctx.drawImage(img, 0, 0, width, height);
                resolve(canvas.toDataURL('image/jpeg', quality));
            };
            img.src = imageDataUrl;
        });
    }

    /**
     * Convert file to base64
     */
    static fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }

    /**
     * Validate image file
     */
    static validateImageFile(file, maxSize = 5 * 1024 * 1024) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        
        if (!allowedTypes.includes(file.type)) {
            throw new Error('Tipo de archivo no permitido. Use JPEG, PNG o WebP');
        }

        if (file.size > maxSize) {
            const maxMB = Math.round(maxSize / 1024 / 1024);
            throw new Error(`Archivo muy grande. Máximo ${maxMB}MB`);
        }

        return true;
    }
}

/**
 * Camera UI Helper Class
 * Provides UI helpers for camera interactions
 */
class CameraUI {
    constructor(cameraManager) {
        this.camera = cameraManager;
        this.isCapturing = false;
    }

    /**
     * Create camera preview modal
     */
    createPreviewModal(title = 'Captura de Imagen') {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <video id="cameraPreview" class="w-100 mb-3" style="max-height: 400px;" autoplay muted></video>
                        <canvas id="captureCanvas" style="display: none;"></canvas>
                        <div class="d-flex justify-content-center gap-2">
                            <button id="captureBtn" class="btn btn-primary">
                                <i class="fas fa-camera"></i> Capturar
                            </button>
                            <button id="retakeBtn" class="btn btn-secondary" style="display: none;">
                                <i class="fas fa-redo"></i> Tomar otra
                            </button>
                            <button id="confirmBtn" class="btn btn-success" style="display: none;">
                                <i class="fas fa-check"></i> Confirmar
                            </button>
                        </div>
                        <div id="cameraError" class="alert alert-danger mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return modal;
    }

    /**
     * Show camera preview and capture
     */
    async showCaptureModal(title = 'Captura de Imagen') {
        return new Promise((resolve, reject) => {
            const modal = this.createPreviewModal(title);
            const modalInstance = new bootstrap.Modal(modal);

            const video = modal.querySelector('#cameraPreview');
            const canvas = modal.querySelector('#captureCanvas');
            const captureBtn = modal.querySelector('#captureBtn');
            const retakeBtn = modal.querySelector('#retakeBtn');
            const confirmBtn = modal.querySelector('#confirmBtn');
            const errorDiv = modal.querySelector('#cameraError');

            let capturedImage = null;

            // Initialize camera
            this.camera.initialize(video, canvas)
                .then(() => {
                    modalInstance.show();
                })
                .catch(error => {
                    errorDiv.textContent = error.message;
                    errorDiv.style.display = 'block';
                    modalInstance.show();
                });

            // Capture button
            captureBtn.addEventListener('click', () => {
                try {
                    capturedImage = this.camera.captureImage();
                    
                    // Show captured image
                    video.style.display = 'none';
                    canvas.style.display = 'block';
                    
                    // Update buttons
                    captureBtn.style.display = 'none';
                    retakeBtn.style.display = 'inline-block';
                    confirmBtn.style.display = 'inline-block';
                    
                } catch (error) {
                    errorDiv.textContent = error.message;
                    errorDiv.style.display = 'block';
                }
            });

            // Retake button
            retakeBtn.addEventListener('click', () => {
                video.style.display = 'block';
                canvas.style.display = 'none';
                captureBtn.style.display = 'inline-block';
                retakeBtn.style.display = 'none';
                confirmBtn.style.display = 'none';
                capturedImage = null;
            });

            // Confirm button
            confirmBtn.addEventListener('click', () => {
                modalInstance.hide();
                resolve(capturedImage);
            });

            // Handle modal close
            modal.addEventListener('hidden.bs.modal', () => {
                this.camera.stop();
                document.body.removeChild(modal);
                
                if (!capturedImage) {
                    reject(new Error('Captura cancelada'));
                }
            });
        });
    }

    /**
     * Show file upload option
     */
    showFileUpload(accept = 'image/*') {
        return new Promise((resolve, reject) => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = accept;
            
            input.addEventListener('change', async (e) => {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    
                    try {
                        CameraManager.validateImageFile(file);
                        const base64 = await CameraManager.fileToBase64(file);
                        resolve(base64);
                    } catch (error) {
                        reject(error);
                    }
                } else {
                    reject(new Error('No se seleccionó archivo'));
                }
            });
            
            input.click();
        });
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CameraManager, CameraUI };
} else {
    window.CameraManager = CameraManager;
    window.CameraUI = CameraUI;
}