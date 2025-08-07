// ===================================================================
// REAL BIOMETRIC RECOGNITION SYSTEM
// Implements actual facial recognition using Face-api.js
// ===================================================================

class RealBiometricSystem {
    constructor() {
        this.faceAPI = window.faceAPILoader;
        this.videoElement = null;
        this.canvasElement = null;
        this.stream = null;
        this.isCapturing = false;
        this.enrollmentData = [];
    }

    async initialize() {
        try {
            await this.faceAPI.loadModels();
            console.log('Real biometric system initialized');
            return true;
        } catch (error) {
            console.error('Failed to initialize biometric system:', error);
            return false;
        }
    }

    async startCamera() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: 640, 
                    height: 480,
                    facingMode: 'user'
                } 
            });
            
            if (this.videoElement) {
                this.videoElement.srcObject = this.stream;
                return true;
            }
            return false;
        } catch (error) {
            console.error('Camera access denied:', error);
            throw error;
        }
    }

    stopCamera() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        if (this.videoElement) {
            this.videoElement.srcObject = null;
        }
    }

    setupVideoElement(videoElementId, canvasElementId) {
        this.videoElement = document.getElementById(videoElementId);
        this.canvasElement = document.getElementById(canvasElementId);
        
        if (this.videoElement && this.canvasElement) {
            this.videoElement.addEventListener('loadedmetadata', () => {
                this.canvasElement.width = this.videoElement.videoWidth;
                this.canvasElement.height = this.videoElement.videoHeight;
            });
            return true;
        }
        return false;
    }

    async detectFaceInVideo() {
        if (!this.videoElement || this.videoElement.paused || this.videoElement.ended) {
            return null;
        }

        try {
            const detections = await this.faceAPI.detectFace(this.videoElement);
            
            // Draw detection on canvas if available
            if (this.canvasElement && detections.length > 0) {
                const ctx = this.canvasElement.getContext('2d');
                ctx.clearRect(0, 0, this.canvasElement.width, this.canvasElement.height);
                
                detections.forEach(detection => {
                    const { x, y, width, height } = detection.detection.box;
                    ctx.strokeStyle = '#00ff00';
                    ctx.lineWidth = 2;
                    ctx.strokeRect(x, y, width, height);
                });
            }
            
            return detections;
        } catch (error) {
            console.error('Face detection error:', error);
            return null;
        }
    }

    async captureFaceForEnrollment() {
        const detections = await this.detectFaceInVideo();
        
        if (!detections || detections.length === 0) {
            throw new Error('No se detectó ningún rostro');
        }

        if (detections.length > 1) {
            throw new Error('Se detectaron múltiples rostros. Asegúrese de que solo una persona esté frente a la cámara.');
        }

        const faceDescriptor = detections[0].descriptor;
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = this.videoElement.videoWidth;
        canvas.height = this.videoElement.videoHeight;
        ctx.drawImage(this.videoElement, 0, 0);
        
        const imageData = canvas.toDataURL('image/jpeg', 0.8);
        
        return {
            descriptor: Array.from(faceDescriptor),
            image: imageData,
            timestamp: new Date().toISOString(),
            quality: this.calculateFaceQuality(detections[0])
        };
    }

    calculateFaceQuality(detection) {
        const { width, height } = detection.detection.box;
        const faceSize = Math.min(width, height);
        
        // Quality based on face size and detection confidence
        const sizeScore = Math.min(faceSize / 100, 1); // Normalize face size
        const confidenceScore = detection.detection.score;
        
        return Math.round((sizeScore * 0.4 + confidenceScore * 0.6) * 100);
    }

    async enrollFace(employeeId, captureCount = 3) {
        this.enrollmentData = [];
        const captures = [];
        
        for (let i = 0; i < captureCount; i++) {
            try {
                const capture = await this.captureFaceForEnrollment();
                if (capture.quality < 50) {
                    throw new Error(`Calidad de imagen insuficiente (${capture.quality}%). Mejore la iluminación y posición.`);
                }
                captures.push(capture);
                
                // Wait a bit between captures
                await new Promise(resolve => setTimeout(resolve, 1000));
            } catch (error) {
                throw new Error(`Error en captura ${i + 1}: ${error.message}`);
            }
        }

        // Create enrollment template by averaging descriptors
        const template = this.createFaceTemplate(captures);
        
        return {
            employeeId: employeeId,
            template: template,
            captures: captures,
            enrollmentDate: new Date().toISOString()
        };
    }

    createFaceTemplate(captures) {
        if (captures.length === 0) return null;
        
        const descriptorLength = captures[0].descriptor.length;
        const averageDescriptor = new Array(descriptorLength).fill(0);
        
        // Average all descriptors
        captures.forEach(capture => {
            capture.descriptor.forEach((value, index) => {
                averageDescriptor[index] += value;
            });
        });
        
        // Normalize by dividing by number of captures
        for (let i = 0; i < descriptorLength; i++) {
            averageDescriptor[i] /= captures.length;
        }
        
        return {
            descriptor: averageDescriptor,
            captureCount: captures.length,
            avgQuality: captures.reduce((sum, c) => sum + c.quality, 0) / captures.length
        };
    }

    async verifyFace(storedTemplate, threshold = 0.6) {
        try {
            const liveCapture = await this.captureFaceForEnrollment();
            
            if (liveCapture.quality < 40) {
                return {
                    success: false,
                    message: 'Calidad de imagen insuficiente para verificación',
                    quality: liveCapture.quality
                };
            }

            const liveDescriptor = new Float32Array(liveCapture.descriptor);
            const storedDescriptor = new Float32Array(storedTemplate.descriptor);
            
            const comparison = this.faceAPI.compareFaces(liveDescriptor, storedDescriptor, threshold);
            
            return {
                success: comparison.match,
                confidence: comparison.confidence,
                distance: comparison.distance,
                threshold: threshold,
                quality: liveCapture.quality,
                message: comparison.match ? 
                    `Verificación exitosa (${comparison.confidence.toFixed(1)}%)` : 
                    `Verificación fallida (${comparison.confidence.toFixed(1)}%)`
            };
        } catch (error) {
            return {
                success: false,
                message: `Error en verificación: ${error.message}`,
                confidence: 0
            };
        }
    }

    // Real-time face detection for preview
    startRealTimeDetection(callback) {
        const detectLoop = async () => {
            if (this.isCapturing) {
                const detections = await this.detectFaceInVideo();
                if (callback) {
                    callback(detections);
                }
                requestAnimationFrame(detectLoop);
            }
        };
        
        this.isCapturing = true;
        detectLoop();
    }

    stopRealTimeDetection() {
        this.isCapturing = false;
    }
}

// Global instance
window.realBiometricSystem = new RealBiometricSystem();