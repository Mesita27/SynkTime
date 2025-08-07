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
    status: '',
    search_code: '',
    search_name: ''
};

let currentBiometricTab = 'all';
let biometricPage = 1;
let biometricLimit = 25;
let biometricSort = { field: '', direction: 'asc' };
let selectedEmployees = new Set();

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

    // Enhanced search inputs with AJAX
    const searchCodeInput = document.getElementById('filter_search_code');
    if (searchCodeInput) {
        searchCodeInput.addEventListener('input', debounce(applyBiometricFilters, 500));
        searchCodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyBiometricFilters();
            }
        });
    }

    const searchNameInput = document.getElementById('filter_search_name');
    if (searchNameInput) {
        searchNameInput.addEventListener('input', debounce(applyBiometricFilters, 500));
        searchNameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyBiometricFilters();
            }
        });
    }

    // Sede change event
    const filterSede = document.getElementById('filter_sede');
    if (filterSede) {
        filterSede.onchange = function() {
            loadEstablecimientosForFilters();
            applyBiometricFilters();
        };
    }

    // Establecimiento change event
    const filterEstablecimiento = document.getElementById('filter_establecimiento');
    if (filterEstablecimiento) {
        filterEstablecimiento.onchange = applyBiometricFilters;
    }

    // Status filter change event
    const filterStatus = document.getElementById('filter_status');
    if (filterStatus) {
        filterStatus.onchange = applyBiometricFilters;
    }

    // Page size change event
    const pageSizeSelect = document.getElementById('biometric_page_size');
    if (pageSizeSelect) {
        pageSizeSelect.onchange = function() {
            biometricLimit = parseInt(this.value);
            biometricPage = 1;
            loadBiometricSummary();
        };
    }

    // Biometric report button
    const btnBiometricReport = document.getElementById('btnBiometricReport');
    if (btnBiometricReport) {
        btnBiometricReport.onclick = generateBiometricReport;
    }

    // Export button
    const btnExportBiometric = document.getElementById('btnExportBiometric');
    if (btnExportBiometric) {
        btnExportBiometric.onclick = exportBiometricData;
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
        tbody.innerHTML = '<tr><td colspan="10" class="no-data-text">No se encontraron empleados</td></tr>';
        return;
    }
    
    data.forEach(employee => {
        const fingerprintStatus = getBiometricStatusBadge(employee.has_fingerprint, 'huella');
        const facialStatus = getBiometricStatusBadge(employee.has_facial, 'facial');
        const generalStatus = getGeneralBiometricStatus(employee);
        const lastActivity = employee.last_activity || 'No registrada';
        
        tbody.innerHTML += `
            <tr class="employee-row" data-employee-id="${employee.ID_EMPLEADO}">
                <td>
                    <input type="checkbox" class="employee-checkbox" value="${employee.ID_EMPLEADO}" 
                           onchange="toggleEmployeeSelection(this)">
                </td>
                <td class="employee-code">${employee.ID_EMPLEADO}</td>
                <td class="employee-name">${employee.NOMBRE} ${employee.APELLIDO}</td>
                <td>${employee.ESTABLECIMIENTO || '-'}</td>
                <td>${employee.SEDE || '-'}</td>
                <td>${fingerprintStatus}</td>
                <td>${facialStatus}</td>
                <td>${generalStatus}</td>
                <td class="last-activity">${lastActivity}</td>
                <td>
                    <div class="btn-actions">
                        <button type="button" class="btn-primary btn-sm" 
                                onclick="openBiometricOptionsModal(${employee.ID_EMPLEADO}, '${employee.NOMBRE} ${employee.APELLIDO}')"
                                title="Inscribir datos biométricos">
                            <i class="fas fa-fingerprint"></i>
                        </button>
                        <button type="button" class="btn-secondary btn-sm" 
                                onclick="viewBiometricHistory(${employee.ID_EMPLEADO})"
                                title="Ver historial biométrico">
                            <i class="fas fa-history"></i>
                        </button>
                        <button type="button" class="btn-info btn-sm" 
                                onclick="viewEmployeeDetails(${employee.ID_EMPLEADO})"
                                title="Ver detalles del empleado">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    // Update selection state
    updateBulkActionsVisibility();
}

/**
 * Get biometric status badge
 */
function getBiometricStatusBadge(hasData, type) {
    if (hasData) {
        return `<span class="biometric-status enrolled">
                    <i class="fas fa-check-circle"></i> Registrado
                </span>`;
    } else {
        return `<span class="biometric-status none">
                    <i class="fas fa-times-circle"></i> No registrado
                </span>`;
    }
}

/**
 * Get general biometric status
 */
function getGeneralBiometricStatus(employee) {
    if (employee.has_fingerprint && employee.has_facial) {
        return '<span class="biometric-status complete"><i class="fas fa-shield-alt"></i> Completo</span>';
    } else if (employee.has_fingerprint || employee.has_facial) {
        return '<span class="biometric-status partial"><i class="fas fa-exclamation-triangle"></i> Parcial</span>';
    } else {
        return '<span class="biometric-status pending"><i class="fas fa-clock"></i> Pendiente</span>';
    }
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
        status: document.getElementById('filter_status').value,
        search_code: document.getElementById('filter_search_code').value.trim(),
        search_name: document.getElementById('filter_search_name').value.trim()
    };
    
    // Remove empty filters
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
    
    // Reset to first page when applying filters
    biometricPage = 1;
    
    loadBiometricSummary();
}

/**
 * Clear biometric filters
 */
function clearBiometricFilters() {
    document.getElementById('filter_sede').value = '';
    document.getElementById('filter_establecimiento').value = '';
    document.getElementById('filter_status').value = '';
    document.getElementById('filter_search_code').value = '';
    document.getElementById('filter_search_name').value = '';
    
    currentFilters = {};
    biometricPage = 1;
    
    loadEstablecimientosForFilters();
    loadBiometricSummary();
}

// ===================================================================
// 8. TAB MANAGEMENT
// ===================================================================

/**
 * Switch biometric status tab
 */
window.switchBiometricStatusTab = function(tabType) {
    // Update active tab
    document.querySelectorAll('.status-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelector(`[data-tab="${tabType}"]`).classList.add('active');
    
    currentBiometricTab = tabType;
    biometricPage = 1;
    
    // Update filters based on tab
    const statusFilter = document.getElementById('filter_status');
    switch (tabType) {
        case 'enrolled':
            statusFilter.value = 'complete';
            break;
        case 'partial':
            statusFilter.value = 'fingerprint_only'; // Could be either fingerprint_only or facial_only
            break;
        case 'pending':
            statusFilter.value = 'none';
            break;
        default:
            statusFilter.value = '';
    }
    
    applyBiometricFilters();
};

// ===================================================================
// 9. SORTING AND PAGINATION
// ===================================================================

/**
 * Sort biometric table
 */
window.sortBiometricTable = function(field) {
    if (biometricSort.field === field) {
        biometricSort.direction = biometricSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        biometricSort.field = field;
        biometricSort.direction = 'asc';
    }
    
    // Update sort indicators
    document.querySelectorAll('.sortable i').forEach(icon => {
        icon.className = 'fas fa-sort';
    });
    
    const sortIcon = document.querySelector(`[onclick="sortBiometricTable('${field}')"] i`);
    if (sortIcon) {
        sortIcon.className = `fas fa-sort-${biometricSort.direction === 'asc' ? 'up' : 'down'}`;
    }
    
    loadBiometricSummary();
};

/**
 * Change biometric page
 */
window.changeBiometricPage = function(page) {
    biometricPage = page;
    loadBiometricSummary();
};

// ===================================================================
// 10. BULK ACTIONS AND SELECTION
// ===================================================================

/**
 * Toggle select all employees
 */
window.toggleSelectAllEmployees = function() {
    const selectAllCheckbox = document.getElementById('select_all_employees');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    
    employeeCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
        toggleEmployeeSelection(checkbox);
    });
};

/**
 * Toggle employee selection
 */
window.toggleEmployeeSelection = function(checkbox) {
    const employeeId = checkbox.value;
    
    if (checkbox.checked) {
        selectedEmployees.add(employeeId);
    } else {
        selectedEmployees.delete(employeeId);
    }
    
    updateBulkActionsVisibility();
    updateSelectAllState();
};

/**
 * Update bulk actions visibility
 */
function updateBulkActionsVisibility() {
    const bulkActions = document.getElementById('bulk_actions');
    const selectedCount = document.getElementById('selected_count');
    
    if (selectedEmployees.size > 0) {
        bulkActions.style.display = 'block';
        selectedCount.textContent = selectedEmployees.size;
    } else {
        bulkActions.style.display = 'none';
    }
}

/**
 * Update select all checkbox state
 */
function updateSelectAllState() {
    const selectAllCheckbox = document.getElementById('select_all_employees');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    const checkedCount = document.querySelectorAll('.employee-checkbox:checked').length;
    
    if (checkedCount === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (checkedCount === employeeCheckboxes.length) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
    }
}

// ===================================================================
// 11. BULK ACTIONS
// ===================================================================

/**
 * Bulk enroll biometric
 */
window.bulkEnrollBiometric = function() {
    if (selectedEmployees.size === 0) return;
    
    const employeeIds = Array.from(selectedEmployees);
    showNotification(`Inscripción masiva iniciada para ${employeeIds.length} empleados`, 'info');
    // Implementation would depend on requirements
};

/**
 * Bulk export selected employees
 */
window.bulkExportSelected = function() {
    if (selectedEmployees.size === 0) return;
    
    const employeeIds = Array.from(selectedEmployees);
    showNotification(`Exportando ${employeeIds.length} empleados seleccionados`, 'info');
    // Implementation would generate and download report
};

/**
 * Bulk reset biometric data
 */
window.bulkResetBiometric = function() {
    if (selectedEmployees.size === 0) return;
    
    if (confirm(`¿Está seguro de resetear los datos biométricos de ${selectedEmployees.size} empleados?`)) {
        showNotification('Función de reseteo masivo (por implementar)', 'warning');
    }
};

// ===================================================================
// 12. EXPORT AND REPORTING
// ===================================================================

/**
 * Export biometric data
 */
function exportBiometricData() {
    const params = new URLSearchParams(currentFilters);
    params.append('export', 'true');
    
    showNotification('Generando archivo de exportación...', 'info');
    
    // This would typically trigger a file download
    window.open(`api/biometric/export.php?${params.toString()}`, '_blank');
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
// 9. UTILITY FUNCTIONS
// ===================================================================

/**
 * Debounce function to limit API calls during typing
 */
function debounce(func, wait) {
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