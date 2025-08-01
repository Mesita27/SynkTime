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
    
    // Configurar el gráfico de distribución para que también sea interactivo
    configurarGraficoDistribucion();
}

// Configurar el gráfico de distribución para abrir modales al hacer clic
function configurarGraficoDistribucion() {
    // Intentar encontrar el gráfico después de que se haya inicializado
    setTimeout(function() {
        const chartElement = document.getElementById('attendanceDistributionChart');
        if (chartElement && window.ApexCharts) {
            // Agregar evento de clic a las secciones del gráfico si es posible
            chartElement.addEventListener('click', function() {
                // Determinar en qué sección se hizo clic es difícil sin acceso directo al objeto ApexCharts
                // Por lo que simplemente mostramos el modal de A Tiempo por defecto
                mostrarModalAsistencias('aTiempo');
            });
        }
    }, 1000); // Esperar 1 segundo para dar tiempo a que se inicialice el gráfico
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

// Hacer la función globalmente accesible
window.mostrarModalAsistencias = mostrarModalAsistencias;

// Modificar la función mostrarModalAsistencias para añadir depuración
function mostrarModalAsistencias(tipo) {
    // Obtener elementos necesarios
    const modal = document.getElementById(`${tipo}-modal`);
    const fechaElement = document.getElementById(`${tipo}-modal-fecha`);
    const ubicacionElement = document.getElementById(`${tipo}-modal-ubicacion`);
    const tableBody = document.getElementById(`${tipo}-table-body`);
    
    if (!modal || !fechaElement || !ubicacionElement || !tableBody) {
        console.error(`No se encontraron los elementos para el modal de ${tipo}`);
        alert(`No se encontraron elementos para el modal: ${tipo}-modal, ${tipo}-modal-fecha, ${tipo}-modal-ubicacion, o ${tipo}-table-body`);
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
    
    // Construir la URL para la API
    let apiUrl = `api/get-attendance-details.php?tipo=${tipo}&fecha=${fecha}`;
    if (establecimientoId) {
        apiUrl += `&establecimiento_id=${establecimientoId}`;
    } else if (sedeId) {
        apiUrl += `&sede_id=${sedeId}`;
    }
    
    // Depuración: mostrar URL completa
    console.log('URL de la API:', apiUrl);
    
    // Cargar datos desde la API
    fetch(apiUrl)
        .then(response => {
            // Depuración: mostrar información detallada de la respuesta
            console.log('Status:', response.status);
            console.log('Status text:', response.statusText);
            console.log('Headers:', [...response.headers].map(([key, value]) => `${key}: ${value}`).join(', '));
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            // Depuración: ver los datos completos
            console.log('Datos recibidos:', data);
            
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
            
            // Depuración: alerta para ver el error completo
            alert(`Error completo: ${error}`);
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
        // Tabla para faltas
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
        // Tabla para asistencias (temprano, a tiempo, tarde)
        datos.forEach(item => {
            // Calcular diferencia de minutos para mostrar
            let minutosDiferencia = item.MINUTOS_DIFERENCIA || 0;
            let diferencia;
            let claseBadge = '';
            
            if (tipo === 'temprano') {
                diferencia = `${Math.abs(minutosDiferencia).toFixed(0)} min antes`;
                claseBadge = 'temprano';
            } else if (tipo === 'aTiempo') {
                if (minutosDiferencia <= 0) {
                    diferencia = `A tiempo`;
                } else {
                    diferencia = `${Math.abs(minutosDiferencia).toFixed(0)} min después`;
                }
                claseBadge = 'a-tiempo';
            } else { // tarde
                diferencia = `${Math.abs(minutosDiferencia).toFixed(0)} min tarde`;
                claseBadge = 'tarde';
            }
            
            tableBody.innerHTML += `
                <tr>
                    <td>${item.CODIGO || '-'}</td>
                    <td>${(item.NOMBRE || '') + ' ' + (item.APELLIDO || '')}</td>
                    <td>${item.ESTABLECIMIENTO || '-'}</td>
                    <td>${formatearHora(item.ENTRADA_HORA) || '--:--'}</td>
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
                if (minutosDiferencia <= 0) {
                    diferencia = `A tiempo`;
                } else {
                    diferencia = `${Math.abs(minutosDiferencia).toFixed(0)} min después`;
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