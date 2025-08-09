/**
 * Biometric Enrollment JavaScript
 * Handles employee biometric data enrollment (face and fingerprint)
 */

class BiometricEnrollmentManager {
    constructor() {
        this.cameraManager = new CameraManager();
        this.cameraUI = new CameraUI(this.cameraManager);
        this.currentEmployee = null;
        this.isProcessing = false;
        this.fingerTypes = {};
    }

    /**
     * Initialize enrollment system
     */
    async init() {
        this.bindEventListeners();
        this.initializeEmployeeSelector();
        await this.loadConfiguration();
        this.checkBiometricSupport();
    }

    /**
     * Load biometric configuration
     */
    async loadConfiguration() {
        try {
            const response = await fetch('api/biometric/get-config.php');
            const data = await response.json();
            
            if (data.success) {
                this.fingerTypes = data.config.finger_types || {};
                this.updateFingerTypeSelector();
            }
        } catch (error) {
            console.warn('Error loading configuration:', error);
        }
    }

    /**
     * Bind event listeners
     */
    bindEventListeners() {
        // Employee selection
        document.addEventListener('change', '#enrollmentEmployeeSelect', (e) => {
            this.selectEmployee(e.target.value);
        });

        // Enrollment buttons
        document.addEventListener('click', '[data-enrollment-type]', (e) => {
            e.preventDefault();
            const type = e.target.dataset.enrollmentType;
            this.startEnrollment(type);
        });

        // Biometric data management
        document.addEventListener('click', '[data-action]', (e) => {
            e.preventDefault();
            const action = e.target.dataset.action;
            const id = e.target.dataset.id;
            this.handleBiometricAction(action, id);
        });
    }

    /**
     * Initialize employee selector
     */
    initializeEmployeeSelector() {
        const selector = document.getElementById('enrollmentEmployeeSelect');
        if (!selector) return;

        // Initialize autocomplete
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $(selector).select2({
                placeholder: 'Buscar empleado para inscripción...',
                allowClear: true,
                ajax: {
                    url: 'api/employee/search.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page,
                            enrollment_eligible: true
                        };
                    },
                    processResults: function (data, params) {
                        return {
                            results: data.employees.map(emp => ({
                                id: emp.ID_EMPLEADO,
                                text: `${emp.NOMBRE} ${emp.APELLIDO} - ${emp.DNI}`,
                                data: emp
                            }))
                        };
                    }
                }
            });
        }
    }

    /**
     * Check biometric support
     */
    checkBiometricSupport() {
        if (!CameraManager.isSupported()) {
            this.disableEnrollmentType('facial');
            this.showWarning('Cámara no disponible - Inscripción facial deshabilitada');
        }
    }

    /**
     * Select employee for enrollment
     */
    async selectEmployee(employeeId) {
        if (!employeeId) {
            this.currentEmployee = null;
            this.updateUI();
            return;
        }

        try {
            this.showLoading(true, 'Cargando datos del empleado...');
            
            const response = await fetch('api/employee/get-profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id_empleado: employeeId })
            });

            const data = await response.json();

            if (data.success) {
                this.currentEmployee = data.employee;
                await this.loadBiometricData();
                this.updateUI();
            } else {
                throw new Error(data.message);
            }

        } catch (error) {
            this.showError('Error al cargar empleado: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Load existing biometric data for employee
     */
    async loadBiometricData() {
        if (!this.currentEmployee) return;

        try {
            const response = await fetch('api/biometric/get-employee-data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id_empleado: this.currentEmployee.ID_EMPLEADO })
            });

            const data = await response.json();

            if (data.success) {
                this.currentEmployee.biometric_data = data.biometric_data;
            }

        } catch (error) {
            console.warn('Error loading biometric data:', error);
        }
    }

    /**
     * Start enrollment process
     */
    async startEnrollment(type) {
        if (!this.currentEmployee) {
            this.showError('Seleccione un empleado primero');
            return;
        }

        if (this.isProcessing) {
            return;
        }

        this.isProcessing = true;

        try {
            switch (type) {
                case 'facial':
                    await this.enrollFacial();
                    break;
                case 'fingerprint':
                    await this.enrollFingerprint();
                    break;
                default:
                    throw new Error('Tipo de inscripción no soportado');
            }

        } catch (error) {
            this.showError(error.message);
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Enroll facial patterns
     */
    async enrollFacial() {
        try {
            // Capture multiple images
            const modal = this.createFacialEnrollmentModal();
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

        } catch (error) {
            throw new Error('Error en inscripción facial: ' + error.message);
        }
    }

    /**
     * Create facial enrollment modal
     */
    createFacialEnrollmentModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Inscripción Facial</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <p>Capture 1-3 imágenes del rostro desde diferentes ángulos</p>
                            <div id="captureProgress" class="progress mb-3" style="display: none;">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                        
                        <video id="facialVideo" class="w-100 mb-3" style="max-height: 400px;" autoplay muted></video>
                        <canvas id="facialCanvas" style="display: none;"></canvas>
                        
                        <div class="row" id="capturedImages"></div>
                        
                        <div class="text-center">
                            <button id="captureImageBtn" class="btn btn-primary me-2">
                                <i class="fas fa-camera"></i> Capturar Imagen
                            </button>
                            <button id="enrollFacialBtn" class="btn btn-success" style="display: none;">
                                <i class="fas fa-check"></i> Completar Inscripción
                            </button>
                        </div>
                        
                        <div id="facialError" class="alert alert-danger mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        `;

        this.initializeFacialEnrollment(modal);
        document.body.appendChild(modal);
        return modal;
    }

    /**
     * Initialize facial enrollment modal
     */
    async initializeFacialEnrollment(modal) {
        const video = modal.querySelector('#facialVideo');
        const canvas = modal.querySelector('#facialCanvas');
        const captureBtn = modal.querySelector('#captureImageBtn');
        const enrollBtn = modal.querySelector('#enrollFacialBtn');
        const errorDiv = modal.querySelector('#facialError');
        const imagesContainer = modal.querySelector('#capturedImages');
        const progressBar = modal.querySelector('.progress-bar');
        const progressContainer = modal.querySelector('#captureProgress');

        let capturedImages = [];

        try {
            await this.cameraManager.initialize(video, canvas);
        } catch (error) {
            errorDiv.textContent = error.message;
            errorDiv.style.display = 'block';
            return;
        }

        // Capture button
        captureBtn.addEventListener('click', () => {
            try {
                const imageData = this.cameraManager.captureImage();
                capturedImages.push(imageData);
                
                // Show captured image
                const imageDiv = document.createElement('div');
                imageDiv.className = 'col-md-4 mb-2';
                imageDiv.innerHTML = `
                    <img src="${imageData}" class="img-fluid rounded" style="max-height: 100px;">
                    <div class="text-center mt-1">
                        <small>Imagen ${capturedImages.length}</small>
                        <button class="btn btn-sm btn-outline-danger ms-1" onclick="this.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                // Remove image from array when deleted
                imageDiv.querySelector('.btn-outline-danger').addEventListener('click', () => {
                    const index = Array.from(imagesContainer.children).indexOf(imageDiv);
                    capturedImages.splice(index, 1);
                    this.updateFacialProgress();
                });
                
                imagesContainer.appendChild(imageDiv);
                this.updateFacialProgress();
                
            } catch (error) {
                errorDiv.textContent = error.message;
                errorDiv.style.display = 'block';
            }
        });

        // Enroll button
        enrollBtn.addEventListener('click', async () => {
            try {
                await this.processFacialEnrollment(capturedImages);
                bootstrap.Modal.getInstance(modal).hide();
                
            } catch (error) {
                errorDiv.textContent = error.message;
                errorDiv.style.display = 'block';
            }
        });

        // Update progress function
        this.updateFacialProgress = () => {
            const count = capturedImages.length;
            const maxImages = 3;
            const percentage = (count / maxImages) * 100;
            
            progressBar.style.width = `${Math.min(percentage, 100)}%`;
            progressContainer.style.display = count > 0 ? 'block' : 'none';
            
            if (count >= 1) {
                enrollBtn.style.display = 'inline-block';
            } else {
                enrollBtn.style.display = 'none';
            }
            
            if (count >= maxImages) {
                captureBtn.disabled = true;
                captureBtn.textContent = 'Máximo de imágenes alcanzado';
            }
        };

        // Handle modal close
        modal.addEventListener('hidden.bs.modal', () => {
            this.cameraManager.stop();
            document.body.removeChild(modal);
        });
    }

    /**
     * Process facial enrollment
     */
    async processFacialEnrollment(images) {
        this.showLoading(true, 'Procesando inscripción facial...');

        const response = await fetch('api/biometrics/FaceController.php?action=enroll', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_empleado: this.currentEmployee.ID_EMPLEADO,
                images: images
            })
        });

        const data = await response.json();

        if (data.success) {
            this.showSuccess('Inscripción facial completada exitosamente');
            await this.loadBiometricData();
            this.updateUI();
        } else {
            throw new Error(data.message);
        }
    }

    /**
     * Enroll fingerprint
     */
    async enrollFingerprint() {
        const modal = this.createFingerprintEnrollmentModal();
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }

    /**
     * Create fingerprint enrollment modal
     */
    createFingerprintEnrollmentModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        
        const fingerOptions = Object.entries(this.fingerTypes)
            .map(([key, value]) => `<option value="${key}">${value}</option>`)
            .join('');

        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Inscripción de Huella</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="fingerTypeSelect" class="form-label">Tipo de Dedo</label>
                            <select id="fingerTypeSelect" class="form-select">
                                <option value="">Seleccione tipo de dedo</option>
                                ${fingerOptions}
                            </select>
                        </div>
                        
                        <div class="text-center mb-3">
                            <p>Seleccione el método de captura:</p>
                            <div class="d-grid gap-2">
                                <button id="fingerprintCameraBtn" class="btn btn-primary">
                                    <i class="fas fa-camera"></i> Usar Cámara
                                </button>
                                <button id="fingerprintFileBtn" class="btn btn-secondary">
                                    <i class="fas fa-upload"></i> Subir Archivo
                                </button>
                            </div>
                        </div>
                        
                        <div id="fingerprintPreview" style="display: none;" class="text-center mb-3">
                            <img id="fingerprintImage" class="img-fluid rounded" style="max-height: 200px;">
                            <div class="mt-2">
                                <button id="enrollFingerprintBtn" class="btn btn-success">
                                    <i class="fas fa-check"></i> Completar Inscripción
                                </button>
                                <button id="retakeFingerprintBtn" class="btn btn-outline-secondary">
                                    <i class="fas fa-redo"></i> Tomar otra
                                </button>
                            </div>
                        </div>
                        
                        <div id="fingerprintError" class="alert alert-danger" style="display: none;"></div>
                    </div>
                </div>
            </div>
        `;

        this.initializeFingerprintEnrollment(modal);
        document.body.appendChild(modal);
        return modal;
    }

    /**
     * Initialize fingerprint enrollment modal
     */
    initializeFingerprintEnrollment(modal) {
        const fingerTypeSelect = modal.querySelector('#fingerTypeSelect');
        const cameraBtn = modal.querySelector('#fingerprintCameraBtn');
        const fileBtn = modal.querySelector('#fingerprintFileBtn');
        const enrollBtn = modal.querySelector('#enrollFingerprintBtn');
        const retakeBtn = modal.querySelector('#retakeFingerprintBtn');
        const previewDiv = modal.querySelector('#fingerprintPreview');
        const imagePreview = modal.querySelector('#fingerprintImage');
        const errorDiv = modal.querySelector('#fingerprintError');

        let capturedImage = null;
        let selectedFingerType = null;

        // Finger type selection
        fingerTypeSelect.addEventListener('change', (e) => {
            selectedFingerType = e.target.value;
        });

        // Camera capture
        cameraBtn.addEventListener('click', async () => {
            if (!selectedFingerType) {
                this.showModalError(errorDiv, 'Seleccione el tipo de dedo primero');
                return;
            }

            try {
                capturedImage = await this.cameraUI.showCaptureModal('Captura de Huella');
                imagePreview.src = capturedImage;
                previewDiv.style.display = 'block';
                
            } catch (error) {
                if (error.message !== 'Captura cancelada') {
                    this.showModalError(errorDiv, error.message);
                }
            }
        });

        // File upload
        fileBtn.addEventListener('click', async () => {
            if (!selectedFingerType) {
                this.showModalError(errorDiv, 'Seleccione el tipo de dedo primero');
                return;
            }

            try {
                capturedImage = await this.cameraUI.showFileUpload('image/*');
                imagePreview.src = capturedImage;
                previewDiv.style.display = 'block';
                
            } catch (error) {
                this.showModalError(errorDiv, error.message);
            }
        });

        // Retake
        retakeBtn.addEventListener('click', () => {
            previewDiv.style.display = 'none';
            capturedImage = null;
        });

        // Enroll
        enrollBtn.addEventListener('click', async () => {
            try {
                await this.processFingerprintEnrollment(selectedFingerType, capturedImage);
                bootstrap.Modal.getInstance(modal).hide();
                
            } catch (error) {
                this.showModalError(errorDiv, error.message);
            }
        });

        // Handle modal close
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    /**
     * Process fingerprint enrollment
     */
    async processFingerprintEnrollment(fingerType, imageData) {
        this.showLoading(true, 'Procesando inscripción de huella...');

        const response = await fetch('api/biometrics/FingerprintController.php?action=enroll', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_empleado: this.currentEmployee.ID_EMPLEADO,
                finger_type: fingerType,
                image: imageData
            })
        });

        const data = await response.json();

        if (data.success) {
            this.showSuccess(`Inscripción de huella completada: ${this.fingerTypes[fingerType]}`);
            await this.loadBiometricData();
            this.updateUI();
        } else {
            throw new Error(data.message);
        }
    }

    /**
     * Handle biometric data actions (activate, deactivate, delete)
     */
    async handleBiometricAction(action, id) {
        if (!confirm(`¿Está seguro de ${action} este dato biométrico?`)) {
            return;
        }

        try {
            this.showLoading(true, `Procesando ${action}...`);

            const response = await fetch('api/biometric/manage-data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    id: id
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(`Acción ${action} completada exitosamente`);
                await this.loadBiometricData();
                this.updateUI();
            } else {
                throw new Error(data.message);
            }

        } catch (error) {
            this.showError(`Error en ${action}: ` + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Update UI components
     */
    updateUI() {
        this.updateEmployeeInfo();
        this.updateBiometricDataList();
        this.updateEnrollmentButtons();
    }

    /**
     * Update employee information display
     */
    updateEmployeeInfo() {
        const employeeInfo = document.getElementById('employeeInfo');
        
        if (this.currentEmployee && employeeInfo) {
            employeeInfo.innerHTML = `
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">${this.currentEmployee.NOMBRE} ${this.currentEmployee.APELLIDO}</h6>
                        <p class="card-text">
                            <small class="text-muted">DNI: ${this.currentEmployee.DNI}</small><br>
                            <small class="text-muted">Establecimiento: ${this.currentEmployee.ESTABLECIMIENTO_NOMBRE || 'N/A'}</small>
                        </p>
                    </div>
                </div>
            `;
        } else if (employeeInfo) {
            employeeInfo.innerHTML = '';
        }
    }

    /**
     * Update biometric data list
     */
    updateBiometricDataList() {
        const dataList = document.getElementById('biometricDataList');
        
        if (!this.currentEmployee || !dataList) {
            if (dataList) dataList.innerHTML = '';
            return;
        }

        const biometricData = this.currentEmployee.biometric_data || [];
        
        if (biometricData.length === 0) {
            dataList.innerHTML = '<p class="text-muted">No hay datos biométricos registrados</p>';
            return;
        }

        dataList.innerHTML = biometricData.map(item => `
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-1">
                                ${item.BIOMETRIC_TYPE === 'facial' ? 'Reconocimiento Facial' : 'Huella Dactilar'}
                                ${item.FINGER_TYPE ? ` (${this.fingerTypes[item.FINGER_TYPE] || item.FINGER_TYPE})` : ''}
                            </h6>
                            <small class="text-muted">
                                Registrado: ${new Date(item.CREATED_AT).toLocaleString()}
                                ${item.ACTIVO ? '' : ' - INACTIVO'}
                            </small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            ${item.ACTIVO ? 
                                '<button class="btn btn-outline-warning" data-action="deactivate" data-id="' + item.ID + '">Desactivar</button>' :
                                '<button class="btn btn-outline-success" data-action="activate" data-id="' + item.ID + '">Activar</button>'
                            }
                            <button class="btn btn-outline-danger" data-action="delete" data-id="${item.ID}">Eliminar</button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    /**
     * Update enrollment buttons based on current data
     */
    updateEnrollmentButtons() {
        const facialBtn = document.querySelector('[data-enrollment-type="facial"]');
        const fingerprintBtn = document.querySelector('[data-enrollment-type="fingerprint"]');

        if (!this.currentEmployee) {
            if (facialBtn) facialBtn.disabled = true;
            if (fingerprintBtn) fingerprintBtn.disabled = true;
            return;
        }

        const biometricData = this.currentEmployee.biometric_data || [];
        const hasFacial = biometricData.some(item => item.BIOMETRIC_TYPE === 'facial' && item.ACTIVO);

        if (facialBtn) {
            facialBtn.disabled = false;
            facialBtn.textContent = hasFacial ? 'Actualizar Rostro' : 'Inscribir Rostro';
        }

        if (fingerprintBtn) {
            fingerprintBtn.disabled = false;
        }
    }

    /**
     * Update finger type selector
     */
    updateFingerTypeSelector() {
        // This will be called when configuration is loaded
    }

    /**
     * Show modal error
     */
    showModalError(errorDiv, message) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    /**
     * Show loading state
     */
    showLoading(show, message = 'Procesando...') {
        const loadingDiv = document.getElementById('loadingIndicator');
        
        if (loadingDiv) {
            if (show) {
                loadingDiv.innerHTML = `
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        <span>${message}</span>
                    </div>
                `;
                loadingDiv.style.display = 'block';
            } else {
                loadingDiv.style.display = 'none';
            }
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        const alertDiv = this.createAlert('success', message);
        document.getElementById('alertContainer')?.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    /**
     * Show error message
     */
    showError(message) {
        const alertDiv = this.createAlert('danger', message);
        document.getElementById('alertContainer')?.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.remove();
        }, 8000);
    }

    /**
     * Show warning message
     */
    showWarning(message) {
        const alertDiv = this.createAlert('warning', message);
        document.getElementById('alertContainer')?.appendChild(alertDiv);
    }

    /**
     * Create alert element
     */
    createAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        return alert;
    }

    /**
     * Disable enrollment type
     */
    disableEnrollmentType(type) {
        const btn = document.querySelector(`[data-enrollment-type="${type}"]`);
        if (btn) {
            btn.disabled = true;
            btn.title = 'Método no disponible';
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const enrollmentManager = new BiometricEnrollmentManager();
    enrollmentManager.init();
    
    // Make globally available
    window.biometricEnrollmentManager = enrollmentManager;
});