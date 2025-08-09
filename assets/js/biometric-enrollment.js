/**
 * SynkTime Biometric Enrollment Page JavaScript
 * Handles employee search, enrollment modal, and biometric enrollment processes
 */

class BiometricEnrollmentPage {
    constructor() {
        this.currentPage = 1;
        this.totalPages = 1;
        this.employees = [];
        this.filteredEmployees = [];
        this.currentEmployee = null;
        this.currentEnrollmentMethod = null;
        this.enrollmentStep = 1;
        this.enrollmentProgress = 0;
        this.maxFingerprints = 3;
        this.maxFacialCaptures = 5;
        
        this.init();
    }

    /**
     * Initialize the enrollment page
     */
    async init() {
        await this.loadInitialData();
        this.bindEvents();
        this.updateDeviceStatus();
        console.log('BiometricEnrollmentPage initialized');
    }

    /**
     * Load initial data (employees, statistics, etc.)
     */
    async loadInitialData() {
        try {
            await Promise.all([
                this.loadEmployees(),
                this.loadStatistics(),
                this.loadSedes(),
                this.loadEstablecimientos()
            ]);
        } catch (error) {
            console.error('Error loading initial data:', error);
            this.showNotification('Error al cargar datos iniciales', 'error');
        }
    }

    /**
     * Load employees list
     */
    async loadEmployees(filters = {}) {
        try {
            const response = await fetch('api/biometric/list-employees.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    page: this.currentPage,
                    filters: filters
                })
            });

            const result = await response.json();
            if (result.success) {
                this.employees = result.employees;
                this.filteredEmployees = result.employees;
                this.totalPages = result.totalPages;
                this.renderEmployeeList();
                this.updatePagination();
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error loading employees:', error);
            this.showNotification('Error al cargar empleados', 'error');
            this.renderEmployeeListError();
        }
    }

    /**
     * Load enrollment statistics
     */
    async loadStatistics() {
        try {
            const response = await fetch('api/biometric/stats.php');
            const result = await response.json();
            
            if (result.success) {
                this.updateStatistics(result.stats);
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
        }
    }

    /**
     * Load sedes for filters
     */
    async loadSedes() {
        try {
            const response = await fetch('/home/runner/work/Synktime/Synktime/api/get-sedes.php');
            const result = await response.json();
            
            if (result.success) {
                this.populateSedeSelect(result.sedes);
            }
        } catch (error) {
            console.error('Error loading sedes:', error);
        }
    }

    /**
     * Load establecimientos for filters
     */
    async loadEstablecimientos() {
        try {
            const response = await fetch('/home/runner/work/Synktime/Synktime/api/get-establecimientos.php');
            const result = await response.json();
            
            if (result.success) {
                this.populateEstablecimientoSelect(result.establecimientos);
            }
        } catch (error) {
            console.error('Error loading establecimientos:', error);
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Search form events
        document.getElementById('search_text').addEventListener('input', 
            this.debounce(this.handleSearch.bind(this), 300));

        // Filter change events
        document.getElementById('sede_filter').addEventListener('change', this.handleFilterChange.bind(this));
        document.getElementById('establecimiento_filter').addEventListener('change', this.handleFilterChange.bind(this));

        // Employee selection in enrollment modal
        document.getElementById('enrollmentEmployeeSelect').addEventListener('change', this.handleEmployeeSelection.bind(this));

        // Enrollment method selection
        document.addEventListener('click', (e) => {
            if (e.target.closest('.enrollment-method')) {
                this.selectEnrollmentMethod(e.target.closest('.enrollment-method'));
            }
        });

        // Finger selection
        document.addEventListener('click', (e) => {
            if (e.target.closest('.finger-option')) {
                this.selectFinger(e.target.closest('.finger-option'));
            }
        });
    }

    /**
     * Render employee list
     */
    renderEmployeeList() {
        const listContainer = document.getElementById('employeeList');
        
        if (this.filteredEmployees.length === 0) {
            listContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-users" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                    <p>No se encontraron empleados</p>
                </div>
            `;
            return;
        }

        const employeeHtml = this.filteredEmployees.map(employee => `
            <div class="employee-item" data-employee-id="${employee.ID_EMPLEADO}">
                <div class="employee-info">
                    <div class="employee-name">${employee.NOMBRE} ${employee.APELLIDO}</div>
                    <div class="employee-details">
                        Código: ${employee.CODIGO} | ${employee.ESTABLECIMIENTO} - ${employee.SEDE}
                    </div>
                </div>
                <div class="employee-biometric-status">
                    <span class="biometric-badge ${employee.hasFingerprint ? 'enrolled' : 'not-enrolled'}">
                        Huella ${employee.hasFingerprint ? 'Inscrita' : 'No Inscrita'}
                    </span>
                    <span class="biometric-badge ${employee.hasFacial ? 'enrolled' : 'not-enrolled'}">
                        Facial ${employee.hasFacial ? 'Inscrito' : 'No Inscrito'}
                    </span>
                </div>
                <div class="employee-actions">
                    <button type="button" class="btn-primary btn-sm" onclick="openEnrollmentForEmployee(${employee.ID_EMPLEADO})">
                        <i class="fas fa-fingerprint"></i> Inscribir
                    </button>
                </div>
            </div>
        `).join('');

        listContainer.innerHTML = employeeHtml;
    }

    /**
     * Render employee list error state
     */
    renderEmployeeListError() {
        const listContainer = document.getElementById('employeeList');
        listContainer.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--danger); margin-bottom: 1rem;"></i>
                <p>Error al cargar la lista de empleados</p>
                <button type="button" class="btn-primary" onclick="enrollmentPage.loadEmployees()">
                    <i class="fas fa-refresh"></i> Reintentar
                </button>
            </div>
        `;
    }

    /**
     * Update statistics display
     */
    updateStatistics(stats) {
        document.getElementById('totalEmployees').textContent = stats.totalEmployees || 0;
        document.getElementById('fingerprintEnrolled').textContent = stats.fingerprintEnrolled || 0;
        document.getElementById('facialEnrolled').textContent = stats.facialEnrolled || 0;
        
        const completionRate = stats.totalEmployees > 0 
            ? Math.round(((stats.fingerprintEnrolled + stats.facialEnrolled) / (stats.totalEmployees * 2)) * 100)
            : 0;
        document.getElementById('completionRate').textContent = completionRate + '%';
    }

    /**
     * Populate sede select dropdown
     */
    populateSedeSelect(sedes) {
        const select = document.getElementById('sede_filter');
        const defaultOption = select.querySelector('option[value=""]');
        
        select.innerHTML = '';
        select.appendChild(defaultOption);
        
        sedes.forEach(sede => {
            const option = document.createElement('option');
            option.value = sede.ID_SEDE;
            option.textContent = sede.NOMBRE;
            select.appendChild(option);
        });
    }

    /**
     * Populate establecimiento select dropdown
     */
    populateEstablecimientoSelect(establecimientos) {
        const select = document.getElementById('establecimiento_filter');
        const defaultOption = select.querySelector('option[value=""]');
        
        select.innerHTML = '';
        select.appendChild(defaultOption);
        
        establecimientos.forEach(establecimiento => {
            const option = document.createElement('option');
            option.value = establecimiento.ID_ESTABLECIMIENTO;
            option.textContent = establecimiento.NOMBRE;
            select.appendChild(option);
        });
    }

    /**
     * Handle search input
     */
    handleSearch() {
        this.currentPage = 1;
        this.applyFilters();
    }

    /**
     * Handle filter changes
     */
    handleFilterChange() {
        this.currentPage = 1;
        this.applyFilters();
    }

    /**
     * Apply current filters
     */
    applyFilters() {
        const filters = {
            search: document.getElementById('search_text').value,
            sede: document.getElementById('sede_filter').value,
            establecimiento: document.getElementById('establecimiento_filter').value
        };

        this.loadEmployees(filters);
    }

    /**
     * Open enrollment modal
     */
    openEnrollmentModal(employeeId = null) {
        this.resetEnrollmentModal();
        this.populateEmployeeSelectForEnrollment();
        
        if (employeeId) {
            document.getElementById('enrollmentEmployeeSelect').value = employeeId;
            this.handleEmployeeSelection();
        }
        
        const modal = document.getElementById('biometricEnrollmentModal');
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }

    /**
     * Close enrollment modal
     */
    closeEnrollmentModal() {
        const modal = document.getElementById('biometricEnrollmentModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
        
        // Stop any active camera streams
        if (window.biometricSystem) {
            window.biometricSystem.stopCamera();
        }
        
        this.resetEnrollmentModal();
    }

    /**
     * Reset enrollment modal to initial state
     */
    resetEnrollmentModal() {
        this.enrollmentStep = 1;
        this.currentEmployee = null;
        this.currentEnrollmentMethod = null;
        this.enrollmentProgress = 0;
        
        // Show only first step
        document.getElementById('employeeSelectionStep').style.display = 'block';
        document.getElementById('methodSelectionStep').style.display = 'none';
        document.getElementById('enrollmentProcessStep').style.display = 'none';
        document.getElementById('enrollmentSuccessStep').style.display = 'none';
        
        // Reset form states
        document.getElementById('enrollmentEmployeeSelect').value = '';
        document.getElementById('selectedEmployeeInfo').style.display = 'none';
        document.querySelector('#employeeSelectionStep .btn-primary').disabled = true;
    }

    /**
     * Populate employee select for enrollment
     */
    populateEmployeeSelectForEnrollment() {
        const select = document.getElementById('enrollmentEmployeeSelect');
        const defaultOption = select.querySelector('option[value=""]');
        
        select.innerHTML = '';
        select.appendChild(defaultOption);
        
        this.employees.forEach(employee => {
            const option = document.createElement('option');
            option.value = employee.ID_EMPLEADO;
            option.textContent = `${employee.NOMBRE} ${employee.APELLIDO} (${employee.CODIGO})`;
            select.appendChild(option);
        });
    }

    /**
     * Handle employee selection in enrollment modal
     */
    handleEmployeeSelection() {
        const employeeId = document.getElementById('enrollmentEmployeeSelect').value;
        const continueBtn = document.querySelector('#employeeSelectionStep .btn-primary');
        
        if (employeeId) {
            this.currentEmployee = this.employees.find(emp => emp.ID_EMPLEADO == employeeId);
            this.showSelectedEmployeeInfo();
            continueBtn.disabled = false;
        } else {
            this.currentEmployee = null;
            document.getElementById('selectedEmployeeInfo').style.display = 'none';
            continueBtn.disabled = true;
        }
    }

    /**
     * Show selected employee information
     */
    showSelectedEmployeeInfo() {
        if (!this.currentEmployee) return;
        
        const infoDiv = document.getElementById('selectedEmployeeInfo');
        const employee = this.currentEmployee;
        
        infoDiv.querySelector('.employee-name').textContent = `${employee.NOMBRE} ${employee.APELLIDO}`;
        infoDiv.querySelector('.employee-code').textContent = `Código: ${employee.CODIGO}`;
        infoDiv.querySelector('.employee-establishment').textContent = `Establecimiento: ${employee.ESTABLECIMIENTO}`;
        
        // Update biometric status
        infoDiv.querySelector('.fingerprint-status-text').textContent = 
            employee.hasFingerprint ? 'Inscrito' : 'No inscrito';
        infoDiv.querySelector('.facial-status-text').textContent = 
            employee.hasFacial ? 'Inscrito' : 'No inscrito';
        
        infoDiv.style.display = 'block';
    }

    /**
     * Proceed to method selection step
     */
    proceedToMethodSelection() {
        if (!this.currentEmployee) return;
        
        this.enrollmentStep = 2;
        document.getElementById('employeeSelectionStep').style.display = 'none';
        document.getElementById('methodSelectionStep').style.display = 'block';
        
        // Update method availability based on current enrollment status
        this.updateMethodAvailability();
    }

    /**
     * Update method availability based on current enrollment
     */
    updateMethodAvailability() {
        const fingerprintMethod = document.querySelector('.enrollment-method[data-method="fingerprint"]');
        const facialMethod = document.querySelector('.enrollment-method[data-method="facial"]');
        
        // Update status badges
        if (this.currentEmployee.hasFingerprint) {
            fingerprintMethod.querySelector('.status-badge').textContent = 'Inscrito';
            fingerprintMethod.querySelector('.status-badge').classList.remove('not-enrolled');
            fingerprintMethod.querySelector('.status-badge').classList.add('enrolled');
        }
        
        if (this.currentEmployee.hasFacial) {
            facialMethod.querySelector('.status-badge').textContent = 'Inscrito';
            facialMethod.querySelector('.status-badge').classList.remove('not-enrolled');
            facialMethod.querySelector('.status-badge').classList.add('enrolled');
        }
    }

    /**
     * Select enrollment method
     */
    selectEnrollmentMethod(element) {
        // Remove previous selections
        document.querySelectorAll('.enrollment-method').forEach(method => {
            method.classList.remove('selected');
        });
        
        // Select current method
        element.classList.add('selected');
        this.currentEnrollmentMethod = element.dataset.method;
        
        // Enable proceed button
        document.querySelector('#methodSelectionStep .btn-primary').disabled = false;
    }

    /**
     * Proceed to enrollment process
     */
    proceedToEnrollment() {
        if (!this.currentEnrollmentMethod) return;
        
        this.enrollmentStep = 3;
        document.getElementById('methodSelectionStep').style.display = 'none';
        document.getElementById('enrollmentProcessStep').style.display = 'block';
        
        // Setup enrollment process based on method
        this.setupEnrollmentProcess();
    }

    /**
     * Setup enrollment process UI
     */
    setupEnrollmentProcess() {
        if (this.currentEnrollmentMethod === 'fingerprint') {
            document.getElementById('fingerprintEnrollmentArea').style.display = 'block';
            document.getElementById('facialEnrollmentArea').style.display = 'none';
            document.getElementById('enrollmentProcessTitle').textContent = 'Paso 3: Inscripción de Huella Dactilar';
        } else if (this.currentEnrollmentMethod === 'facial') {
            document.getElementById('fingerprintEnrollmentArea').style.display = 'none';
            document.getElementById('facialEnrollmentArea').style.display = 'block';
            document.getElementById('enrollmentProcessTitle').textContent = 'Paso 3: Inscripción Facial';
            
            // Initialize camera for facial enrollment
            this.initializeCameraForEnrollment();
        }
        
        this.resetEnrollmentProgress();
    }

    /**
     * Initialize camera for facial enrollment
     */
    async initializeCameraForEnrollment() {
        if (window.biometricSystem) {
            await window.biometricSystem.initializeCamera();
        }
    }

    /**
     * Start enrollment process
     */
    async startEnrollmentProcess() {
        if (this.currentEnrollmentMethod === 'fingerprint') {
            await this.startFingerprintEnrollment();
        } else if (this.currentEnrollmentMethod === 'facial') {
            await this.startFacialEnrollment();
        }
    }

    /**
     * Start fingerprint enrollment
     */
    async startFingerprintEnrollment() {
        const selectedFinger = document.querySelector('.finger-option.selected');
        if (!selectedFinger) {
            this.showNotification('Seleccione un dedo para inscribir', 'warning');
            return;
        }
        
        try {
            this.showEnrollmentStatus('Iniciando inscripción de huella...', 'info');
            
            // Simulate fingerprint enrollment process
            for (let i = 1; i <= this.maxFingerprints; i++) {
                this.updateEnrollmentProgress(i, this.maxFingerprints, 'capturas');
                await this.captureFingerprint(selectedFinger.dataset.finger, i);
                await this.delay(2000);
            }
            
            await this.completeEnrollment();
        } catch (error) {
            console.error('Error in fingerprint enrollment:', error);
            this.showEnrollmentStatus('Error en la inscripción de huella', 'error');
        }
    }

    /**
     * Start facial enrollment
     */
    async startFacialEnrollment() {
        try {
            this.showEnrollmentStatus('Iniciando inscripción facial...', 'info');
            
            // Simulate facial enrollment process
            for (let i = 1; i <= this.maxFacialCaptures; i++) {
                this.updateEnrollmentProgress(i, this.maxFacialCaptures, 'capturas');
                await this.captureFacialData(i);
                await this.delay(1500);
            }
            
            await this.completeEnrollment();
        } catch (error) {
            console.error('Error in facial enrollment:', error);
            this.showEnrollmentStatus('Error en la inscripción facial', 'error');
        }
    }

    /**
     * Capture fingerprint data
     */
    async captureFingerprint(fingerType, captureNumber) {
        this.showEnrollmentStatus(`Captura ${captureNumber}/${this.maxFingerprints} - Presione el dedo firmemente`, 'info');
        
        // Simulate fingerprint capture
        const scanner = document.querySelector('.fingerprint-scanner');
        scanner.classList.add('scanning');
        
        await this.delay(2000);
        
        scanner.classList.remove('scanning');
        this.showEnrollmentStatus(`Captura ${captureNumber} completada`, 'success');
    }

    /**
     * Capture facial data
     */
    async captureFacialData(captureNumber) {
        this.showEnrollmentStatus(`Captura ${captureNumber}/${this.maxFacialCaptures} - Mantenga el rostro en posición`, 'info');
        
        // Simulate facial capture
        if (window.biometricSystem) {
            const imageData = window.biometricSystem.captureFromVideo();
            // Process image data here
        }
        
        this.showEnrollmentStatus(`Captura ${captureNumber} completada`, 'success');
    }

    /**
     * Complete enrollment process
     */
    async completeEnrollment() {
        try {
            const enrollmentData = {
                employee_id: this.currentEmployee.ID_EMPLEADO,
                method: this.currentEnrollmentMethod,
                finger_type: this.currentEnrollmentMethod === 'fingerprint' 
                    ? document.querySelector('.finger-option.selected')?.dataset.finger 
                    : null
            };

            const response = await fetch(`api/biometric/enroll-${this.currentEnrollmentMethod}.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(enrollmentData)
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccessStep();
                await this.loadStatistics(); // Refresh statistics
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error completing enrollment:', error);
            this.showEnrollmentStatus('Error al completar la inscripción', 'error');
        }
    }

    /**
     * Show success step
     */
    showSuccessStep() {
        this.enrollmentStep = 4;
        document.getElementById('enrollmentProcessStep').style.display = 'none';
        document.getElementById('enrollmentSuccessStep').style.display = 'block';
        
        // Update summary
        document.getElementById('summaryEmployeeName').textContent = 
            `${this.currentEmployee.NOMBRE} ${this.currentEmployee.APELLIDO}`;
        document.getElementById('summaryMethod').textContent = 
            this.currentEnrollmentMethod === 'fingerprint' ? 'Huella Dactilar' : 'Reconocimiento Facial';
        document.getElementById('summaryDate').textContent = new Date().toLocaleDateString();
    }

    /**
     * Select finger for enrollment
     */
    selectFinger(element) {
        // Remove previous selections
        document.querySelectorAll('.finger-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Select current finger
        element.classList.add('selected');
        
        // Enable start button if finger is selected
        document.getElementById('startEnrollmentBtn').disabled = false;
    }

    /**
     * Back to employee selection
     */
    backToEmployeeSelection() {
        this.enrollmentStep = 1;
        document.getElementById('methodSelectionStep').style.display = 'none';
        document.getElementById('employeeSelectionStep').style.display = 'block';
    }

    /**
     * Back to method selection
     */
    backToMethodSelection() {
        this.enrollmentStep = 2;
        document.getElementById('enrollmentProcessStep').style.display = 'none';
        document.getElementById('methodSelectionStep').style.display = 'block';
        
        // Stop camera if active
        if (window.biometricSystem) {
            window.biometricSystem.stopCamera();
        }
    }

    /**
     * Enroll another employee
     */
    enrollAnotherEmployee() {
        this.resetEnrollmentModal();
        this.populateEmployeeSelectForEnrollment();
    }

    /**
     * Update enrollment progress
     */
    updateEnrollmentProgress(current, total, unit) {
        const percentage = (current / total) * 100;
        const progressFill = document.querySelector('#enrollmentProcessStep .progress-fill');
        const progressText = document.querySelector('#enrollmentProcessStep .progress-text');
        
        if (progressFill) {
            progressFill.style.width = percentage + '%';
        }
        
        if (progressText) {
            progressText.textContent = `Progreso: ${current}/${total} ${unit}`;
        }
        
        if (current === total) {
            document.getElementById('startEnrollmentBtn').style.display = 'none';
            document.getElementById('completeEnrollmentBtn').style.display = 'inline-flex';
        }
    }

    /**
     * Reset enrollment progress
     */
    resetEnrollmentProgress() {
        this.enrollmentProgress = 0;
        this.updateEnrollmentProgress(0, 
            this.currentEnrollmentMethod === 'fingerprint' ? this.maxFingerprints : this.maxFacialCaptures,
            'capturas'
        );
        
        document.getElementById('startEnrollmentBtn').style.display = 'inline-flex';
        document.getElementById('completeEnrollmentBtn').style.display = 'none';
        document.getElementById('startEnrollmentBtn').disabled = this.currentEnrollmentMethod === 'fingerprint';
    }

    /**
     * Show enrollment status message
     */
    showEnrollmentStatus(message, type) {
        const statusEl = document.querySelector('#enrollmentProcessStep .biometric-status');
        if (statusEl) {
            statusEl.className = `biometric-status ${type}`;
            statusEl.innerHTML = `<i class="fas fa-${this.getStatusIcon(type)}"></i> ${message}`;
            statusEl.style.display = 'block';
        }
    }

    /**
     * Update device status
     */
    updateDeviceStatus() {
        if (window.biometricSystem) {
            window.biometricSystem.updateDeviceStatus();
        }
    }

    /**
     * Update pagination
     */
    updatePagination() {
        const container = document.getElementById('paginationContainer');
        if (this.totalPages <= 1) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'block';
        // Implementation of pagination controls would go here
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Simple notification implementation
        console.log(`${type.toUpperCase()}: ${message}`);
        
        // You can implement a toast notification system here
        alert(message);
    }

    /**
     * Utility functions
     */
    getStatusIcon(type) {
        const icons = {
            info: 'info-circle',
            success: 'check-circle',
            warning: 'exclamation-triangle',
            error: 'times-circle'
        };
        return icons[type] || 'info-circle';
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Initialize enrollment page when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.enrollmentPage = new BiometricEnrollmentPage();
});

// Global functions for external access
function searchEmployees() {
    if (window.enrollmentPage) {
        window.enrollmentPage.handleSearch();
    }
}

function clearSearch() {
    document.getElementById('search_text').value = '';
    document.getElementById('sede_filter').value = '';
    document.getElementById('establecimiento_filter').value = '';
    
    if (window.enrollmentPage) {
        window.enrollmentPage.applyFilters();
    }
}

function openEnrollmentModal() {
    if (window.enrollmentPage) {
        window.enrollmentPage.openEnrollmentModal();
    }
}

function openEnrollmentForEmployee(employeeId) {
    if (window.enrollmentPage) {
        window.enrollmentPage.openEnrollmentModal(employeeId);
    }
}

function closeEnrollmentModal() {
    if (window.enrollmentPage) {
        window.enrollmentPage.closeEnrollmentModal();
    }
}

function proceedToMethodSelection() {
    if (window.enrollmentPage) {
        window.enrollmentPage.proceedToMethodSelection();
    }
}

function proceedToEnrollment() {
    if (window.enrollmentPage) {
        window.enrollmentPage.proceedToEnrollment();
    }
}

function backToEmployeeSelection() {
    if (window.enrollmentPage) {
        window.enrollmentPage.backToEmployeeSelection();
    }
}

function backToMethodSelection() {
    if (window.enrollmentPage) {
        window.enrollmentPage.backToMethodSelection();
    }
}

function startEnrollmentProcess() {
    if (window.enrollmentPage) {
        window.enrollmentPage.startEnrollmentProcess();
    }
}

function completeEnrollment() {
    if (window.enrollmentPage) {
        window.enrollmentPage.completeEnrollment();
    }
}

function enrollAnotherEmployee() {
    if (window.enrollmentPage) {
        window.enrollmentPage.enrollAnotherEmployee();
    }
}