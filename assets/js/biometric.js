// ===================================================================
// BIOMETRIC.JS - SYNKTIME BIOMETRIC VERIFICATION AND ENROLLMENT
// Handles fingerprint and facial recognition functionality
// ===================================================================

// Global variables for biometric functionality
let selectedEmployee = null;
let selectedVerificationMethod = null;
let selectedEnrollmentType = null;
let selectedFinger = null;
let facialCaptureCount = 0;
let maxFacialCaptures = 3;
let fingerprintProgress = 0;
let maxFingerprintScans = 5;
let biometricDevices = {
    fingerprint: false,
    camera: false
};

// Device detection timeout
const DEVICE_DETECTION_TIMEOUT = 5000;

// ===================================================================
// 1. DEVICE DETECTION AND INITIALIZATION
// ===================================================================

/**
 * Initialize biometric system
 */
function initializeBiometricSystem() {
    console.log('Initializing biometric system...');
    detectBiometricDevices();
}

/**
 * Detect available biometric devices
 */
async function detectBiometricDevices() {
    // Detect camera
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        biometricDevices.camera = true;
        stream.getTracks().forEach(track => track.stop());
        updateDeviceStatus('facial', true, 'Cámara detectada');
        updateDeviceStatus('traditional', true, 'Disponible');
    } catch (error) {
        biometricDevices.camera = false;
        updateDeviceStatus('facial', false, 'Cámara no disponible');
        console.warn('Camera not available:', error);
    }

    // Detect fingerprint reader (simulated for demo)
    // In a real implementation, this would check for actual fingerprint hardware
    setTimeout(() => {
        // Simulate fingerprint device detection
        const hasFingerprint = checkFingerprintDevice();
        biometricDevices.fingerprint = hasFingerprint;
        updateDeviceStatus('fingerprint', hasFingerprint, 
            hasFingerprint ? 'Lector detectado' : 'Lector no detectado');
    }, 2000);
}

/**
 * Check for fingerprint device (simulated)
 * In real implementation, this would use Web Authentication API or specialized SDK
 */
function checkFingerprintDevice() {
    // Simulate device detection based on user agent or available APIs
    // This is a placeholder - real implementation would check actual hardware
    return window.PublicKeyCredential !== undefined;
}

/**
 * Update device status in UI
 */
function updateDeviceStatus(deviceType, available, message) {
    const statusElements = {
        fingerprint: document.getElementById('fingerprint_status'),
        facial: document.getElementById('facial_status'),
        traditional: document.getElementById('traditional_status')
    };

    const enrollmentStatusElements = {
        fingerprint: document.getElementById('fingerprint_device_status'),
        facial: document.getElementById('facial_device_status')
    };

    const statusElement = statusElements[deviceType];
    const enrollmentStatusElement = enrollmentStatusElements[deviceType];

    if (statusElement) {
        const statusText = statusElement.querySelector('.status-text');
        const statusIcon = statusElement.querySelector('.status-icon');
        
        statusText.textContent = message;
        statusIcon.className = `fas status-icon ${available ? 'fa-check-circle available' : 'fa-times-circle unavailable'}`;
    }

    if (enrollmentStatusElement) {
        const statusText = enrollmentStatusElement.querySelector('.status-text');
        const statusIcon = enrollmentStatusElement.querySelector('.status-icon');
        
        statusText.textContent = message;
        statusIcon.className = `fas status-icon ${available ? 'fa-check-circle available' : 'fa-times-circle unavailable'}`;
    }

    // Update option availability
    const option = document.querySelector(`.verification-option[onclick*="${deviceType}"], .enrollment-option[onclick*="${deviceType}"]`);
    if (option && !available && deviceType !== 'traditional') {
        option.classList.add('disabled');
    }
}

// ===================================================================
// 2. BIOMETRIC VERIFICATION MODAL FUNCTIONS
// ===================================================================

/**
 * Open biometric verification modal
 */
window.openBiometricVerificationModal = function(employeeId, employeeName) {
    selectedEmployee = { id: employeeId, name: employeeName };
    
    // Update employee name in modal
    document.getElementById('biometric_employee_name').textContent = employeeName;
    
    // Show modal and initialize devices
    document.getElementById('biometricVerificationModal').classList.add('show');
    detectBiometricDevices();
};

/**
 * Close biometric verification modal
 */
window.closeBiometricVerificationModal = function() {
    document.getElementById('biometricVerificationModal').classList.remove('show');
    selectedEmployee = null;
    selectedVerificationMethod = null;
    hideDeviceInfo();
};

/**
 * Select verification method
 */
window.selectVerificationMethod = function(method) {
    selectedVerificationMethod = method;
    
    // Update UI selection
    document.querySelectorAll('.verification-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    const selectedOption = document.querySelector(`.verification-option[onclick*="${method}"]`);
    if (selectedOption && !selectedOption.classList.contains('disabled')) {
        selectedOption.classList.add('selected');
        showDeviceInfo(method);
    }
};

/**
 * Show device information
 */
function showDeviceInfo(method) {
    const deviceInfo = document.getElementById('device_info');
    const deviceDetails = document.getElementById('device_details');
    
    let details = '';
    switch (method) {
        case 'fingerprint':
            details = 'Se utilizará el lector de huellas dactilares conectado para verificar la identidad.';
            break;
        case 'facial':
            details = 'Se utilizará la cámara para realizar reconocimiento facial automático.';
            break;
        case 'traditional':
            details = 'Se utilizará la cámara para captura manual de fotografía.';
            break;
    }
    
    deviceDetails.textContent = details;
    deviceInfo.style.display = 'block';
}

/**
 * Hide device information
 */
function hideDeviceInfo() {
    document.getElementById('device_info').style.display = 'none';
}

/**
 * Start verification process
 */
window.startVerification = function() {
    if (!selectedVerificationMethod || !selectedEmployee) return;
    
    switch (selectedVerificationMethod) {
        case 'fingerprint':
            startFingerprintVerification();
            break;
        case 'facial':
            startFacialVerification();
            break;
        case 'traditional':
            // Use existing photo modal
            closeBiometricVerificationModal();
            openAttendancePhotoModal(selectedEmployee.id);
            break;
    }
};

// ===================================================================
// 3. FINGERPRINT VERIFICATION
// ===================================================================

/**
 * Start fingerprint verification
 */
function startFingerprintVerification() {
    closeBiometricVerificationModal();
    document.getElementById('fingerprintVerificationModal').classList.add('show');
    
    // Update UI
    updateFingerprintInstruction('Coloca tu dedo en el lector');
    updateFingerprintStatus('Esperando lectura...');
    
    // Start fingerprint scanning simulation
    simulateFingerprintScan();
}

/**
 * Close fingerprint verification modal
 */
window.closeFingerprintVerificationModal = function() {
    document.getElementById('fingerprintVerificationModal').classList.remove('show');
};

/**
 * Cancel fingerprint verification
 */
window.cancelFingerprintVerification = function() {
    closeFingerprintVerificationModal();
    openBiometricVerificationModal(selectedEmployee.id, selectedEmployee.name);
};

/**
 * Retry fingerprint verification
 */
window.retryFingerprintVerification = function() {
    document.getElementById('retry_fingerprint_btn').style.display = 'none';
    updateFingerprintInstruction('Coloca tu dedo en el lector');
    updateFingerprintStatus('Esperando lectura...');
    simulateFingerprintScan();
};

/**
 * Update fingerprint instruction text
 */
function updateFingerprintInstruction(text) {
    document.getElementById('fingerprint_instruction').textContent = text;
}

/**
 * Update fingerprint status text
 */
function updateFingerprintStatus(text) {
    document.getElementById('fingerprint_status_text').textContent = text;
}

/**
 * Simulate fingerprint scanning process
 */
function simulateFingerprintScan() {
    updateFingerprintStatus('Escaneando...');
    
    // Simulate scanning process
    setTimeout(() => {
        // Simulate random success/failure
        const success = Math.random() > 0.3; // 70% success rate
        
        if (success) {
            updateFingerprintInstruction('Verificación exitosa');
            updateFingerprintStatus('Huella reconocida correctamente');
            
            setTimeout(() => {
                processFingerprintVerification(true);
            }, 1500);
        } else {
            updateFingerprintInstruction('Verificación fallida');
            updateFingerprintStatus('Huella no reconocida. Intenta de nuevo.');
            document.getElementById('retry_fingerprint_btn').style.display = 'inline-flex';
        }
    }, 3000);
}

/**
 * Process fingerprint verification result
 */
function processFingerprintVerification(success) {
    if (success) {
        // Register attendance automatically
        registerAttendanceWithBiometric('fingerprint');
    }
}

// ===================================================================
// 4. FACIAL RECOGNITION VERIFICATION
// ===================================================================

/**
 * Start facial verification
 */
function startFacialVerification() {
    closeBiometricVerificationModal();
    document.getElementById('facialVerificationModal').classList.add('show');
    
    // Initialize camera
    initializeFacialCamera();
}

/**
 * Close facial verification modal
 */
window.closeFacialVerificationModal = function() {
    document.getElementById('facialVerificationModal').classList.remove('show');
    stopFacialCamera();
};

/**
 * Cancel facial verification
 */
window.cancelFacialVerification = function() {
    closeFacialVerificationModal();
    openBiometricVerificationModal(selectedEmployee.id, selectedEmployee.name);
};

/**
 * Initialize facial recognition camera
 */
async function initializeFacialCamera() {
    const video = document.getElementById('facial_video');
    const captureBtn = document.getElementById('capture_face_btn');
    
    try {
        updateFacialInstruction('Iniciando cámara...');
        updateFacialStatus('Conectando con la cámara...');
        
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        });
        
        video.srcObject = stream;
        
        video.onloadedmetadata = () => {
            updateFacialInstruction('Posiciona tu rostro en el marco');
            updateFacialStatus('Cámara lista - Mantén tu rostro visible');
            captureBtn.style.display = 'inline-flex';
            
            // Auto-capture after positioning
            setTimeout(() => {
                automaticFaceCapture();
            }, 3000);
        };
        
    } catch (error) {
        updateFacialInstruction('Error de cámara');
        updateFacialStatus('No se pudo acceder a la cámara');
        console.error('Camera error:', error);
    }
}

/**
 * Stop facial camera
 */
function stopFacialCamera() {
    const video = document.getElementById('facial_video');
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
}

/**
 * Update facial instruction text
 */
function updateFacialInstruction(text) {
    document.getElementById('facial_instruction').textContent = text;
}

/**
 * Update facial status text
 */
function updateFacialStatus(text) {
    document.getElementById('facial_status_text').textContent = text;
}

/**
 * Automatic face capture for verification
 */
function automaticFaceCapture() {
    updateFacialInstruction('Permanece inmóvil...');
    updateFacialStatus('Capturando imagen...');
    
    const video = document.getElementById('facial_video');
    const canvas = document.getElementById('facial_canvas');
    const ctx = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    
    const imageData = canvas.toDataURL('image/jpeg');
    
    // Simulate facial recognition processing
    processFacialRecognition(imageData);
}

/**
 * Manual face capture for verification
 */
window.captureFaceForVerification = function() {
    automaticFaceCapture();
};

/**
 * Process facial recognition
 */
function processFacialRecognition(imageData) {
    updateFacialInstruction('Procesando...');
    updateFacialStatus('Analizando imagen facial...');
    
    // Simulate processing time
    setTimeout(() => {
        // Simulate random success/failure
        const success = Math.random() > 0.2; // 80% success rate
        
        if (success) {
            updateFacialInstruction('Verificación exitosa');
            updateFacialStatus('Rostro reconocido correctamente');
            
            setTimeout(() => {
                registerAttendanceWithBiometric('facial', imageData);
            }, 1500);
        } else {
            updateFacialInstruction('Verificación fallida');
            updateFacialStatus('Rostro no reconocido. Intenta de nuevo.');
            
            setTimeout(() => {
                updateFacialInstruction('Posiciona tu rostro en el marco');
                updateFacialStatus('Presiona verificar para intentar de nuevo');
            }, 2000);
        }
    }, 3000);
}

// ===================================================================
// 5. ATTENDANCE REGISTRATION WITH BIOMETRIC DATA
// ===================================================================

/**
 * Register attendance with biometric verification
 */
function registerAttendanceWithBiometric(method, imageData = null) {
    if (!selectedEmployee) return;
    
    const formData = new URLSearchParams({
        id_empleado: selectedEmployee.id,
        verification_method: method,
        image_data: imageData || ''
    });
    
    fetch('api/attendance/register-biometric.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showNotification(`${res.tipo === 'ENTRADA' ? 'Entrada' : 'Salida'} registrada con verificación ${method}`, 'success');
            
            // Close modals
            closeFingerprintVerificationModal();
            closeFacialVerificationModal();
            
            // Reload attendance data
            if (typeof loadAttendanceDay === 'function') {
                loadAttendanceDay();
            }
        } else {
            showNotification('Error: ' + (res.message || 'No se pudo registrar la asistencia.'), 'error');
        }
    })
    .catch(error => {
        console.error('Error registering biometric attendance:', error);
        showNotification('Error al comunicarse con el servidor.', 'error');
    });
}

// ===================================================================
// 6. BIOMETRIC ENROLLMENT MODAL FUNCTIONS
// ===================================================================

/**
 * Open biometric enrollment modal
 */
window.openBiometricEnrollmentModal = function() {
    document.getElementById('biometricEnrollmentModal').classList.add('show');
    initializeEnrollmentModal();
};

/**
 * Close biometric enrollment modal
 */
window.closeBiometricEnrollmentModal = function() {
    document.getElementById('biometricEnrollmentModal').classList.remove('show');
    selectedEmployee = null;
    hideEnrollmentSection();
};

/**
 * Initialize enrollment modal
 */
async function initializeEnrollmentModal() {
    try {
        await loadSedesForEnrollment();
        await loadEstablecimientosForEnrollment();
        await loadEmployeesForEnrollment();
        setupEnrollmentEventListeners();
        detectBiometricDevices();
    } catch (error) {
        console.error('Error initializing enrollment modal:', error);
    }
}

/**
 * Setup enrollment event listeners
 */
function setupEnrollmentEventListeners() {
    const btnBuscar = document.getElementById('btnBuscarEnrollment');
    const btnLimpiar = document.getElementById('btnLimpiarEnrollment');
    const codigoBusqueda = document.getElementById('enrollment_codigo');
    const sedeSelect = document.getElementById('enrollment_sede');
    const establecimientoSelect = document.getElementById('enrollment_establecimiento');
    
    if (btnBuscar) {
        btnBuscar.onclick = loadEmployeesForEnrollment;
    }
    
    if (btnLimpiar) {
        btnLimpiar.onclick = clearEnrollmentFilters;
    }
    
    if (codigoBusqueda) {
        codigoBusqueda.onkeypress = function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                loadEmployeesForEnrollment();
            }
        };
    }
    
    if (sedeSelect) {
        sedeSelect.onchange = async function() {
            await loadEstablecimientosForEnrollment();
            loadEmployeesForEnrollment();
        };
    }
    
    if (establecimientoSelect) {
        establecimientoSelect.onchange = loadEmployeesForEnrollment;
    }
}

/**
 * Load sedes for enrollment
 */
async function loadSedesForEnrollment() {
    try {
        const response = await fetch('api/get-sedes.php');
        const data = await response.json();
        const sedeSelect = document.getElementById('enrollment_sede');
        
        sedeSelect.innerHTML = '<option value="">Todas las sedes</option>';
        
        if (data.sedes) {
            data.sedes.forEach(sede => {
                sedeSelect.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading sedes:', error);
    }
}

/**
 * Load establecimientos for enrollment
 */
async function loadEstablecimientosForEnrollment() {
    try {
        const sedeId = document.getElementById('enrollment_sede').value;
        let url = 'api/get-establecimientos.php';
        if (sedeId) {
            url += `?sede_id=${sedeId}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        const establecimientoSelect = document.getElementById('enrollment_establecimiento');
        
        establecimientoSelect.innerHTML = '<option value="">Todos los establecimientos</option>';
        
        if (data.establecimientos) {
            data.establecimientos.forEach(establecimiento => {
                establecimientoSelect.innerHTML += `<option value="${establecimiento.ID_ESTABLECIMIENTO}">${establecimiento.NOMBRE}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading establecimientos:', error);
    }
}

/**
 * Load employees for enrollment
 */
async function loadEmployeesForEnrollment() {
    const tbody = document.getElementById('enrollmentTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando empleados...</td></tr>';
    
    const sede = document.getElementById('enrollment_sede').value;
    const establecimiento = document.getElementById('enrollment_establecimiento').value;
    const codigo = document.getElementById('enrollment_codigo').value.trim();
    
    const params = new URLSearchParams();
    if (sede) params.append('sede', sede);
    if (establecimiento) params.append('establecimiento', establecimiento);
    if (codigo) params.append('codigo', codigo);
    
    try {
        // Use the attendance employees-available endpoint as it follows the correct schema
        const response = await fetch(`api/attendance/employees-available.php?${params.toString()}`);
        const data = await response.json();
        
        tbody.innerHTML = '';
        
        if (!data.success || !data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data-text">No se encontraron empleados</td></tr>';
            return;
        }
        
        data.data.forEach(emp => {
            const biometricStatus = getBiometricStatusHTML(emp);
            
            tbody.innerHTML += `
                <tr>
                    <td>${emp.ID_EMPLEADO}</td>
                    <td>${emp.NOMBRE} ${emp.APELLIDO}</td>
                    <td>${emp.ESTABLECIMIENTO || ''}</td>
                    <td>${emp.SEDE || ''}</td>
                    <td>${biometricStatus}</td>
                    <td>
                        <button type="button" class="btn-primary btn-sm" onclick="openBiometricOptionsModal(${emp.ID_EMPLEADO}, '${emp.NOMBRE} ${emp.APELLIDO}')">
                            <i class="fas fa-fingerprint"></i> Inscribir
                        </button>
                    </td>
                </tr>
            `;
        });
        
    } catch (error) {
        console.error('Error loading employees:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="error-text">Error al cargar empleados</td></tr>';
    }
}

/**
 * Get biometric status HTML
 */
function getBiometricStatusHTML(employee) {
    // This would be populated from actual biometric data
    const hasFingerprint = employee.biometric_fingerprint || false;
    const hasFacial = employee.biometric_facial || false;
    
    if (hasFingerprint && hasFacial) {
        return '<span class="biometric-status enrolled">Completo</span>';
    } else if (hasFingerprint || hasFacial) {
        return '<span class="biometric-status partial">Parcial</span>';
    } else {
        return '<span class="biometric-status none">Sin registrar</span>';
    }
}

/**
 * Clear enrollment filters
 */
function clearEnrollmentFilters() {
    document.getElementById('enrollment_sede').value = '';
    document.getElementById('enrollment_establecimiento').value = '';
    document.getElementById('enrollment_codigo').value = '';
    loadEstablecimientosForEnrollment();
    loadEmployeesForEnrollment();
}

/**
 * Open biometric options modal for direct enrollment
 */
window.openBiometricOptionsModal = function(employeeId, employeeName) {
    selectedEmployee = { id: employeeId, name: employeeName };
    
    // Update employee info
    document.getElementById('biometric_options_employee_name').textContent = employeeName;
    document.getElementById('biometric_options_employee_code').textContent = employeeId;
    
    // Show modal
    document.getElementById('biometricOptionsModal').classList.add('show');
    
    // Detect devices for the options
    detectBiometricDevices();
};

/**
 * Close biometric options modal
 */
window.closeBiometricOptionsModal = function() {
    document.getElementById('biometricOptionsModal').classList.remove('show');
    selectedEmployee = null;
};

/**
 * Select biometric enrollment type from options modal
 */
window.selectBiometricEnrollmentType = function(type) {
    if (!selectedEmployee) return;
    
    // Close options modal
    closeBiometricOptionsModal();
    
    // Open specific enrollment modal
    switch (type) {
        case 'fingerprint':
            openFingerprintEnrollmentModal();
            break;
        case 'facial':
            openFacialEnrollmentModal();
            break;
        case 'both':
            // Start with fingerprint, then facial
            openFingerprintEnrollmentModal();
            break;
    }
};

/**
 * Select employee for enrollment
 */
window.selectEmployeeForEnrollment = function(employeeId, employeeName) {
    selectedEmployee = { id: employeeId, name: employeeName };
    
    // Update UI
    document.getElementById('selected_employee_name').textContent = employeeName;
    document.getElementById('selected_employee_code').textContent = employeeId;
    
    // Show enrollment section
    showEnrollmentSection();
};

/**
 * Show enrollment section
 */
function showEnrollmentSection() {
    document.getElementById('biometric_enrollment_section').style.display = 'block';
}

/**
 * Hide enrollment section
 */
function hideEnrollmentSection() {
    document.getElementById('biometric_enrollment_section').style.display = 'none';
}

/**
 * Select enrollment type
 */
window.selectEnrollmentType = function(type) {
    selectedEnrollmentType = type;
    
    switch (type) {
        case 'fingerprint':
            openFingerprintEnrollmentModal();
            break;
        case 'facial':
            openFacialEnrollmentModal();
            break;
    }
};

// ===================================================================
// 7. FINGERPRINT ENROLLMENT
// ===================================================================

/**
 * Open fingerprint enrollment modal
 */
function openFingerprintEnrollmentModal() {
    if (!selectedEmployee) return;
    
    document.getElementById('fingerprintEnrollmentModal').classList.add('show');
    document.getElementById('fingerprint_enrollment_employee').textContent = selectedEmployee.name;
    
    resetFingerprintEnrollment();
}

/**
 * Close fingerprint enrollment modal
 */
window.closeFingerprintEnrollmentModal = function() {
    document.getElementById('fingerprintEnrollmentModal').classList.remove('show');
    resetFingerprintEnrollment();
};

/**
 * Cancel fingerprint enrollment
 */
window.cancelFingerprintEnrollment = function() {
    closeFingerprintEnrollmentModal();
};

/**
 * Reset fingerprint enrollment
 */
function resetFingerprintEnrollment() {
    selectedFinger = null;
    fingerprintProgress = 0;
    
    // Reset UI
    document.querySelectorAll('.finger').forEach(finger => {
        finger.classList.remove('selected');
    });
    
    document.getElementById('fingerprint_enrollment_process').style.display = 'none';
    document.getElementById('save_fingerprint_btn').style.display = 'none';
    document.getElementById('fingerprint_progress').style.width = '0%';
}

/**
 * Select finger for enrollment
 */
window.selectFinger = function(fingerType) {
    selectedFinger = fingerType;
    
    // Update UI
    document.querySelectorAll('.finger').forEach(finger => {
        finger.classList.remove('selected');
    });
    
    const selectedFingerElement = document.querySelector(`[data-finger="${fingerType}"]`);
    if (selectedFingerElement) {
        selectedFingerElement.classList.add('selected');
        
        // Show enrollment process
        document.getElementById('fingerprint_enrollment_process').style.display = 'block';
        startFingerprintEnrollment();
    }
};

/**
 * Start fingerprint enrollment process
 */
function startFingerprintEnrollment() {
    fingerprintProgress = 0;
    updateFingerprintEnrollmentProgress();
    
    updateFingerprintEnrollmentInstruction('Coloca el dedo seleccionado en el lector');
    updateFingerprintEnrollmentStatus('Iniciando inscripción...');
    
    // Simulate enrollment process
    simulateFingerprintEnrollment();
}

/**
 * Simulate fingerprint enrollment
 */
function simulateFingerprintEnrollment() {
    const interval = setInterval(() => {
        fingerprintProgress += 20;
        updateFingerprintEnrollmentProgress();
        
        if (fingerprintProgress >= 100) {
            clearInterval(interval);
            updateFingerprintEnrollmentInstruction('Inscripción completada');
            updateFingerprintEnrollmentStatus('Huella registrada exitosamente');
            document.getElementById('save_fingerprint_btn').style.display = 'inline-flex';
        } else {
            updateFingerprintEnrollmentStatus(`Progreso: ${fingerprintProgress}% - Mantén el dedo presionado`);
        }
    }, 1500);
}

/**
 * Update fingerprint enrollment progress
 */
function updateFingerprintEnrollmentProgress() {
    document.getElementById('fingerprint_progress').style.width = `${fingerprintProgress}%`;
}

/**
 * Update fingerprint enrollment instruction
 */
function updateFingerprintEnrollmentInstruction(text) {
    document.getElementById('fingerprint_enrollment_instruction').textContent = text;
}

/**
 * Update fingerprint enrollment status
 */
function updateFingerprintEnrollmentStatus(text) {
    document.getElementById('fingerprint_enrollment_status').textContent = text;
}

/**
 * Save fingerprint enrollment
 */
window.saveFingerprintEnrollment = function() {
    if (!selectedEmployee || !selectedFinger) return;
    
    const formData = new URLSearchParams({
        employee_id: selectedEmployee.id,
        finger_type: selectedFinger,
        fingerprint_data: 'simulated_fingerprint_data' // In real implementation, this would be actual biometric data
    });
    
    fetch('api/biometric/enroll-fingerprint.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showNotification('Huella registrada exitosamente', 'success');
            closeFingerprintEnrollmentModal();
            loadEmployeesForEnrollment(); // Refresh employee list
        } else {
            showNotification('Error: ' + (res.message || 'No se pudo registrar la huella.'), 'error');
        }
    })
    .catch(error => {
        console.error('Error saving fingerprint:', error);
        showNotification('Error al comunicarse con el servidor.', 'error');
    });
};

// ===================================================================
// 8. FACIAL ENROLLMENT
// ===================================================================

/**
 * Open facial enrollment modal
 */
function openFacialEnrollmentModal() {
    if (!selectedEmployee) return;
    
    document.getElementById('facialEnrollmentModal').classList.add('show');
    document.getElementById('facial_enrollment_employee').textContent = selectedEmployee.name;
    
    resetFacialEnrollment();
    initializeFacialEnrollmentCamera();
}

/**
 * Close facial enrollment modal
 */
window.closeFacialEnrollmentModal = function() {
    document.getElementById('facialEnrollmentModal').classList.remove('show');
    stopFacialEnrollmentCamera();
    resetFacialEnrollment();
};

/**
 * Cancel facial enrollment
 */
window.cancelFacialEnrollment = function() {
    closeFacialEnrollmentModal();
};

/**
 * Reset facial enrollment
 */
function resetFacialEnrollment() {
    facialCaptureCount = 0;
    document.getElementById('captured_images').style.display = 'none';
    document.getElementById('facial_captures').innerHTML = '';
    document.getElementById('save_facial_btn').style.display = 'none';
    document.getElementById('capture_face_enrollment_btn').style.display = 'none';
    
    // Reset steps
    document.querySelectorAll('.step').forEach(step => {
        step.classList.remove('active');
    });
}

/**
 * Initialize facial enrollment camera
 */
async function initializeFacialEnrollmentCamera() {
    const video = document.getElementById('facial_enrollment_video');
    
    try {
        updateFacialEnrollmentInstruction('Iniciando cámara...');
        updateFacialEnrollmentStatus('Conectando con la cámara...');
        
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        });
        
        video.srcObject = stream;
        
        video.onloadedmetadata = () => {
            updateFacialEnrollmentInstruction('Posiciona tu rostro en el marco');
            updateFacialEnrollmentStatus('Cámara lista - Sigue las instrucciones');
            document.getElementById('capture_face_enrollment_btn').style.display = 'inline-flex';
            
            // Activate first step
            document.getElementById('step_1').classList.add('active');
        };
        
    } catch (error) {
        updateFacialEnrollmentInstruction('Error de cámara');
        updateFacialEnrollmentStatus('No se pudo acceder a la cámara');
        console.error('Camera error:', error);
    }
}

/**
 * Stop facial enrollment camera
 */
function stopFacialEnrollmentCamera() {
    const video = document.getElementById('facial_enrollment_video');
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
}

/**
 * Update facial enrollment instruction
 */
function updateFacialEnrollmentInstruction(text) {
    document.getElementById('facial_enrollment_instruction').textContent = text;
}

/**
 * Update facial enrollment status
 */
function updateFacialEnrollmentStatus(text) {
    document.getElementById('facial_enrollment_status').textContent = text;
}

/**
 * Capture face for enrollment
 */
window.captureFaceForEnrollment = function() {
    if (facialCaptureCount >= maxFacialCaptures) return;
    
    const video = document.getElementById('facial_enrollment_video');
    const canvas = document.getElementById('facial_enrollment_canvas');
    const ctx = canvas.getContext('2d');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);
    
    const imageData = canvas.toDataURL('image/jpeg');
    
    // Add to captures
    addFacialCapture(imageData);
    
    facialCaptureCount++;
    
    // Update steps
    updateEnrollmentSteps();
    
    if (facialCaptureCount >= maxFacialCaptures) {
        updateFacialEnrollmentInstruction('Capturas completadas');
        updateFacialEnrollmentStatus('Se han tomado todas las capturas necesarias');
        document.getElementById('capture_face_enrollment_btn').style.display = 'none';
        document.getElementById('save_facial_btn').style.display = 'inline-flex';
    } else {
        updateFacialEnrollmentStatus(`Captura ${facialCaptureCount}/${maxFacialCaptures} tomada. Continúa con la siguiente.`);
    }
};

/**
 * Add facial capture to gallery
 */
function addFacialCapture(imageData) {
    const gallery = document.getElementById('facial_captures');
    const capturesContainer = document.getElementById('captured_images');
    
    capturesContainer.style.display = 'block';
    
    const imageDiv = document.createElement('div');
    imageDiv.className = 'captured-image';
    imageDiv.innerHTML = `<img src="${imageData}" alt="Captura ${facialCaptureCount + 1}">`;
    
    gallery.appendChild(imageDiv);
}

/**
 * Update enrollment steps
 */
function updateEnrollmentSteps() {
    const steps = ['step_1', 'step_2', 'step_3'];
    
    // Reset all steps
    steps.forEach(stepId => {
        document.getElementById(stepId).classList.remove('active');
    });
    
    // Activate current step
    if (facialCaptureCount < steps.length) {
        document.getElementById(steps[facialCaptureCount]).classList.add('active');
    }
}

/**
 * Save facial enrollment
 */
window.saveFacialEnrollment = function() {
    if (!selectedEmployee || facialCaptureCount < maxFacialCaptures) return;
    
    // Collect all captured images
    const capturedImages = [];
    document.querySelectorAll('#facial_captures img').forEach(img => {
        capturedImages.push(img.src);
    });
    
    const formData = new URLSearchParams({
        employee_id: selectedEmployee.id,
        facial_data: JSON.stringify(capturedImages)
    });
    
    fetch('api/biometric/enroll-facial.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showNotification('Patrón facial registrado exitosamente', 'success');
            closeFacialEnrollmentModal();
            loadEmployeesForEnrollment(); // Refresh employee list
        } else {
            showNotification('Error: ' + (res.message || 'No se pudo registrar el patrón facial.'), 'error');
        }
    })
    .catch(error => {
        console.error('Error saving facial data:', error);
        showNotification('Error al comunicarse con el servidor.', 'error');
    });
};

// ===================================================================
// 9. INITIALIZE BIOMETRIC SYSTEM ON PAGE LOAD
// ===================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Initialize biometric system if biometric modals are present
    if (document.getElementById('biometricVerificationModal') || 
        document.getElementById('biometricEnrollmentModal')) {
        initializeBiometricSystem();
    }
});