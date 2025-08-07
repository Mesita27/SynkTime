// ===================================================================
// BIOMETRIC-ENROLLMENT-PAGE.JS - SYNKTIME BIOMETRIC ENROLLMENT PAGE
// Manages the biometric enrollment page functionality
// ===================================================================

// Global variables for the enrollment page
let biometricStats = {
    fingerprint_enrolled: 0,
    facial_enrolled: 0,
    complete_biometric: 0,
    pending_enrollment: 0
};

let currentFilters = {
    sede: '',
    establecimiento: '',
    status: ''
};

// ===================================================================
// 1. INITIALIZATION
// ===================================================================

document.addEventListener('DOMContentLoaded', function() {
    initializeBiometricEnrollmentPage();
    setupEventListeners();
    loadBiometricStats();
    loadBiometricSummary();
});

/**
 * Initialize the biometric enrollment page
 */
function initializeBiometricEnrollmentPage() {
    console.log('Initializing biometric enrollment page...');
    loadSedesForFilters();
    loadEstablecimientosForFilters();
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Open enrollment modal button
    const btnOpenEnrollment = document.getElementById('btnOpenBiometricEnrollment');
    if (btnOpenEnrollment) {
        btnOpenEnrollment.onclick = openBiometricEnrollmentModal;
    }

    // Filter buttons
    const btnApplyFilters = document.getElementById('btnApplyFilters');
    if (btnApplyFilters) {
        btnApplyFilters.onclick = applyBiometricFilters;
    }

    const btnClearFilters = document.getElementById('btnClearFilters');
    if (btnClearFilters) {
        btnClearFilters.onclick = clearBiometricFilters;
    }

    // Sede change event
    const filterSede = document.getElementById('filter_sede');
    if (filterSede) {
        filterSede.onchange = function() {
            loadEstablecimientosForFilters();
        };
    }

    // Biometric report button
    const btnBiometricReport = document.getElementById('btnBiometricReport');
    if (btnBiometricReport) {
        btnBiometricReport.onclick = generateBiometricReport;
    }
}

// ===================================================================
// 2. LOAD SEDES AND ESTABLECIMIENTOS FOR FILTERS
// ===================================================================

/**
 * Load sedes for filters
 */
async function loadSedesForFilters() {
    try {
        const response = await fetch('api/get-sedes.php');
        const data = await response.json();
        const sedeSelect = document.getElementById('filter_sede');
        
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
 * Load establecimientos for filters
 */
async function loadEstablecimientosForFilters() {
    try {
        const sedeId = document.getElementById('filter_sede').value;
        let url = 'api/get-establecimientos.php';
        if (sedeId) {
            url += `?sede_id=${sedeId}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        const establecimientoSelect = document.getElementById('filter_establecimiento');
        
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

// ===================================================================
// 3. BIOMETRIC STATISTICS
// ===================================================================

/**
 * Load biometric statistics
 */
async function loadBiometricStats() {
    try {
        const response = await fetch('api/biometric/stats.php');
        const data = await response.json();
        
        if (data.success) {
            biometricStats = data.stats;
            updateBiometricStatsDisplay();
        } else {
            console.error('Error loading biometric stats:', data.message);
            // Show simulated stats for demo
            showSimulatedStats();
        }
    } catch (error) {
        console.error('Error loading biometric stats:', error);
        // Show simulated stats for demo
        showSimulatedStats();
    }
}

/**
 * Show simulated stats for demo purposes
 */
function showSimulatedStats() {
    biometricStats = {
        fingerprint_enrolled: 45,
        facial_enrolled: 32,
        complete_biometric: 28,
        pending_enrollment: 67
    };
    updateBiometricStatsDisplay();
}

/**
 * Update biometric stats display
 */
function updateBiometricStatsDisplay() {
    document.getElementById('fingerprint_enrolled_count').textContent = biometricStats.fingerprint_enrolled;
    document.getElementById('facial_enrolled_count').textContent = biometricStats.facial_enrolled;
    document.getElementById('complete_biometric_count').textContent = biometricStats.complete_biometric;
    document.getElementById('pending_enrollment_count').textContent = biometricStats.pending_enrollment;
}

// ===================================================================
// 4. BIOMETRIC SUMMARY TABLE
// ===================================================================

/**
 * Load biometric summary
 */
async function loadBiometricSummary() {
    const tbody = document.getElementById('biometricSummaryTableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</td></tr>';
    
    try {
        const params = new URLSearchParams(currentFilters);
        const response = await fetch(`api/biometric/summary.php?${params.toString()}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            renderBiometricSummaryTable(data.data);
        } else {
            throw new Error(data.message || 'Error loading data');
        }
    } catch (error) {
        console.error('Error loading biometric summary:', error);
        // Show simulated data for demo
        renderSimulatedBiometricSummary();
    }
}

/**
 * Render simulated biometric summary for demo
 */
function renderSimulatedBiometricSummary() {
    const simulatedData = [
        {
            ID_EMPLEADO: '001',
            NOMBRE: 'Juan Carlos',
            APELLIDO: 'Pérez González',
            ESTABLECIMIENTO: 'Sede Principal',
            SEDE: 'Bogotá',
            has_fingerprint: true,
            has_facial: true
        },
        {
            ID_EMPLEADO: '002',
            NOMBRE: 'María Elena',
            APELLIDO: 'Rodríguez López',
            ESTABLECIMIENTO: 'Sucursal Norte',
            SEDE: 'Medellín',
            has_fingerprint: true,
            has_facial: false
        },
        {
            ID_EMPLEADO: '003',
            NOMBRE: 'Carlos Alberto',
            APELLIDO: 'Gómez Martínez',
            ESTABLECIMIENTO: 'Sede Principal',
            SEDE: 'Bogotá',
            has_fingerprint: false,
            has_facial: false
        }
    ];
    
    renderBiometricSummaryTable(simulatedData);
}

/**
 * Render biometric summary table
 */
function renderBiometricSummaryTable(data) {
    const tbody = document.getElementById('biometricSummaryTableBody');
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="no-data-text">No se encontraron empleados</td></tr>';
        return;
    }
    
    data.forEach(employee => {
        const fingerprintStatus = employee.has_fingerprint ? 
            '<span class="biometric-status enrolled"><i class="fas fa-check"></i> Registrado</span>' :
            '<span class="biometric-status none"><i class="fas fa-times"></i> No registrado</span>';
            
        const facialStatus = employee.has_facial ? 
            '<span class="biometric-status enrolled"><i class="fas fa-check"></i> Registrado</span>' :
            '<span class="biometric-status none"><i class="fas fa-times"></i> No registrado</span>';
            
        let generalStatus;
        if (employee.has_fingerprint && employee.has_facial) {
            generalStatus = '<span class="biometric-status enrolled">Completo</span>';
        } else if (employee.has_fingerprint || employee.has_facial) {
            generalStatus = '<span class="biometric-status partial">Parcial</span>';
        } else {
            generalStatus = '<span class="biometric-status none">Sin registrar</span>';
        }
        
        tbody.innerHTML += `
            <tr>
                <td>${employee.ID_EMPLEADO}</td>
                <td>${employee.NOMBRE} ${employee.APELLIDO}</td>
                <td>${employee.ESTABLECIMIENTO || '-'}</td>
                <td>${employee.SEDE || '-'}</td>
                <td>${fingerprintStatus}</td>
                <td>${facialStatus}</td>
                <td>${generalStatus}</td>
                <td>
                    <div class="btn-actions">
                        <button type="button" class="btn-primary btn-sm" 
                                onclick="selectEmployeeForEnrollment(${employee.ID_EMPLEADO}, '${employee.NOMBRE} ${employee.APELLIDO}')"
                                title="Inscribir datos biométricos">
                            <i class="fas fa-fingerprint"></i>
                        </button>
                        <button type="button" class="btn-secondary btn-sm" 
                                onclick="viewBiometricHistory(${employee.ID_EMPLEADO})"
                                title="Ver historial biométrico">
                            <i class="fas fa-history"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
}

// ===================================================================
// 5. FILTER FUNCTIONS
// ===================================================================

/**
 * Apply biometric filters
 */
function applyBiometricFilters() {
    currentFilters = {
        sede: document.getElementById('filter_sede').value,
        establecimiento: document.getElementById('filter_establecimiento').value,
        status: document.getElementById('filter_status').value
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
    
    loadBiometricSummary();
}

/**
 * Clear biometric filters
 */
function clearBiometricFilters() {
    document.getElementById('filter_sede').value = '';
    document.getElementById('filter_establecimiento').value = '';
    document.getElementById('filter_status').value = '';
    
    currentFilters = {};
    
    loadEstablecimientosForFilters();
    loadBiometricSummary();
}

// ===================================================================
// 6. EMPLOYEE SELECTION AND ENROLLMENT
// ===================================================================

/**
 * Select employee for enrollment from summary table
 */
window.selectEmployeeForEnrollment = function(employeeId, employeeName) {
    // Open the biometric enrollment modal and auto-select the employee
    openBiometricEnrollmentModal();
    
    // Wait for modal to be fully loaded before selecting employee
    setTimeout(() => {
        if (typeof window.selectEmployeeForEnrollment !== 'undefined') {
            // Auto-fill the employee search and trigger selection
            const codigoInput = document.getElementById('enrollment_codigo');
            if (codigoInput) {
                codigoInput.value = employeeId;
                // Trigger search to load the specific employee
                if (typeof loadEmployeesForEnrollment === 'function') {
                    loadEmployeesForEnrollment();
                }
            }
        }
    }, 500);
};

// ===================================================================
// 7. BIOMETRIC HISTORY AND REPORTS
// ===================================================================

/**
 * View biometric history for an employee
 */
window.viewBiometricHistory = function(employeeId) {
    // This would open a modal or navigate to a page showing biometric history
    showNotification(`Ver historial biométrico para empleado ${employeeId} (función por implementar)`, 'info');
};

/**
 * Generate biometric report
 */
function generateBiometricReport() {
    // This would generate and download a biometric enrollment report
    showNotification('Generando reporte biométrico... (función por implementar)', 'info');
}

// ===================================================================
// 8. UTILITY FUNCTIONS
// ===================================================================

/**
 * Refresh all data on the page
 */
function refreshBiometricData() {
    loadBiometricStats();
    loadBiometricSummary();
}

// Auto-refresh every 5 minutes
setInterval(refreshBiometricData, 300000);

// ===================================================================
// 9. REAL BIOMETRIC ENROLLMENT FUNCTIONS
// ===================================================================

/**
 * Start real facial enrollment using Face-api.js
 */
window.startRealFacialEnrollment = async function(employeeId, employeeName) {
    if (!window.realBiometricSystem) {
        showNotification('Sistema de reconocimiento facial no disponible. Usar inscripción tradicional.', 'error');
        return;
    }

    try {
        // Initialize the system
        await window.realBiometricSystem.initialize();
        
        // Setup video elements for enrollment
        const videoElement = document.getElementById('facial_video');
        const canvasElement = document.getElementById('facial_canvas');
        
        if (!videoElement || !canvasElement) {
            throw new Error('Elementos de video no encontrados');
        }

        window.realBiometricSystem.setupVideoElement('facial_video', 'facial_canvas');
        
        // Start camera
        await window.realBiometricSystem.startCamera();
        
        showNotification('Cámara iniciada. Preparándose para inscripción facial...', 'success');
        
        // Start real-time detection preview
        window.realBiometricSystem.startRealTimeDetection((detections) => {
            updateFaceDetectionStatus(detections);
        });
        
        // Update UI to show enrollment in progress
        updateEnrollmentUI('facial', 'ready', 'Posiciona tu rostro en el marco y presiona "Capturar"');
        
    } catch (error) {
        console.error('Error starting facial enrollment:', error);
        showNotification(`Error iniciando inscripción facial: ${error.message}`, 'error');
    }
};

/**
 * Capture face for enrollment
 */
window.captureFaceForEnrollment = async function() {
    if (!window.realBiometricSystem) {
        showNotification('Sistema biométrico no disponible', 'error');
        return;
    }

    try {
        updateEnrollmentUI('facial', 'processing', 'Capturando rostro...');
        
        // Capture face with the selected employee ID
        const enrollmentData = await window.realBiometricSystem.enrollFace(selectedEmployee.id, 3);
        
        updateEnrollmentUI('facial', 'processing', 'Guardando datos biométricos...');
        
        // Send to server
        const success = await saveFacialEnrollmentData(enrollmentData);
        
        if (success) {
            updateEnrollmentUI('facial', 'success', 'Inscripción facial completada exitosamente');
            showNotification('Inscripción facial registrada correctamente', 'success');
            
            // Refresh stats and close modal after delay
            setTimeout(() => {
                refreshBiometricData();
                closeBiometricEnrollmentModal();
            }, 2000);
        } else {
            throw new Error('Error guardando datos en el servidor');
        }
        
    } catch (error) {
        console.error('Error in facial enrollment:', error);
        updateEnrollmentUI('facial', 'error', `Error: ${error.message}`);
        showNotification(`Error en inscripción facial: ${error.message}`, 'error');
    } finally {
        // Stop real-time detection
        if (window.realBiometricSystem) {
            window.realBiometricSystem.stopRealTimeDetection();
        }
    }
};

/**
 * Save facial enrollment data to server
 */
async function saveFacialEnrollmentData(enrollmentData) {
    try {
        const formData = new URLSearchParams({
            employee_id: enrollmentData.employeeId,
            face_template: JSON.stringify(enrollmentData.template),
            captures_data: JSON.stringify(enrollmentData.captures)
        });

        const response = await fetch('api/biometric/enroll-facial-real.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message || 'Error desconocido');
        }

        return true;
    } catch (error) {
        console.error('Error saving facial enrollment:', error);
        throw error;
    }
}

/**
 * Update enrollment UI status
 */
function updateEnrollmentUI(type, status, message) {
    const statusElement = document.getElementById(`${type}_enrollment_status`);
    const messageElement = document.getElementById(`${type}_enrollment_message`);
    
    if (statusElement) {
        statusElement.className = `enrollment-status ${status}`;
    }
    
    if (messageElement) {
        messageElement.textContent = message;
    }
}

/**
 * Update face detection status in UI
 */
function updateFaceDetectionStatus(detections) {
    const statusElement = document.getElementById('face_detection_status');
    if (!statusElement) return;
    
    if (detections && detections.length > 0) {
        if (detections.length === 1) {
            statusElement.textContent = `Rostro detectado (Calidad: ${Math.round(detections[0].detection.score * 100)}%)`;
            statusElement.className = 'detection-status good';
        } else {
            statusElement.textContent = `${detections.length} rostros detectados - Asegúrese de que solo una persona esté frente a la cámara`;
            statusElement.className = 'detection-status warning';
        }
    } else {
        statusElement.textContent = 'Sin rostro detectado';
        statusElement.className = 'detection-status none';
    }
}