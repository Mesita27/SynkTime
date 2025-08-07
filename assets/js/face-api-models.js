// ===================================================================
// FACE-API MODELS LOADER
// Loads Face-api.js models for facial recognition
// ===================================================================

class FaceAPILoader {
    constructor() {
        this.modelsLoaded = false;
        this.modelPath = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@latest/model/';
    }

    async loadModels() {
        if (this.modelsLoaded) {
            return true;
        }

        try {
            console.log('Loading Face-api.js models...');
            
            // Load required models
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(this.modelPath),
                faceapi.nets.faceLandmark68Net.loadFromUri(this.modelPath),
                faceapi.nets.faceRecognitionNet.loadFromUri(this.modelPath),
                faceapi.nets.faceExpressionNet.loadFromUri(this.modelPath)
            ]);

            this.modelsLoaded = true;
            console.log('Face-api.js models loaded successfully');
            return true;
        } catch (error) {
            console.error('Error loading Face-api.js models:', error);
            throw error;
        }
    }

    async detectFace(imageElement) {
        if (!this.modelsLoaded) {
            await this.loadModels();
        }

        const detections = await faceapi
            .detectAllFaces(imageElement, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptors();

        return detections;
    }

    async getFaceDescriptor(imageElement) {
        const detections = await this.detectFace(imageElement);
        if (detections.length > 0) {
            return detections[0].descriptor;
        }
        return null;
    }

    compareFaces(descriptor1, descriptor2, threshold = 0.6) {
        if (!descriptor1 || !descriptor2) {
            return { match: false, distance: 1 };
        }

        const distance = faceapi.euclideanDistance(descriptor1, descriptor2);
        const match = distance < threshold;
        
        return {
            match: match,
            distance: distance,
            confidence: Math.max(0, (1 - distance) * 100)
        };
    }
}

// Global instance
window.faceAPILoader = new FaceAPILoader();