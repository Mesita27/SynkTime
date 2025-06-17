<?php
// Incluir la conexión a la base de datos
require_once 'config/database.php';
// Incluir controlador del dashboard
require_once 'dashboard-controller.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Obtener información del usuario logueado
$usuarioInfo = null;
$empresaId = 1; // Default fallback

if (isset($_SESSION['username'])) {
    $usuarioInfo = getUsuarioInfo($_SESSION['username']);
    if ($usuarioInfo) {
        $empresaId = $usuarioInfo['ID_EMPRESA'];
        // Actualizar datos de sesión
        $_SESSION['id_empresa'] = $empresaId;
        $_SESSION['user_id'] = $usuarioInfo['ID_USUARIO']; // Para el logout
        $_SESSION['nombre_completo'] = $usuarioInfo['NOMBRE_COMPLETO'];
        $_SESSION['rol'] = $usuarioInfo['ROL'];
        $_SESSION['empresa_nombre'] = $usuarioInfo['EMPRESA_NOMBRE'];
    }
} else {
    // Si no hay sesión, usar valores por defecto o redireccionar al login
    $empresaId = isset($_SESSION['id_empresa']) ? $_SESSION['id_empresa'] : 1;
}

// Usar fecha actual del sistema
$fechaDashboard = date('Y-m-d');

// Obtener información de la empresa
$empresaInfo = getEmpresaInfo($empresaId);

// Obtener sedes de la empresa
$sedes = getSedesByEmpresa($empresaId);

// Obtener la primera sede por defecto
$sedeDefault = count($sedes) > 0 ? $sedes[0] : null;
$sedeDefaultId = $sedeDefault ? $sedeDefault['ID_SEDE'] : null;

// Obtener establecimientos de la primera sede
$establecimientos = $sedeDefaultId ? getEstablecimientosByEmpresa($empresaId, $sedeDefaultId) : [];

// Obtener el primer establecimiento por defecto
$establecimientoDefault = count($establecimientos) > 0 ? $establecimientos[0] : null;
$establecimientoDefaultId = $establecimientoDefault ? $establecimientoDefault['ID_ESTABLECIMIENTO'] : null;

// Obtener estadísticas del primer establecimiento
$estadisticas = $establecimientoDefaultId ? getEstadisticasAsistencia($establecimientoDefaultId, $fechaDashboard) : null;

// Obtener datos para gráficos del primer establecimiento
$asistenciasPorHora = $establecimientoDefaultId ? getAsistenciasPorHoraEstablecimiento($establecimientoDefaultId, $fechaDashboard) : ['categories' => [], 'data' => []];
$distribucionAsistencias = $establecimientoDefaultId ? getDistribucionAsistenciasEstablecimiento($establecimientoDefaultId, $fechaDashboard) : ['series' => [0, 0, 0]];

// Obtener actividad reciente del primer establecimiento
$actividadReciente = $establecimientoDefaultId ? getActividadRecienteEstablecimiento($establecimientoDefaultId, $fechaDashboard) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SynkTime - Dashboard</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    
    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <div class="main-wrapper">
            <!-- Header -->
            <?php include 'components/header.php'; ?>

            <!-- Main Content -->
            <main class="main-content">
                <div class="dashboard-container">
                    <!-- Filtros de ubicación -->
                    <div class="filters-section">
                        <div class="company-info">
                            <h2><?php echo htmlspecialchars($empresaInfo['NOMBRE'] ?? 'Empresa'); ?></h2>
                            <p class="company-details">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($empresaInfo['RUC'] ?? 'RUC no disponible'); ?>
                            </p>
                        </div>
                        <div class="location-filters">
                            <div class="filter-group">
                                <label for="selectSede">Sede:</label>
                                <select id="selectSede" class="filter-select">
                                    <?php foreach ($sedes as $sede): ?>
                                        <option value="<?php echo $sede['ID_SEDE']; ?>" <?php echo ($sedeDefaultId == $sede['ID_SEDE']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sede['NOMBRE']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="selectEstablecimiento">Establecimiento:</label>
                                <select id="selectEstablecimiento" class="filter-select">
                                    <?php foreach ($establecimientos as $establecimiento): ?>
                                        <option value="<?php echo $establecimiento['ID_ESTABLECIMIENTO']; ?>" <?php echo ($establecimientoDefaultId == $establecimiento['ID_ESTABLECIMIENTO']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($establecimiento['NOMBRE']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="selectFecha">Fecha:</label>
                                <input type="date" id="selectFecha" class="filter-select" value="<?php echo $fechaDashboard; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3>A Tiempo</h3>
                                <div class="stat-value" id="llegadasTiempo"><?php echo $estadisticas ? ($estadisticas['llegadas_tiempo'] ?? 0) : 0; ?></div>
                                <div class="stat-trend up">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>Asistencias puntuales</span>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Llegadas Tarde</h3>
                                <div class="stat-value" id="llegadasTarde"><?php echo $estadisticas ? ($estadisticas['llegadas_tarde'] ?? 0) : 0; ?></div>
                                <div class="stat-trend down">
                                    <i class="fas fa-arrow-down"></i>
                                    <span>Registros con tardanza</span>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon danger">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Faltas</h3>
                                <div class="stat-value" id="faltas"><?php echo $estadisticas ? ($estadisticas['faltas'] ?? 0) : 0; ?></div>
                                <div class="stat-trend neutral">
                                    <i class="fas fa-minus"></i>
                                    <span>Ausencias registradas</span>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Horas Trabajadas</h3>
                                <div class="stat-value" id="horasTrabajadas"><?php echo $estadisticas ? ($estadisticas['horas_trabajadas'] ?? 0) : 0; ?></div>
                                <div class="stat-trend up">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>Horas productivas</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Grid -->
                    <div class="charts-grid">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3>Asistencia por Hora</h3>
                                <div class="chart-actions">
                                    <button class="btn-icon" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="chart-container" id="hourlyAttendanceChart"></div>
                        </div>

                        <div class="chart-card">
                            <div class="chart-header">
                                <h3>Distribución de Asistencias</h3>
                                <div class="chart-actions">
                                    <button class="btn-icon" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="chart-container" id="attendanceDistributionChart"></div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="activity-section">
                        <div class="section-header">
                            <h3>Actividad Reciente</h3>
                            <a href="attendance.php" class="btn-primary">Ver Todo</a>
                        </div>
                        <div class="table-container">
                            <table class="activity-table">
                                <thead>
                                    <tr>
                                        <th>Empleado</th>
                                        <th>Hora</th>
                                        <th>Tipo</th>
                                        <th>Estado</th>
                                        <th>Ubicación</th>
                                    </tr>
                                </thead>
                                <tbody id="activityTableBody">
                                    <?php if ($actividadReciente && count($actividadReciente) > 0): ?>
                                        <?php foreach ($actividadReciente as $actividad): ?>
                                            <tr>
                                                <td>
                                                    <div class="employee-column">
                                                        <div class="employee-avatar"><?php echo substr($actividad['NOMBRE'], 0, 1) . substr($actividad['APELLIDO'], 0, 1); ?></div>
                                                        <div class="employee-details">
                                                            <span class="employee-name"><?php echo htmlspecialchars($actividad['NOMBRE'] . ' ' . $actividad['APELLIDO']); ?></span>
                                                            <span class="employee-id">#EMP<?php echo str_pad($actividad['ID_EMPLEADO'], 3, '0', STR_PAD_LEFT); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $actividad['HORA']; ?></td>
                                                <td><?php echo $actividad['TIPO'] == 'ENTRADA' ? 'Entrada' : 'Salida'; ?></td>
                                                <td>
                                                    <?php if ($actividad['TIPO'] == 'ENTRADA'): ?>
                                                        <?php if ($actividad['TARDANZA'] == 'N'): ?>
                                                            <span class="status-badge ontime">
                                                                <i class="fas fa-check-circle"></i>
                                                                A tiempo
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="status-badge late">
                                                                <i class="fas fa-clock"></i>
                                                                Tarde
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="status-badge info">
                                                            <i class="fas fa-sign-out-alt"></i>
                                                            Salida
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="location-column">
                                                        <i class="fas fa-building"></i>
                                                        <?php echo htmlspecialchars($actividad['SEDE_NOMBRE']); ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="no-data">No hay actividad reciente para mostrar en la fecha seleccionada.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/layout.js"></script>
    <script>
    // Variables globales para los datos iniciales
    const initialData = {
        sedeId: <?php echo json_encode($sedeDefaultId); ?>,
        establecimientoId: <?php echo json_encode($establecimientoDefaultId); ?>,
        fecha: <?php echo json_encode($fechaDashboard); ?>,
        hourlyAttendanceData: <?php echo json_encode($asistenciasPorHora); ?>,
        distributionData: <?php echo json_encode($distribucionAsistencias); ?>
    };

    // Inicializar dashboard cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar dashboard con datos iniciales
        const dashboard = new Dashboard(initialData);
        
        // Referencias a elementos del DOM
        const selectSede = document.getElementById('selectSede');
        const selectEstablecimiento = document.getElementById('selectEstablecimiento');
        const selectFecha = document.getElementById('selectFecha');
        const llegadasTiempo = document.getElementById('llegadasTiempo');
        const llegadasTarde = document.getElementById('llegadasTarde');
        const faltas = document.getElementById('faltas');
        const horasTrabajadas = document.getElementById('horasTrabajadas');
        const activityTableBody = document.getElementById('activityTableBody');
        
        // Configurar fecha máxima (fecha actual)
        const today = new Date().toISOString().split('T')[0];
        selectFecha.setAttribute('max', today);
        
        // Evento para cambio de sede
        if (selectSede) {
            selectSede.addEventListener('change', function() {
                const sedeId = this.value;
                if (!sedeId) {
                    selectEstablecimiento.innerHTML = '<option value="">Seleccione un establecimiento</option>';
                    limpiarEstadisticas();
                    return;
                }
                
                // Limpiar establecimientos actuales
                selectEstablecimiento.innerHTML = '';
                
                // Mostrar indicador de carga
                selectEstablecimiento.innerHTML = '<option>Cargando...</option>';
                
                // Obtener establecimientos para la sede seleccionada
                fetch(`api/get-establecimientos.php?sede_id=${sedeId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Limpiar opciones actuales
                        selectEstablecimiento.innerHTML = '';
                        
                        if (data.success && data.establecimientos && data.establecimientos.length > 0) {
                            // Agregar nuevas opciones
                            data.establecimientos.forEach((establecimiento, index) => {
                                const option = document.createElement('option');
                                option.value = establecimiento.ID_ESTABLECIMIENTO;
                                option.textContent = establecimiento.NOMBRE;
                                // Seleccionar el primer establecimiento automáticamente
                                if (index === 0) {
                                    option.selected = true;
                                }
                                selectEstablecimiento.appendChild(option);
                            });
                            
                            // Cargar estadísticas para el primer establecimiento automáticamente
                            if (data.establecimientos.length > 0) {
                                cargarEstadisticas(data.establecimientos[0].ID_ESTABLECIMIENTO);
                            }
                        } else {
                            // No hay establecimientos
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = 'No hay establecimientos disponibles';
                            selectEstablecimiento.appendChild(option);
                            
                            // Limpiar estadísticas
                            limpiarEstadisticas();
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        selectEstablecimiento.innerHTML = '<option>Error al cargar establecimientos</option>';
                    });
            });
        }
        
        // Evento para cambio de establecimiento
        if (selectEstablecimiento) {
            selectEstablecimiento.addEventListener('change', function() {
                const establecimientoId = this.value;
                if (!establecimientoId) {
                    limpiarEstadisticas();
                    return;
                }
                
                cargarEstadisticas(establecimientoId);
            });
        }
        
        // Evento para cambio de fecha
        if (selectFecha) {
            selectFecha.addEventListener('change', function() {
                const establecimientoId = selectEstablecimiento.value;
                if (!establecimientoId) {
                    return;
                }
                
                cargarEstadisticas(establecimientoId);
            });
        }
        
        // Función para cargar estadísticas
        function cargarEstadisticas(establecimientoId) {
            const fecha = selectFecha.value || initialData.fecha;
            
            // Mostrar indicador de carga
            mostrarCargando();
            
            fetch(`api/get-dashboard-stats.php?establecimiento_id=${establecimientoId}&fecha=${fecha}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Actualizar tarjetas de estadísticas
                        llegadasTiempo.textContent = data.estadisticas.llegadas_tiempo || 0;
                        llegadasTarde.textContent = data.estadisticas.llegadas_tarde || 0;
                        faltas.textContent = data.estadisticas.faltas || 0;
                        horasTrabajadas.textContent = data.estadisticas.horas_trabajadas || 0;
                        
                        // Actualizar gráficos
                        dashboard.updateCharts(data.asistenciasPorHora, data.distribucionAsistencias);
                        
                        // Actualizar tabla de actividad reciente
                        actualizarTablaActividad(data.actividadReciente);
                    } else {
                        console.error('Error en la respuesta:', data.error);
                        limpiarEstadisticas();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    limpiarEstadisticas();
                });
        }
        
        // Función para mostrar indicador de carga
        function mostrarCargando() {
            llegadasTiempo.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            llegadasTarde.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            faltas.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            horasTrabajadas.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            activityTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="no-data">
                        <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                    </td>
                </tr>
            `;
        }
        
        // Función para limpiar estadísticas
        function limpiarEstadisticas() {
            llegadasTiempo.textContent = '0';
            llegadasTarde.textContent = '0';
            faltas.textContent = '0';
            horasTrabajadas.textContent = '0';
            
            dashboard.updateCharts({ categories: [], data: [] }, { series: [0, 0, 0] });
            
            activityTableBody.innerHTML = `
                <tr>
                    <td colspan="5" class="no-data">Seleccione un establecimiento para ver la actividad.</td>
                </tr>
            `;
        }
        
        // Función para actualizar tabla de actividad reciente
        function actualizarTablaActividad(actividades) {
            if (!activityTableBody) return;
            
            if (actividades && actividades.length > 0) {
                activityTableBody.innerHTML = '';
                
                actividades.forEach(actividad => {
                    const row = document.createElement('tr');
                    
                    const initials = actividad.NOMBRE.charAt(0) + actividad.APELLIDO.charAt(0);
                    const employeeId = `#EMP${String(actividad.ID_EMPLEADO).padStart(3, '0')}`;
                    const statusBadgeClass = actividad.TIPO === 'ENTRADA' 
                        ? (actividad.TARDANZA === 'N' ? 'ontime' : 'late')
                        : 'info';
                    const statusIcon = actividad.TIPO === 'ENTRADA'
                        ? (actividad.TARDANZA === 'N' ? 'check-circle' : 'clock')
                        : 'sign-out-alt';
                    const statusText = actividad.TIPO === 'ENTRADA'
                        ? (actividad.TARDANZA === 'N' ? 'A tiempo' : 'Tarde')
                        : 'Salida';
                    
                    row.innerHTML = `
                        <td>
                            <div class="employee-column">
                                <div class="employee-avatar">${initials}</div>
                                <div class="employee-details">
                                    <span class="employee-name">${escapeHtml(actividad.NOMBRE + ' ' + actividad.APELLIDO)}</span>
                                    <span class="employee-id">${employeeId}</span>
                                </div>
                            </div>
                        </td>
                        <td>${actividad.HORA}</td>
                        <td>${actividad.TIPO === 'ENTRADA' ? 'Entrada' : 'Salida'}</td>
                        <td>
                            <span class="status-badge ${statusBadgeClass}">
                                <i class="fas fa-${statusIcon}"></i>
                                ${statusText}
                            </span>
                        </td>
                        <td>
                            <div class="location-column">
                                <i class="fas fa-building"></i>
                                ${escapeHtml(actividad.SEDE_NOMBRE)}
                            </div>
                        </td>
                    `;
                    
                    activityTableBody.appendChild(row);
                });
            } else {
                activityTableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="no-data">No hay actividad reciente para mostrar en la fecha seleccionada.</td>
                    </tr>
                `;
            }
        }
        
        // Función para escapar HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });

    // Clase Dashboard para manejar los gráficos
    class Dashboard {
        constructor(initialData) {
            this.hourlyAttendanceChart = null;
            this.attendanceDistributionChart = null;
            this.initializeCharts(initialData);
        }

        initializeCharts(initialData) {
            // Gráfica de Asistencia por Hora
            const hourlyOptions = {
                series: [{
                    name: 'Entradas',
                    data: initialData.hourlyAttendanceData ? initialData.hourlyAttendanceData.data : []
                }],
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                colors: ['#4B96FA'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        type: 'vertical',
                        shadeIntensity: 0.3,
                        opacityFrom: 0.7,
                        opacityTo: 0.2,
                        stops: [0, 90, 100]
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    categories: initialData.hourlyAttendanceData ? initialData.hourlyAttendanceData.categories : [],
                    labels: {
                        style: {
                            colors: '#718096'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#718096'
                        }
                    }
                },
                tooltip: {
                    theme: 'light',
                    y: {
                        formatter: function(value) {
                            return value + ' empleados'
                        }
                    }
                },
                grid: {
                    borderColor: '#e0e6ed',
                    strokeDashArray: 5,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                }
            };

            // Gráfica de Distribución de Asistencias
            const distributionOptions = {
                series: initialData.distributionData ? initialData.distributionData.series : [0, 0, 0],
                chart: {
                    type: 'donut',
                    height: 350
                },
                colors: ['#48BB78', '#F6AD55', '#F56565'],
                labels: ['A Tiempo', 'Tardanzas', 'Faltas'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => {
                                            return a + b
                                        }, 0)
                                    }
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val, opts) {
                        return opts.w.config.series[opts.seriesIndex]
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 300
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            // Inicializar gráficos
            this.hourlyAttendanceChart = new ApexCharts(
                document.querySelector("#hourlyAttendanceChart"), 
                hourlyOptions
            );
            
            this.attendanceDistributionChart = new ApexCharts(
                document.querySelector("#attendanceDistributionChart"), 
                distributionOptions
            );

            // Renderizar gráficos
            this.hourlyAttendanceChart.render();
            this.attendanceDistributionChart.render();
        }

        updateCharts(hourlyData, distributionData) {
            // Actualizar gráfico de asistencias por hora
            if (this.hourlyAttendanceChart) {
                this.hourlyAttendanceChart.updateOptions({
                    xaxis: {
                        categories: hourlyData.categories || []
                    }
                });
                this.hourlyAttendanceChart.updateSeries([{
                    name: 'Entradas',
                    data: hourlyData.data || []
                }]);
            }

            // Actualizar gráfico de distribución
            if (this.attendanceDistributionChart) {
                this.attendanceDistributionChart.updateSeries(
                    distributionData.series || [0, 0, 0]
                );
            }
        }
    }
    </script>
</body>
</html>