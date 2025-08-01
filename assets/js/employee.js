// ===================================================================
// EMPLOYEE.JS - VERSIÓN INTEGRADA CON PAGINACIÓN AJAX
// ===================================================================

// Variables globales para paginación
let currentPage = 1;
let currentLimit = 10;
let totalPages = 1;
let currentFilters = {};

// Configuración de límites disponibles
const AVAILABLE_LIMITS = [10, 15, 20, 30, 40, 50];

// Variable para modal de eliminación
let empleadoAEliminar = null;

// ===================================================================
// 1. INICIALIZACIÓN Y CONFIGURACIÓN
// ===================================================================

document.addEventListener('DOMContentLoaded', function () {
    initializePagination();
    cargarSedesEmpleado();
    loadEmployees();
    setupEventListeners();
    setupModalListeners();
    setupExportListener();
});

// ===================================================================
// 2. SISTEMA DE PAGINACIÓN
// ===================================================================

function initializePagination() {
    if (document.getElementById('paginationControls')) return;

    const limitContainer = document.createElement('div');
    limitContainer.className = 'pagination-controls';
    limitContainer.innerHTML = `
        <div class="limit-selector">
            <label for="limitSelector">Mostrar:</label>
            <select id="limitSelector" class="form-control limit-select">
                ${AVAILABLE_LIMITS.map(limit => 
                    `<option value="${limit}" ${limit === currentLimit ? 'selected' : ''}>${limit} registros</option>`
                ).join('')}
            </select>
        </div>
        <div class="pagination-info">
            <span id="paginationInfo">Cargando...</span>
        </div>
        <div class="pagination-buttons" id="paginationButtons">
            <!-- Los botones se generan dinámicamente -->
        </div>
    `;
    
    const tableContainer = document.querySelector('.employee-table-container');
    tableContainer.parentNode.insertBefore(limitContainer, tableContainer);
}

function setupEventListeners() {
    // Selector de límite
    const limitSelector = document.getElementById('limitSelector');
    if (limitSelector) {
        limitSelector.addEventListener('change', function() {
            currentLimit = parseInt(this.value);
            currentPage = 1;
            loadEmployees();
        });
    }

    // Formulario de filtros
    const form = document.getElementById('employeeQueryForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            updateFiltersFromForm();
            loadEmployees();
        });
    }

    // Botón limpiar filtros
    const clearBtn = document.getElementById('btnClearEmployeeQuery');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearFilters();
            loadEmployees();
        });
    }

    // Cambio de sede
    const sedeSelect = document.getElementById('q_sede');
    if (sedeSelect) {
        sedeSelect.addEventListener('change', function() {
            cargarEstablecimientosEmpleado();
            setTimeout(() => {
                currentPage = 1;
                updateFiltersFromForm();
                loadEmployees();
            }, 100);
        });
    }

    // Cambio de establecimiento
    const estSelect = document.getElementById('q_establecimiento');
    if (estSelect) {
        estSelect.addEventListener('change', function() {
            currentPage = 1;
            updateFiltersFromForm();
            loadEmployees();
        });
    }
}

function updateFiltersFromForm() {
    const form = document.getElementById('employeeQueryForm');
    if (form) {
        currentFilters = {
            codigo: document.getElementById('q_codigo')?.value || '',
            identificacion: document.getElementById('q_identificacion')?.value || '',
            nombre: document.getElementById('q_nombre')?.value || '',
            sede: document.getElementById('q_sede')?.value || '',
            establecimiento: document.getElementById('q_establecimiento')?.value || '',
            estado: document.getElementById('q_estado')?.value || ''
        };
        
        // Remover filtros vacíos
        Object.keys(currentFilters).forEach(key => {
            if (!currentFilters[key]) {
                delete currentFilters[key];
            }
        });
    }
}

function clearFilters() {
    const form = document.getElementById('employeeQueryForm');
    if (form) {
        form.reset();
    }
    currentFilters = {};
    currentPage = 1;
}

// ===================================================================
// 3. CARGA DE DATOS CON PAGINACIÓN
// ===================================================================

async function loadEmployees() {
    try {
        showLoadingState();
        
        const params = new URLSearchParams({
            page: currentPage,
            limit: currentLimit,
            ...currentFilters
        });

        const response = await fetch(`api/employee/list.php?${params.toString()}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        if (data.success) {
            renderEmployeeTable(data.data);
            updatePaginationInfo(data.pagination);
            renderPaginationButtons(data.pagination);
        } else {
            throw new Error(data.message || 'Error desconocido');
        }
        
    } catch (error) {
        console.error('Error al cargar empleados:', error);
        showErrorState(error.message);
    }
}

function showLoadingState() {
    const tbody = document.getElementById('employeeTableBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                </td>
            </tr>
        `;
    }
}

function showErrorState(message) {
    const tbody = document.getElementById('employeeTableBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="error-state">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Error: ${message}
                    <button onclick="loadEmployees()" class="btn-retry">Reintentar</button>
                </td>
            </tr>
        `;
    }
}

// ===================================================================
// 4. RENDERIZADO DE TABLA (MEJORADO CON PAGINACIÓN)
// ===================================================================

function renderEmployeeTable(data) {
    const tbody = document.getElementById('employeeTableBody');
    
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!data.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" class="no-data-state">
                    <i class="fas fa-users"></i>
                    No se encontraron empleados con los filtros aplicados
                </td>
            </tr>
        `;
        return;
    }

    data.forEach(emp => {
        // Create biometric status indicators
        let biometricStatus = '';
        const hasFacial = emp.FACIAL_RECOGNITION_ENABLED === 'Y';
        const hasFingerprint = emp.FINGERPRINT_ENABLED === 'Y';
        
        if (hasFacial || hasFingerprint) {
            biometricStatus = '<div class="biometric-indicators">';
            if (hasFacial) {
                biometricStatus += '<span class="biometric-icon facial" title="Reconocimiento facial habilitado"><i class="fas fa-face-smile"></i></span>';
            }
            if (hasFingerprint) {
                biometricStatus += '<span class="biometric-icon fingerprint" title="Huella dactilar habilitada"><i class="fas fa-fingerprint"></i></span>';
            }
            biometricStatus += '</div>';
        } else {
            biometricStatus = '<span class="biometric-none" title="Sin datos biométricos">-</span>';
        }

        tbody.innerHTML += `
            <tr>
                <td>${emp.id ?? ''}</td>
                <td>${emp.identificacion ?? ''}</td>
                <td>${emp.nombre ?? ''} ${emp.apellido ?? ''}</td>
                <td>${emp.email ?? ''}</td>
                <td>${emp.establecimiento ?? ''}</td>
                <td>${emp.sede ?? ''}</td>
                <td>${emp.fecha_contratacion ?? ''}</td>
                <td>
                    <span class="${emp.estado === 'A' ? 'status-active' : 'status-inactive'}">
                        ${emp.estado === 'A' ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td>${biometricStatus}</td>
                <td>
                    <button class="btn-icon btn-edit" title="Editar" onclick="editarEmpleado('${emp.id}')">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon btn-delete" title="Eliminar" onclick="eliminarEmpleado('${emp.id}','${emp.nombre} ${emp.apellido}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

// ===================================================================
// 5. CONTROLES DE PAGINACIÓN
// ===================================================================

function updatePaginationInfo(pagination) {
    const info = document.getElementById('paginationInfo');
    if (info && pagination) {
        const start = ((pagination.current_page - 1) * pagination.limit) + 1;
        const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
        
        info.textContent = `Mostrando ${start} - ${end} de ${pagination.total_records} empleados`;
        
        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;
    }
}

function renderPaginationButtons(pagination) {
    const container = document.getElementById('paginationButtons');
    if (!container || !pagination) return;

    let buttonsHTML = '';
    
    // Botón anterior
    if (pagination.has_prev) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToPage(${pagination.current_page - 1})">
            <i class="fas fa-chevron-left"></i> Anterior
        </button>`;
    }

    // Botones de páginas
    const maxButtons = 5;
    let startPage = Math.max(1, pagination.current_page - Math.floor(maxButtons / 2));
    let endPage = Math.min(pagination.total_pages, startPage + maxButtons - 1);
    
    if (endPage - startPage + 1 < maxButtons) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }

    if (startPage > 1) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        buttonsHTML += `<button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" 
                            onclick="goToPage(${i})">${i}</button>`;
    }

    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
        buttonsHTML += `<button class="pagination-btn" onclick="goToPage(${pagination.total_pages})">${pagination.total_pages}</button>`;
    }

    // Botón siguiente
    if (pagination.has_next) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToPage(${pagination.current_page + 1})">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>`;
    }

    container.innerHTML = buttonsHTML;
}

function goToPage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadEmployees();
    }
}

// ===================================================================
// 6. FUNCIONES DE SEDES Y ESTABLECIMIENTOS (MANTIENEN COMPATIBILIDAD)
// ===================================================================

function cargarSedesEmpleado() {
    fetch('api/get-sedes.php')
        .then(r => r.json())
        .then(res => {
            const sedeSelect = document.getElementById('q_sede');
            if (sedeSelect) {
                sedeSelect.innerHTML = '<option value="">Todas</option>';
                if (res.sedes && res.sedes.length > 0) {
                    res.sedes.forEach(sede => {
                        sedeSelect.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
                    });
                }
                sedeSelect.value = "";
                cargarEstablecimientosEmpleado();
            }
        })
        .catch(error => console.error('Error al cargar sedes:', error));
}

function cargarEstablecimientosEmpleado() {
    const sedeId = document.getElementById('q_sede')?.value;
    const establecimientoSelect = document.getElementById('q_establecimiento');
    
    if (!establecimientoSelect) return;
    
    establecimientoSelect.innerHTML = '<option value="">Todos</option>';
    if (!sedeId) return;
    
    fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(sedeId))
        .then(r => r.json())
        .then(res => {
            if (res.establecimientos && res.establecimientos.length > 0) {
                res.establecimientos.forEach(est => {
                    establecimientoSelect.innerHTML += `<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`;
                });
            }
            establecimientoSelect.value = "";
        })
        .catch(error => console.error('Error al cargar establecimientos:', error));
}

// ===================================================================
// 7. GESTIÓN DE MODALES (MANTIENE FUNCIONALIDAD ORIGINAL)
// ===================================================================

function setupModalListeners() {
    // Botón agregar empleado
    const btnAdd = document.getElementById('btnAddEmployee');
    if (btnAdd) {
        btnAdd.addEventListener('click', function() {
            openEmployeeModal('crear');
        });
    }

    // Listeners para cerrar modal de empleado
    const closeBtn = document.getElementById('closeEmployeeModal');
    const cancelBtn = document.getElementById('cancelEmployeeModal');
    const modal = document.getElementById('employeeModal');
    
    if (closeBtn) closeBtn.addEventListener('click', closeEmployeeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeEmployeeModal);
    if (modal) {
        modal.addEventListener('mousedown', function(e) {
            if (e.target === this) closeEmployeeModal();
        });
    }

    // Listeners para modal de eliminación
    setupDeleteModalListeners();

    // Formulario de registro/edición
    const form = document.getElementById('employeeRegisterForm');
    if (form) {
        form.addEventListener('submit', handleEmployeeFormSubmit);
    }

    // Selector de sede en modal
    const sedeEmpleado = document.getElementById('sedeEmpleado');
    if (sedeEmpleado) {
        sedeEmpleado.addEventListener('change', function() {
            cargarDepartamentosRegistro();
        });
    }
}

function openEmployeeModal(mode, empleado = null) {
    const modal = document.getElementById('employeeModal');
    const form = document.getElementById('employeeRegisterForm');
    const errorDiv = document.getElementById('employeeFormError');
    
    if (!modal || !form) return;
    
    modal.classList.add('show');
    form.reset();
    if (errorDiv) errorDiv.style.display = 'none';

    if (mode === 'editar' && empleado) {
        document.getElementById('employeeModalTitle').textContent = 'Editar Empleado';
        document.getElementById('employeeModalSubmitBtn').textContent = 'Guardar Cambios';
        document.getElementById('modoEmpleado').value = 'editar';
        document.getElementById('id_empleado').value = empleado.id;
        document.getElementById('id_empleado').readOnly = true;
        document.getElementById('dni').value = empleado.identificacion || '';
        document.getElementById('nombre').value = empleado.nombre || '';
        document.getElementById('apellido').value = empleado.apellido || '';
        document.getElementById('correo').value = empleado.email || '';
        document.getElementById('telefono').value = empleado.telefono || '';
        document.getElementById('fecha_ingreso').value = empleado.fecha_contratacion || '';
        document.getElementById('estado').value = empleado.estado || 'A';

        // Load sedes and establishments for editing
        cargarSedesRegistro(empleado.sede_id, empleado.establecimiento_id);
        
        // Show biometric button for existing employees
        if (typeof toggleBiometricButton === 'function') {
            toggleBiometricButton('editar', empleado);
        }
    } else {
        document.getElementById('employeeModalTitle').textContent = 'Registrar Empleado';
        document.getElementById('employeeModalSubmitBtn').textContent = 'Registrar';
        document.getElementById('modoEmpleado').value = 'crear';
        document.getElementById('id_empleado').readOnly = false;
        cargarSedesRegistro();
        
        // Hide biometric button for new employees
        if (typeof toggleBiometricButton === 'function') {
            toggleBiometricButton('crear');
        }
    }
}

function closeEmployeeModal() {
    const modal = document.getElementById('employeeModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

function cargarSedesRegistro(selectedSedeId = '', selectedDeptId = '') {
    fetch('api/get-sedes.php')
        .then(r => r.json())
        .then(res => {
            const sedeSelect = document.getElementById('sedeEmpleado');
            if (sedeSelect) {
                sedeSelect.innerHTML = '<option value="">Seleccione sede</option>';
                if (res.sedes && res.sedes.length > 0) {
                    res.sedes.forEach(sede => {
                        const isSelected = selectedSedeId && selectedSedeId == sede.ID_SEDE ? "selected" : "";
                        sedeSelect.innerHTML += `<option value="${sede.ID_SEDE}" ${isSelected}>${sede.NOMBRE}</option>`;
                    });
                }
                cargarDepartamentosRegistro(selectedDeptId);
            }
        })
        .catch(error => console.error('Error al cargar sedes para registro:', error));
}

function cargarDepartamentosRegistro(selectedDeptId = '') {
    const sedeId = document.getElementById('sedeEmpleado')?.value;
    const depSelect = document.getElementById('departamentoEmpleado');
    
    if (!depSelect) return;
    
    depSelect.innerHTML = '<option value="">Seleccione departamento</option>';
    if (!sedeId) return;
    
    fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(sedeId))
        .then(r => r.json())
        .then(res => {
            if (res.establecimientos && res.establecimientos.length > 0) {
                res.establecimientos.forEach(dep => {
                    const isSelected = selectedDeptId && selectedDeptId == dep.ID_ESTABLECIMIENTO ? "selected" : "";
                    depSelect.innerHTML += `<option value="${dep.ID_ESTABLECIMIENTO}" ${isSelected}>${dep.NOMBRE}</option>`;
                });
            }
        })
        .catch(error => console.error('Error al cargar departamentos:', error));
}

function handleEmployeeFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const errorDiv = document.getElementById('employeeFormError');
    
    if (errorDiv) errorDiv.style.display = 'none';

    // Validación de campos
    const requiredFields = ['id_empleado', 'nombre', 'apellido', 'dni', 'correo', 'sede', 'establecimiento', 'fecha_ingreso', 'estado'];
    const missingFields = requiredFields.filter(field => !form[field]?.value);
    
    if (missingFields.length > 0) {
        if (errorDiv) {
            errorDiv.textContent = "Todos los campos requeridos deben estar completos.";
            errorDiv.style.display = 'block';
        }
        return;
    }

    const modo = form.modo.value;
    const url = modo === 'editar' ? 'api/employee/update.php' : 'api/employee/register.php';
    
    fetch(url, {
        method: 'POST',
        body: new FormData(form)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeEmployeeModal();
            loadEmployees(); // Recargar con paginación
        } else {
            if (errorDiv) {
                errorDiv.textContent = res.message || "No se pudo registrar/editar el empleado.";
                errorDiv.style.display = 'block';
            }
        }
    })
    .catch(() => {
        if (errorDiv) {
            errorDiv.textContent = "Error de conexión con el servidor.";
            errorDiv.style.display = 'block';
        }
    });
}

// ===================================================================
// 8. FUNCIONES DE EDICIÓN Y ELIMINACIÓN (MANTIENEN FUNCIONALIDAD)
// ===================================================================

window.editarEmpleado = function(id) {
    fetch('api/employee/get.php?id=' + encodeURIComponent(id))
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                openEmployeeModal('editar', res.data);
            }
        })
        .catch(error => console.error('Error al cargar empleado:', error));
}

function setupDeleteModalListeners() {
    const closeBtn = document.getElementById('closeEmployeeDeleteModal');
    const cancelBtn = document.getElementById('cancelDeleteEmployeeBtn');
    const verifyBtn = document.getElementById('verifyDeleteEmployeeBtn');
    const confirmBtn = document.getElementById('confirmDeleteEmployeeBtn');
    const modal = document.getElementById('employeeDeleteModal');
    
    if (closeBtn) closeBtn.addEventListener('click', closeEmployeeDeleteModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeEmployeeDeleteModal);
    if (modal) {
        modal.addEventListener('mousedown', function(e) {
            if (e.target === this) closeEmployeeDeleteModal();
        });
    }
    
    if (verifyBtn) {
        verifyBtn.addEventListener('click', function() {
            document.getElementById('deleteStep1').style.display = 'none';
            document.getElementById('deleteStep2').style.display = '';
            document.getElementById('verifyDeleteEmployeeBtn').style.display = 'none';
            document.getElementById('confirmDeleteEmployeeBtn').style.display = '';
        });
    }
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            if (!empleadoAEliminar) return;
            
            fetch('api/employee/delete.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'id_empleado=' + encodeURIComponent(empleadoAEliminar)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    closeEmployeeDeleteModal();
                    loadEmployees(); // Recargar con paginación
                } else {
                    alert(res.message || 'No se pudo eliminar el empleado');
                }
            })
            .catch(error => {
                console.error('Error al eliminar empleado:', error);
                alert('Error de conexión al eliminar empleado');
            });
        });
    }
}

window.eliminarEmpleado = function(id, nombre) {
    empleadoAEliminar = id;
    const modal = document.getElementById('employeeDeleteModal');
    if (modal) {
        modal.classList.add('show');
        document.getElementById('deleteStep1').style.display = '';
        document.getElementById('deleteStep2').style.display = 'none';
        document.getElementById('verifyDeleteEmployeeBtn').style.display = '';
        document.getElementById('confirmDeleteEmployeeBtn').style.display = 'none';
    }
}

function closeEmployeeDeleteModal() {
    const modal = document.getElementById('employeeDeleteModal');
    if (modal) {
        modal.classList.remove('show');
    }
    empleadoAEliminar = null;
}

// ===================================================================
// 9. EXPORTACIÓN XLS (MANTIENE FUNCIONALIDAD ORIGINAL)
// ===================================================================

function setupExportListener() {
    const exportBtn = document.getElementById('btnExportXLS');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            const table = document.getElementById('employeeTable');
            if (!table) return alert('No se encontró la tabla para exportar.');

            const tableClone = table.cloneNode(true);

            // Quitar la columna de botones de acción
            for (let row of tableClone.rows) {
                if (row.cells.length > 0) row.deleteCell(-1);
            }

            const html = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" 
                      xmlns:x="urn:schemas-microsoft-com:office:excel" 
                      xmlns="http://www.w3.org/TR/REC-html40">
                <head><!--[if gte mso 9]>
                <xml>
                <x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
                    <x:Name>Empleados</x:Name>
                    <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
                </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook>
                </xml><![endif]-->
                </head>
                <body>
                    ${tableClone.outerHTML}
                </body>
                </html>
            `;

            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'empleados.xls';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(() => URL.revokeObjectURL(url), 1000);
        });
    }
}