/**
 * Camera Helper Module
 * Utilities for camera access and image capture
 */

class CameraHelper {
    constructor() {
        this.stream = null;
        this.video = null;
        this.canvas = null;
    }
    
    /**
     * Open camera stream
     */
    async openCamera(videoElement, constraints = {}) {
        try {
            const defaultConstraints = {
                video: {
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: 'user'
                },
                audio: false
            };
            
            const finalConstraints = { ...defaultConstraints, ...constraints };
            
            this.stream = await navigator.mediaDevices.getUserMedia(finalConstraints);
            this.video = videoElement;
            this.video.srcObject = this.stream;
            
            return new Promise((resolve, reject) => {
                this.video.onloadedmetadata = () => {
                    this.video.play();
                    resolve(this.stream);
                };
                this.video.onerror = reject;
            });
        } catch (error) {
            console.error('Error accessing camera:', error);
            throw new Error('No se puede acceder a la cámara: ' + error.message);
        }
    }
    
    /**
     * Close camera stream
     */
    closeCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => {
                track.stop();
            });
            this.stream = null;
        }
        
        if (this.video) {
            this.video.srcObject = null;
        }
    }
    
    /**
     * Capture image from video
     */
    captureImage(canvasElement, format = 'image/jpeg', quality = 0.8) {
        if (!this.video || !canvasElement) {
            throw new Error('Video o canvas no disponible');
        }
        
        this.canvas = canvasElement;
        const context = this.canvas.getContext('2d');
        
        // Set canvas dimensions to match video
        this.canvas.width = this.video.videoWidth;
        this.canvas.height = this.video.videoHeight;
        
        // Draw current video frame to canvas
        context.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);
        
        // Convert to base64
        return this.canvas.toDataURL(format, quality);
    }
    
    /**
     * Check if camera is available
     */
    static async isCameraAvailable() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            return devices.some(device => device.kind === 'videoinput');
        } catch (error) {
            console.error('Error checking camera availability:', error);
            return false;
        }
    }
    
    /**
     * Get available cameras
     */
    static async getAvailableCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            return devices.filter(device => device.kind === 'videoinput');
        } catch (error) {
            console.error('Error getting camera list:', error);
            return [];
        }
    }
    
    /**
     * Check browser compatibility
     */
    static isSupported() {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    }
    
    /**
     * Request camera permissions
     */
    static async requestPermissions() {
        if (!CameraHelper.isSupported()) {
            throw new Error('Este navegador no soporta acceso a cámara');
        }
        
        try {
            // Request permissions by trying to access camera briefly
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: true, 
                audio: false 
            });
            
            // Stop the stream immediately
            stream.getTracks().forEach(track => track.stop());
            
            return true;
        } catch (error) {
            if (error.name === 'NotAllowedError') {
                throw new Error('Permisos de cámara denegados');
            } else if (error.name === 'NotFoundError') {
                throw new Error('No se encontró ninguna cámara');
            } else {
                throw new Error('Error accediendo a la cámara: ' + error.message);
            }
        }
    }
}

// Utility functions for image processing
const ImageUtils = {
    /**
     * Resize image while maintaining aspect ratio
     */
    resizeImage(imageData, maxWidth = 640, maxHeight = 480, quality = 0.8) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                // Calculate new dimensions
                let { width, height } = this;
                
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
                
                // Draw resized image
                ctx.drawImage(this, 0, 0, width, height);
                
                resolve(canvas.toDataURL('image/jpeg', quality));
            };
            img.src = imageData;
        });
    },
    
    /**
     * Validate image format and size
     */
    validateImage(imageData, maxSize = 5 * 1024 * 1024) {
        // Check if it's a valid data URL
        if (!imageData || !imageData.startsWith('data:image/')) {
            return { valid: false, error: 'Formato de imagen no válido' };
        }
        
        // Estimate file size (base64 is ~33% larger than binary)
        const estimatedSize = (imageData.length * 0.75);
        if (estimatedSize > maxSize) {
            return { 
                valid: false, 
                error: `Imagen demasiado grande (${Math.round(estimatedSize / 1024 / 1024)}MB). Máximo permitido: ${Math.round(maxSize / 1024 / 1024)}MB` 
            };
        }
        
        return { valid: true };
    },
    
    /**
     * Convert blob to base64
     */
    blobToBase64(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }
};

// Export for use in other modules
window.CameraHelper = CameraHelper;
window.ImageUtils = ImageUtils;