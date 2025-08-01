// =================================================================
// BIOMETRIC ENROLLMENT SYSTEM
// Handles fingerprint and facial recognition enrollment
// =================================================================

class BiometricEnrollment {
    constructor() {
        this.selectedEmployee = null;
        this.currentEnrollmentType = null;
        this.enrollmentSession = null;
        this.faceApiLoaded = false;
        this.fingerprintDevices = [];
        this.cameras = [];
        this.currentStream = null;
        
        this.init();
    }
    
    async init() {
        // Initialize event listeners
        this.setupEventListeners();
        
        // Load filters
        await this.loadFilters();
        
        // Check device availability
        await this.checkDeviceAvailability();
        
        // Load face-api models
        await this.loadFaceApiModels();
    }
    
    setupEventListeners() {
        // Employee search
        document.getElementById('btnBuscarEmpleado')?.addEventListener('click', () => this.searchEmployee());
        document.getElementById('codigo_empleado_bio')?.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') this.searchEmployee();
        });
        
        // Enrollment buttons
        document.getElementById('btnStartFingerprintEnrollment')?.addEventListener('click', () => this.startFingerprintEnrollment());
        document.getElementById('btnStartFacialEnrollment')?.addEventListener('click', () => this.startFacialEnrollment());
        
        // Fingerprint modal events
        document.getElementById('btnStartFingerprintCapture')?.addEventListener('click', () => this.startFingerprintCapture());
        document.getElementById('btnCaptureFingerprintSample')?.addEventListener('click', () => this.captureFingerprintSample());
        document.getElementById('btnTestFingerprint')?.addEventListener('click', () => this.testFingerprintRecognition());
        document.getElementById('btnFinishFingerprint')?.addEventListener('click', () => this.finishFingerprintEnrollment());
        document.getElementById('btnRetryFingerprint')?.addEventListener('click', () => this.retryFingerprintEnrollment());
        
        // Facial modal events
        document.getElementById('btnTestCamera')?.addEventListener('click', () => this.testCamera());
        document.getElementById('btnStartFacialCapture')?.addEventListener('click', () => this.startFacialCapture());
        document.getElementById('btnCaptureFaceSample')?.addEventListener('click', () => this.captureFaceSample());
        document.getElementById('btnTestFacial')?.addEventListener('click', () => this.testFacialRecognition());
        document.getElementById('btnFinishFacial')?.addEventListener('click', () => this.finishFacialEnrollment());
        document.getElementById('btnRetryFacial')?.addEventListener('click', () => this.retryFacialEnrollment());
        
        // Camera selection
        document.getElementById('cameraSelect')?.addEventListener('change', (e) => this.selectCamera(e.target.value));
        
        // Modal close events
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });
    }
    
    async loadFilters() {
        try {
            // Load sedes
            const sedesResponse = await fetch('api/get-sedes.php');
            const sedes = await sedesResponse.json();
            this.populateSelect('filtro_sede_bio', sedes, 'id', 'nombre');
            
            // Load establecimientos when sede changes
            document.getElementById('filtro_sede_bio').addEventListener('change', async (e) => {
                if (e.target.value) {
                    const response = await fetch(`api/get-establecimientos.php?sede_id=${e.target.value}`);
                    const establecimientos = await response.json();
                    this.populateSelect('filtro_establecimiento_bio', establecimientos, 'id', 'nombre');
                }
            });
        } catch (error) {
            console.error('Error loading filters:', error);
        }
    }
    
    populateSelect(selectId, data, valueField, textField) {
        const select = document.getElementById(selectId);
        if (!select) return;
        
        select.innerHTML = '<option value="">Seleccionar...</option>';
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = item[textField];
            select.appendChild(option);
        });
    }
    
    async checkDeviceAvailability() {
        // Check for fingerprint devices
        await this.checkFingerprintDevices();
        
        // Check for cameras
        await this.checkCameras();
    }
    
    async checkFingerprintDevices() {
        const statusElement = document.getElementById('fingerprintDeviceStatus');
        
        try {
            // Check for WebAuthn support (modern fingerprint API)
            if (window.PublicKeyCredential) {
                const isAvailable = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
                if (isAvailable) {
                    this.fingerprintDevices.push({
                        id: 'webauthn',
                        name: 'Dispositivo integrado',
                        type: 'integrated'
                    });
                }
            }
            
            // Check for other fingerprint devices (this would need specific drivers)
            // For now, we'll simulate checking for common devices
            await this.checkThirdPartyFingerprintDevices();
            
            if (this.fingerprintDevices.length > 0) {
                statusElement.textContent = `${this.fingerprintDevices.length} dispositivo(s) detectado(s)`;
                statusElement.classList.add('ready');
                document.getElementById('btnStartFingerprintEnrollment').disabled = false;
            } else {
                statusElement.textContent = 'No se detectaron dispositivos de huella digital';
                statusElement.classList.add('error');
            }
        } catch (error) {
            console.error('Error checking fingerprint devices:', error);
            statusElement.textContent = 'Error al detectar dispositivos';
            statusElement.classList.add('error');
        }
    }
    
    async checkThirdPartyFingerprintDevices() {
        // This would integrate with specific fingerprint device SDKs
        // For demo purposes, we'll simulate device detection
        const commonDevices = [
            { id: 'futronic', name: 'Futronic FS88', type: 'usb' },
            { id: 'digital_persona', name: 'Digital Persona U.are.U', type: 'usb' },
            { id: 'suprema', name: 'Suprema BioMini', type: 'usb' }
        ];
        
        // Simulate device detection (in real implementation, this would use device APIs)
        // For now, we'll just add WebAuthn support
        return Promise.resolve();
    }
    
    async checkCameras() {
        const statusElement = document.getElementById('facialDeviceStatus');
        
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            this.cameras = devices.filter(device => device.kind === 'videoinput');
            
            if (this.cameras.length > 0) {
                statusElement.textContent = `${this.cameras.length} cámara(s) detectada(s)`;
                statusElement.classList.add('ready');
                document.getElementById('btnStartFacialEnrollment').disabled = false;
                
                // Populate camera select
                this.populateCameraSelect();
            } else {
                statusElement.textContent = 'No se detectaron cámaras';
                statusElement.classList.add('error');
            }
        } catch (error) {
            console.error('Error checking cameras:', error);
            statusElement.textContent = 'Error al acceder a las cámaras';
            statusElement.classList.add('error');
        }
    }
    
    populateCameraSelect() {
        const select = document.getElementById('cameraSelect');
        if (!select) return;
        
        select.innerHTML = '<option value="">Seleccionar cámara...</option>';
        this.cameras.forEach((camera, index) => {
            const option = document.createElement('option');
            option.value = camera.deviceId;
            option.textContent = camera.label || `Cámara ${index + 1}`;
            select.appendChild(option);
        });
    }
    
    async loadFaceApiModels() {
        try {
            const modelPath = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model/';
            
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),
                faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),
                faceapi.nets.faceRecognitionNet.loadFromUri(modelPath),
                faceapi.nets.faceExpressionNet.loadFromUri(modelPath)
            ]);
            
            this.faceApiLoaded = true;
            console.log('Face-api models loaded successfully');
        } catch (error) {
            console.error('Error loading face-api models:', error);
        }
    }
    
    async searchEmployee() {
        const codigo = document.getElementById('codigo_empleado_bio').value.trim();
        const sede = document.getElementById('filtro_sede_bio').value;
        const establecimiento = document.getElementById('filtro_establecimiento_bio').value;
        
        if (!codigo && !sede && !establecimiento) {
            alert('Por favor ingrese al menos un criterio de búsqueda');
            return;
        }
        
        try {
            const params = new URLSearchParams();
            if (codigo) params.append('codigo', codigo);
            if (sede) params.append('sede', sede);
            if (establecimiento) params.append('establecimiento', establecimiento);
            
            const response = await fetch(`api/employee/search.php?${params}`);
            const result = await response.json();
            
            if (result.success && result.employee) {
                this.selectedEmployee = result.employee;
                await this.displayEmployeeInfo();
                await this.loadEmployeeBiometricStatus();
            } else {
                alert('Empleado no encontrado');
                this.hideEmployeeInfo();
            }
        } catch (error) {
            console.error('Error searching employee:', error);
            alert('Error al buscar empleado');
        }
    }
    
    displayEmployeeInfo() {
        const emp = this.selectedEmployee;
        
        document.getElementById('employeeName').textContent = emp.nombre;
        document.getElementById('employeeCode').textContent = emp.codigo;
        document.getElementById('employeeEstablishment').textContent = emp.establecimiento;
        document.getElementById('employeeSede').textContent = emp.sede;
        
        document.getElementById('employeeInfo').style.display = 'block';
        document.getElementById('biometricEnrollmentSection').style.display = 'block';
    }
    
    hideEmployeeInfo() {
        document.getElementById('employeeInfo').style.display = 'none';
        document.getElementById('biometricEnrollmentSection').style.display = 'none';
        this.selectedEmployee = null;
    }
    
    async loadEmployeeBiometricStatus() {
        if (!this.selectedEmployee) return;
        
        try {
            const response = await fetch(`api/biometric/status.php?employee_id=${this.selectedEmployee.id}`);
            const status = await response.json();
            
            // Update status badges
            const fingerprintStatus = document.getElementById('fingerprintStatus');
            const facialStatus = document.getElementById('facialStatus');
            
            if (status.fingerprint_enrolled) {
                fingerprintStatus.textContent = 'Inscrito';
                fingerprintStatus.className = 'status-badge enrolled';
            } else {
                fingerprintStatus.textContent = 'No inscrito';
                fingerprintStatus.className = 'status-badge not-enrolled';
            }
            
            if (status.facial_enrolled) {
                facialStatus.textContent = 'Inscrito';
                facialStatus.className = 'status-badge enrolled';
            } else {
                facialStatus.textContent = 'No inscrito';
                facialStatus.className = 'status-badge not-enrolled';
            }
        } catch (error) {
            console.error('Error loading biometric status:', error);
        }
    }
    
    // Fingerprint Enrollment Methods
    startFingerprintEnrollment() {
        if (!this.selectedEmployee) {
            alert('Por favor seleccione un empleado primero');
            return;
        }
        
        this.currentEnrollmentType = 'fingerprint';
        this.showModal('fingerprintEnrollmentModal');
        this.populateFingerprintDevices();
    }
    
    populateFingerprintDevices() {
        const deviceList = document.getElementById('fingerprintDeviceList');
        deviceList.innerHTML = '';
        
        this.fingerprintDevices.forEach(device => {
            const deviceCard = document.createElement('div');
            deviceCard.className = 'device-card';
            deviceCard.innerHTML = `
                <div class="device-info">
                    <i class="fas fa-fingerprint"></i>
                    <div>
                        <h5>${device.name}</h5>
                        <p>Tipo: ${device.type}</p>
                    </div>
                </div>
                <button type="button" class="btn-primary" onclick="biometricEnrollment.selectFingerprintDevice('${device.id}')">
                    Seleccionar
                </button>
            `;
            deviceList.appendChild(deviceCard);
        });
    }
    
    selectFingerprintDevice(deviceId) {
        this.selectedFingerprintDevice = deviceId;
        this.showFingerprintStep('fingerprintInstructions');
    }
    
    startFingerprintCapture() {
        this.showFingerprintStep('fingerprintCapture');
        this.initializeFingerprintSession();
    }
    
    async initializeFingerprintSession() {
        try {
            const response = await fetch('api/biometric/start-session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    employee_id: this.selectedEmployee.id,
                    biometric_type: 'fingerprint',
                    device_id: this.selectedFingerprintDevice
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.enrollmentSession = result.session;
                this.updateFingerprintProgress();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error initializing session:', error);
            this.showFingerprintError(error.message);
        }
    }
    
    async captureFingerprintSample() {
        try {
            document.getElementById('fingerprintStatus').textContent = 'Capturando...';
            
            // Simulate fingerprint capture (in real implementation, this would use device SDK)
            const sampleData = await this.simulateFingerprintCapture();
            
            const response = await fetch('api/biometric/capture-sample.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: this.enrollmentSession.id,
                    sample_data: sampleData,
                    sample_number: this.enrollmentSession.samples_collected + 1
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.enrollmentSession = result.session;
                this.updateFingerprintProgress();
                
                if (this.enrollmentSession.samples_collected >= 3) {
                    await this.completeFingerprintEnrollment();
                } else {
                    document.getElementById('fingerprintStatus').textContent = 
                        `Muestra ${this.enrollmentSession.samples_collected} capturada. Capture la siguiente muestra.`;
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error capturing sample:', error);
            document.getElementById('fingerprintStatus').textContent = 'Error en la captura. Intente nuevamente.';
        }
    }
    
    async simulateFingerprintCapture() {
        // Simulate delay for fingerprint capture
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        // Return simulated fingerprint template (in real implementation, this would come from device)
        return {
            template: btoa(Math.random().toString(36).substr(2, 100)),
            quality: Math.floor(Math.random() * 30) + 70 // 70-100% quality
        };
    }
    
    updateFingerprintProgress() {
        const current = this.enrollmentSession.samples_collected;
        const total = this.enrollmentSession.required_samples;
        const percentage = (current / total) * 100;
        
        document.getElementById('fingerprintProgressFill').style.width = `${percentage}%`;
        document.getElementById('fingerprintCurrentSample').textContent = current + 1;
        document.getElementById('fingerprintTotalSamples').textContent = total;
        
        // Update sample dots
        for (let i = 1; i <= total; i++) {
            const dot = document.getElementById(`sample${i}`);
            if (i <= current) {
                dot.classList.add('collected');
                dot.classList.remove('current');
            } else if (i === current + 1) {
                dot.classList.add('current');
            }
        }
    }
    
    async completeFingerprintEnrollment() {
        try {
            const response = await fetch('api/biometric/complete-enrollment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: this.enrollmentSession.id
                })
            });
            
            const result = await response.json();
            if (result.success) {
                document.getElementById('fingerprintQualityScore').textContent = result.quality_score;
                this.showFingerprintStep('fingerprintComplete');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error completing enrollment:', error);
            this.showFingerprintError(error.message);
        }
    }
    
    // Facial Recognition Enrollment Methods
    startFacialEnrollment() {
        if (!this.selectedEmployee) {
            alert('Por favor seleccione un empleado primero');
            return;
        }
        
        this.currentEnrollmentType = 'facial';
        this.showModal('facialEnrollmentModal');
        this.showFacialStep('facialCameraSetup');
    }
    
    async testCamera() {
        const selectedCamera = document.getElementById('cameraSelect').value;
        if (!selectedCamera) {
            alert('Por favor seleccione una cámara');
            return;
        }
        
        try {
            if (this.currentStream) {
                this.currentStream.getTracks().forEach(track => track.stop());
            }
            
            this.currentStream = await navigator.mediaDevices.getUserMedia({
                video: { deviceId: selectedCamera }
            });
            
            alert('Cámara funcionando correctamente');
            document.getElementById('btnStartFacialCapture').disabled = false;
        } catch (error) {
            console.error('Error testing camera:', error);
            alert('Error al acceder a la cámara');
        }
    }
    
    selectCamera(cameraId) {
        if (cameraId) {
            document.getElementById('btnTestCamera').disabled = false;
        }
    }
    
    startFacialCapture() {
        this.showFacialStep('facialInstructions');
    }
    
    async proceedToCapture() {
        this.showFacialStep('facialCapture');
        await this.initializeFacialSession();
        await this.startVideoStream();
    }
    
    async initializeFacialSession() {
        try {
            const response = await fetch('api/biometric/start-session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    employee_id: this.selectedEmployee.id,
                    biometric_type: 'facial',
                    required_samples: 5
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.enrollmentSession = result.session;
                this.updateFacialProgress();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error initializing facial session:', error);
            this.showFacialError(error.message);
        }
    }
    
    async startVideoStream() {
        try {
            const video = document.getElementById('facialVideo');
            const cameraId = document.getElementById('cameraSelect').value;
            
            if (this.currentStream) {
                this.currentStream.getTracks().forEach(track => track.stop());
            }
            
            this.currentStream = await navigator.mediaDevices.getUserMedia({
                video: { 
                    deviceId: cameraId,
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });
            
            video.srcObject = this.currentStream;
            
            // Start face detection
            video.addEventListener('loadedmetadata', () => {
                this.startFaceDetection();
            });
            
        } catch (error) {
            console.error('Error starting video stream:', error);
            this.showFacialError('Error al acceder a la cámara');
        }
    }
    
    async startFaceDetection() {
        if (!this.faceApiLoaded) return;
        
        const video = document.getElementById('facialVideo');
        const detectionBox = document.getElementById('faceDetectionBox');
        
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
                    
                    // Enable capture button if face is well positioned
                    const isWellPositioned = this.isFaceWellPositioned(box, video);
                    document.getElementById('btnCaptureFaceSample').disabled = !isWellPositioned;
                    
                    if (isWellPositioned) {
                        document.getElementById('facialStatus').textContent = 'Rostro detectado - Puede capturar';
                        detectionBox.style.borderColor = '#10b981';
                    } else {
                        document.getElementById('facialStatus').textContent = 'Ajuste la posición del rostro';
                        detectionBox.style.borderColor = '#f59e0b';
                    }
                } else {
                    detectionBox.style.display = 'none';
                    document.getElementById('btnCaptureFaceSample').disabled = true;
                    document.getElementById('facialStatus').textContent = 'Posicione su rostro en el centro';
                }
                
                // Continue detection
                if (this.currentStream && this.currentStream.active) {
                    requestAnimationFrame(detectFaces);
                }
            } catch (error) {
                console.error('Error in face detection:', error);
            }
        };
        
        // Start detection loop
        detectFaces();
    }
    
    isFaceWellPositioned(faceBox, video) {
        const videoRect = video.getBoundingClientRect();
        const centerX = videoRect.width / 2;
        const centerY = videoRect.height / 2;
        
        const faceCenterX = faceBox.x + faceBox.width / 2;
        const faceCenterY = faceBox.y + faceBox.height / 2;
        
        // Check if face is centered and properly sized
        const iscentered = Math.abs(faceCenterX - centerX) < 50 && Math.abs(faceCenterY - centerY) < 50;
        const isProperSize = faceBox.width > 100 && faceBox.height > 100;
        
        return iscentered && isProperSize;
    }
    
    async captureFaceSample() {
        try {
            const video = document.getElementById('facialVideo');
            const canvas = document.getElementById('facialCanvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas dimensions
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw current frame
            ctx.drawImage(video, 0, 0);
            
            // Get image data
            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            
            // Extract face descriptors
            const faceDescriptor = await this.extractFaceDescriptor(video);
            
            if (!faceDescriptor) {
                throw new Error('No se pudo extraer características faciales');
            }
            
            const response = await fetch('api/biometric/capture-sample.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: this.enrollmentSession.id,
                    sample_data: {
                        image: imageData,
                        descriptor: Array.from(faceDescriptor),
                        timestamp: Date.now()
                    },
                    sample_number: this.enrollmentSession.samples_collected + 1
                })
            });
            
            const result = await response.json();
            if (result.success) {
                this.enrollmentSession = result.session;
                this.updateFacialProgress();
                
                if (this.enrollmentSession.samples_collected >= 5) {
                    this.showFacialStep('facialProcessing');
                    await this.completeFacialEnrollment();
                } else {
                    this.updateFacialInstructions();
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error capturing facial sample:', error);
            document.getElementById('facialStatus').textContent = 'Error en la captura. Intente nuevamente.';
        }
    }
    
    async extractFaceDescriptor(video) {
        if (!this.faceApiLoaded) return null;
        
        try {
            const detection = await faceapi
                .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            return detection ? detection.descriptor : null;
        } catch (error) {
            console.error('Error extracting face descriptor:', error);
            return null;
        }
    }
    
    updateFacialProgress() {
        const current = this.enrollmentSession.samples_collected;
        const total = this.enrollmentSession.required_samples;
        const percentage = (current / total) * 100;
        
        document.getElementById('facialProgressFill').style.width = `${percentage}%`;
        document.getElementById('facialCurrentSample').textContent = current + 1;
        document.getElementById('facialTotalSamples').textContent = total;
        
        // Update sample dots
        for (let i = 1; i <= total; i++) {
            const dot = document.getElementById(`faceSample${i}`);
            if (i <= current) {
                dot.classList.add('collected');
                dot.classList.remove('current');
            } else if (i === current + 1) {
                dot.classList.add('current');
            }
        }
    }
    
    updateFacialInstructions() {
        const instructions = [
            'Mire directamente a la cámara',
            'Gire ligeramente la cabeza hacia la izquierda',
            'Gire ligeramente la cabeza hacia la derecha',
            'Incline ligeramente la cabeza hacia arriba',
            'Mire directamente a la cámara nuevamente'
        ];
        
        const currentSample = this.enrollmentSession.samples_collected;
        if (currentSample < instructions.length) {
            document.getElementById('currentInstruction').textContent = instructions[currentSample];
        }
    }
    
    async completeFacialEnrollment() {
        try {
            // Simulate processing delay
            await new Promise(resolve => setTimeout(resolve, 3000));
            
            const response = await fetch('api/biometric/complete-enrollment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    session_id: this.enrollmentSession.id
                })
            });
            
            const result = await response.json();
            if (result.success) {
                document.getElementById('facialQualityScore').textContent = result.quality_score;
                document.getElementById('facialSamplesProcessed').textContent = this.enrollmentSession.samples_collected;
                this.showFacialStep('facialComplete');
                
                // Stop video stream
                if (this.currentStream) {
                    this.currentStream.getTracks().forEach(track => track.stop());
                    this.currentStream = null;
                }
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error completing facial enrollment:', error);
            this.showFacialError(error.message);
        }
    }
    
    // Utility Methods
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
        
        // Stop video stream if closing facial modal
        if (modalId === 'facialEnrollmentModal' && this.currentStream) {
            this.currentStream.getTracks().forEach(track => track.stop());
            this.currentStream = null;
        }
    }
    
    closeAllModals() {
        this.closeModal('fingerprintEnrollmentModal');
        this.closeModal('facialEnrollmentModal');
    }
    
    showFingerprintStep(stepId) {
        const steps = ['fingerprintDeviceSelection', 'fingerprintInstructions', 'fingerprintCapture', 'fingerprintComplete', 'fingerprintError'];
        steps.forEach(step => {
            const element = document.getElementById(step);
            if (element) {
                element.style.display = step === stepId ? 'block' : 'none';
            }
        });
    }
    
    showFacialStep(stepId) {
        const steps = ['facialCameraSetup', 'facialInstructions', 'facialCapture', 'facialProcessing', 'facialComplete', 'facialError'];
        steps.forEach(step => {
            const element = document.getElementById(step);
            if (element) {
                element.style.display = step === stepId ? 'block' : 'none';
            }
        });
    }
    
    showFingerprintError(message) {
        document.getElementById('fingerprintErrorText').textContent = message;
        this.showFingerprintStep('fingerprintError');
    }
    
    showFacialError(message) {
        document.getElementById('facialErrorText').textContent = message;
        this.showFacialStep('facialError');
    }
    
    finishFingerprintEnrollment() {
        this.closeModal('fingerprintEnrollmentModal');
        this.loadEmployeeBiometricStatus();
    }
    
    finishFacialEnrollment() {
        this.closeModal('facialEnrollmentModal');
        this.loadEmployeeBiometricStatus();
    }
    
    retryFingerprintEnrollment() {
        this.showFingerprintStep('fingerprintInstructions');
    }
    
    retryFacialEnrollment() {
        this.showFacialStep('facialCameraSetup');
    }
    
    async testFingerprintRecognition() {
        alert('Función de prueba no implementada en esta demo');
    }
    
    async testFacialRecognition() {
        alert('Función de prueba no implementada en esta demo');
    }
}

// Global functions for modal close buttons
function closeFingerprintModal() {
    biometricEnrollment.closeModal('fingerprintEnrollmentModal');
}

function closeFacialModal() {
    biometricEnrollment.closeModal('facialEnrollmentModal');
}

// Initialize when DOM is loaded
let biometricEnrollment;
document.addEventListener('DOMContentLoaded', function() {
    biometricEnrollment = new BiometricEnrollment();
});