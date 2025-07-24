// ===========================================================================
// SISTEMA DE ASISTENCIAS - ARCHIVO PRINCIPAL
// Organizado en secciones para mejor mantenimiento
// ===========================================================================

// ===========================================================================
// 1. INICIALIZACIÓN Y CONFIGURACIÓN GENERAL
// ===========================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar componentes principales
    cargarFiltros();
    loadAttendanceDay();
    
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
});

// Configuración de eventos para cerrar modales
function setupModalBehaviors() {
    // Cerrar modales al hacer click fuera
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('mousedown', function(e) {
            if (e.target === this) {
                const modalId = this.id;
                
                // Llamar a la función específica de cierre según el modal
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

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
    // Crear elemento de notificación si no existe
    let notification = document.getElementById('appNotification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'appNotification';
        notification.className = 'notification';
        document.body.appendChild(notification);
    }
    
    // Configurar la notificación
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        </div>
    `;
    
    // Mostrar la notificación con animación
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Configurar el botón de cerrar
    notification.querySelector('.notification-close').addEventListener('click', function() {
        hideNotification();
    });
    
    // Auto-ocultar después de 5 segundos
    const timeout = setTimeout(() => {
        hideNotification();
    }, 5000);
    
    // Función para ocultar la notificación
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
// 2. FUNCIONES DE FORMATEO Y UTILIDADES
// ===========================================================================
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).replace(/\//g, '-');
}

// ===========================================================================
// 3. FILTROS PRINCIPALES DE LA TABLA DE ASISTENCIAS
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
        loadAttendanceDay();
    });
    
    document.getElementById('btnLimpiar').addEventListener('click', limpiarFiltros);
    document.getElementById('codigoBusqueda').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            loadAttendanceDay();
        }
    });
}

function buscarAsistencias() {
    loadAttendanceDay();
}

function limpiarFiltros() {
    document.getElementById('filtro_sede').value = '';
    document.getElementById('filtro_establecimiento').value = '';
    document.getElementById('codigoBusqueda').value = '';
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
    loadAttendanceDay();
}

// ===========================================================================
// 4. TABLA PRINCIPAL DE ASISTENCIAS
// ===========================================================================
function loadAttendanceDay() {
    const sede = document.getElementById('filtro_sede').value;
    const establecimiento = document.getElementById('filtro_establecimiento').value;
    const codigo = document.getElementById('codigoBusqueda').value.trim();
    
    // Mostrar indicador de carga
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '<tr><td colspan="9" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando datos...</td></tr>';
    
    // Construir URL con los parámetros
    let url = `api/attendance/list-day.php?`;
    if (sede) url += `sede=${encodeURIComponent(sede)}&`;
    if (establecimiento) url += `establecimiento=${encodeURIComponent(establecimiento)}&`;
    if (codigo) url += `codigo=${encodeURIComponent(codigo)}&`;
    
    // Agregar indicador visual de búsqueda por código
    if (codigo) {
        document.getElementById('codigoBusqueda').classList.add('searching');
    } else {
        document.getElementById('codigoBusqueda').classList.remove('searching');
    }

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            tbody.innerHTML = '';
            if (!data.success || !data.data || !data.data.length) {
                tbody.innerHTML = '<tr><td colspan="9" class="no-data-text">No se encontraron asistencias</td></tr>';
                return;
            }
            
            data.data.forEach(att => {
                let accion = '';
                if (!att.SALIDA_HORA) {
                    accion = `<button type="button" class="btn-primary btn-sm" onclick="registrarSalida(${att.ID_EMPLEADO}, '${att.FECHA}')">
                                <i class="fas fa-sign-out-alt"></i> Registrar Salida
                              </button>`;
                }
                
                // Formatear el estado
                let estadoEntrada = att.ENTRADA_ESTADO || '--';
                let estadoSalida = att.SALIDA_ESTADO || '--';
                
                // Formatear foto con clase para hacerla ampliable
                let fotoBtn = att.FOTO ? 
                    `<img src="uploads/${att.FOTO}" alt="Foto de asistencia" class="asistencia-foto" 
                     data-nombre="${att.NOMBRE} ${att.APELLIDO}" onclick="openPhotoModal('uploads/${att.FOTO}', '${att.NOMBRE} ${att.APELLIDO}')">` : 
                    '-';
                
                // Formatear horario
                let horarioEntrada = att.ENTRADA_HORA || '-';
                let horarioSalida = att.SALIDA_HORA || '-';
                
                // Resaltar la fila si coincide con el código buscado
                const highlightClass = codigo && att.ID_EMPLEADO == codigo ? 'highlighted-row' : '';
                
                tbody.innerHTML += `
                    <tr class="${highlightClass}">
                        <td>${att.ID_EMPLEADO}</td>
                        <td>${att.NOMBRE} ${att.APELLIDO}</td>
                        <td>${att.establecimiento}</td>
                        <td>${att.sede}</td>
                        <td>${formatDate(att.FECHA)}</td>
                        <td>
                            <strong>Entrada:</strong> ${horarioEntrada}<br>
                            <strong>Salida:</strong> ${horarioSalida}
                        </td>
                        <td>
                            <strong>Entrada:</strong> ${estadoEntrada}<br>
                            <strong>Salida:</strong> ${estadoSalida}
                        </td>
                        <td>${fotoBtn}</td>
                        <td>${accion}</td>
                    </tr>
                `;
            });
        })
        .catch(error => {
            console.error('Error al cargar asistencias:', error);
            tbody.innerHTML = '<tr><td colspan="9" class="error-text">Error al cargar datos. Intente de nuevo.</td></tr>';
        });
}

// ===========================================================================
// 5. MODAL PARA REGISTRAR ASISTENCIA (ENTRADA)
// ===========================================================================
window.openAttendanceRegisterModal = function() {
    // Mostrar el modal
    document.getElementById('attendanceRegisterModal').classList.add('show');
    
    // Establecer la fecha actual
    const fechaActual = new Date().toLocaleDateString('es-CO');
    const fechaElement = document.getElementById('reg_fecha');
    if (fechaElement) {
        fechaElement.textContent = fechaActual;
    }
    
    // Iniciar la carga de datos
    inicializarModalRegistro();
};

window.closeAttendanceRegisterModal = function() {
    document.getElementById('attendanceRegisterModal').classList.remove('show');
};

async function inicializarModalRegistro() {
    try {
        // Cargar las sedes
        await cargarSedesRegistro();
        
        // Cargar establecimientos según la sede seleccionada
        await cargarEstablecimientosRegistro();
        
        // Cargar empleados según los filtros
        await cargarEmpleadosParaRegistro();
        
        // Configurar eventos de los filtros
        configureRegistroEventListeners();
    } catch (error) {
        console.error('Error al inicializar modal:', error);
    }
}

function configureRegistroEventListeners() {
    // Botón de búsqueda
    const btnBuscar = document.getElementById('btnBuscarCodigoRegistro');
    if (btnBuscar) {
        btnBuscar.onclick = function(e) {
            e.preventDefault();
            cargarEmpleadosParaRegistro();
        };
    }
    
    // Campo de búsqueda por código
    const codigoBusqueda = document.getElementById('codigoRegistroBusqueda');
    if (codigoBusqueda) {
        codigoBusqueda.onkeypress = function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                cargarEmpleadosParaRegistro();
            }
        };
    }
    
    // Cambio de sede
    const sedeSel = document.getElementById('reg_sede');
    if (sedeSel) {
        sedeSel.onchange = async function() {
            await cargarEstablecimientosRegistro();
            cargarEmpleadosParaRegistro();
        };
    }
    
    // Cambio de establecimiento
    const estSel = document.getElementById('reg_establecimiento');
    if (estSel) {
        estSel.onchange = function() {
            cargarEmpleadosParaRegistro();
        };
    }
}

// Función para cargar sedes en el modal de registro
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

// Función para cargar establecimientos en el modal de registro
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

// Función para cargar empleados disponibles en el modal de registro
async function cargarEmpleadosParaRegistro() {
    // Verificar si el tbody existe
    const tbody = document.getElementById('attendanceRegisterTableBody');
    if (!tbody) {
        console.error('Elemento attendanceRegisterTableBody no encontrado');
        return;
    }
    
    // Obtener los valores de los filtros, con verificación de null
    const sede = document.getElementById('reg_sede')?.value || '';
    const establecimiento = document.getElementById('reg_establecimiento')?.value || '';
    const codigo = document.getElementById('codigoRegistroBusqueda')?.value?.trim() || '';
    
    // Mostrar indicador de carga
    tbody.innerHTML = '<tr><td colspan="5" class="loading-text"><i class="fas fa-spinner fa-spin"></i> Cargando empleados disponibles...</td></tr>';
    
    // Ruta conocida de la API
    const apiPath = 'api/attendance/employees-available.php';
    
    // Construir los parámetros de la URL
    const params = new URLSearchParams();
    if (sede) params.append('sede', sede);
    if (establecimiento) params.append('establecimiento', establecimiento);
    if (codigo) params.append('codigo', codigo);
    params.append('_t', Date.now()); // Para evitar caché
    
    const url = `${apiPath}?${params.toString()}`;
    
    try {
        console.log('Consultando API:', url);
        
        const response = await fetch(url);
        
        // Verificar si la respuesta es OK
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        // Obtener respuesta y parsear JSON
        const responseText = await response.text();
        let data;
        
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Respuesta no es JSON válido:', responseText.substring(0, 100));
            throw new Error('Formato de respuesta inválido');
        }
        
        // Verificar éxito en la respuesta
        if (!data.success) {
            throw new Error(data.message || 'Error en la respuesta del servidor');
        }
        
        // Actualizar la tabla con los datos
        tbody.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="no-data-text">No hay empleados disponibles para registro de asistencia</td></tr>';
            return;
        }
        
        // Mostrar los empleados en la tabla
        data.data.forEach(emp => {
            tbody.innerHTML += `
                <tr>
                    <td>${emp.ID_EMPLEADO}</td>
                    <td>${emp.NOMBRE} ${emp.APELLIDO}</td>
                    <td>${emp.ESTABLECIMIENTO || ''}</td>
                    <td>${emp.SEDE || ''}</td>
                    <td>
                        <button type="button" class="btn-primary" onclick="openAttendancePhotoModal(${emp.ID_EMPLEADO}, '${emp.NOMBRE} ${emp.APELLIDO}')">
                            <i class="fas fa-camera"></i> Registrar
                        </button>
                    </td>
                </tr>
            `;
        });
        
        // Actualizar texto informativo con el filtro de horas si está disponible
        try {
            if (data.filter_info && document.getElementById('filtroInfo')) {
                const hoursBack = data.filter_info.hours_back || 8;
                document.getElementById('filtroInfo').textContent = 
                    `Solo se muestran empleados sin registro de asistencia en las últimas ${hoursBack} horas.`;
            }
        } catch (e) {
            // Si hay error al actualizar el texto informativo, lo ignoramos
            console.log('No se pudo actualizar el texto informativo del filtro', e);
        }
        
    } catch (error) {
        console.error('Error al cargar empleados:', error);
        
        // Mostrar error con botón para reintentar
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
// 6. MODAL DE FOTO PARA REGISTRO DE ENTRADA
// ===========================================================================
let empleadoSeleccionado = null;
let imageBase64 = "";

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
    
    // Limpiar el estado previo
    document.getElementById('canvas').style.display = 'none';
    document.getElementById('photoPreview').innerHTML = '';
    document.getElementById('saveAttendanceBtn').disabled = true;
    
    // Asegurarse de que ambos botones estén visibles
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

// Función para tomar la foto
document.getElementById('takePhotoBtn').onclick = function() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    
    // Asegurarse de que el video está listo
    if (!video.srcObject || !video.srcObject.active) {
        alert("La cámara no está activa. Por favor recargue la página e intente de nuevo.");
        return;
    }
    
    // Capturar la imagen
    canvas.style.display = 'none'; // Mantenerlo oculto
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    imageBase64 = canvas.toDataURL('image/jpeg');
    
    // Mostrar la vista previa
    document.getElementById('photoPreview').innerHTML = `<img src="${imageBase64}" alt="Vista previa">`;
    
    // Activar el botón de guardar y asegurarse de que esté visible
    const saveBtn = document.getElementById('saveAttendanceBtn');
    saveBtn.disabled = false;
    saveBtn.style.display = 'inline-flex';
};

// Función para guardar la asistencia con foto
document.getElementById('saveAttendanceBtn').onclick = function() {
    // Verificar que haya una imagen
    if (!imageBase64) {
        alert("Debe tomar una foto primero.");
        return;
    }
    
    // Mostrar indicador de carga
    const saveBtn = document.getElementById('saveAttendanceBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    saveBtn.disabled = true;
    
    // Enviar los datos al servidor
    fetch('api/attendance/register.php', {
        method: 'POST',
        body: new URLSearchParams({
            id_empleado: empleadoSeleccionado,
            tipo: 'ENTRADA',
            foto_base64: imageBase64
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Asistencia registrada correctamente');
            
            // Cerrar los modales
            closeAttendancePhotoModal();
            closeAttendanceRegisterModal();
            
            // Recargar la tabla de asistencias
            loadAttendanceDay();
        } else {
            alert('Error: ' + (res.message || 'No se pudo registrar la asistencia.'));
            
            // Restaurar el botón para permitir reintentar
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error al registrar asistencia:', error);
        alert('Error al comunicarse con el servidor. Por favor intente de nuevo.');
        
        // Restaurar el botón para permitir reintentar
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
};

// Asegurarnos de cerrar modal al hacer click fuera
document.getElementById('attendancePhotoModal').addEventListener('mousedown', function(e) {
    if (e.target === this) closeAttendancePhotoModal();
});

// ===========================================================================
// 7. REGISTRAR SALIDA (SIN FOTO)
// ===========================================================================
window.registrarSalida = function(id_empleado, fecha) {
    if(!confirm("¿Está seguro de registrar la salida?")) return;
    
    fetch('api/attendance/register.php', {
        method: 'POST',
        body: new URLSearchParams({
            id_empleado: id_empleado,
            tipo: 'SALIDA',
            fecha: fecha
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
// 8. FUNCIONALIDAD DE AMPLIACIÓN DE FOTOS
// ===========================================================================
// Función para abrir el modal de foto ampliada
window.openPhotoModal = function(photoUrl, nombreEmpleado = '') {
    // Si no existe el modal, lo creamos dinámicamente
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
    
    // Ahora que estamos seguros de que existe, obtenemos las referencias
    const modalImg = document.getElementById('photoModalImage');
    const modalTitle = document.getElementById('photoModalTitle');
    
    // Asignar la URL y opcionalmente el nombre
    modalImg.src = photoUrl;
    
    if (nombreEmpleado) {
        modalTitle.textContent = nombreEmpleado;
        modalTitle.style.display = 'block';
    } else {
        modalTitle.style.display = 'none';
    }
    
    // Animación de apertura
    modalImg.style.opacity = '0';
    modal.classList.add('show');
    
    // Animar la aparición de la imagen después de que cargue
    modalImg.onload = function() {
        setTimeout(() => {
            modalImg.style.opacity = '1';
        }, 100);
    };
    
    // Si la imagen ya está en caché
    if (modalImg.complete) {
        setTimeout(() => {
            modalImg.style.opacity = '1';
        }, 100);
    }
};

// Función para cerrar el modal de foto
window.closePhotoModal = function() {
    const modal = document.getElementById('photoModal');
    if (!modal) return;
    
    const img = document.getElementById('photoModalImage');
    
    // Animación de cierre
    img.style.opacity = '0';
    
    setTimeout(() => {
        modal.classList.remove('show');
    }, 300);
};

// Cerrar modal al presionar ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
    }
});

// ===========================================================================
// 9. INICIALIZAR EVENTOS AL CARGAR EL DOM
// ===========================================================================
// Configurar botones del modal de foto
document.addEventListener('DOMContentLoaded', function() {
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
});