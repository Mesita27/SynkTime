/**
 * SynkTime Dashboard
 * Maneja la funcionalidad del dashboard, incluyendo filtros y actualización de datos
 */
class Dashboard {
    constructor() {
        // Referencias a elementos DOM
        this.selectSede = document.getElementById('selectSede');
        this.selectEstablecimiento = document.getElementById('selectEstablecimiento');
        
        // Referencias a elementos de estadísticas
        this.llegadasTiempo = document.getElementById('llegadasTiempo');
        this.llegadasTarde = document.getElementById('llegadasTarde');
        this.faltas = document.getElementById('faltas');
        this.horasTrabajadas = document.getElementById('horasTrabajadas');
        this.activityList = document.getElementById('activityList');
        
        // Referencias a gráficos
        this.attendanceByHourChart = null;
        this.attendanceDistributionChart = null;
        
        // Inicializar eventos
        this.initEvents();
        
        // Inicializar gráficos
        this.initCharts();
    }
    
    /**
     * Inicializar eventos de la página
     */
    initEvents() {
        if (this.selectSede) {
            this.selectSede.addEventListener('change', () => this.cargarEstablecimientos());
        }
        
        if (this.selectEstablecimiento) {
            this.selectEstablecimiento.addEventListener('change', () => this.cargarEstadisticas());
        }
    }
    
    /**
     * Inicializar gráficos de Chart.js
     */
    initCharts() {
        // Obtener los contextos de los gráficos
        const hourCtx = document.getElementById('attendanceByHourChart')?.getContext('2d');
        const distCtx = document.getElementById('attendanceDistributionChart')?.getContext('2d');
        
        if (hourCtx) {
            // Inicializar gráfico de asistencias por hora
            this.attendanceByHourChart = new Chart(hourCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Asistencias',
                        data: [],
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
        
        if (distCtx) {
            // Inicializar gráfico de distribución de asistencias
            this.attendanceDistributionChart = new Chart(distCtx, {
                type: 'pie',
                data: {
                    labels: ['A tiempo', 'Tardanzas', 'Faltas'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(255, 99, 132, 0.7)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 159, 64, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round(value / total * 100) : 0;
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    
    /**
     * Cargar establecimientos según la sede seleccionada
     */
    cargarEstablecimientos() {
        const sedeId = this.selectSede.value;
        if (!sedeId) return;
        
        // Limpiar establecimientos actuales
        this.selectEstablecimiento.innerHTML = '';
        
        // Mostrar indicador de carga
        this.selectEstablecimiento.innerHTML = '<option>Cargando...</option>';
        
        // Obtener establecimientos para la sede seleccionada
        fetch(`api/get-establecimientos.php?sede_id=${sedeId}`)
            .then(response => response.json())
            .then(data => {
                // Limpiar opciones actuales
                this.selectEstablecimiento.innerHTML = '';
                
                if (data.success && data.establecimientos && data.establecimientos.length > 0) {
                    // Agregar nuevas opciones
                    data.establecimientos.forEach(establecimiento => {
                        const option = document.createElement('option');
                        option.value = establecimiento.ID_ESTABLECIMIENTO;
                        option.textContent = establecimiento.NOMBRE;
                        this.selectEstablecimiento.appendChild(option);
                    });
                    
                    // Cargar estadísticas para el primer establecimiento
                    this.cargarEstadisticas();
                } else {
                    // No hay establecimientos
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No hay establecimientos disponibles';
                    this.selectEstablecimiento.appendChild(option);
                    
                    // Limpiar estadísticas
                    this.limpiarEstadisticas();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.selectEstablecimiento.innerHTML = '<option>Error al cargar establecimientos</option>';
            });
    }
    
    /**
     * Cargar estadísticas según el establecimiento seleccionado
     */
    cargarEstadisticas() {
        const establecimientoId = this.selectEstablecimiento.value;
        if (!establecimientoId) {
            this.limpiarEstadisticas();
            return;
        }
        
        // Obtener estadísticas para el establecimiento seleccionado
        fetch(`api/get-dashboard-stats.php?establecimiento_id=${establecimientoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar tarjetas de estadísticas
                    this.llegadasTiempo.textContent = data.estadisticas.llegadas_tiempo;
                    this.llegadasTarde.textContent = data.estadisticas.llegadas_tarde;
                    this.faltas.textContent = data.estadisticas.faltas;
                    this.horasTrabajadas.textContent = data.estadisticas.horas_trabajadas;
                    
                    // Actualizar gráfico de asistencias por hora
                    if (this.attendanceByHourChart) {
                        this.attendanceByHourChart.data.labels = data.asistenciasPorHora.labels;
                        this.attendanceByHourChart.data.datasets[0].data = data.asistenciasPorHora.values;
                        this.attendanceByHourChart.update();
                    }
                    
                    // Actualizar gráfico de distribución de asistencias
                    if (this.attendanceDistributionChart) {
                        this.attendanceDistributionChart.data.datasets[0].data = data.distribucionAsistencias.values;
                        this.attendanceDistributionChart.update();
                    }
                    
                    // Actualizar actividad reciente
                    this.actualizarActividadReciente(data.actividadReciente);
                } else {
                    console.error('Error en la respuesta:', data.error);
                    this.limpiarEstadisticas();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.limpiarEstadisticas();
            });
    }
    
    /**
     * Limpiar todas las estadísticas cuando no hay datos
     */
    limpiarEstadisticas() {
        // Limpiar tarjetas de estadísticas
        this.llegadasTiempo.textContent = '0';
        this.llegadasTarde.textContent = '0';
        this.faltas.textContent = '0';
        this.horasTrabajadas.textContent = '0';
        
        // Limpiar gráficos
        if (this.attendanceByHourChart) {
            this.attendanceByHourChart.data.labels = [];
            this.attendanceByHourChart.data.datasets[0].data = [];
            this.attendanceByHourChart.update();
        }
        
        if (this.attendanceDistributionChart) {
            this.attendanceDistributionChart.data.datasets[0].data = [0, 0, 0];
            this.attendanceDistributionChart.update();
        }
        
        // Limpiar actividad reciente
        this.activityList.innerHTML = '<p class="no-activity">No hay actividad reciente para mostrar.</p>';
    }
    
    /**
     * Actualizar la lista de actividad reciente
     */
    actualizarActividadReciente(actividades) {
        // Limpiar lista actual
        this.activityList.innerHTML = '';
        
        if (actividades && actividades.length > 0) {
            actividades.forEach(actividad => {
                const fechaHora = new Date(actividad.FECHA + 'T' + actividad.HORA);
                const fechaFormateada = fechaHora.toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }) + ' ' + fechaHora.toLocaleTimeString('es-ES', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const item = document.createElement('div');
                item.className = 'activity-item';
                
                const iconClass = actividad.TIPO === 'ENTRADA' ? 'entry' : 'exit';
                const iconType = actividad.TIPO === 'ENTRADA' ? 'sign-in-alt' : 'sign-out-alt';
                const actionText = actividad.TIPO === 'ENTRADA' ? 'llegó' : 'salió';
                
                item.innerHTML = `
                    <div class="activity-icon ${iconClass}">
                        <i class="fas fa-${iconType}"></i>
                    </div>
                    <div class="activity-details">
                        <p class="activity-title">
                            ${this.escapeHtml(actividad.NOMBRE)} ${this.escapeHtml(actividad.APELLIDO)} 
                            <span class="activity-type">${actionText}</span>
                            ${actividad.TIPO === 'ENTRADA' && actividad.TARDANZA === 'S' ? 
                                '<span class="badge-late">Tarde</span>' : ''}
                        </p>
                        <p class="activity-time">${fechaFormateada}</p>
                        ${actividad.OBSERVACION ? 
                            `<p class="activity-note">${this.escapeHtml(actividad.OBSERVACION)}</p>` : ''}
                    </div>
                `;
                
                this.activityList.appendChild(item);
            });
        } else {
            this.activityList.innerHTML = '<p class="no-activity">No hay actividad reciente para mostrar.</p>';
        }
    }
    
    /**
     * Función auxiliar para escapar HTML y prevenir XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializar el dashboard cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new Dashboard();
    
    // Manejar la apertura/cierre del menú de usuario
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');
    
    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', () => {
            userMenu.classList.toggle('active');
        });
        
        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!userMenuBtn.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('active');
            }
        });
    }
    
    // Manejar toggle del sidebar
    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    const container = document.querySelector('.container');
    
    if (toggleSidebarBtn && container) {
        toggleSidebarBtn.addEventListener('click', () => {
            container.classList.toggle('sidebar-collapsed');
        });
    }
});