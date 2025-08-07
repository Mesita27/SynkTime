// ===================================================================
// EMPLOYEE NAVIGATION MODAL - Enhanced photo modal with navigation
// Implements AJAX search by code and name, employee navigation
// ===================================================================

// Global variables for employee navigation
let currentEmployeeTab = 'search';
let employeeNavigationFilters = {};
let employeeNavigationPage = 1;
let employeeNavigationLimit = 10;
let recentEmployees = [];

// ===================================================================
// 1. MODAL MANAGEMENT
// ===================================================================

/**
 * Open enhanced employee navigation modal
 */
window.openEmployeeNavigationModal = function() {
    document.getElementById('employeeNavigationModal').classList.add('show');
    initializeEmployeeNavigationModal();
};

/**
 * Close employee navigation modal
 */
window.closeEmployeeNavigationModal = function() {
    document.getElementById('employeeNavigationModal').classList.remove('show');
    resetEmployeeNavigationModal();
};

/**
 * Initialize the employee navigation modal
 */
async function initializeEmployeeNavigationModal() {
    try {
        await loadSedesForNavigation();
        await loadEstablecimientosForNavigation();
        
        // Load data for active tab
        switch (currentEmployeeTab) {
            case 'search':
                await searchEmployeesForNavigation();
                break;
            case 'recent':
                await loadRecentEmployees();
                break;
            case 'all':
                await loadAllEmployees();
                break;
        }
        
        setupEmployeeNavigationEventListeners();
    } catch (error) {
        console.error('Error initializing employee navigation modal:', error);
    }
}

/**
 * Reset modal state
 */
function resetEmployeeNavigationModal() {
    currentEmployeeTab = 'search';
    employeeNavigationFilters = {};
    employeeNavigationPage = 1;
    
    // Reset form fields
    document.getElementById('nav_search_codigo').value = '';
    document.getElementById('nav_search_nombre').value = '';
    document.getElementById('nav_search_sede').value = '';
    document.getElementById('nav_search_establecimiento').value = '';
    
    // Reset active tab
    document.querySelectorAll('.nav-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.querySelector('.nav-tab[onclick*="search"]').classList.add('active');
    document.getElementById('searchTab').classList.add('active');
}

/**
 * Setup event listeners for the navigation modal
 */
function setupEmployeeNavigationEventListeners() {
    // Search input events
    const codigoInput = document.getElementById('nav_search_codigo');
    const nombreInput = document.getElementById('nav_search_nombre');
    const sedeSelect = document.getElementById('nav_search_sede');
    const establecimientoSelect = document.getElementById('nav_search_establecimiento');
    
    if (codigoInput) {
        codigoInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchEmployeesForNavigation();
            }
        });
        
        codigoInput.addEventListener('input', debounce(searchEmployeesForNavigation, 500));
    }
    
    if (nombreInput) {
        nombreInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchEmployeesForNavigation();
            }
        });
        
        nombreInput.addEventListener('input', debounce(searchEmployeesForNavigation, 500));
    }
    
    if (sedeSelect) {
        sedeSelect.addEventListener('change', async function() {
            await loadEstablecimientosForNavigation();
            searchEmployeesForNavigation();
        });
    }
    
    if (establecimientoSelect) {
        establecimientoSelect.addEventListener('change', searchEmployeesForNavigation);
    }
}

// ===================================================================
// 2. TAB MANAGEMENT
// ===================================================================

/**
 * Switch between navigation tabs
 */
window.switchEmployeeTab = function(tabName) {
    // Update active tab
    document.querySelectorAll('.nav-tab').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    
    document.querySelector(`.nav-tab[onclick*="${tabName}"]`).classList.add('active');
    document.getElementById(`${tabName}Tab`).classList.add('active');
    
    currentEmployeeTab = tabName;
    
    // Load data for the new tab
    switch (tabName) {
        case 'search':
            searchEmployeesForNavigation();
            break;
        case 'recent':
            loadRecentEmployees();
            break;
        case 'all':
            loadAllEmployees();
            break;
    }
};

// ===================================================================
// 3. DATA LOADING FUNCTIONS
// ===================================================================

/**
 * Load sedes for navigation filters
 */
async function loadSedesForNavigation() {
    try {
        const response = await fetch('api/get-sedes.php');
        const data = await response.json();
        const sedeSelect = document.getElementById('nav_search_sede');
        
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
 * Load establecimientos for navigation filters
 */
async function loadEstablecimientosForNavigation() {
    try {
        const sedeId = document.getElementById('nav_search_sede').value;
        let url = 'api/get-establecimientos.php';
        if (sedeId) {
            url += `?sede_id=${sedeId}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        const establecimientoSelect = document.getElementById('nav_search_establecimiento');
        
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
 * Search employees with AJAX based on filters
 */
window.searchEmployeesForNavigation = async function() {
    const tbody = document.getElementById('employeeNavigationTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Buscando empleados...</td></tr>';
    
    // Collect filters
    employeeNavigationFilters = {
        sede: document.getElementById('nav_search_sede').value,
        establecimiento: document.getElementById('nav_search_establecimiento').value,
        codigo: document.getElementById('nav_search_codigo').value.trim(),
        nombre: document.getElementById('nav_search_nombre').value.trim(),
        page: employeeNavigationPage,
        limit: employeeNavigationLimit
    };
    
    // Remove empty filters
    Object.keys(employeeNavigationFilters).forEach(key => {
        if (!employeeNavigationFilters[key] && key !== 'page' && key !== 'limit') {
            delete employeeNavigationFilters[key];
        }
    });
    
    try {
        const params = new URLSearchParams(employeeNavigationFilters);
        const response = await fetch(`api/attendance/employees-available.php?${params.toString()}`);
        const data = await response.json();
        
        tbody.innerHTML = '';
        
        if (!data.success || !data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data-text">No se encontraron empleados</td></tr>';
            return;
        }
        
        // Render employee rows
        data.data.forEach(emp => {
            const scheduleInfo = renderScheduleInfo(emp.HORARIOS || []);
            const actionButtons = createEmployeeActionButtons(emp);
            
            tbody.innerHTML += `
                <tr class="employee-row" data-employee-id="${emp.ID_EMPLEADO}">
                    <td class="employee-code">${emp.ID_EMPLEADO}</td>
                    <td class="employee-name">${emp.NOMBRE} ${emp.APELLIDO}</td>
                    <td>${emp.ESTABLECIMIENTO || '-'}</td>
                    <td>${emp.SEDE || '-'}</td>
                    <td class="schedule-info">${scheduleInfo}</td>
                    <td>
                        <div class="btn-actions">
                            ${actionButtons}
                        </div>
                    </td>
                </tr>
            `;
        });
        
        // Update pagination if needed
        updateEmployeeNavigationPagination(data.pagination);
        
    } catch (error) {
        console.error('Error searching employees:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="error-text">Error al buscar empleados</td></tr>';
    }
};

/**
 * Load recent employees
 */
async function loadRecentEmployees() {
    const grid = document.getElementById('recentEmployeesGrid');
    grid.innerHTML = '<div class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando empleados recientes...</div>';
    
    try {
        // This would typically load from a recent activity endpoint
        // For now, we'll simulate some recent employees
        const recentData = await getSimulatedRecentEmployees();
        
        grid.innerHTML = '';
        
        if (recentData.length === 0) {
            grid.innerHTML = '<div class="no-data-text">No hay actividad reciente</div>';
            return;
        }
        
        recentData.forEach(emp => {
            const card = createRecentEmployeeCard(emp);
            grid.appendChild(card);
        });
        
    } catch (error) {
        console.error('Error loading recent employees:', error);
        grid.innerHTML = '<div class="error-text">Error al cargar empleados recientes</div>';
    }
}

/**
 * Load all employees
 */
async function loadAllEmployees() {
    const tbody = document.getElementById('allEmployeesTableBody');
    tbody.innerHTML = '<tr><td colspan="6" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando todos los empleados...</td></tr>';
    
    try {
        const response = await fetch('api/attendance/employees-available.php');
        const data = await response.json();
        
        tbody.innerHTML = '';
        
        if (!data.success || !data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="no-data-text">No se encontraron empleados activos</td></tr>';
            return;
        }
        
        data.data.forEach(emp => {
            const status = getEmployeeStatus(emp);
            const actionButtons = createEmployeeActionButtons(emp);
            
            tbody.innerHTML += `
                <tr class="employee-row" data-employee-id="${emp.ID_EMPLEADO}">
                    <td class="employee-code">${emp.ID_EMPLEADO}</td>
                    <td class="employee-name">${emp.NOMBRE} ${emp.APELLIDO}</td>
                    <td>${emp.ESTABLECIMIENTO || '-'}</td>
                    <td>${emp.SEDE || '-'}</td>
                    <td>${status}</td>
                    <td>
                        <div class="btn-actions">
                            ${actionButtons}
                        </div>
                    </td>
                </tr>
            `;
        });
        
    } catch (error) {
        console.error('Error loading all employees:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="error-text">Error al cargar empleados</td></tr>';
    }
}

// ===================================================================
// 4. HELPER FUNCTIONS
// ===================================================================

/**
 * Render schedule information for an employee
 */
function renderScheduleInfo(horarios) {
    if (!horarios || horarios.length === 0) {
        return '<span class="status-badge">Sin horarios</span>';
    }
    
    let html = '<div class="schedule-list">';
    horarios.forEach(horario => {
        const statusClass = horario.estado?.estado === 'disponible' ? 'available' : 'complete';
        const statusText = horario.estado?.mensaje || 'Completado';
        
        html += `
            <div class="schedule-item">
                <span class="schedule-time">${horario.hora_entrada} - ${horario.hora_salida}</span>
                <span class="schedule-status status-badge ${statusClass}">${statusText}</span>
            </div>
        `;
    });
    html += '</div>';
    
    return html;
}

/**
 * Create action buttons for employee row
 */
function createEmployeeActionButtons(employee) {
    return `
        <button type="button" class="btn-sm btn-select-employee" 
                onclick="selectEmployeeFromNavigation(${employee.ID_EMPLEADO}, '${employee.NOMBRE} ${employee.APELLIDO}')"
                title="Seleccionar empleado">
            <i class="fas fa-check"></i> Seleccionar
        </button>
        <button type="button" class="btn-sm btn-view-employee" 
                onclick="viewEmployeeDetails(${employee.ID_EMPLEADO})"
                title="Ver detalles">
            <i class="fas fa-eye"></i>
        </button>
    `;
}

/**
 * Get employee status
 */
function getEmployeeStatus(employee) {
    if (employee.TIENE_HORARIOS_DISPONIBLES) {
        return '<span class="status-badge available">Disponible</span>';
    } else {
        return '<span class="status-badge complete">Completo</span>';
    }
}

/**
 * Create recent employee card
 */
function createRecentEmployeeCard(employee) {
    const card = document.createElement('div');
    card.className = 'recent-employee-card';
    card.onclick = () => selectEmployeeFromNavigation(employee.ID_EMPLEADO, employee.NOMBRE + ' ' + employee.APELLIDO);
    
    card.innerHTML = `
        <div class="employee-info">
            <div class="employee-code">${employee.ID_EMPLEADO}</div>
            <div class="employee-name">${employee.NOMBRE} ${employee.APELLIDO}</div>
            <div class="employee-location">${employee.ESTABLECIMIENTO} - ${employee.SEDE}</div>
        </div>
        <div class="last-activity">
            <i class="fas fa-clock"></i> Última actividad: ${employee.last_activity || 'Hoy'}
        </div>
    `;
    
    return card;
}

/**
 * Get simulated recent employees data
 */
async function getSimulatedRecentEmployees() {
    // This would typically come from an API endpoint
    return [
        {
            ID_EMPLEADO: '001',
            NOMBRE: 'Juan Carlos',
            APELLIDO: 'Pérez González',
            ESTABLECIMIENTO: 'Sede Principal',
            SEDE: 'Bogotá',
            last_activity: 'Hace 5 minutos'
        },
        {
            ID_EMPLEADO: '002',
            NOMBRE: 'María Elena',
            APELLIDO: 'Rodríguez López',
            ESTABLECIMIENTO: 'Sucursal Norte',
            SEDE: 'Medellín',
            last_activity: 'Hace 1 hora'
        },
        {
            ID_EMPLEADO: '003',
            NOMBRE: 'Carlos Alberto',
            APELLIDO: 'Gómez Martínez',
            ESTABLECIMIENTO: 'Sede Principal',
            SEDE: 'Bogotá',
            last_activity: 'Hace 2 horas'
        }
    ];
}

/**
 * Update pagination for employee navigation
 */
function updateEmployeeNavigationPagination(pagination) {
    const container = document.getElementById('employeeNavigationPagination');
    if (!container || !pagination) return;
    
    let html = '';
    
    // Previous button
    if (pagination.current_page > 1) {
        html += `<button class="page-btn" onclick="changeEmployeeNavigationPage(${pagination.current_page - 1})">
                    <i class="fas fa-chevron-left"></i> Anterior
                 </button>`;
    }
    
    // Page numbers (simplified)
    for (let i = Math.max(1, pagination.current_page - 2); 
         i <= Math.min(pagination.total_pages, pagination.current_page + 2); 
         i++) {
        const activeClass = i === pagination.current_page ? 'active' : '';
        html += `<button class="page-btn ${activeClass}" onclick="changeEmployeeNavigationPage(${i})">${i}</button>`;
    }
    
    // Next button
    if (pagination.current_page < pagination.total_pages) {
        html += `<button class="page-btn" onclick="changeEmployeeNavigationPage(${pagination.current_page + 1})">
                    Siguiente <i class="fas fa-chevron-right"></i>
                 </button>`;
    }
    
    // Add pagination info
    html += `<div class="pagination-info">
                Página ${pagination.current_page} de ${pagination.total_pages} 
                (${pagination.total_records} empleados)
             </div>`;
    
    container.innerHTML = html;
}

/**
 * Change page for employee navigation
 */
window.changeEmployeeNavigationPage = function(page) {
    employeeNavigationPage = page;
    searchEmployeesForNavigation();
};

/**
 * Clear employee navigation filters
 */
window.clearEmployeeNavigationFilters = function() {
    document.getElementById('nav_search_codigo').value = '';
    document.getElementById('nav_search_nombre').value = '';
    document.getElementById('nav_search_sede').value = '';
    document.getElementById('nav_search_establecimiento').value = '';
    
    employeeNavigationFilters = {};
    employeeNavigationPage = 1;
    
    loadEstablecimientosForNavigation();
    searchEmployeesForNavigation();
};

// ===================================================================
// 5. EMPLOYEE SELECTION
// ===================================================================

/**
 * Select employee from navigation and proceed to photo modal
 */
window.selectEmployeeFromNavigation = function(employeeId, employeeName) {
    // Close navigation modal
    closeEmployeeNavigationModal();
    
    // Open traditional attendance photo modal
    if (typeof openAttendancePhotoModal === 'function') {
        openAttendancePhotoModal(employeeId, employeeName);
    } else {
        // Fallback: show notification
        showNotification(`Empleado seleccionado: ${employeeName} (${employeeId})`, 'success');
    }
};

/**
 * View employee details (placeholder function)
 */
window.viewEmployeeDetails = function(employeeId) {
    showNotification(`Ver detalles del empleado ${employeeId} (función por implementar)`, 'info');
};

// ===================================================================
// 6. UTILITY FUNCTIONS
// ===================================================================

/**
 * Debounce function to limit API calls
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

/**
 * Initialize on page load if modal exists
 */
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('employeeNavigationModal')) {
        console.log('Employee navigation modal initialized');
    }
});