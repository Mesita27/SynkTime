/**
 * Biometric Enrollment JavaScript
 * Handles enrollment page functionality and wizard flow
 */

let enrollmentStep = 1;
let selectedEmployeeForEnrollment = null;
let enrollmentType = null;
let enrollmentCameraHelper = null;
let capturedFacialImages = [];
let selectedFingerType = null;

/**
 * Initialize enrollment page
 */
function initializeEnrollmentPage() {
    enrollmentCameraHelper = new CameraHelper();
    setupEventListeners();
    resetEnrollmentModal();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Open enrollment modal
    document.getElementById('btnOpenEnrollment').addEventListener('click', openEnrollmentModal);
    
    // Employee search in modal
    const modalSearch = document.getElementById('modalEmployeeSearch');
    if (modalSearch) {
        modalSearch.addEventListener('input', handleModalEmployeeSearch);
    }
    
    // Finger selection buttons
    document.querySelectorAll('.finger-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectFinger(this.dataset.finger);
        });
    });
}

/**
 * Handle employee search in modal
 */
let modalSearchTimeout = null;
function handleModalEmployeeSearch() {
    const searchInput = document.getElementById('modalEmployeeSearch');
    const query = searchInput.value.trim();
    
    clearTimeout(modalSearchTimeout);
    
    if (query.length < 2) {
        document.getElementById('modalEmployeeResults').innerHTML = '';
        return;
    }
    
    modalSearchTimeout = setTimeout(async () => {
        try {
            const response = await fetch('../../api/employee/search.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query, limit: 10 })
            });
            
            const data = await response.json();
            displayModalEmployeeResults(data.employees || []);
        } catch (error) {
            console.error('Error searching employees:', error);
        }
    }, 300);
}

/**
 * Display employee results in modal
 */
function displayModalEmployeeResults(employees) {
    const resultsContainer = document.getElementById('modalEmployeeResults');
    
    if (employees.length === 0) {
        resultsContainer.innerHTML = '<div class="no-results">No se encontraron empleados</div>';
        return;
    }
    
    const html = employees.map(emp => `
        <div class="employee-result" onclick="selectEmployeeForEnrollment(${emp.ID_EMPLEADO}, '${emp.NOMBRE}', '${emp.APELLIDO}', '${emp.CODIGO || ''}')">
            <div class="employee-info">
                <span class="employee-name">${emp.NOMBRE} ${emp.APELLIDO}</span>
                <span class="employee-details">${emp.CODIGO ? 'Código: ' + emp.CODIGO : ''}</span>
            </div>
            <div class="employee-meta">
                <span class="establishment">${emp.ESTABLECIMIENTO_NOMBRE || ''}</span>
            </div>
        </div>
    `).join('');
    
    resultsContainer.innerHTML = html;
}

/**
 * Select employee for enrollment
 */
function selectEmployeeForEnrollment(id, nombre, apellido, codigo) {
    selectedEmployeeForEnrollment = { id, nombre, apellido, codigo };
    
    // Update modal search input
    document.getElementById('modalEmployeeSearch').value = `${nombre} ${apellido}`;
    document.getElementById('modalEmployeeResults').innerHTML = '';
    
    // Show selected employee card
    const card = document.getElementById('selectedEmployeeCard');
    card.innerHTML = `
        <div class="employee-card">
            <div class="employee-details">
                <h4>${nombre} ${apellido}</h4>
                <p>Código: ${codigo || 'N/A'}</p>
            </div>
            <button type="button" class="btn-clear" onclick="clearEmployeeForEnrollment()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    card.style.display = 'block';
    
    // Enable next button
    document.getElementById('nextStepBtn').disabled = false;
}

/**
 * Clear employee selection for enrollment
 */
function clearEmployeeForEnrollment() {
    selectedEmployeeForEnrollment = null;
    document.getElementById('modalEmployeeSearch').value = '';
    document.getElementById('selectedEmployeeCard').style.display = 'none';
    document.getElementById('nextStepBtn').disabled = true;
}

/**
 * Open enrollment modal
 */
function openEnrollmentModal() {
    const modal = document.getElementById('enrollmentModal');
    modal.style.display = 'block';
    resetEnrollmentModal();
}

/**
 * Close enrollment modal
 */
function closeEnrollmentModal() {
    const modal = document.getElementById('enrollmentModal');
    modal.style.display = 'none';
    enrollmentCameraHelper.closeCamera();
    resetEnrollmentModal();
}

/**
 * Reset enrollment modal to initial state
 */
function resetEnrollmentModal() {
    enrollmentStep = 1;
    selectedEmployeeForEnrollment = null;
    enrollmentType = null;
    capturedFacialImages = [];
    selectedFingerType = null;
    
    // Reset UI
    showEnrollmentStep(1);
    clearEmployeeForEnrollment();
    
    // Reset buttons
    document.getElementById('nextStepBtn').disabled = true;
    document.getElementById('prevStepBtn').style.display = 'none';
    document.getElementById('enrollBtn').style.display = 'none';
    document.getElementById('captureFacialEnrollBtn').style.display = 'none';
}

/**
 * Show specific enrollment step
 */
function showEnrollmentStep(step) {
    // Hide all steps
    document.querySelectorAll('.enrollment-step').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show current step
    const stepElement = document.querySelector(`#step${getStepName(step)}`);
    if (stepElement) {
        stepElement.classList.add('active');
    }
    
    // Update navigation buttons
    updateNavigationButtons(step);
}

/**
 * Get step name by number
 */
function getStepName(step) {
    const stepNames = {
        1: 'EmployeeSelection',
        2: 'TypeSelection',
        3: enrollmentType === 'fingerprint' ? 'FingerprintEnrollment' : 'FacialEnrollment'
    };
    return stepNames[step] || 'EmployeeSelection';
}

/**
 * Update navigation buttons based on step
 */
function updateNavigationButtons(step) {
    const prevBtn = document.getElementById('prevStepBtn');
    const nextBtn = document.getElementById('nextStepBtn');
    const enrollBtn = document.getElementById('enrollBtn');
    const captureFacialBtn = document.getElementById('captureFacialEnrollBtn');
    
    // Hide all action buttons initially
    enrollBtn.style.display = 'none';
    captureFacialBtn.style.display = 'none';
    
    if (step === 1) {
        prevBtn.style.display = 'none';
        nextBtn.style.display = 'inline-block';
        nextBtn.disabled = !selectedEmployeeForEnrollment;
    } else if (step === 2) {
        prevBtn.style.display = 'inline-block';
        nextBtn.style.display = 'inline-block';
        nextBtn.disabled = !enrollmentType;
    } else if (step === 3) {
        prevBtn.style.display = 'inline-block';
        nextBtn.style.display = 'none';
        
        if (enrollmentType === 'fingerprint') {
            enrollBtn.style.display = 'inline-block';
            enrollBtn.disabled = !selectedFingerType;
        } else if (enrollmentType === 'facial') {
            captureFacialBtn.style.display = 'inline-block';
            enrollBtn.style.display = 'inline-block';
            enrollBtn.disabled = capturedFacialImages.length === 0;
        }
    }
}

/**
 * Next step
 */
function nextStep() {
    if (enrollmentStep < 3) {
        enrollmentStep++;
        showEnrollmentStep(enrollmentStep);
        
        // Initialize step-specific functionality
        if (enrollmentStep === 3 && enrollmentType === 'facial') {
            initializeFacialEnrollment();
        }
    }
}

/**
 * Previous step
 */
function previousStep() {
    if (enrollmentStep > 1) {
        enrollmentStep--;
        showEnrollmentStep(enrollmentStep);
        
        // Clean up step-specific functionality
        if (enrollmentStep === 2) {
            enrollmentCameraHelper.closeCamera();
        }
    }
}

/**
 * Select enrollment type
 */
function selectEnrollmentType(type) {
    enrollmentType = type;
    
    // Update UI to show selection
    document.querySelectorAll('.enrollment-type-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    event.target.closest('.enrollment-type-btn').classList.add('selected');
    
    // Enable next button
    document.getElementById('nextStepBtn').disabled = false;
}

/**
 * Select finger for fingerprint enrollment
 */
function selectFinger(fingerType) {
    selectedFingerType = fingerType;
    
    // Update UI
    document.querySelectorAll('.finger-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    document.querySelector(`[data-finger="${fingerType}"]`).classList.add('selected');
    
    // Show capture area
    document.getElementById('fingerprintCapture').style.display = 'block';
    
    // Enable enroll button
    document.getElementById('enrollBtn').disabled = false;
}

/**
 * Initialize facial enrollment
 */
async function initializeFacialEnrollment() {
    try {
        const video = document.getElementById('enrollmentVideo');
        await enrollmentCameraHelper.openCamera(video);
        
        // Reset capture state
        capturedFacialImages = [];
        updateFacialCaptureProgress();
    } catch (error) {
        console.error('Error initializing facial enrollment:', error);
        showToast('Error al acceder a la cámara: ' + error.message, 'error');
    }
}

/**
 * Capture facial image for enrollment
 */
function captureFacialForEnrollment() {
    if (capturedFacialImages.length >= 3) {
        showToast('Ya se han capturado las 3 imágenes requeridas', 'warning');
        return;
    }
    
    try {
        const canvas = document.getElementById('enrollmentCanvas');
        const imageData = enrollmentCameraHelper.captureImage(canvas);
        
        capturedFacialImages.push(imageData);
        updateFacialCaptureProgress();
        
        if (capturedFacialImages.length >= 3) {
            showToast('Imágenes capturadas completadas', 'success');
        }
        
        // Enable enroll button if we have at least 1 image
        document.getElementById('enrollBtn').disabled = capturedFacialImages.length === 0;
        
    } catch (error) {
        console.error('Error capturing facial image:', error);
        showToast('Error al capturar imagen', 'error');
    }
}

/**
 * Update facial capture progress
 */
function updateFacialCaptureProgress() {
    const count = capturedFacialImages.length;
    document.getElementById('capturedCount').textContent = count;
    
    const progress = (count / 3) * 100;
    document.getElementById('captureProgress').style.width = progress + '%';
    
    if (count >= 3) {
        document.getElementById('captureFacialEnrollBtn').disabled = true;
        document.getElementById('captureFacialEnrollBtn').textContent = 'Capturas Completas';
    }
}

/**
 * Simulate fingerprint capture (for demo)
 */
function simulateFingerprintCapture() {
    if (!selectedFingerType) return;
    
    showToast('Huella capturada (simulada)', 'success');
    document.getElementById('enrollBtn').disabled = false;
}

/**
 * Perform enrollment
 */
async function performEnrollment() {
    if (!selectedEmployeeForEnrollment || !enrollmentType) {
        showToast('Datos incompletos para la inscripción', 'error');
        return;
    }
    
    try {
        let endpoint, payload;
        
        if (enrollmentType === 'fingerprint') {
            if (!selectedFingerType) {
                showToast('Seleccione un dedo para inscribir', 'error');
                return;
            }
            
            endpoint = '../../api/biometrics/fingerprint/enroll';
            payload = {
                id_empleado: selectedEmployeeForEnrollment.id,
                finger_type: selectedFingerType,
                image: 'simulated_fingerprint_data' // In real implementation, this would be actual fingerprint data
            };
        } else if (enrollmentType === 'facial') {
            if (capturedFacialImages.length === 0) {
                showToast('Capture al menos una imagen facial', 'error');
                return;
            }
            
            endpoint = '../../api/biometrics/face/enroll';
            payload = {
                id_empleado: selectedEmployeeForEnrollment.id,
                images: capturedFacialImages
            };
        }
        
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(`Inscripción ${enrollmentType === 'fingerprint' ? 'de huella' : 'facial'} exitosa`, 'success');
            closeEnrollmentModal();
            loadEnrollmentStats();
            loadEmployeeList();
        } else {
            showToast('Error en la inscripción: ' + data.message, 'error');
        }
        
    } catch (error) {
        console.error('Error performing enrollment:', error);
        showToast('Error en la inscripción', 'error');
    }
}

/**
 * Load enrollment statistics
 */
async function loadEnrollmentStats() {
    try {
        const response = await fetch('../../api/biometric/stats.php');
        const data = await response.json();
        
        if (data.success && data.stats) {
            document.getElementById('fingerprintEnrolledCount').textContent = data.stats.fingerprint_enrolled || 0;
            document.getElementById('facialEnrolledCount').textContent = data.stats.facial_enrolled || 0;
            document.getElementById('completeEnrollmentCount').textContent = data.stats.complete_enrollment || 0;
            document.getElementById('pendingEnrollmentCount').textContent = data.stats.pending_enrollment || 0;
            
            // Update percentages
            const total = data.stats.total_employees || 1;
            document.getElementById('fingerprintPercentage').textContent = 
                Math.round((data.stats.fingerprint_enrolled / total) * 100) + '%';
            document.getElementById('facialPercentage').textContent = 
                Math.round((data.stats.facial_enrolled / total) * 100) + '%';
            document.getElementById('completePercentage').textContent = 
                Math.round((data.stats.complete_enrollment / total) * 100) + '%';
            document.getElementById('pendingPercentage').textContent = 
                Math.round((data.stats.pending_enrollment / total) * 100) + '%';
        }
    } catch (error) {
        console.error('Error loading enrollment stats:', error);
    }
}

/**
 * Load employee list with enrollment status
 */
async function loadEmployeeList() {
    try {
        const filters = {
            sede: document.getElementById('filterSede')?.value || '',
            establecimiento: document.getElementById('filterEstablecimiento')?.value || '',
            enrollment_status: document.getElementById('filterEnrollmentStatus')?.value || '',
            search: document.getElementById('employeeNameSearch')?.value || ''
        };
        
        const response = await fetch('../../api/employee/enrollment-list.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(filters)
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayEmployeeList(data.employees || []);
        }
    } catch (error) {
        console.error('Error loading employee list:', error);
    }
}

/**
 * Display employee list in table
 */
function displayEmployeeList(employees) {
    const tbody = document.getElementById('employeeTableBody');
    
    if (employees.length === 0) {
        tbody.innerHTML = `
            <tr class="no-data">
                <td colspan="7">
                    <div class="no-data-message">
                        <i class="fas fa-users"></i>
                        <p>No se encontraron empleados</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    const rows = employees.map(emp => {
        const hasFingerprint = emp.biometric_status?.fingerprint?.enrolled || false;
        const hasFacial = emp.biometric_status?.facial?.enrolled || false;
        
        let status = 'pending';
        let statusText = 'Pendiente';
        
        if (hasFingerprint && hasFacial) {
            status = 'complete';
            statusText = 'Completo';
        } else if (hasFingerprint || hasFacial) {
            status = 'partial';
            statusText = 'Parcial';
        }
        
        return `
            <tr>
                <td>
                    <div class="employee-info">
                        <span class="employee-name">${emp.NOMBRE} ${emp.APELLIDO}</span>
                        <span class="employee-dni">${emp.DNI || ''}</span>
                    </div>
                </td>
                <td>${emp.CODIGO || 'N/A'}</td>
                <td>${emp.ESTABLECIMIENTO_NOMBRE || 'N/A'}</td>
                <td>
                    <span class="status-badge ${hasFingerprint ? 'enrolled' : 'not-enrolled'}">
                        <i class="fas fa-fingerprint"></i>
                        ${hasFingerprint ? 'Inscrito' : 'No inscrito'}
                    </span>
                </td>
                <td>
                    <span class="status-badge ${hasFacial ? 'enrolled' : 'not-enrolled'}">
                        <i class="fas fa-user-shield"></i>
                        ${hasFacial ? 'Inscrito' : 'No inscrito'}
                    </span>
                </td>
                <td>
                    <span class="enrollment-status ${status}">${statusText}</span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn-sm btn-primary" onclick="enrollEmployee(${emp.ID_EMPLEADO})">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        <button class="btn-sm btn-secondary" onclick="viewEmployeeDetails(${emp.ID_EMPLEADO})">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    tbody.innerHTML = rows;
}

/**
 * Load sedes and establecimientos for filters
 */
async function loadSedesAndEstablecimientos() {
    try {
        // Load sedes
        const sedesResponse = await fetch('../../api/get-sedes.php');
        const sedesData = await sedesResponse.json();
        
        const sedeSelect = document.getElementById('filterSede');
        if (sedeSelect && sedesData.sedes) {
            sedeSelect.innerHTML = '<option value="">Todas las sedes</option>' +
                sedesData.sedes.map(sede => `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`).join('');
        }
        
        // Load establecimientos  
        const estabResponse = await fetch('../../api/get-establecimientos.php');
        const estabData = await estabResponse.json();
        
        const estabSelect = document.getElementById('filterEstablecimiento');
        if (estabSelect && estabData.establecimientos) {
            estabSelect.innerHTML = '<option value="">Todos los establecimientos</option>' +
                estabData.establecimientos.map(est => `<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`).join('');
        }
        
    } catch (error) {
        console.error('Error loading filter options:', error);
    }
}

/**
 * Clear filters
 */
function clearFilters() {
    document.getElementById('filterSede').value = '';
    document.getElementById('filterEstablecimiento').value = '';
    document.getElementById('filterEnrollmentStatus').value = '';
    document.getElementById('employeeNameSearch').value = '';
    loadEmployeeList();
}

/**
 * Enroll specific employee
 */
function enrollEmployee(employeeId) {
    // Pre-select employee and open modal
    // This would need to fetch employee details first
    openEnrollmentModal();
}

/**
 * View employee biometric details
 */
function viewEmployeeDetails(employeeId) {
    // Open details modal or navigate to details page
    console.log('View details for employee:', employeeId);
}

/**
 * Export enrollment report
 */
function exportEnrollmentReport() {
    // Generate and download enrollment report
    window.open('../../api/reports/enrollment-export.php', '_blank');
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    
    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}