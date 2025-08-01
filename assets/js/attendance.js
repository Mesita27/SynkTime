// ===========================================================================
// SISTEMA DE ASISTENCIAS CON PAGINACIÓN AJAX - VERSIÓN ADAPTADA
// Integrado con las mejoras de control de horarios múltiples por trabajador
// ===========================================================================

// Variables globales para paginación
let currentPage = 1;
let currentLimit = 10;
let totalPages = 1;
let currentFilters = {};

// Configuración de límites disponibles
const AVAILABLE_LIMITS = [10, 15, 20, 30, 40, 50];

// Variables existentes
let empleadoSeleccionado = null;
let horarioSeleccionado = null;
let tipoRegistroSeleccionado = null;
let imageBase64 = '';
let autoRefreshTimer;
let observacionIdAsistencia = null;
let observacionTipo = null;

// ===========================================================================
// 1. INICIALIZACIÓN Y CONFIGURACIÓN GENERAL
// ===========================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes principales
    initializeAttendancePagination();
    cargarFiltros();
    loadAttendanceDay();
    
    // Iniciar actualización automática cada 30 minutos
    startAutoRefresh();
    
    // Configurar eventos del modal de registro
    const btnBuscarCodigoRegistro = document.getElementById('btnBuscarCodigoRegistro');
    const codigoRegistroBusqueda = document.getElementById('codigoRegistroBusqueda');
    
    if (btnBuscarCodigoRegistro && codigoRegistroBusqueda) {
        btnBuscarCodigoRegistro.onclick = cargarEmpleadosParaRegistro;
        codigoRegistroBusqueda.addEventListener('keyup', function(e) {
            if (e.key === "Enter") cargarEmpleadosParaRegistro();
        });
    }
    
    // Configurar cierre de modales con clic fuera o ESC
    setupModalBehaviors();
    
    // Configurar botones del modal de foto
    setupPhotoModalButtons();
    
    // Agregar evento para cuando la página pierde el foco
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Página no visible, pausamos la actualización para ahorrar recursos
            stopAutoRefresh();
        } else {
            // Página visible de nuevo, actualizamos datos y reiniciamos temporizador
            loadAttendanceDay();
            startAutoRefresh();
        }
    });

    const textarea = document.getElementById('observacionTexto');
    if (textarea) {
        textarea.addEventListener('input', updateCharCounter);
    }

    

});

// ===========================================================================
// 2. SISTEMA DE PAGINACIÓN
// ===========================================================================
function initializeAttendancePagination() {
    if (document.getElementById('attendancePaginationControls')) return;

    const limitContainer = document.createElement('div');
    limitContainer.className = 'pagination-controls';
    limitContainer.id = 'attendancePaginationControls';
    limitContainer.innerHTML = `
        <div class="limit-selector">
            <label for="attendanceLimitSelector">Mostrar:</label>
            <select id="attendanceLimitSelector" class="form-control limit-select">
                ${AVAILABLE_LIMITS.map(limit => 
                    `<option value="${limit}" ${limit === currentLimit ? 'selected' : ''}>${limit} registros</option>`
                ).join('')}
            </select>
        </div>
        <div class="pagination-info">
            <span id="attendancePaginationInfo">Cargando...</span>
        </div>
        <div class="pagination-buttons" id="attendancePaginationButtons">
            <!-- Los botones se generan dinámicamente -->
        </div>
    `;
    
    const tableContainer = document.querySelector('.attendance-table-container');
    tableContainer.parentNode.insertBefore(limitContainer, tableContainer);
    
    // Configurar evento del selector de límite
    setupPaginationEventListeners();
}

function setupPaginationEventListeners() {
    const limitSelector = document.getElementById('attendanceLimitSelector');
    if (limitSelector) {
        limitSelector.addEventListener('change', function() {
            currentLimit = parseInt(this.value);
            currentPage = 1;
            loadAttendanceDay();
        });
    }
}

function updateAttendanceFiltersFromForm() {
    currentFilters = {
        sede: document.getElementById('filtro_sede')?.value || '',
        establecimiento: document.getElementById('filtro_establecimiento')?.value || '',
        codigo: document.getElementById('codigoBusqueda')?.value?.trim() || ''
    };
    
    // Remover filtros vacíos
    Object.keys(currentFilters).forEach(key => {
        if (!currentFilters[key]) {
            delete currentFilters[key];
        }
    });
}

function goToAttendancePage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadAttendanceDay();
    }
}

// ===========================================================================
// 3. CONFIGURACIÓN DE MODALES (EXISTENTE MANTENIDA)
// ===========================================================================
function setupModalBehaviors() {
    // Cerrar modales al hacer click fuera
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('mousedown', function(e) {
            if (e.target === this) {
                const modalId = this.id;
                
                if (modalId === 'attendanceRegisterModal') {
                    closeAttendanceRegisterModal();
                } else if (modalId === 'attendancePhotoModal') {
                    closeAttendancePhotoModal();
                } else if (modalId === 'photoModal') {
                    closePhotoModal();
                }
            }
        });
    });
    
    // Cerrar modales con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const modalId = modal.id;
                
                if (modalId === 'attendanceRegisterModal') {
                    closeAttendanceRegisterModal();
                } else if (modalId === 'attendancePhotoModal') {
                    closeAttendancePhotoModal();
                } else if (modalId === 'photoModal') {
                    closePhotoModal();
                }
            });
        }
    });
}

function setupPhotoModalButtons() {
    // Botón para tomar foto
    const takePhotoBtn = document.getElementById('takePhotoBtn');
    if (takePhotoBtn) {
        takePhotoBtn.onclick = takePhoto;
    }
    
    // Botón para guardar asistencia
    const saveAttendanceBtn = document.getElementById('saveAttendanceBtn');
    if (saveAttendanceBtn) {
        saveAttendanceBtn.onclick = saveAttendance;
    }
}

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
    let notification = document.getElementById('appNotification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'appNotification';
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    notification.querySelector('.notification-close').addEventListener('click', function() {
        hideNotification();
    });
    
    const timeout = setTimeout(() => {
        hideNotification();
    }, 5000);
    
    function hideNotification() {
        clearTimeout(timeout);
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}

// ===========================================================================
// 4. FUNCIONES DE FORMATEO Y UTILIDADES
// ===========================================================================
function formatDate(dateStr) {
    if (!dateStr) return '-';
    
    // Dividir la fecha en sus componentes
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr; // Si no tiene el formato esperado, devolver la original
    
    // Crear objeto Date manteniendo la fecha exactamente como viene
    // Nota: el mes en JavaScript se indexa desde 0 (enero = 0)
    const year = parseInt(parts[0]);
    const month = parseInt(parts[1]) - 1; // Restar 1 al mes
    const day = parseInt(parts[2]);
    
    // Crear fecha SIN considerar la zona horaria local (usando UTC)
    // Esto evita que JavaScript ajuste la fecha por la zona horaria
    const fecha = new Date(Date.UTC(year, month, day));
    
    // Formatear según la localización, pero EXTRAYENDO los componentes de fecha para evitar problemas de zona horaria
    return `${day.toString().padStart(2, '0')}/${(month + 1).toString().padStart(2, '0')}/${year}`;
}

function formatTime(timeStr) {
    if (!timeStr) return '-';
    return timeStr;
}

// ===========================================================================
// 5. FILTROS PRINCIPALES (SIN FECHAS)
// ===========================================================================
async function cargarFiltros() {
    // Cargar sedes
    let sedes = await fetch('api/get-sedes.php').then(r => r.json());
    let sedeSel = document.getElementById('filtro_sede');
    sedeSel.innerHTML = '<option value="">Todos</option>';
    sedes.sedes.forEach(s => {
        sedeSel.innerHTML += `<option value="${s.ID_SEDE}">${s.NOMBRE}</option>`;
    });
    sedeSel.onchange = cargarEstablecimientosFiltro2;
    await cargarEstablecimientosFiltro2();
    
    // Configurar eventos
    document.getElementById('btnBuscarCodigo').addEventListener('click', function() {
        currentPage = 1;
        updateAttendanceFiltersFromForm();
        loadAttendanceDay();
    });
    
    document.getElementById('btnLimpiar').addEventListener('click', limpiarFiltros);
    document.getElementById('codigoBusqueda').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            currentPage = 1;
            updateAttendanceFiltersFromForm();
            loadAttendanceDay();
        }
    });
}

function buscarAsistencias() {
    currentPage = 1;
    updateAttendanceFiltersFromForm();
    loadAttendanceDay();
}

function limpiarFiltros() {
    document.getElementById('filtro_sede').value = '';
    document.getElementById('filtro_establecimiento').value = '';
    document.getElementById('codigoBusqueda').value = '';
    currentFilters = {};
    currentPage = 1;
    cargarEstablecimientosFiltro2();
    loadAttendanceDay();
}

async function cargarEstablecimientosFiltro2() {
    let sedeId = document.getElementById('filtro_sede').value;
    let estSel = document.getElementById('filtro_establecimiento');
    estSel.innerHTML = '<option value="">Todos</option>';
    let url = 'api/get-establecimientos.php';
    if (sedeId) url += '?sede_id=' + encodeURIComponent(sedeId);
    let res = await fetch(url).then(r => r.json());
    if (res.establecimientos) {
        res.establecimientos.forEach(e => {
            estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
        });
    }
    
    // Solo recargar si no es la carga inicial
    if (currentPage > 0) {
        currentPage = 1;
        updateAttendanceFiltersFromForm();
        loadAttendanceDay();
    }
}

// ===========================================================================
// 6. AUTO-REFRESH Y TABLA PRINCIPAL CON PAGINACIÓN 
// ===========================================================================

// Función para iniciar la actualización automática cada 30 minutos
function startAutoRefresh() {
    // Limpiar cualquier temporizador existente
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
    }
    
    // Establecer intervalo de actualización (30 minutos = 1800000 ms)
    autoRefreshTimer = setInterval(function() {
        loadAttendanceDay();
        console.log('Actualización automática de asistencias: ' + new Date().toLocaleString('es-CO'));
    }, 1800000); // 30 minutos
}

// Función para detener la actualización automática
function stopAutoRefresh() {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }
}

function loadAttendanceDay() {
    updateAttendanceFiltersFromForm();
    
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '<tr><td colspan="9" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</td></tr>';
    
    // Construir URL con paginación y filtros
    const params = new URLSearchParams({
        page: currentPage,
        limit: currentLimit,
        preserve_dates: 'true', // Añadimos este parámetro para indicar al backend que preserve las fechas
        ...currentFilters
    });
    
    const url = `api/attendance/list.php?${params.toString()}`;
    
    // Agregar indicador visual de búsqueda por código
    const codigoInput = document.getElementById('codigoBusqueda');
    if (currentFilters.codigo) {
        codigoInput.classList.add('searching');
    } else {
        codigoInput.classList.remove('searching');
    }

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Actualizar el indicador de última actualización con la hora de Colombia
            actualizarIndicadorTiempo(data.server_time || null);
            
            if (data.success) {
                renderAttendanceTable(data.data);
                updateAttendancePaginationInfo(data.pagination);
                renderAttendancePaginationButtons(data.pagination);
            } else {
                throw new Error(data.message || 'Error al cargar datos');
            }
        })
        .catch(error => {
            console.error('Error al cargar asistencias:', error);
            tbody.innerHTML = '<tr><td colspan="9" class="error-text">Error al cargar datos. Intente de nuevo.</td></tr>';
        });
}

/**
 * Actualiza el indicador de tiempo con la hora correcta del servidor
 * @param {string|null} serverTime - Timestamp del servidor (si está disponible)
 */
function actualizarIndicadorTiempo(serverTime) {
    const ahora = serverTime ? new Date(serverTime) : new Date();
    
    // Formatear fecha y hora con el formato local de Colombia
    const options = { 
        day: 'numeric', 
        month: 'numeric', 
        year: 'numeric',
        hour: 'numeric', 
        minute: 'numeric',
        second: 'numeric',
        hour12: true 
    };
    
    const fechaHoraFormateada = ahora.toLocaleDateString('es-CO', options);
    
    const infoActualizacion = document.getElementById('lastUpdateInfo');
    if (infoActualizacion) {
        infoActualizacion.textContent = `Última actualización: ${fechaHoraFormateada}`;
    } else {
        const infoElement = document.createElement('div');
        infoElement.id = 'lastUpdateInfo';
        infoElement.className = 'last-update-info';
        infoElement.textContent = `Última actualización: ${fechaHoraFormateada}`;
        
        const header = document.querySelector('.attendance-header');
        if (header) {
            const actionDiv = header.querySelector('.attendance-actions') || header;
            actionDiv.prepend(infoElement);
        }
    }
}

function renderAttendanceTable(data) {
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '';
    
    if (!data || !data.length) {
        tbody.innerHTML = '<tr><td colspan="10" class="no-data-text">No se encontraron asistencias para las últimas 20 horas</td></tr>';
        return;
    }
    
    // Organizar los datos por empleado, horario y fecha para poder mostrarlos agrupados
    const asistenciasAgrupadas = {};
    
    data.forEach(asistencia => {
        // Crear una clave única para cada combinación de empleado, horario y fecha
        const key = `${asistencia.codigo_empleado}_${asistencia.ID_HORARIO || 'default'}_${asistencia.fecha}`;
        
        if (!asistenciasAgrupadas[key]) {
            asistenciasAgrupadas[key] = {
                ID_EMPLEADO: asistencia.codigo_empleado,
                NOMBRE: asistencia.nombre_empleado,
                establecimiento: asistencia.establecimiento,
                sede: asistencia.sede,
                FECHA: asistencia.fecha,
                HORARIO_NOMBRE: asistencia.HORARIO_NOMBRE || 'Sin horario',
                HORA_ENTRADA_PROGRAMADA: asistencia.HORA_ENTRADA,
                HORA_SALIDA_PROGRAMADA: asistencia.HORA_SALIDA,
                ID_HORARIO: asistencia.ID_HORARIO,
                ENTRADA_HORA: null,
                ENTRADA_TARDANZA: null,
                ENTRADA_ID: null,
                ENTRADA_FOTO: null,
                SALIDA_HORA: null,
                SALIDA_TARDANZA: null,
                SALIDA_ID: null,
                SALIDA_FOTO: null
            };
        }
        
        // Asignar datos según el tipo (entrada o salida)
        if (asistencia.tipo === 'ENTRADA') {
            asistenciasAgrupadas[key].ENTRADA_HORA = asistencia.hora;
            asistenciasAgrupadas[key].ENTRADA_TARDANZA = asistencia.tardanza;
            asistenciasAgrupadas[key].ENTRADA_ID = asistencia.id;
            asistenciasAgrupadas[key].ENTRADA_FOTO = asistencia.foto;
        } else if (asistencia.tipo === 'SALIDA') {
            asistenciasAgrupadas[key].SALIDA_HORA = asistencia.hora;
            asistenciasAgrupadas[key].SALIDA_TARDANZA = asistencia.tardanza;
            asistenciasAgrupadas[key].SALIDA_ID = asistencia.id;
            asistenciasAgrupadas[key].SALIDA_FOTO = asistencia.foto;
        }
    });
    
    // Convertir el objeto en array y calcular estados
    const asistenciasFinal = Object.values(asistenciasAgrupadas).map(att => {
        // Calcular estado de entrada
        let estadoEntrada = '--';
        if (att.ENTRADA_HORA && att.HORA_ENTRADA_PROGRAMADA) {
            const entrada_prog = new Date(`${att.FECHA}T${att.HORA_ENTRADA_PROGRAMADA}`);
            const entrada_real = new Date(`${att.FECHA}T${att.ENTRADA_HORA}`);
            
            if (entrada_real < entrada_prog) {
                estadoEntrada = 'Temprano';
            } else if (entrada_real <= new Date(entrada_prog.getTime() + (att.TOLERANCIA || 0) * 60000)) {
                estadoEntrada = 'Puntual';
            } else {
                estadoEntrada = 'Tardanza';
            }
        }
        
        // Calcular estado de salida
        let estadoSalida = '--';
        if (att.SALIDA_HORA && att.HORA_SALIDA_PROGRAMADA) {
            const salida_prog = new Date(`${att.FECHA}T${att.HORA_SALIDA_PROGRAMADA}`);
            const salida_real = new Date(`${att.FECHA}T${att.SALIDA_HORA}`);
            
            if (salida_real < salida_prog) {
                estadoSalida = 'Temprano';
            } else {
                estadoSalida = 'Normal';
            }
        }
        
        return {
            ...att,
            ENTRADA_ESTADO: estadoEntrada,
            SALIDA_ESTADO: estadoSalida
        };
    });
    
    // Ordenar por fecha y hora más reciente
    asistenciasFinal.sort((a, b) => {
        const fecha_a = new Date(a.FECHA);
        const fecha_b = new Date(b.FECHA);
        
        if (fecha_a.getTime() !== fecha_b.getTime()) {
            return fecha_b.getTime() - fecha_a.getTime(); // Ordenar por fecha descendente
        }
        
        // Si tienen la misma fecha, ordenar por la hora de entrada/salida más reciente
        const hora_a = a.ENTRADA_HORA ? new Date(`${a.FECHA}T${a.ENTRADA_HORA}`).getTime() : 
                      (a.SALIDA_HORA ? new Date(`${a.FECHA}T${a.SALIDA_HORA}`).getTime() : 0);
        
        const hora_b = b.ENTRADA_HORA ? new Date(`${b.FECHA}T${b.ENTRADA_HORA}`).getTime() : 
                      (b.SALIDA_HORA ? new Date(`${b.FECHA}T${b.SALIDA_HORA}`).getTime() : 0);
        
        return hora_b - hora_a;
    });
    
    // Renderizar la tabla
    asistenciasFinal.forEach(att => {
        let accion = '';
        
        // Si hay entrada pero no salida para este horario, mostrar botón de salida
        if (att.ENTRADA_HORA && !att.SALIDA_HORA) {
            accion = `<button type="button" class="btn-primary btn-sm" onclick="registrarSalida(${att.ID_EMPLEADO}, '${att.FECHA}', ${att.ID_HORARIO})">
                        <i class="fas fa-sign-out-alt"></i> Registrar Salida
                      </button>`;
        }
        
        // Botones de observación para entrada y salida
        let observacionEntradaBtn = att.ENTRADA_ID ? 
            `<button type="button" class="btn-icon btn-comment" 
                title="${att.ENTRADA_OBSERVACION ? 'Editar observación' : 'Agregar observación'}" 
                onclick="openObservationModal(${att.ENTRADA_ID}, 'ENTRADA', '${att.NOMBRE.replace("'", "\\'")}', '${att.FECHA}', '${att.ENTRADA_HORA}', '${(att.ENTRADA_OBSERVACION || '').replace("'", "\\'")}')">
                <i class="fas fa-${att.ENTRADA_OBSERVACION ? 'edit' : 'comment-medical'}"></i>
             </button>` : '';
        
        let observacionSalidaBtn = att.SALIDA_ID ? 
            `<button type="button" class="btn-icon btn-comment" 
                title="${att.SALIDA_OBSERVACION ? 'Editar observación' : 'Agregar observación'}" 
                onclick="openObservationModal(${att.SALIDA_ID}, 'SALIDA', '${att.NOMBRE.replace("'", "\\'")}', '${att.FECHA}', '${att.SALIDA_HORA}', '${(att.SALIDA_OBSERVACION || '').replace("'", "\\'")}')">
                <i class="fas fa-${att.SALIDA_OBSERVACION ? 'edit' : 'comment-medical'}"></i>
             </button>` : '';
        
        // Formatear fotos con clase para hacerlas ampliables
        let fotoEntrada = att.ENTRADA_FOTO ? 
            `<img src="uploads/${att.ENTRADA_FOTO}" alt="Foto de entrada" class="asistencia-foto" 
             onclick="openPhotoModal('uploads/${att.ENTRADA_FOTO}', '${att.NOMBRE}')">` : 
            '-';
            
        let fotoSalida = att.SALIDA_FOTO ? 
            `<img src="uploads/${att.SALIDA_FOTO}" alt="Foto de salida" class="asistencia-foto" 
             onclick="openPhotoModal('uploads/${att.SALIDA_FOTO}', '${att.NOMBRE}')">` : 
            '-';
        
        // Formatear horarios
        let horarioEntrada = att.ENTRADA_HORA || '-';
        let horarioSalida = att.SALIDA_HORA || '-';
        
        // Mostrar el horario programado
        let horarioProgramado = `
            <strong>${att.HORARIO_NOMBRE || 'Sin nombre'}</strong><br>
            <span class="programado-info">Programado: ${att.HORA_ENTRADA_PROGRAMADA || '--:--'} - ${att.HORA_SALIDA_PROGRAMADA || '--:--'}</span><br>
            <strong>Entrada:</strong> ${horarioEntrada}<br>
            <strong>Salida:</strong> ${horarioSalida}
        `;
        
        // Mostrar observaciones si existen
        const observacionEntrada = att.ENTRADA_OBSERVACION ? 
            `<div class="observacion-badge" title="${att.ENTRADA_OBSERVACION}">
                <i class="fas fa-comment"></i> ${truncateText(att.ENTRADA_OBSERVACION, 20)}
             </div>` : '';
        
        const observacionSalida = att.SALIDA_OBSERVACION ? 
            `<div class="observacion-badge" title="${att.SALIDA_OBSERVACION}">
                <i class="fas fa-comment"></i> ${truncateText(att.SALIDA_OBSERVACION, 20)}
             </div>` : '';
        
        // Resaltar la fila si coincide con el código buscado
        const highlightClass = currentFilters.codigo && att.ID_EMPLEADO == currentFilters.codigo ? 'highlighted-row' : '';
        
        tbody.innerHTML += `
            <tr class="${highlightClass}">
                <td>${att.ID_EMPLEADO}</td>
                <td>${att.NOMBRE}</td>
                <td>${att.establecimiento}</td>
                <td>${att.sede}</td>
                <td>${formatDate(att.FECHA)}</td>
                <td>${horarioProgramado}</td>
                <td>
                    <strong>Entrada:</strong> <span class="status-${att.ENTRADA_ESTADO?.toLowerCase()}">${att.ENTRADA_ESTADO || '--'}</span>
                    ${observacionEntrada}<br>
                    <strong>Salida:</strong> <span class="status-${att.SALIDA_ESTADO?.toLowerCase()}">${att.SALIDA_ESTADO || '--'}</span>
                    ${observacionSalida}
                </td>
                <td>
                    <strong>Entrada:</strong> ${fotoEntrada}<br>
                    <strong>Salida:</strong> ${fotoSalida}
                </td>
                <td>
                    <div class="btn-actions">
                        ${observacionEntradaBtn}
                        ${observacionSalidaBtn}
                        ${accion}
                    </div>
                </td>
            </tr>
        `;
    });
}

/**
 * Trunca un texto si supera la longitud máxima
 * @param {string} text - Texto a truncar
 * @param {number} maxLength - Longitud máxima
 * @returns {string} - Texto truncado
 */
function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

// ===========================================================================
// 7. CONTROLES DE PAGINACIÓN
// ===========================================================================
function updateAttendancePaginationInfo(pagination) {
    const info = document.getElementById('attendancePaginationInfo');
    if (info && pagination) {
        const start = ((pagination.current_page - 1) * pagination.limit) + 1;
        const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
        
        info.textContent = `Mostrando ${start} - ${end} de ${pagination.total_records} asistencias`;
        
        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;
    }
}

function renderAttendancePaginationButtons(pagination) {
    const container = document.getElementById('attendancePaginationButtons');
    if (!container || !pagination) return;

    let buttonsHTML = '';
    
    // Botón anterior
    if (pagination.has_prev) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToAttendancePage(${pagination.current_page - 1})">
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
        buttonsHTML += `<button class="pagination-btn" onclick="goToAttendancePage(1)">1</button>`;
        if (startPage > 2) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        buttonsHTML += `<button class="pagination-btn ${i === pagination.current_page ? 'active' : ''}" 
                            onclick="goToAttendancePage(${i})">${i}</button>`;
    }

    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            buttonsHTML += `<span class="pagination-ellipsis">...</span>`;
        }
        buttonsHTML += `<button class="pagination-btn" onclick="goToAttendancePage(${pagination.total_pages})">${pagination.total_pages}</button>`;
    }

    // Botón siguiente
    if (pagination.has_next) {
        buttonsHTML += `<button class="pagination-btn" onclick="goToAttendancePage(${pagination.current_page + 1})">
            Siguiente <i class="fas fa-chevron-right"></i>
        </button>`;
    }

    container.innerHTML = buttonsHTML;
}

// ===========================================================================
// 8. MODAL PARA REGISTRAR ASISTENCIA (EXISTENTE MANTENIDA)
// ===========================================================================
window.openAttendanceRegisterModal = function() {
    document.getElementById('attendanceRegisterModal').classList.add('show');
    
    const fechaActual = new Date().toLocaleDateString('es-CO');
    const fechaElement = document.getElementById('reg_fecha');
    if (fechaElement) {
        fechaElement.textContent = fechaActual;
    }
    
    inicializarModalRegistro();
};

window.closeAttendanceRegisterModal = function() {
    document.getElementById('attendanceRegisterModal').classList.remove('show');
};

async function inicializarModalRegistro() {
    try {
        await cargarSedesRegistro();
        await cargarEstablecimientosRegistro();
        await cargarEmpleadosParaRegistro();
        configureRegistroEventListeners();
    } catch (error) {
        console.error('Error al inicializar modal:', error);
    }
}

function configureRegistroEventListeners() {
    const btnBuscar = document.getElementById('btnBuscarCodigoRegistro');
    if (btnBuscar) {
        btnBuscar.onclick = function(e) {
            e.preventDefault();
            cargarEmpleadosParaRegistro();
        };
    }
    
    const codigoBusqueda = document.getElementById('codigoRegistroBusqueda');
    if (codigoBusqueda) {
        codigoBusqueda.onkeypress = function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cargarEmpleadosParaRegistro();
            }
        };
    }
    
    const sedeSel = document.getElementById('reg_sede');
    if (sedeSel) {
        sedeSel.onchange = async function() {
            await cargarEstablecimientosRegistro();
            cargarEmpleadosParaRegistro();
        };
    }
    
    const estSel = document.getElementById('reg_establecimiento');
    if (estSel) {
        estSel.onchange = function() {
            cargarEmpleadosParaRegistro();
        };
    }
}

// Cargar sedes en el modal de registro
async function cargarSedesRegistro() {
    try {
        const response = await fetch('api/get-sedes.php');
        if (!response.ok) {
            throw new Error(`Error al cargar sedes: ${response.status}`);
        }
        
        const data = await response.json();
        const sedeSel = document.getElementById('reg_sede');
        if (!sedeSel) {
            console.error('Elemento reg_sede no encontrado');
            return;
        }
        
        sedeSel.innerHTML = '<option value="">Todas</option>';
        
        if (data.sedes && data.sedes.length > 0) {
            data.sedes.forEach(s => {
                sedeSel.innerHTML += `<option value="${s.ID_SEDE}">${s.NOMBRE}</option>`;
            });
        }
    } catch (error) {
        console.error('Error al cargar sedes:', error);
    }
}

// Cargar establecimientos en el modal de registro
async function cargarEstablecimientosRegistro() {
    try {
        const sedeId = document.getElementById('reg_sede')?.value || '';
        let url = 'api/get-establecimientos.php';
        if (sedeId) {
            url += `?sede_id=${sedeId}`;
        }
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Error al cargar establecimientos: ${response.status}`);
        }
        
        const data = await response.json();
        const estSel = document.getElementById('reg_establecimiento');
        if (!estSel) {
            console.error('Elemento reg_establecimiento no encontrado');
            return;
        }
        
        estSel.innerHTML = '<option value="">Todos</option>';
        
        if (data.establecimientos && data.establecimientos.length > 0) {
            data.establecimientos.forEach(e => {
                estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
            });
        }
    } catch (error) {
        console.error('Error al cargar establecimientos:', error);
    }
}

// Cargar empleados disponibles 
async function cargarEmpleadosParaRegistro() {
    const tbody = document.getElementById('attendanceRegisterTableBody');
    if (!tbody) {
        console.error('Elemento attendanceRegisterTableBody no encontrado');
        return;
    }
    
    const sede = document.getElementById('reg_sede')?.value || '';
    const establecimiento = document.getElementById('reg_establecimiento')?.value || '';
    const codigo = document.getElementById('codigoRegistroBusqueda')?.value?.trim() || '';
    
    tbody.innerHTML = '<tr><td colspan="5" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando empleados disponibles...</td></tr>';
    
    const apiPath = 'api/attendance/employees-available.php';
    const params = new URLSearchParams();
    if (sede) params.append('sede', sede);
    if (establecimiento) params.append('establecimiento', establecimiento);
    if (codigo) params.append('codigo', codigo);
    params.append('_t', Date.now());
    
    const url = `${apiPath}?${params.toString()}`;
    
    try {
        console.log('Consultando API:', url);
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const responseText = await response.text();
        let data;
        
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Respuesta no es JSON válido:', responseText.substring(0, 100));
            throw new Error('Formato de respuesta inválido');
        }
        
        if (!data.success) {
            throw new Error(data.message || 'Error en la respuesta del servidor');
        }
        
        tbody.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="no-data-text">No hay empleados disponibles para registro de asistencia</td></tr>';
            return;
        }
        
        data.data.forEach(emp => {
            tbody.innerHTML += `
                <tr>
                    <td>${emp.ID_EMPLEADO}</td>
                    <td>${emp.NOMBRE} ${emp.APELLIDO}</td>
                    <td>${emp.ESTABLECIMIENTO || ''}</td>
                    <td>${emp.SEDE || ''}</td>
                    <td>
                        <button type="button" class="btn-primary btn-sm" onclick="openBiometricVerificationModal(${emp.ID_EMPLEADO}, '${emp.NOMBRE} ${emp.APELLIDO}')">
                            <i class="fas fa-shield-alt"></i> Verificar
                        </button>
                        <button type="button" class="btn-secondary btn-sm" onclick="openAttendancePhotoModal(${emp.ID_EMPLEADO}, '${emp.NOMBRE} ${emp.APELLIDO}')">
                            <i class="fas fa-camera"></i> Tradicional
                        </button>
                    </td>
                </tr>
            `;
        });
        
    } catch (error) {
        console.error('Error al cargar empleados:', error);
        
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="error-text">
                    <div>Error al cargar datos: ${error.message || 'Error desconocido'}</div>
                    <button onclick="cargarEmpleadosParaRegistro()" class="btn-sm btn-secondary mt-2">
                        <i class="fas fa-redo"></i> Reintentar
                    </button>
                </td>
            </tr>
        `;
    }
}

// ===========================================================================
// 9. MODAL DE FOTO PARA REGISTRO (EXISTENTE MANTENIDA)
// ===========================================================================
window.openAttendancePhotoModal = function(id_empleado) {
    empleadoSeleccionado = id_empleado;
    document.getElementById('attendancePhotoModal').classList.add('show');
    const video = document.getElementById('video');
    navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
        video.srcObject = stream;
    }).catch(err => {
        console.error("Error accediendo a la cámara:", err);
        alert("Error al acceder a la cámara. Por favor verifique los permisos.");
    });
    
    document.getElementById('canvas').style.display = 'none';
    document.getElementById('photoPreview').innerHTML = '';
    document.getElementById('saveAttendanceBtn').disabled = true;
    
    document.getElementById('takePhotoBtn').style.display = 'inline-flex';
    document.getElementById('saveAttendanceBtn').style.display = 'inline-flex';
};

window.closeAttendancePhotoModal = function() {
    document.getElementById('attendancePhotoModal').classList.remove('show');
    const video = document.getElementById('video');
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
};

function takePhoto() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    
    if (!video.srcObject || !video.srcObject.active) {
        alert("La cámara no está activa. Por favor recargue la página e intente de nuevo.");
        return;
    }
    
    canvas.style.display = 'none';
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    imageBase64 = canvas.toDataURL('image/jpeg');
    
    document.getElementById('photoPreview').innerHTML = `<img src="${imageBase64}" alt="Vista previa">`;
    
    const saveBtn = document.getElementById('saveAttendanceBtn');
    saveBtn.disabled = false;
    saveBtn.style.display = 'inline-flex';
}

function saveAttendance() {
    if (!imageBase64) {
        alert("Debe tomar una foto primero.");
        return;
    }
    
    const saveBtn = document.getElementById('saveAttendanceBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;
    
    fetch('api/attendance/register.php', {
        method: 'POST',
        body: new URLSearchParams({
            id_empleado: empleadoSeleccionado,
            image_data: imageBase64
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showNotification(`${res.tipo === 'ENTRADA' ? 'Entrada' : 'Salida'} registrada correctamente`, 'success');
            
            closeAttendancePhotoModal();
            closeAttendanceRegisterModal();
            
            loadAttendanceDay();
        } else {
            showNotification('Error: ' + (res.message || 'No se pudo registrar la asistencia.'), 'error');
            
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error al registrar asistencia:', error);
        showNotification('Error al comunicarse con el servidor. Por favor intente de nuevo.', 'error');
        
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// ===========================================================================
// 10. REGISTRAR SALIDA
// ===========================================================================
window.registrarSalida = function(id_empleado, fecha, id_horario) {
    if(!confirm("¿Está seguro de registrar la salida?")) return;
    
    fetch('api/attendance/register-salida.php', {
        method: 'POST',
        body: new URLSearchParams({
            id_empleado: id_empleado,
            fecha: fecha,
            id_horario: id_horario
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showNotification('Salida registrada correctamente', 'success');
            loadAttendanceDay();
        } else {
            showNotification('Error: ' + (res.message || 'No se pudo registrar la salida.'), 'error');
        }
    })
    .catch(error => {
        console.error('Error al registrar salida:', error);
        showNotification('Error al comunicarse con el servidor', 'error');
    });
};

// ===========================================================================
// 11. FUNCIONALIDAD DE AMPLIACIÓN DE FOTOS
// ===========================================================================
window.openPhotoModal = function(photoUrl, nombreEmpleado = '') {
    let modal = document.getElementById('photoModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'photoModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="photo-modal-content">
                <h3 id="photoModalTitle"></h3>
                <img id="photoModalImage" src="" alt="Foto de asistencia">
                <button class="photo-modal-close" onclick="closePhotoModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    const modalImg = document.getElementById('photoModalImage');
    const modalTitle = document.getElementById('photoModalTitle');
    
    modalImg.src = photoUrl;
    
    if (nombreEmpleado) {
        modalTitle.textContent = nombreEmpleado;
        modalTitle.style.display = 'block';
    } else {
        modalTitle.style.display = 'none';
    }
    
    modalImg.style.opacity = '0';
    modal.classList.add('show');
    
    modalImg.onload = function() {
        setTimeout(() => {
            modalImg.style.opacity = '1';
        }, 100);
    };
    
    if (modalImg.complete) {
        setTimeout(() => {
            modalImg.style.opacity = '1';
        }, 100);
    }
};

window.closePhotoModal = function() {
    const modal = document.getElementById('photoModal');
    if (!modal) return;
    
    const img = document.getElementById('photoModalImage');
    
    img.style.opacity = '0';
    
    setTimeout(() => {
        modal.classList.remove('show');
    }, 300);
};






function openObservationModal(idAsistencia, tipo, empleado, fecha, hora, observacionActual = '') {
    // Guardar datos en variables globales
    observacionIdAsistencia = idAsistencia;
    observacionTipo = tipo;
    
    // Actualizar el título del modal
    document.getElementById('observationModalTitle').textContent = 
        `${tipo === 'ENTRADA' ? 'Entrada' : 'Salida'} - Observación`;
    
    // Actualizar la información del modal
    document.getElementById('observationModalInfo').innerHTML = 
        `<strong>Empleado:</strong> ${empleado}<br>` +
        `<strong>Fecha:</strong> ${formatDate(fecha)}<br>` +
        `<strong>Hora ${tipo.toLowerCase()}:</strong> ${hora}`;
    
    // Establecer la observación actual (si existe)
    document.getElementById('observacionTexto').value = observacionActual || '';
    
    // Actualizar el contador de caracteres
    updateCharCounter();
    
    // Configurar los campos ocultos
    document.getElementById('observacionIdAsistencia').value = idAsistencia;
    document.getElementById('observacionTipo').value = tipo;
    
    // Mostrar el modal
    document.getElementById('observationModal').classList.add('show');
    
    // Enfocar el campo de texto
    setTimeout(() => {
        document.getElementById('observacionTexto').focus();
    }, 300);
}
/**
 * Cierra el modal de observaciones
 */
function closeObservationModal() {
    document.getElementById('observationModal').classList.remove('show');
    
    // Limpiar variables globales
    observacionIdAsistencia = null;
    observacionTipo = null;
}

/**
 * Actualiza el contador de caracteres
 */
function updateCharCounter() {
    const textarea = document.getElementById('observacionTexto');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        charCount.textContent = textarea.value.length;
        
        // Cambiar color si se acerca al límite
        if (textarea.value.length >= 180) {
            charCount.style.color = '#dc3545'; // Rojo cuando está cerca del límite
        } else {
            charCount.style.color = ''; // Color por defecto
        }
    }
}
/**
 * Guarda la observación
 */
function saveObservation() {
    const idAsistencia = document.getElementById('observacionIdAsistencia').value;
    const observacion = document.getElementById('observacionTexto').value;
    
    if (!idAsistencia) {
        showNotification('Error: ID de asistencia no válido', 'error');
        return;
    }
    
    // Mostrar indicador de carga
    const saveBtn = document.getElementById('btnSaveObservation');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;
    
    // Enviar solicitud al servidor
    fetch('api/attendance/update_observation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            id_asistencia: idAsistencia,
            observacion: observacion
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Observación guardada correctamente', 'success');
            closeObservationModal();
            
            // Recargar los datos para mostrar la observación actualizada
            loadAttendanceDay();
        } else {
            showNotification('Error: ' + (data.message || 'No se pudo guardar la observación'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error de comunicación con el servidor', 'error');
    })
    .finally(() => {
        // Restaurar el botón
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}