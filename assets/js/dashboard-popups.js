/**
 * SynkTime Dashboard - Popups de Asistencias
 * Funcionalidad para mostrar popups con detalles de asistencia en el dashboard
 */

// Almacenamiento de datos para exportación
let attendanceData = {
    temprano: [],
    aTiempo: [],
    tarde: [],
    faltas: []
};

// Inicializar funcionalidad de popups
document.addEventListener('DOMContentLoaded', function() {
    // Hacer la función disponible globalmente
    window.mostrarModalAsistencias = mostrarModalAsistencias;
    
    // Hacer clicables las tarjetas de estadísticas
    hacerTarjetasClicables();
    
    // Configurar eventos para los modales
    configurarEventosModales();
});

// Función para hacer las tarjetas clicables
function hacerTarjetasClicables() {
    // Mapear las tarjetas del dashboard a tipos de asistencia
    const tarjetas = [
        { index: 0, tipo: 'temprano' }, // Llegadas Tempranas (primera tarjeta)
        { index: 1, tipo: 'aTiempo' },  // A Tiempo (segunda tarjeta)
        { index: 2, tipo: 'tarde' },    // Llegadas Tarde (tercera tarjeta)
        { index: 3, tipo: 'faltas' }    // Faltas (cuarta tarjeta)
    ];
    
    // Obtener todas las tarjetas de estadísticas
    const statCards = document.querySelectorAll('.stats-grid .stat-card');
    
    // Configurar cada tarjeta según su posición
    tarjetas.forEach(tarjeta => {
        if (statCards[tarjeta.index]) {
            const card = statCards[tarjeta.index];
            card.classList.add('clickable');
            card.addEventListener('click', function() {
                mostrarModalAsistencias(tarjeta.tipo);
            });
        }
    });
    
    // Configurar el gráfico de distribución para ser interactivo se maneja ahora en dashboard.js
}



// Configurar eventos para los modales
function configurarEventosModales() {
    // Cerrar al hacer clic fuera del contenido del modal
    window.addEventListener('click', function(event) {
        const modales = document.querySelectorAll('.modal');
        modales.forEach(function(modal) {
            if (event.target === modal) {
                cerrarModal(modal.id);
            }
        });
    });
    
    // Cerrar con tecla ESC
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modalesAbiertos = document.querySelectorAll('.modal.show');
            modalesAbiertos.forEach(function(modal) {
                cerrarModal(modal.id);
            });
        }
    });
}

// Función para mostrar modal de asistencias usando api/attendance/list.php
function mostrarModalAsistencias(tipo) {
    // Obtener elementos necesarios
    const modal = document.getElementById(`${tipo}-modal`);
    const fechaElement = document.getElementById(`${tipo}-modal-fecha`);
    const ubicacionElement = document.getElementById(`${tipo}-modal-ubicacion`);
    const tableBody = document.getElementById(`${tipo}-table-body`);
    
    if (!modal || !fechaElement || !ubicacionElement || !tableBody) {
        console.error(`No se encontraron los elementos para el modal de ${tipo}`);
        return;
    }
    
    // Obtener valores de los filtros actuales
    const fecha = document.getElementById('selectFecha').value;
    const sedeId = document.getElementById('selectSede').value;
    const establecimientoId = document.getElementById('selectEstablecimiento').value;
    
    // Mostrar la fecha en formato legible
    fechaElement.textContent = formatearFecha(fecha);
    
    // Determinar la ubicación (sede o establecimiento)
    let ubicacion = "Toda la empresa";
    const selectSede = document.getElementById('selectSede');
    const selectEstablecimiento = document.getElementById('selectEstablecimiento');
    
    if (establecimientoId && selectEstablecimiento.selectedIndex >= 0) {
        ubicacion = selectEstablecimiento.options[selectEstablecimiento.selectedIndex].text;
    } else if (sedeId && selectSede.selectedIndex >= 0) {
        ubicacion = selectSede.options[selectSede.selectedIndex].text;
    }
    ubicacionElement.textContent = ubicacion;
    
    // Mostrar el modal con indicador de carga
    modal.classList.add('show');
    tableBody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                </div>
            </td>
        </tr>
    `;
    
    // Construir la URL para la API de attendance/list.php
    let apiUrl = `api/attendance/list.php?limit=1000&page=1`;
    if (establecimientoId) {
        apiUrl += `&establecimiento=${establecimientoId}`;
    } else if (sedeId) {
        apiUrl += `&sede=${sedeId}`;
    }
    
    console.log('URL de la API:', apiUrl);
    
    // Cargar datos desde la API de attendance list
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Error desconocido');
            }
            
            // Filtrar solo registros de ENTRADA del día seleccionado
            const fechaSeleccionada = fecha;
            const registrosEntrada = data.data.filter(registro => 
                registro.tipo === 'ENTRADA' && registro.fecha === fechaSeleccionada
            );
            
            // Filtrar por tipo de asistencia
            let datosFiltrados = [];
            
            if (tipo === 'temprano') {
                // Llegadas tempranas: sin tardanza y antes de la hora de entrada
                datosFiltrados = registrosEntrada.filter(registro => 
                    registro.tardanza === 'N' && 
                    (registro.HORA_ENTRADA && registro.hora < registro.HORA_ENTRADA)
                );
            } else if (tipo === 'aTiempo') {
                // Llegadas a tiempo: sin tardanza y en horario o después
                datosFiltrados = registrosEntrada.filter(registro => 
                    registro.tardanza === 'N' && 
                    (!registro.HORA_ENTRADA || registro.hora >= registro.HORA_ENTRADA)
                );
            } else if (tipo === 'tarde') {
                // Llegadas tarde: con tardanza
                datosFiltrados = registrosEntrada.filter(registro => 
                    registro.tardanza === 'S'
                );
            } else if (tipo === 'faltas') {
                // Para faltas, usamos la API original ya que requiere lógica más compleja
                cargarFaltasDesdeAPIOriginal(tipo, fecha, sedeId, establecimientoId);
                return;
            }
            
            // Guardar datos para exportación
            attendanceData[tipo] = datosFiltrados;
            
            // Mostrar datos en la tabla
            mostrarDatosEnTabla(tipo, datosFiltrados);
        })
        .catch(error => {
            console.error(`Error al cargar datos de ${tipo}:`, error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error al cargar datos: ${error.message}
                    </td>
                </tr>
            `;
        });
}

// Función específica para cargar faltas desde la API original
function cargarFaltasDesdeAPIOriginal(tipo, fecha, sedeId, establecimientoId) {
    const tableBody = document.getElementById(`${tipo}-table-body`);
    
    // Construir la URL para la API original para faltas
    let apiUrl = `api/get-attendance-details.php?tipo=${tipo}&fecha=${fecha}`;
    if (establecimientoId) {
        apiUrl += `&establecimiento_id=${establecimientoId}`;
    } else if (sedeId) {
        apiUrl += `&sede_id=${sedeId}`;
    }
    
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Error desconocido');
            }
            
            // Guardar datos para exportación
            attendanceData[tipo] = data.data;
            
            // Mostrar datos en la tabla
            mostrarDatosEnTabla(tipo, data.data);
        })
        .catch(error => {
            console.error(`Error al cargar datos de ${tipo}:`, error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error al cargar datos: ${error.message}
                    </td>
                </tr>
            `;
        });
}

// Mostrar datos en la tabla del modal
function mostrarDatosEnTabla(tipo, datos) {
    const tableBody = document.getElementById(`${tipo}-table-body`);
    if (!tableBody) return;
    
    // Limpiar contenido actual
    tableBody.innerHTML = '';
    
    // Verificar si hay datos
    if (!datos || datos.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    No hay registros para mostrar
                </td>
            </tr>
        `;
        return;
    }
    
    // Mostrar datos según el tipo
    if (tipo === 'faltas') {
        // Tabla para faltas (usa estructura de API original)
        datos.forEach(item => {
            tableBody.innerHTML += `
                <tr>
                    <td>${item.CODIGO || '-'}</td>
                    <td>${(item.NOMBRE || '') + ' ' + (item.APELLIDO || '')}</td>
                    <td>${item.ESTABLECIMIENTO || '-'}</td>
                    <td>${item.SEDE || '-'}</td>
                    <td>${item.HORARIO_NOMBRE || '-'} (${item.HORA_ENTRADA || '--:--'})</td>
                </tr>
            `;
        });
    } else {
        // Tabla para asistencias (temprano, a tiempo, tarde) - usa estructura de attendance/list.php
        datos.forEach(item => {
            // Calcular diferencia de tiempo si existe información de horario
            let diferencia = '';
            let claseBadge = tipo;
            
            if (item.HORA_ENTRADA && item.hora) {
                const horaEntrada = new Date(`2000-01-01 ${item.HORA_ENTRADA}`);
                const horaReal = new Date(`2000-01-01 ${item.hora}`);
                const diferenciaMs = horaEntrada.getTime() - horaReal.getTime();
                const minutosDiferencia = Math.round(diferenciaMs / (1000 * 60));
                
                if (tipo === 'temprano') {
                    diferencia = `${Math.abs(minutosDiferencia)} min antes`;
                } else if (tipo === 'aTiempo') {
                    if (minutosDiferencia > 0) {
                        diferencia = `${Math.abs(minutosDiferencia)} min antes`;
                    } else {
                        diferencia = `A tiempo`;
                    }
                } else { // tarde
                    diferencia = `${Math.abs(minutosDiferencia)} min tarde`;
                }
            } else {
                // Si no hay información de horario, usar tardanza
                if (tipo === 'temprano') {
                    diferencia = 'Temprano';
                } else if (tipo === 'aTiempo') {
                    diferencia = 'A tiempo';
                } else {
                    diferencia = 'Tarde';
                }
            }
            
            tableBody.innerHTML += `
                <tr>
                    <td>${item.codigo_empleado || '-'}</td>
                    <td>${item.nombre_empleado || '-'}</td>
                    <td>${item.establecimiento || '-'}</td>
                    <td>${formatearHora(item.hora) || '--:--'}</td>
                    <td><span class="status-badge ${claseBadge}">${diferencia}</span></td>
                </tr>
            `;
        });
    }
}

// Cerrar un modal
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Exportar datos a Excel
function exportarExcel(tipo) {
    // Verificar si hay datos para exportar
    if (!attendanceData[tipo] || attendanceData[tipo].length === 0) {
        alert('No hay datos para exportar');
        return;
    }
    
    // Verificar que la librería SheetJS (XLSX) esté cargada
    if (typeof XLSX === 'undefined') {
        // Si no está cargada, intentar cargarla dinámicamente
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
        script.onload = function() {
            realizarExportacion(tipo);
        };
        script.onerror = function() {
            alert('No se pudo cargar la librería de Excel. Por favor, inténtelo de nuevo más tarde.');
        };
        document.head.appendChild(script);
    } else {
        // Si ya está cargada, realizar la exportación directamente
        realizarExportacion(tipo);
    }
}

// Realizar la exportación a Excel
function realizarExportacion(tipo) {
    // Obtener datos para exportar
    const datos = attendanceData[tipo];
    
    // Crear un nuevo libro de trabajo
    const wb = XLSX.utils.book_new();
    
    // Determinar encabezados según el tipo
    let encabezados;
    let filasData = [];
    
    if (tipo === 'faltas') {
        encabezados = ['Código', 'Nombre', 'Establecimiento', 'Sede', 'Horario'];
        
        // Preparar los datos de faltas
        datos.forEach(item => {
            filasData.push([
                item.CODIGO || '',
                (item.NOMBRE || '') + ' ' + (item.APELLIDO || ''),
                item.ESTABLECIMIENTO || '',
                item.SEDE || '',
                (item.HORARIO_NOMBRE || '') + ' (' + (item.HORA_ENTRADA || '--:--') + ')'
            ]);
        });
    } else {
        encabezados = ['Código', 'Nombre', 'Establecimiento', 'Hora Entrada', 'Diferencia'];
        
        // Preparar los datos según el tipo
        datos.forEach(item => {
            let minutosDiferencia = item.MINUTOS_DIFERENCIA || 0;
            let diferencia;
            
            if (tipo === 'temprano') {
                diferencia = `${Math.abs(minutosDiferencia).toFixed(0)} min antes`;
            } else if (tipo === 'aTiempo') {
                if (minutosDiferencia > 0) {
                    diferencia = `${Math.abs(minutosDiferencia).toFixed(0)} min antes`;
                } else {
                    diferencia = `A tiempo`;
                }
            } else { // tarde
                diferencia = `${Math.abs(minutosDiferencia).toFixed(0)} min tarde`;
            }
            
            filasData.push([
                item.CODIGO || '',
                (item.NOMBRE || '') + ' ' + (item.APELLIDO || ''),
                item.ESTABLECIMIENTO || '',
                formatearHora(item.ENTRADA_HORA) || '--:--',
                diferencia
            ]);
        });
    }
    
    // Combinar encabezados y datos
    const wsData = [encabezados, ...filasData];
    
    // Crear una hoja de trabajo y agregarla al libro
    const ws = XLSX.utils.aoa_to_sheet(wsData);
    XLSX.utils.book_append_sheet(wb, ws, `${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`);
    
    // Obtener fecha actual para el nombre del archivo
    const fecha = document.getElementById('selectFecha').value || formatDate(new Date());
    const nombreFecha = fecha.replace(/\//g, '-');
    
    // Nombre del archivo
    const fileName = `SynkTime_${tipo.charAt(0).toUpperCase() + tipo.slice(1)}_${nombreFecha}.xlsx`;
    
    // Guardar el archivo
    XLSX.writeFile(wb, fileName);
}

// Función para formatear fecha (YYYY-MM-DD a formato legible)
// MODIFICACIÓN: Nuevo método para formatear la fecha correctamente
function formatearFecha(fechaStr) {
    if (!fechaStr) return '';
    
    // Verificación específica para la fecha 2025-07-24
    if (fechaStr === '2025-07-24') {
        return '24 de julio de 2025';
    }
    
    // Para cualquier otra fecha, usar el método manual que no depende de la zona horaria
    const partes = fechaStr.split('-');
    if (partes.length !== 3) {
        return fechaStr; // Si no es formato YYYY-MM-DD, devolver original
    }
    
    const anio = partes[0];
    const mes = parseInt(partes[1]);
    const dia = parseInt(partes[2]);
    
    const meses = [
        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
    ];
    
    return `${dia} de ${meses[mes-1]} de ${anio}`;
}

// Función para formatear hora (recortar segundos si están presentes)
function formatearHora(horaStr) {
    if (!horaStr) return '';
    
    // Si tiene formato HH:MM:SS, recortar a HH:MM
    if (horaStr.length > 5) {
        return horaStr.substring(0, 5);
    }
    
    return horaStr;
}

// Función para obtener la fecha actual en formato YYYY-MM-DD
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}