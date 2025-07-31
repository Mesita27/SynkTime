/**
 * SynkTime - Módulo de Reportes de Asistencia
 * 
 * Archivo principal para la funcionalidad del módulo de reportes.
 * Se encarga de cargar los datos, filtrarlos y manejar la paginación.
 */

// Variables globales
window.reportData = {
    asistencias: [],
    pagination: {
        page: 1,
        limit: 10,
        totalPages: 1
    },
    filtros: {
        tipo_reporte: null,
        fecha_desde: null,
        fecha_hasta: null,
        codigo: null,
        nombre: null,
        sede: null,
        establecimiento: null,
        estado_entrada: null
    }
};

// Inicialización del módulo cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function() {
    initReportesModule();
});

/**
 * Inicializa el módulo de reportes
 */
function initReportesModule() {
    console.log('Inicializando módulo de reportes de asistencia');
    
    // Cargar sedes para filtros
    loadSedesForFilters();
    
    // Configurar eventos
    setupEventListeners();
    
    // Cargar datos iniciales (sin filtros)
    loadReportes();
}

/**
 * Configura los listeners de eventos
 */
function setupEventListeners() {
    // Botones de filtro rápido
    document.getElementById('btnDiaActual')?.addEventListener('click', function() {
        setReportFilter('dia');
    });
    
    document.getElementById('btnSemanaActual')?.addEventListener('click', function() {
        setReportFilter('semana');
    });
    
    document.getElementById('btnMesActual')?.addEventListener('click', function() {
        setReportFilter('mes');
    });
    
    // Botones de consulta y limpieza
    document.getElementById('btnConsultar')?.addEventListener('click', function() {
        updateFilters();
        reportData.pagination.page = 1;
        loadReportes();
    });
    
    document.getElementById('btnLimpiar')?.addEventListener('click', function() {
        resetFilters();
        reportData.pagination.page = 1;
        loadReportes();
    });
    
    // Botones adicionales
    document.getElementById('btnConsultarReporte')?.addEventListener('click', function() {
        updateFilters();
        reportData.pagination.page = 1;
        loadReportes();
    });
    
    document.getElementById('btnLimpiarReporte')?.addEventListener('click', function() {
        resetFilters();
        reportData.pagination.page = 1;
        loadReportes();
    });
    
    // Cambio de sede en filtros
    document.getElementById('filtroSede')?.addEventListener('change', function() {
        loadEstablecimientosForFilter(this.value);
    });
    
    // Botón de exportación
    document.getElementById('btnExportarXLS')?.addEventListener('click', function() {
        exportReportToExcel();
    });
    
    // Prevenir envío de formulario
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            updateFilters();
            reportData.pagination.page = 1;
            loadReportes();
        });
    }
}

/**
 * Establece un filtro rápido de reporte (día, semana, mes)
 */
function setReportFilter(tipo) {
    reportData.filtros.tipo_reporte = tipo;
    reportData.filtros.fecha_desde = null;
    reportData.filtros.fecha_hasta = null;
    
    // Limpiar fechas personalizadas
    const fechaDesdeElem = document.getElementById('fechaDesde');
    const fechaHastaElem = document.getElementById('fechaHasta');
    if (fechaDesdeElem) fechaDesdeElem.value = '';
    if (fechaHastaElem) fechaHastaElem.value = '';
    
    // Actualizar el resto de filtros
    updateFilters();
    reportData.pagination.page = 1;
    loadReportes();
    
    // Resaltar el botón activo
    const buttons = document.querySelectorAll('.btn-filter');
    buttons.forEach(btn => btn.classList.remove('active'));
    
    if (tipo === 'dia') {
        document.getElementById('btnDiaActual')?.classList.add('active');
    } else if (tipo === 'semana') {
        document.getElementById('btnSemanaActual')?.classList.add('active');
    } else if (tipo === 'mes') {
        document.getElementById('btnMesActual')?.classList.add('active');
    }
}

/**
 * Actualiza los filtros desde los inputs del formulario
 */
function updateFilters() {
    const fields = {
        'filtroCodigo': 'codigo',
        'filtroNombre': 'nombre',
        'filtroSede': 'sede',
        'filtroEstablecimiento': 'establecimiento',
        'filtroEstadoEntrada': 'estado_entrada',
        'fechaDesde': 'fecha_desde',
        'fechaHasta': 'fecha_hasta'
    };
    
    // Actualizar filtros desde campos
    Object.entries(fields).forEach(([elementId, filterKey]) => {
        const element = document.getElementById(elementId);
        if (element) {
            reportData.filtros[filterKey] = element.value || null;
            
            // Si es un campo de texto, hacer trim
            if (element.type === 'text' && reportData.filtros[filterKey]) {
                reportData.filtros[filterKey] = reportData.filtros[filterKey].trim();
            }
        }
    });
    
    // Desactivar filtro de tipo_reporte si se seleccionan fechas
    if (reportData.filtros.fecha_desde || reportData.filtros.fecha_hasta) {
        reportData.filtros.tipo_reporte = null;
        // Quitar clase activa de botones rápidos
        const buttons = document.querySelectorAll('.btn-filter');
        buttons.forEach(btn => btn.classList.remove('active'));
    }
}

/**
 * Restablece todos los filtros a su valor predeterminado
 */
function resetFilters() {
    // Limpiar filtros
    reportData.filtros = {
        tipo_reporte: null,
        fecha_desde: null,
        fecha_hasta: null,
        codigo: null,
        nombre: null,
        sede: null,
        establecimiento: null,
        estado_entrada: null
    };
    
    // Limpiar campos de formulario
    const fields = {
        'filtroCodigo': '',
        'filtroNombre': '',
        'filtroSede': 'Todas',
        'filtroEstablecimiento': 'Todos',
        'filtroEstadoEntrada': 'Todos',
        'fechaDesde': '',
        'fechaHasta': ''
    };
    
    Object.entries(fields).forEach(([elementId, defaultValue]) => {
        const element = document.getElementById(elementId);
        if (element) element.value = defaultValue;
    });
    
    // Quitar clase activa de botones rápidos
    const buttons = document.querySelectorAll('.btn-filter');
    buttons.forEach(btn => btn.classList.remove('active'));
}

/**
 * Carga las sedes para los filtros
 */
function loadSedesForFilters() {
    const sedeSelect = document.getElementById('filtroSede');
    if (!sedeSelect) return;
    
    sedeSelect.innerHTML = '<option value="Todas">Todas</option>';
    sedeSelect.disabled = true;
    
    fetch('api/get-sedes.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar sedes');
            }
            
            if (data.sedes && Array.isArray(data.sedes)) {
                data.sedes.forEach(sede => {
                    sedeSelect.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
                });
            }
            
            sedeSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error al cargar sedes:', error);
            if (typeof showNotification === 'function') {
                showNotification('Error al cargar sedes: ' + error.message, 'error');
            }
            sedeSelect.disabled = false;
        });
}

/**
 * Carga los establecimientos para una sede seleccionada
 */
function loadEstablecimientosForFilter(sedeId) {
    const establecimientoSelect = document.getElementById('filtroEstablecimiento');
    if (!establecimientoSelect) return;
    
    establecimientoSelect.innerHTML = '<option value="Todos">Todos</option>';
    establecimientoSelect.disabled = true;
    
    if (!sedeId || sedeId === 'Todas') {
        establecimientoSelect.disabled = false;
        return;
    }
    
    fetch(`api/get-establecimientos.php?sede_id=${sedeId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar establecimientos');
            }
            
            if (data.establecimientos && Array.isArray(data.establecimientos)) {
                data.establecimientos.forEach(establecimiento => {
                    establecimientoSelect.innerHTML += `<option value="${establecimiento.ID_ESTABLECIMIENTO}">${establecimiento.NOMBRE}</option>`;
                });
            }
            
            establecimientoSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error al cargar establecimientos:', error);
            if (typeof showNotification === 'function') {
                showNotification('Error al cargar establecimientos: ' + error.message, 'error');
            }
            establecimientoSelect.disabled = false;
        });
}

/**
 * Carga los reportes de asistencia
 */
function loadReportes() {
    const tableBody = document.getElementById('reporteTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = `
        <tr>
            <td colspan="9" style="text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin"></i> Cargando datos...
            </td>
        </tr>
    `;
    
    // Construir parámetros de la consulta
    const params = new URLSearchParams({
        page: reportData.pagination.page,
        limit: reportData.pagination.limit
    });
    
    // Agregar filtros a los parámetros
    Object.entries(reportData.filtros).forEach(([key, value]) => {
        if (value) {
            params.append(key, value);
        }
    });
    
    fetch(`api/reports/list.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar reportes');
            }
            
            reportData.asistencias = data.data;
            renderReportesTable();
            updatePagination(data.pagination);
        })
        .catch(error => {
            console.error('Error al cargar reportes:', error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px; color: #e53e3e;">
                        <i class="fas fa-exclamation-circle"></i> ${error.message || 'Error al cargar reportes'}
                    </td>
                </tr>
            `;
            if (typeof showNotification === 'function') {
                showNotification('Error al cargar reportes: ' + error.message, 'error');
            }
        });
}

/**
 * Renderiza la tabla de reportes
 */
function renderReportesTable() {
    const tableBody = document.getElementById('reporteTableBody');
    if (!tableBody) return;
    
    tableBody.innerHTML = '';
    
    if (!reportData.asistencias || reportData.asistencias.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align: center; padding: 20px; color: #718096;">
                    <i class="fas fa-info-circle"></i> No se encontraron registros
                </td>
            </tr>
        `;
        return;
    }
    
    reportData.asistencias.forEach(asistencia => {
        // Formatear fecha
        const fecha = formatDate(asistencia.fecha);
        
        // Determinar clase para el estado
        let statusClass = '';
        const estado = asistencia.estado_entrada || '';
        
        switch (estado.toLowerCase()) {
            case 'a tiempo':
            case 'temprano':
                statusClass = 'status-temprano';
                break;
            case 'tardanza':
                statusClass = 'status-tardanza';
                break;
            case 'ausente':
                statusClass = 'status-ausente';
                break;
            default:
                statusClass = '';
        }
        
        tableBody.innerHTML += `
            <tr>
                <td><a href="#" onclick="openEmployeeDetails(${asistencia.codigo}); return false;">${asistencia.codigo}</a></td>
                <td>${asistencia.nombre || '-'}</td>
                <td>${asistencia.sede || '-'}</td>
                <td>${asistencia.establecimiento || '-'}</td>
                <td>${fecha}</td>
                <td>${asistencia.hora_entrada || '-'}</td>
                <td><span class="${statusClass}">${estado || 'No definido'}</span></td>
                <td>${asistencia.observacion || ''}</td>
                <td>
                    <button type="button" class="btn-icon" title="Ver detalles" 
                        onclick="openAttendanceDetails(${asistencia.ID_ASISTENCIA})">
                        <i class="fas fa-info-circle"></i>
                    </button>
                </td>
            </tr>
        `;
    });
}

/**
 * Actualiza la paginación
 */
function updatePagination(pagination) {
    if (!pagination) return;
    
    const infoContainer = document.getElementById('paginationInfo');
    const container = document.getElementById('paginationControls');
    
    if (!container || !infoContainer) return;
    
    // Guardar datos de paginación
    reportData.pagination.page = pagination.current_page;
    reportData.pagination.totalPages = pagination.total_pages;
    reportData.pagination.totalRecords = pagination.total_records;
    
    // Actualizar información de registros
    const start = ((pagination.current_page - 1) * pagination.limit) + 1;
    const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
    
    infoContainer.textContent = `Mostrando ${start} - ${end} de ${pagination.total_records} registros`;
    
    // Crear controles de paginación
    let html = '';
    
    // Botón anterior
    if (pagination.has_prev) {
        html += `<button class="pagination-button" onclick="goToPage(${pagination.current_page - 1})">
            <i class="fas fa-chevron-left"></i>
        </button>`;
    }

    // Páginas numeradas
    const maxButtons = 5;
    let startPage = Math.max(1, pagination.current_page - Math.floor(maxButtons / 2));
    let endPage = Math.min(pagination.total_pages, startPage + maxButtons - 1);
    
    if (endPage - startPage + 1 < maxButtons) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }
    
    if (startPage > 1) {
        html += `<button class="pagination-button" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="pagination-button ${i === pagination.current_page ? 'active' : ''}" 
                    onclick="goToPage(${i})">${i}</button>`;
    }

    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            html += `<span class="pagination-ellipsis">...</span>`;
        }
        html += `<button class="pagination-button" onclick="goToPage(${pagination.total_pages})">${pagination.total_pages}</button>`;
    }

    // Botón siguiente
    if (pagination.has_next) {
        html += `<button class="pagination-button" onclick="goToPage(${pagination.current_page + 1})">
            <i class="fas fa-chevron-right"></i>
        </button>`;
    }
    
    container.innerHTML = html;
}

/**
 * Navega a una página específica
 * @param {number} page - Número de página a mostrar
 */
function goToPage(page) {
    if (page >= 1 && page <= reportData.pagination.totalPages && page !== reportData.pagination.page) {
        reportData.pagination.page = page;
        loadReportes();
    }
}

/**
 * Exporta el reporte a Excel
 */
function exportReportToExcel() {
    // Construir parámetros de la consulta
    const params = new URLSearchParams();
    
    // Agregar filtros a los parámetros
    Object.entries(reportData.filtros).forEach(([key, value]) => {
        if (value) {
            params.append(key, value);
        }
    });
    
    // Redireccionar a la URL de exportación
    window.location.href = `api/reports/export-excel.php?${params.toString()}`;
}

/**
 * Formatea una fecha YYYY-MM-DD a formato DD/MM/YYYY
 * @param {string} dateStr - Fecha en formato YYYY-MM-DD
 * @returns {string} - Fecha formateada
 */
function formatDate(dateStr) {
    if (!dateStr) return '';
    
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

/**
 * Muestra una notificación si no está disponible el manejador de modales
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo de notificación
 */
function showNotification(message, type = 'info') {
    // Si la función está definida en reports_modals.js, usarla
    if (window.showNotification && typeof window.showNotification === 'function') {
        window.showNotification(message, type);
        return;
    }
    
    // Implementación simple como fallback
    console.log(`[${type.toUpperCase()}]: ${message}`);
    
    // Crear notificación temporal
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.backgroundColor = 'white';
    notification.style.padding = '15px 20px';
    notification.style.borderRadius = '5px';
    notification.style.boxShadow = '0 3px 10px rgba(0,0,0,0.2)';
    notification.style.zIndex = '9999';
    notification.style.borderLeft = `5px solid ${type === 'error' ? '#e53e3e' : '#2B7DE9'}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        document.body.removeChild(notification);
    }, 5000);
}