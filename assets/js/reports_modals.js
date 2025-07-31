/**
 * SynkTime - Manejador de modales para el módulo de reportes
 * 
 * Este archivo contiene las funciones necesarias para gestionar los modales
 * de detalles de asistencia y empleados, incluyendo la visualización de fotos
 * desde la carpeta "uploads".
 */

/**
 * Abre el modal de detalles de asistencia
 * @param {number} id - ID de la asistencia a mostrar
 */
function openAttendanceDetails(id) {
    // Mostrar modal con indicador de carga
    const modal = document.getElementById('reportAttendanceModal');
    if (!modal) return;
    
    // Limpiar pestañas y contenido
    document.getElementById('attendanceTabs').innerHTML = '';
    document.getElementById('attendanceModalBody').innerHTML = `
        <div class="loader-container">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Cargando detalles de asistencia...</p>
        </div>
    `;
    
    modal.classList.add('show');
    document.body.classList.add('modal-open');
    
    // Cargar datos de asistencia
    fetch(`api/reports/details.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar detalles de asistencia');
            }
            
            // Depurar información de la foto si existe
            if (data.data && data.data.foto) {
                debugPhotoPath(data.data);
            }
            
            renderAttendanceDetails(data.data);
        })
        .catch(error => {
            console.error('Error al cargar detalles de asistencia:', error);
            document.getElementById('attendanceModalBody').innerHTML = `
                <div class="error-container" style="text-align: center; padding: 30px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #e53e3e;"></i>
                    <p style="margin-top: 15px; color: #e53e3e;">${error.message}</p>
                </div>
            `;
        });
}

/**
 * Renderiza los detalles de asistencia en el modal
 * @param {Object} asistencia - Datos de la asistencia
 */
function renderAttendanceDetails(asistencia) {
    // Determinar clase para el estado
    let estadoClass = getStatusClass(asistencia.estado_entrada);
    
    // Crear pestañas
    let tabs = `
        <div class="modal-tab active" data-tab="tabInfoGeneral">
            <i class="fas fa-info-circle"></i> General
        </div>
    `;
    
    if (asistencia.horario && asistencia.horario.id) {
        tabs += `
            <div class="modal-tab" data-tab="tabHorario">
                <i class="fas fa-calendar-alt"></i> Horario
            </div>
        `;
    }
    
    // Verificar que la foto existe
    const hasPhoto = asistencia.foto && asistencia.foto !== '';
    console.log("¿Tiene foto?", hasPhoto, "Foto:", asistencia.foto);
    
    if (hasPhoto) {
        tabs += `
            <div class="modal-tab" data-tab="tabFoto">
                <i class="fas fa-camera"></i> Foto
            </div>
        `;
    }
    
    if (asistencia.todos_registros && 
        (asistencia.todos_registros.entradas.length > 1 || asistencia.todos_registros.salidas.length > 1)) {
        tabs += `
            <div class="modal-tab" data-tab="tabHistorial">
                <i class="fas fa-history"></i> Historial
            </div>
        `;
    }
    
    document.getElementById('attendanceTabs').innerHTML = tabs;
    
    // Generar contenido
    let content = `
        <!-- Tab: Información General -->
        <div id="tabInfoGeneral" class="modal-tab-content active">
            <!-- Información del Empleado -->
            <div class="modal-section">
                <h4 class="modal-section-title">
                    <i class="fas fa-user"></i> Información del Empleado
                </h4>
                <div class="modal-grid">
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Código</span>
                        <span class="modal-grid-item-value">
                            <a href="#" onclick="closeReportModal('reportAttendanceModal'); openEmployeeDetails(${asistencia.codigo}); return false;">
                                ${asistencia.codigo}
                            </a>
                        </span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">DNI</span>
                        <span class="modal-grid-item-value">${asistencia.dni || '-'}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Nombre</span>
                        <span class="modal-grid-item-value">${asistencia.nombre || '-'} ${asistencia.apellido || ''}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Sede</span>
                        <span class="modal-grid-item-value">${asistencia.sede || '-'}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Establecimiento</span>
                        <span class="modal-grid-item-value">${asistencia.establecimiento || '-'}</span>
                    </div>
                </div>
            </div>
            
            <!-- Registro de Asistencia -->
            <div class="modal-section">
                <h4 class="modal-section-title">
                    <i class="fas fa-clock"></i> Registro de Asistencia
                </h4>
                <div class="modal-grid">
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Fecha</span>
                        <span class="modal-grid-item-value">${formatDate(asistencia.fecha) || '-'}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Hora Entrada</span>
                        <span class="modal-grid-item-value">${asistencia.hora_entrada || 'No registrada'}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Hora Salida</span>
                        <span class="modal-grid-item-value">${asistencia.hora_salida || 'No registrada'}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Estado</span>
                        <span class="modal-grid-item-value">
                            <span class="status-badge ${estadoClass}">${asistencia.estado_entrada || '-'}</span>
                        </span>
                    </div>`;
    
    if (asistencia.minutos_tardanza) {
        content += `
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Tardanza</span>
                        <span class="modal-grid-item-value">${asistencia.minutos_tardanza} minutos</span>
                    </div>`;
    }
    
    if (asistencia.horas_trabajadas) {
        content += `
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Horas Trabajadas</span>
                        <span class="modal-grid-item-value">${asistencia.horas_trabajadas} horas</span>
                    </div>`;
    }
    
    content += `
                    <div class="modal-grid-item modal-grid-item-full">
                        <span class="modal-grid-item-label">Observación</span>
                        <span class="modal-grid-item-value">${asistencia.registro_actual?.observacion || 'Sin observaciones'}</span>
                    </div>
                </div>
            </div>`;
    
    // Datos del dispositivo
    if (asistencia.dispositivo || asistencia.navegador || 
       (asistencia.registro_actual && asistencia.registro_actual.es_manual)) {
        content += `
            <div class="modal-section">
                <h4 class="modal-section-title">
                    <i class="fas fa-mobile-alt"></i> Datos del Registro
                </h4>
                <div class="modal-grid">`;
        
        if (asistencia.dispositivo) {
            content += `
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Dispositivo</span>
                        <span class="modal-grid-item-value">${asistencia.dispositivo}</span>
                    </div>`;
        }
        
        if (asistencia.navegador) {
            content += `
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Navegador</span>
                        <span class="modal-grid-item-value">${asistencia.navegador}</span>
                    </div>`;
        }
        
        if (asistencia.registro_actual && asistencia.registro_actual.es_manual) {
            content += `
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Tipo</span>
                        <span class="modal-grid-item-value">
                            <span class="status-badge status-tardanza">Registro manual</span>
                        </span>
                    </div>`;
        }
        
        content += `
                </div>
            </div>`;
    }
    
    content += `
        </div>`;
    
    // Tab de horario
    if (asistencia.horario && asistencia.horario.id) {
        content += `
            <div id="tabHorario" class="modal-tab-content">
                <div class="modal-section">
                    <h4 class="modal-section-title">
                        <i class="fas fa-calendar-alt"></i> Horario Asignado
                    </h4>
                    <div class="modal-grid">
                        <div class="modal-grid-item">
                            <span class="modal-grid-item-label">Nombre</span>
                            <span class="modal-grid-item-value">${asistencia.horario.nombre || '-'}</span>
                        </div>
                        <div class="modal-grid-item">
                            <span class="modal-grid-item-label">Hora Entrada</span>
                            <span class="modal-grid-item-value">${asistencia.horario.hora_entrada || '-'}</span>
                        </div>
                        <div class="modal-grid-item">
                            <span class="modal-grid-item-label">Hora Salida</span>
                            <span class="modal-grid-item-value">${asistencia.horario.hora_salida || '-'}</span>
                        </div>
                        <div class="modal-grid-item">
                            <span class="modal-grid-item-label">Tolerancia</span>
                            <span class="modal-grid-item-value">${asistencia.horario.tolerancia || '0'} minutos</span>
                        </div>`;
        
        if (asistencia.horario.dias) {
            content += `
                        <div class="modal-grid-item modal-grid-item-full">
                            <span class="modal-grid-item-label">Días</span>
                            <span class="modal-grid-item-value">${asistencia.horario.dias}</span>
                        </div>`;
        }
        
        content += `
                    </div>
                </div>
            </div>`;
    }
    
    // Tab de foto - Usar nuestra función de renderizado de foto
    if (hasPhoto) {
        content += renderPhotoTab(asistencia);
    }
    
    // Tab de historial
    if (asistencia.todos_registros && 
        (asistencia.todos_registros.entradas.length > 1 || asistencia.todos_registros.salidas.length > 1)) {
        content += `
            <div id="tabHistorial" class="modal-tab-content">
                <div class="modal-section">
                    <h4 class="modal-section-title">
                        <i class="fas fa-history"></i> Historial de registros
                    </h4>
                    
                    <div class="modal-tabs history-tabs">
                        <div class="modal-tab active" data-tab="tabEntradas">
                            <i class="fas fa-sign-in-alt"></i> Entradas (${asistencia.todos_registros.entradas.length})
                        </div>
                        <div class="modal-tab" data-tab="tabSalidas">
                            <i class="fas fa-sign-out-alt"></i> Salidas (${asistencia.todos_registros.salidas.length})
                        </div>
                    </div>
                    
                    <div id="tabEntradas" class="modal-tab-content active">
                        <table class="modal-table">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>`;
        
        if (asistencia.todos_registros.entradas.length > 0) {
            asistencia.todos_registros.entradas.forEach(entrada => {
                content += `
                                <tr>
                                    <td>${entrada.HORA}</td>
                                    <td>${entrada.TARDANZA || '-'}</td>
                                    <td>${entrada.OBSERVACION || '-'}</td>
                                </tr>`;
            });
        } else {
            content += `
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #718096;">No hay registros de entrada</td>
                                </tr>`;
        }
        
        content += `
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="tabSalidas" class="modal-tab-content">
                        <table class="modal-table">
                            <thead>
                                <tr>
                                    <th>Hora</th>
                                    <th>Estado</th>
                                    <th>Observación</th>
                                </tr>
                            </thead>
                            <tbody>`;
        
        if (asistencia.todos_registros.salidas.length > 0) {
            asistencia.todos_registros.salidas.forEach(salida => {
                content += `
                                <tr>
                                    <td>${salida.HORA}</td>
                                    <td>${salida.TARDANZA || '-'}</td>
                                    <td>${salida.OBSERVACION || '-'}</td>
                                </tr>`;
            });
        } else {
            content += `
                                <tr>
                                    <td colspan="3" style="text-align: center; color: #718096;">No hay registros de salida</td>
                                </tr>`;
        }
        
        content += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
    }
    
    // Actualizar contenido y botones
    document.getElementById('attendanceModalBody').innerHTML = content;
    document.getElementById('attendanceModalFooter').innerHTML = `
        <button type="button" class="btn-secondary" onclick="closeReportModal('reportAttendanceModal')">
            <i class="fas fa-times"></i> Cerrar
        </button>
    `;
    
    // Inicializar las pestañas
    initTabs();
    
    // Inicializar las interacciones con las fotos
    initPhotoInteractions();
}

/**
 * Renderiza la pestaña de foto en el modal de asistencia
 * @param {Object} asistencia - Datos de la asistencia con el nombre de la foto
 * @returns {string} - HTML de la pestaña de fotos
 */
function renderPhotoTab(asistencia) {
    if (!asistencia.foto) return '';
    
    // Construir la URL de la imagen basada en la información de la asistencia
    let photoUrl = '';
    
    try {
        // Si la foto es una ruta o un nombre de archivo
        if (typeof asistencia.foto === 'string') {
            // Si ya es una URL completa
            if (asistencia.foto.startsWith('http') || asistencia.foto.startsWith('data:image')) {
                photoUrl = asistencia.foto;
            } 
            // Si es base64 (comienza con patrones comunes de base64 para imágenes)
            else if (asistencia.foto.startsWith('/9j/') || asistencia.foto.startsWith('iVBOR')) {
                photoUrl = `data:image/jpeg;base64,${asistencia.foto}`;
            } 
            // Si es una ruta o un nombre de archivo
            else {
                // Eliminar cualquier barra inicial o ruta relativa
                const fileName = asistencia.foto.split('/').pop();
                // Construir la URL a la carpeta uploads
                photoUrl = `uploads/${fileName}`;
            }
        } else {
            console.warn('El formato de la foto no es reconocido:', typeof asistencia.foto);
            return '';
        }
    } catch (e) {
        console.error('Error al procesar la imagen:', e);
        return `
            <div id="tabFoto" class="modal-tab-content">
                <div class="modal-section" style="text-align: center;">
                    <h4 class="modal-section-title">
                        <i class="fas fa-camera"></i> Imagen de registro
                    </h4>
                    <div class="photo-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>No se pudo cargar la imagen</p>
                    </div>
                </div>
            </div>`;
    }
    
    return `
        <div id="tabFoto" class="modal-tab-content">
            <div class="modal-section" style="text-align: center;">
                <h4 class="modal-section-title">
                    <i class="fas fa-camera"></i> Imagen de registro
                </h4>
                <div class="photo-container">
                    <img src="${photoUrl}" alt="Foto de registro" class="attendance-photo"
                         onerror="this.onerror=null; this.src='assets/img/photo-error.png'; this.classList.add('photo-error-img');">
                    <div class="photo-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Cargando imagen...</span>
                    </div>
                </div>
                <div class="photo-controls">
                    <button type="button" class="btn-primary btn-sm view-fullscreen-photo" data-src="${photoUrl}">
                        <i class="fas fa-search-plus"></i> Ver a tamaño completo
                    </button>
                </div>
            </div>
        </div>`;
}

/**
 * Inicializa las interacciones con las fotos
 */
function initPhotoInteractions() {
    // Ocultar los loaders de las imágenes cargadas
    document.querySelectorAll('.attendance-photo').forEach(img => {
        if (img.complete) {
            hidePhotoLoading(img);
        } else {
            img.addEventListener('load', () => hidePhotoLoading(img));
            img.addEventListener('error', () => handlePhotoError(img));
        }
    });
    
    // Configurar botones de vista completa
    document.querySelectorAll('.view-fullscreen-photo').forEach(btn => {
        btn.addEventListener('click', function() {
            const photoUrl = this.getAttribute('data-src');
            if (photoUrl) {
                showFullscreenPhoto(photoUrl);
            }
        });
    });
}

/**
 * Oculta el loader de una imagen
 * @param {HTMLImageElement} img - Elemento de imagen
 */
function hidePhotoLoading(img) {
    const container = img.closest('.photo-container');
    if (container) {
        const loader = container.querySelector('.photo-loading');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(() => {
                if (loader && loader.parentNode) {
                    loader.parentNode.removeChild(loader);
                }
            }, 300);
        }
    }
}

/**
 * Maneja el error al cargar una imagen
 * @param {HTMLImageElement} img - Elemento de imagen
 */
function handlePhotoError(img) {
    const container = img.closest('.photo-container');
    if (container) {
        const loader = container.querySelector('.photo-loading');
        if (loader) {
            loader.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="color: #e53e3e;"></i>
                <span style="color: #e53e3e;">No se pudo cargar la imagen</span>
            `;
        }
    }
}

/**
 * Muestra una foto en pantalla completa
 * @param {string} photoUrl - URL de la foto
 */
function showFullscreenPhoto(photoUrl) {
    // Crear el overlay
    const overlay = document.createElement('div');
    overlay.className = 'photo-fullscreen-overlay';
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.9)';
    overlay.style.zIndex = '9999';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.opacity = '0';
    overlay.style.transition = 'opacity 0.3s ease';
    
    // Crear la imagen
    const img = document.createElement('img');
    img.src = photoUrl;
    img.style.maxWidth = '90%';
    img.style.maxHeight = '90%';
    img.style.objectFit = 'contain';
    img.style.boxShadow = '0 0 20px rgba(0, 0, 0, 0.5)';
    img.style.transform = 'scale(0.95)';
    img.style.transition = 'transform 0.3s ease';
    
    // Indicador de carga
    const loader = document.createElement('div');
    loader.style.position = 'absolute';
    loader.style.display = 'flex';
    loader.style.flexDirection = 'column';
    loader.style.alignItems = 'center';
    loader.style.color = 'white';
    loader.innerHTML = `
        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 10px;"></i>
        <span>Cargando imagen...</span>
    `;
    
    // Botón de cierre
    const closeButton = document.createElement('button');
    closeButton.innerHTML = '&times;';
    closeButton.style.position = 'absolute';
    closeButton.style.top = '20px';
    closeButton.style.right = '20px';
    closeButton.style.background = 'none';
    closeButton.style.border = 'none';
    closeButton.style.color = 'white';
    closeButton.style.fontSize = '2rem';
    closeButton.style.cursor = 'pointer';
    closeButton.style.zIndex = '10000';
    
    // Agregar elementos al DOM
    overlay.appendChild(loader);
    overlay.appendChild(img);
    overlay.appendChild(closeButton);
    document.body.appendChild(overlay);
    
    // Mostrar con animación
    setTimeout(() => {
        overlay.style.opacity = '1';
        img.style.transform = 'scale(1)';
    }, 10);
    
    // Manejar eventos de la imagen
    img.onload = function() {
        overlay.removeChild(loader);
    };
    
    img.onerror = function() {
        this.src = 'assets/img/photo-error.png';
        this.style.maxWidth = '300px';
        overlay.removeChild(loader);
    };
    
    // Manejar cierre
    closeButton.addEventListener('click', function() {
        closeFullscreenPhoto(overlay);
    });
    
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeFullscreenPhoto(overlay);
        }
    });
    
    // Permitir cerrar con Escape
    document.addEventListener('keydown', function escapeHandler(e) {
        if (e.key === 'Escape') {
            closeFullscreenPhoto(overlay);
            document.removeEventListener('keydown', escapeHandler);
        }
    });
}

/**
 * Cierra la foto en pantalla completa con animación
 * @param {HTMLElement} overlay - Elemento del overlay
 */
function closeFullscreenPhoto(overlay) {
    overlay.style.opacity = '0';
    const img = overlay.querySelector('img');
    if (img) {
        img.style.transform = 'scale(0.95)';
    }
    setTimeout(() => {
        if (overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
        }
    }, 300);
}

/**
 * Obtiene la clase CSS para un estado de asistencia
 * @param {string} estado - Estado de la asistencia
 * @returns {string} - Clase CSS correspondiente
 */
function getStatusClass(estado) {
    if (!estado) return '';
    
    const estadoLower = estado.toLowerCase();
    
    if (estadoLower.includes('tiempo') || estadoLower.includes('temprano')) {
        return 'status-temprano';
    } else if (estadoLower.includes('tardanza')) {
        return 'status-tardanza';
    } else if (estadoLower.includes('ausente')) {
        return 'status-ausente';
    }
    
    return '';
}

/**
 * Inicializa las pestañas en el modal
 */
function initTabs() {
    document.querySelectorAll('.modal-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            // Obtener el target
            const targetId = this.getAttribute('data-tab');
            
            // Quitar clase activa de todas las pestañas en este grupo
            const tabContainer = this.parentElement;
            tabContainer.querySelectorAll('.modal-tab').forEach(t => {
                t.classList.remove('active');
            });
            
            // Añadir clase activa a esta pestaña
            this.classList.add('active');
            
            // Ocultar todos los contenidos de pestaña
            const modalBody = this.closest('.modal-content').querySelector('.modal-body');
            modalBody.querySelectorAll('.modal-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Mostrar el contenido correspondiente
            const tabContent = document.getElementById(targetId);
            if (tabContent) {
                tabContent.classList.add('active');
            }
        });
    });
}

/**
 * Cierra un modal específico
 * @param {string} modalId - ID del modal a cerrar
 */
function closeReportModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
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
 * Depura información sobre la ruta de la foto
 * @param {Object} asistencia - Datos de la asistencia
 */
function debugPhotoPath(asistencia) {
    console.group('Depuración de ruta de foto');
    
    if (!asistencia.foto) {
        console.warn('No hay datos de foto en la asistencia');
    } else {
        console.log('Valor de foto en asistencia:', asistencia.foto);
        
        if (typeof asistencia.foto === 'string') {
            // Verificar si es un archivo
            const isFile = !asistencia.foto.startsWith('data:') && 
                          !asistencia.foto.startsWith('/9j/') && 
                          !asistencia.foto.startsWith('iVBOR');
            
            console.log('¿Es un nombre de archivo?', isFile);
            
            if (isFile) {
                // Obtener solo el nombre del archivo
                const fileName = asistencia.foto.split('/').pop();
                console.log('Nombre del archivo extraído:', fileName);
                console.log('Ruta completa esperada:', `uploads/${fileName}`);
                
                // Verificar si existe la ruta (solo informativo)
                console.log('Para verificar si existe el archivo, revisa la carpeta "uploads"');
                console.log('Busca el archivo:', fileName);
            }
        }
    }
    
    console.groupEnd();
}

/**
 * Abre el modal de detalles de empleado
 * @param {number} id - ID del empleado
 */
function openEmployeeDetails(id) {
    console.log('Abriendo modal de empleado ID:', id); // Log para diagnóstico
    
    // Obtener el modal directamente
    const modal = document.getElementById('reportEmployeeModal');
    if (!modal) {
        console.error('Modal de empleado no encontrado en el DOM');
        return;
    }
    
    // Verificar que existan los elementos internos
    const tabsContainer = document.getElementById('employeeTabs');
    const modalBody = document.getElementById('employeeModalBody');
    
    if (!tabsContainer || !modalBody) {
        console.error('Elementos internos del modal no encontrados');
        return;
    }
    
    // Limpiar pestañas y contenido
    tabsContainer.innerHTML = '';
    modalBody.innerHTML = `
        <div class="loader-container">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Cargando información del empleado...</p>
        </div>
    `;
    
    // Mostrar el modal directamente (sin usar funciones intermedias que podrían fallar)
    modal.classList.add('show');
    document.body.classList.add('modal-open');
    
    // Cargar datos del empleado
    fetch(`api/employee/get.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos del empleado recibidos:', data.success); // Log para diagnóstico
            
            if (!data.success) {
                throw new Error(data.message || 'Error al cargar datos del empleado');
            }
            
            renderEmployeeDetails(data.data);
        })
        .catch(error => {
            console.error('Error al cargar datos del empleado:', error);
            modalBody.innerHTML = `
                <div class="error-container" style="text-align: center; padding: 30px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; color: #e53e3e;"></i>
                    <p style="margin-top: 15px; color: #e53e3e;">${error.message}</p>
                </div>
            `;
        });
}

/**
 * Renderiza los detalles del empleado en el modal
 * @param {Object} empleado - Datos del empleado
 */
function renderEmployeeDetails(empleado) {
    // Estado del empleado
    const estadoTexto = empleado.ESTADO === 'A' ? 'Activo' : 'Inactivo';
    const estadoClass = empleado.ESTADO === 'A' ? 'status-active' : 'status-inactive';
    
    // Generar pestañas
    let tabs = `
        <div class="modal-tab active" data-tab="tabInfoEmpleado">
            <i class="fas fa-address-card"></i> Información
        </div>
    `;
    
    if (empleado.HORARIOS && empleado.HORARIOS.length > 0) {
        tabs += `
            <div class="modal-tab" data-tab="tabHorarios">
                <i class="fas fa-calendar-alt"></i> Horarios
            </div>
        `;
    }
    
    document.getElementById('employeeTabs').innerHTML = tabs;
    
    // Generar contenido
    let content = `
        <!-- Tab: Información del Empleado -->
        <div id="tabInfoEmpleado" class="modal-tab-content active">
            <!-- Cabecera con foto -->
            <div class="employee-profile">
                <div class="employee-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="employee-info">
                    <h3 class="employee-name">${empleado.NOMBRE || '-'} ${empleado.APELLIDO || ''}</h3>
                    <p class="employee-subtitle">${empleado.DNI || '-'}</p>
                    <span class="status-badge ${estadoClass}">${estadoTexto}</span>
                </div>
            </div>
            
            <!-- Datos Personales -->
            <div class="modal-section">
                <h4 class="modal-section-title">
                    <i class="fas fa-address-card"></i> Datos Personales
                </h4>
                <div class="modal-grid">
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Código</span>
                        <span class="modal-grid-item-value">${empleado.ID_EMPLEADO || '-'}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Correo</span>
                        <span class="modal-grid-item-value">${empleado.CORREO || '-'}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Teléfono</span>
                        <span class="modal-grid-item-value">${empleado.TELEFONO || '-'}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Fecha Ingreso</span>
                        <span class="modal-grid-item-value">${formatDate(empleado.FECHA_INGRESO) || '-'}</span>
                    </div>
                </div>
            </div>
            
            <!-- Ubicación -->
            <div class="modal-section">
                <h4 class="modal-section-title">
                    <i class="fas fa-map-marker-alt"></i> Ubicación
                </h4>
                <div class="modal-grid">
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Sede</span>
                        <span class="modal-grid-item-value">${empleado.SEDE || '-'}</span>
                    </div>
                    <div class="modal-grid-item">
                        <span class="modal-grid-item-label">Establecimiento</span>
                        <span class="modal-grid-item-value">${empleado.ESTABLECIMIENTO || '-'}</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Pestaña de horarios
    if (empleado.HORARIOS && empleado.HORARIOS.length > 0) {
        content += `
            <div id="tabHorarios" class="modal-tab-content">
                <div class="modal-section">
                    <h4 class="modal-section-title">
                        <i class="fas fa-calendar-alt"></i> Horarios Asignados
                    </h4>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Horario</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        empleado.HORARIOS.forEach(horario => {
            const fechaHasta = horario.FECHA_HASTA ? formatDate(horario.FECHA_HASTA) : 'Sin límite';
            content += `
                <tr>
                    <td>${horario.NOMBRE}</td>
                    <td>${horario.HORA_ENTRADA}</td>
                    <td>${horario.HORA_SALIDA}</td>
                    <td>${formatDate(horario.FECHA_DESDE)}</td>
                    <td>${fechaHasta}</td>
                </tr>
            `;
        });
        
        content += `
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
    
    // Actualizar contenido y botones
    document.getElementById('employeeModalBody').innerHTML = content;
    document.getElementById('employeeModalFooter').innerHTML = `
        <button type="button" class="btn-secondary" onclick="closeReportModal('reportEmployeeModal')">
            <i class="fas fa-times"></i> Cerrar
        </button>
        <button type="button" class="btn-primary" onclick="viewEmployeeAttendance(${empleado.ID_EMPLEADO})">
            <i class="fas fa-calendar-check"></i> Ver Asistencias
        </button>
    `;
    
    // Inicializar las pestañas
    initTabs();
}


// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Asegurarse de que no haya modales abiertos
    closeReportModal('reportAttendanceModal');
    closeReportModal('reportEmployeeModal');
});

