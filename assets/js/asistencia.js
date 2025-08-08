/**
 * Attendance Management JavaScript
 * Handles biometric attendance registration with modal interactions
 */

class AttendanceManager {
    constructor() {
        this.cameraManager = new CameraManager();
        this.cameraUI = new CameraUI(this.cameraManager);
        this.currentEmployee = null;
        this.currentMethod = null;
        this.isProcessing = false;
    }

    /**
     * Initialize attendance system
     */
    init() {
        this.bindEventListeners();
        this.initializeEmployeeSelector();
        this.checkBiometricSupport();
    }

    /**
     * Bind event listeners
     */
    bindEventListeners() {
        // Employee selection
        document.addEventListener('change', '#employeeSelect', (e) => {
            this.selectEmployee(e.target.value);
        });

        // Registration method buttons
        document.addEventListener('click', '[data-method]', (e) => {
            e.preventDefault();
            const method = e.target.dataset.method;
            this.startRegistration(method);
        });

        // Registration type buttons
        document.addEventListener('click', '[data-tipo]', (e) => {
            e.preventDefault();
            const tipo = e.target.dataset.tipo;
            this.setRegistrationType(tipo);
        });
    }

    /**
     * Initialize employee selector with autocomplete
     */
    initializeEmployeeSelector() {
        const selector = document.getElementById('employeeSelect');
        if (!selector) return;

        // Initialize autocomplete if available
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $(selector).select2({
                placeholder: 'Buscar empleado...',
                allowClear: true,
                ajax: {
                    url: 'api/employee/search.php',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        return {
                            results: data.employees.map(emp => ({
                                id: emp.ID_EMPLEADO,
                                text: `${emp.NOMBRE} ${emp.APELLIDO} - ${emp.DNI}`
                            }))
                        };
                    }
                }
            });
        }
    }

    /**
     * Check biometric support and show warnings
     */
    checkBiometricSupport() {
        const warnings = [];

        if (!CameraManager.isSupported()) {
            warnings.push('Cámara no disponible - Verificación facial deshabilitada');
            this.disableMethod('facial');
        }

        if (!navigator.credentials) {
            warnings.push('API de credenciales no disponible - Verificación de huella limitada');
        }

        if (warnings.length > 0) {
            this.showWarnings(warnings);
        }
    }

    /**
     * Select employee for attendance
     */
    async selectEmployee(employeeId) {
        if (!employeeId) {
            this.currentEmployee = null;
            this.updateUI();
            return;
        }

        try {
            this.showLoading(true);
            
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
                this.updateUI();
                this.checkEmployeeBiometrics();
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
     * Check employee biometric enrollment status
     */
    async checkEmployeeBiometrics() {
        if (!this.currentEmployee) return;

        try {
            const response = await fetch('api/biometric/get-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id_empleado: this.currentEmployee.ID_EMPLEADO })
            });

            const data = await response.json();

            if (data.success) {
                this.updateBiometricStatus(data.status);
            }

        } catch (error) {
            console.warn('Error checking biometric status:', error);
        }
    }

    /**
     * Start attendance registration process
     */
    async startRegistration(method) {
        if (!this.currentEmployee) {
            this.showError('Seleccione un empleado primero');
            return;
        }

        if (this.isProcessing) {
            return;
        }

        this.currentMethod = method;
        this.isProcessing = true;

        try {
            switch (method) {
                case 'facial':
                    await this.registerWithFacial();
                    break;
                case 'fingerprint':
                    await this.registerWithFingerprint();
                    break;
                case 'traditional':
                    await this.registerTraditional();
                    break;
                default:
                    throw new Error('Método no soportado');
            }

        } catch (error) {
            this.showError(error.message);
        } finally {
            this.isProcessing = false;
            this.currentMethod = null;
        }
    }

    /**
     * Register attendance with facial recognition
     */
    async registerWithFacial() {
        try {
            // Capture image
            const imageData = await this.cameraUI.showCaptureModal('Verificación Facial');
            
            this.showLoading(true, 'Verificando rostro...');

            // Send to server
            const response = await fetch('api/asistencia/registrar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_empleado: this.currentEmployee.ID_EMPLEADO,
                    method: 'facial',
                    payload: {
                        image_data: imageData
                    }
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Asistencia registrada exitosamente', data.data);
                this.refreshEmployeeData();
            } else {
                throw new Error(data.message);
            }

        } catch (error) {
            if (error.message !== 'Captura cancelada') {
                throw error;
            }
        }
    }

    /**
     * Register attendance with fingerprint
     */
    async registerWithFingerprint() {
        try {
            // Show fingerprint capture options
            const modal = this.createFingerprintModal();
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();

        } catch (error) {
            throw new Error('Error en verificación de huella: ' + error.message);
        }
    }

    /**
     * Register attendance traditionally (photo only)
     */
    async registerTraditional() {
        try {
            // Capture image
            const imageData = await this.cameraUI.showCaptureModal('Registro Tradicional');
            
            this.showLoading(true, 'Registrando asistencia...');

            // Send to server
            const response = await fetch('api/asistencia/registrar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_empleado: this.currentEmployee.ID_EMPLEADO,
                    method: 'traditional',
                    payload: {
                        image_data: imageData
                    }
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess('Asistencia registrada exitosamente', data.data);
                this.refreshEmployeeData();
            } else {
                throw new Error(data.message);
            }

        } catch (error) {
            if (error.message !== 'Captura cancelada') {
                throw error;
            }
        }
    }

    /**
     * Create fingerprint capture modal
     */
    createFingerprintModal() {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Verificación de Huella</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p>Seleccione el método de captura de huella:</p>
                        <div class="d-grid gap-2">
                            <button id="fingerprintCamera" class="btn btn-primary">
                                <i class="fas fa-camera"></i> Usar Cámara
                            </button>
                            <button id="fingerprintFile" class="btn btn-secondary">
                                <i class="fas fa-upload"></i> Subir Archivo
                            </button>
                        </div>
                        <div id="fingerprintError" class="alert alert-danger mt-3" style="display: none;"></div>
                    </div>
                </div>
            </div>
        `;

        // Bind events
        modal.querySelector('#fingerprintCamera').addEventListener('click', () => {
            this.captureFingerprintWithCamera(modal);
        });

        modal.querySelector('#fingerprintFile').addEventListener('click', () => {
            this.captureFingerprintWithFile(modal);
        });

        document.body.appendChild(modal);
        return modal;
    }

    /**
     * Capture fingerprint with camera
     */
    async captureFingerprintWithCamera(modal) {
        try {
            const imageData = await this.cameraUI.showCaptureModal('Captura de Huella');
            await this.processFingerprintData(imageData);
            bootstrap.Modal.getInstance(modal).hide();
            
        } catch (error) {
            const errorDiv = modal.querySelector('#fingerprintError');
            errorDiv.textContent = error.message;
            errorDiv.style.display = 'block';
        }
    }

    /**
     * Capture fingerprint with file upload
     */
    async captureFingerprintWithFile(modal) {
        try {
            const imageData = await this.cameraUI.showFileUpload('image/*');
            await this.processFingerprintData(imageData);
            bootstrap.Modal.getInstance(modal).hide();
            
        } catch (error) {
            const errorDiv = modal.querySelector('#fingerprintError');
            errorDiv.textContent = error.message;
            errorDiv.style.display = 'block';
        }
    }

    /**
     * Process fingerprint data and register attendance
     */
    async processFingerprintData(imageData) {
        this.showLoading(true, 'Verificando huella...');

        const response = await fetch('api/asistencia/registrar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id_empleado: this.currentEmployee.ID_EMPLEADO,
                method: 'fingerprint',
                payload: {
                    image_data: imageData
                }
            })
        });

        const data = await response.json();

        if (data.success) {
            this.showSuccess('Asistencia registrada exitosamente', data.data);
            this.refreshEmployeeData();
        } else {
            throw new Error(data.message);
        }
    }

    /**
     * Update UI based on current state
     */
    updateUI() {
        const employeeInfo = document.getElementById('employeeInfo');
        const registrationButtons = document.getElementById('registrationButtons');

        if (this.currentEmployee) {
            // Show employee info
            if (employeeInfo) {
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
            }

            // Show registration buttons
            if (registrationButtons) {
                registrationButtons.style.display = 'block';
            }

        } else {
            // Hide employee info and buttons
            if (employeeInfo) employeeInfo.innerHTML = '';
            if (registrationButtons) registrationButtons.style.display = 'none';
        }
    }

    /**
     * Update biometric status indicators
     */
    updateBiometricStatus(status) {
        const facialBtn = document.querySelector('[data-method="facial"]');
        const fingerprintBtn = document.querySelector('[data-method="fingerprint"]');

        if (facialBtn) {
            if (status.face_enrolled) {
                facialBtn.classList.remove('btn-outline-primary');
                facialBtn.classList.add('btn-primary');
                facialBtn.title = 'Rostro registrado';
            } else {
                facialBtn.classList.add('btn-outline-primary');
                facialBtn.classList.remove('btn-primary');
                facialBtn.title = 'Rostro no registrado';
            }
        }

        if (fingerprintBtn) {
            if (status.fingerprint_enrolled) {
                fingerprintBtn.classList.remove('btn-outline-success');
                fingerprintBtn.classList.add('btn-success');
                fingerprintBtn.title = 'Huella registrada';
            } else {
                fingerprintBtn.classList.add('btn-outline-success');
                fingerprintBtn.classList.remove('btn-success');
                fingerprintBtn.title = 'Huella no registrada';
            }
        }
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
    showSuccess(message, data = null) {
        const alertDiv = this.createAlert('success', message);
        document.getElementById('alertContainer')?.appendChild(alertDiv);
        
        // Auto-hide after 5 seconds
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
        
        // Auto-hide after 8 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 8000);
    }

    /**
     * Show warnings
     */
    showWarnings(warnings) {
        const alertDiv = this.createAlert('warning', warnings.join('<br>'));
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
     * Disable biometric method
     */
    disableMethod(method) {
        const btn = document.querySelector(`[data-method="${method}"]`);
        if (btn) {
            btn.disabled = true;
            btn.title = 'Método no disponible';
        }
    }

    /**
     * Refresh employee data after registration
     */
    async refreshEmployeeData() {
        if (this.currentEmployee) {
            await this.selectEmployee(this.currentEmployee.ID_EMPLEADO);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    const attendanceManager = new AttendanceManager();
    attendanceManager.init();
    
    // Make globally available
    window.attendanceManager = attendanceManager;
});