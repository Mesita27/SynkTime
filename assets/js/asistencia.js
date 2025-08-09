/**
 * Attendance Page JavaScript
 * Handles employee selection, verification methods, and modal interactions
 */

let selectedEmployee = null;
let cameraHelper = null;
let employeeSearchTimeout = null;

/**
 * Initialize attendance page
 */
function initializeAttendancePage() {
    initializeEmployeeSearch();
    initializeCameraHelper();
    checkBiometricAPIsStatus();
}

/**
 * Initialize employee search functionality
 */
function initializeEmployeeSearch() {
    const searchInput = document.getElementById('employeeSearch');
    const resultsContainer = document.getElementById('employeeSearchResults');
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(employeeSearchTimeout);
        
        if (query.length < 2) {
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            return;
        }
        
        employeeSearchTimeout = setTimeout(() => {
            searchEmployees(query);
        }, 300);
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
}

/**
 * Search employees via API
 */
async function searchEmployees(query) {
    const resultsContainer = document.getElementById('employeeSearchResults');
    
    try {
        const response = await fetch('../../api/employee/search.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ query: query, limit: 10 })
        });
        
        const data = await response.json();
        
        if (data.success && data.employees) {
            displayEmployeeResults(data.employees);
        } else {
            resultsContainer.innerHTML = '<div class="no-results">No se encontraron empleados</div>';
            resultsContainer.style.display = 'block';
        }
    } catch (error) {
        console.error('Error searching employees:', error);
        showToast('Error al buscar empleados', 'error');
    }
}

/**
 * Display employee search results
 */
function displayEmployeeResults(employees) {
    const resultsContainer = document.getElementById('employeeSearchResults');
    
    if (employees.length === 0) {
        resultsContainer.innerHTML = '<div class="no-results">No se encontraron empleados</div>';
    } else {
        const resultsHTML = employees.map(employee => `
            <div class="employee-result" onclick="selectEmployee(${employee.ID_EMPLEADO}, '${employee.NOMBRE}', '${employee.APELLIDO}', '${employee.CODIGO || ''}')">
                <div class="employee-info">
                    <span class="employee-name">${employee.NOMBRE} ${employee.APELLIDO}</span>
                    <span class="employee-details">${employee.CODIGO ? 'Código: ' + employee.CODIGO : ''} ${employee.DNI ? '- DNI: ' + employee.DNI : ''}</span>
                </div>
                <div class="employee-meta">
                    <span class="establishment">${employee.ESTABLECIMIENTO_NOMBRE || 'Sin establecimiento'}</span>
                </div>
            </div>
        `).join('');
        
        resultsContainer.innerHTML = resultsHTML;
    }
    
    resultsContainer.style.display = 'block';
}

/**
 * Select an employee
 */
async function selectEmployee(id, nombre, apellido, codigo) {
    selectedEmployee = { id, nombre, apellido, codigo };
    
    // Update UI
    document.getElementById('employeeSearch').value = `${nombre} ${apellido}`;
    document.getElementById('selectedEmployeeId').value = id;
    document.getElementById('employeeSearchResults').style.display = 'none';
    
    // Show selected employee info
    const infoContainer = document.getElementById('selectedEmployeeInfo');
    infoContainer.querySelector('.employee-name').textContent = `${nombre} ${apellido}`;
    infoContainer.querySelector('.employee-code').textContent = codigo ? `Código: ${codigo}` : '';
    infoContainer.style.display = 'block';
    
    // Check attendance type and biometric status
    await updateAttendanceInfo(id);
    
    // Enable verification buttons
    enableVerificationButtons();
}

/**
 * Clear employee selection
 */
function clearEmployeeSelection() {
    selectedEmployee = null;
    document.getElementById('employeeSearch').value = '';
    document.getElementById('selectedEmployeeId').value = '';
    document.getElementById('selectedEmployeeInfo').style.display = 'none';
    document.getElementById('registrationTypeDisplay').innerHTML = `
        <span class="type-indicator">
            <i class="fas fa-question-circle"></i>
            Seleccione un empleado para determinar el tipo
        </span>
    `;
    
    // Disable verification buttons
    disableVerificationButtons();
}

/**
 * Update attendance information for selected employee
 */
async function updateAttendanceInfo(employeeId) {
    try {
        const response = await fetch('../../api/asistencia/info.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id_empleado: employeeId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update registration type display
            const typeDisplay = document.getElementById('registrationTypeDisplay');
            const nextType = data.next_type || 'ENTRADA';
            const iconClass = nextType === 'ENTRADA' ? 'fa-sign-in-alt' : 'fa-sign-out-alt';
            const typeClass = nextType === 'ENTRADA' ? 'entrada' : 'salida';
            
            typeDisplay.innerHTML = `
                <span class="type-indicator ${typeClass}">
                    <i class="fas ${iconClass}"></i>
                    ${nextType}
                </span>
            `;
            
            // Update verification button states based on biometric enrollment
            updateVerificationButtonStates(data.biometric_status);
        }
    } catch (error) {
        console.error('Error getting attendance info:', error);
        showToast('Error al obtener información de asistencia', 'error');
    }
}

/**
 * Update verification button states
 */
function updateVerificationButtonStates(biometricStatus) {
    const fingerprintBtn = document.getElementById('fingerprintBtn');
    const facialBtn = document.getElementById('facialBtn');
    
    // Fingerprint button
    if (biometricStatus.fingerprint?.enrolled) {
        fingerprintBtn.classList.remove('disabled');
        fingerprintBtn.querySelector('small').textContent = 'Disponible';
    } else {
        fingerprintBtn.classList.add('disabled');
        fingerprintBtn.querySelector('small').textContent = 'No inscrito';
    }
    
    // Facial button
    if (biometricStatus.facial?.enrolled) {
        facialBtn.classList.remove('disabled');
        facialBtn.querySelector('small').textContent = 'Disponible';
    } else {
        facialBtn.classList.add('disabled');
        facialBtn.querySelector('small').textContent = 'No inscrito';
    }
}

/**
 * Enable verification buttons
 */
function enableVerificationButtons() {
    document.getElementById('fingerprintBtn').disabled = false;
    document.getElementById('facialBtn').disabled = false;
    document.getElementById('traditionalBtn').disabled = false;
}

/**
 * Disable verification buttons
 */
function disableVerificationButtons() {
    document.getElementById('fingerprintBtn').disabled = true;
    document.getElementById('facialBtn').disabled = true;
    document.getElementById('traditionalBtn').disabled = true;
}

/**
 * Initialize camera helper
 */
function initializeCameraHelper() {
    cameraHelper = new CameraHelper();
}

/**
 * Open fingerprint verification modal
 */
function openFingerprintModal() {
    if (!selectedEmployee) {
        showToast('Seleccione un empleado primero', 'error');
        return;
    }
    
    const modal = document.getElementById('fingerprintModal');
    modal.style.display = 'block';
    
    // Reset status
    updateFingerprintStatus('Esperando lectura de huella dactilar...');
}

/**
 * Close fingerprint verification modal
 */
function closeFingerprintModal() {
    const modal = document.getElementById('fingerprintModal');
    modal.style.display = 'none';
}

/**
 * Simulate fingerprint scan (for demo purposes)
 */
async function simulateFingerprintScan() {
    if (!selectedEmployee) return;
    
    updateFingerprintStatus('Procesando huella...');
    
    try {
        // Simulate fingerprint verification
        const response = await fetch('../../api/asistencia/registrar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id_empleado: selectedEmployee.id,
                method: 'fingerprint',
                payload: {
                    simulated: true
                }
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateFingerprintStatus('¡Verificación exitosa!');
            setTimeout(() => {
                closeFingerprintModal();
                showAttendanceSuccess(data);
            }, 1500);
        } else {
            updateFingerprintStatus('Verificación fallida: ' + data.message);
        }
    } catch (error) {
        console.error('Error in fingerprint verification:', error);
        updateFingerprintStatus('Error en la verificación');
    }
}

/**
 * Update fingerprint status message
 */
function updateFingerprintStatus(message) {
    document.getElementById('fingerprintStatus').querySelector('p').textContent = message;
}

/**
 * Open facial recognition modal
 */
async function openFacialModal() {
    if (!selectedEmployee) {
        showToast('Seleccione un empleado primero', 'error');
        return;
    }
    
    const modal = document.getElementById('facialModal');
    const video = document.getElementById('facialVideo');
    
    modal.style.display = 'block';
    
    try {
        await cameraHelper.openCamera(video);
        updateFacialStatus('Posicione su rostro en el marco');
    } catch (error) {
        console.error('Error opening camera:', error);
        updateFacialStatus('Error al acceder a la cámara: ' + error.message);
    }
}

/**
 * Close facial recognition modal
 */
function closeFacialModal() {
    const modal = document.getElementById('facialModal');
    modal.style.display = 'none';
    cameraHelper.closeCamera();
}

/**
 * Capture facial image for verification
 */
async function captureFacialImage() {
    if (!selectedEmployee) return;
    
    try {
        const canvas = document.getElementById('facialCanvas');
        const imageData = cameraHelper.captureImage(canvas);
        
        updateFacialStatus('Procesando imagen...');
        
        const response = await fetch('../../api/asistencia/registrar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id_empleado: selectedEmployee.id,
                method: 'facial',
                payload: {
                    image: imageData
                }
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateFacialStatus('¡Verificación exitosa!');
            setTimeout(() => {
                closeFacialModal();
                showAttendanceSuccess(data);
            }, 1500);
        } else {
            updateFacialStatus('Verificación fallida: ' + data.message);
        }
    } catch (error) {
        console.error('Error in facial verification:', error);
        updateFacialStatus('Error en la verificación');
    }
}

/**
 * Update facial status message
 */
function updateFacialStatus(message) {
    document.getElementById('facialStatus').querySelector('p').textContent = message;
}

/**
 * Open traditional photo modal
 */
async function openTraditionalModal() {
    if (!selectedEmployee) {
        showToast('Seleccione un empleado primero', 'error');
        return;
    }
    
    const modal = document.getElementById('traditionalModal');
    const video = document.getElementById('traditionalVideo');
    
    modal.style.display = 'block';
    
    try {
        await cameraHelper.openCamera(video);
        updateTraditionalStatus('Posiciónese frente a la cámara');
    } catch (error) {
        console.error('Error opening camera:', error);
        updateTraditionalStatus('Error al acceder a la cámara: ' + error.message);
    }
}

/**
 * Close traditional photo modal
 */
function closeTraditionalModal() {
    const modal = document.getElementById('traditionalModal');
    modal.style.display = 'none';
    cameraHelper.closeCamera();
}

/**
 * Capture traditional photo
 */
async function captureTraditionalPhoto() {
    if (!selectedEmployee) return;
    
    try {
        const canvas = document.getElementById('traditionalCanvas');
        const imageData = cameraHelper.captureImage(canvas);
        
        updateTraditionalStatus('Procesando...');
        
        const response = await fetch('../../api/asistencia/registrar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id_empleado: selectedEmployee.id,
                method: 'traditional',
                payload: {
                    image: imageData
                }
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateTraditionalStatus('¡Foto capturada!');
            setTimeout(() => {
                closeTraditionalModal();
                showAttendanceSuccess(data);
            }, 1500);
        } else {
            updateTraditionalStatus('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error in traditional capture:', error);
        updateTraditionalStatus('Error al capturar foto');
    }
}

/**
 * Update traditional status message
 */
function updateTraditionalStatus(message) {
    document.getElementById('traditionalStatus').querySelector('p').textContent = message;
}

/**
 * Show attendance success message
 */
function showAttendanceSuccess(data) {
    const message = `Asistencia registrada: ${data.attendance.tipo} a las ${data.attendance.hora}`;
    showToast(message, 'success');
    
    // Clear selection and reload stats
    clearEmployeeSelection();
    loadTodayStats();
}

/**
 * Load today's statistics
 */
async function loadTodayStats() {
    try {
        const response = await fetch('../../api/asistencia/stats.php');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('todayAttendances').textContent = data.stats.total_attendances || 0;
            document.getElementById('lateArrivals').textContent = data.stats.late_arrivals || 0;
            document.getElementById('biometricVerifications').textContent = data.stats.biometric_verifications || 0;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

/**
 * Check biometric APIs status
 */
async function checkBiometricAPIsStatus() {
    try {
        // Check facial API
        const facialResponse = await fetch('../../api/biometrics/face/status');
        const facialData = await facialResponse.json();
        
        // Check fingerprint API
        const fingerprintResponse = await fetch('../../api/biometrics/fingerprint/status');
        const fingerprintData = await fingerprintResponse.json();
        
        // Update UI based on API status
        if (!facialData.api_status?.available) {
            console.warn('Facial recognition API not available');
        }
        
        if (!fingerprintData.api_status?.available) {
            console.warn('Fingerprint API not available');
        }
    } catch (error) {
        console.error('Error checking biometric APIs:', error);
    }
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