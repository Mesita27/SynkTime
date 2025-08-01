<?php
// Dashboard demo with mock data to show enhanced interactivity
session_start();

// Mock data for demonstration
$empresaInfo = [
    'NOMBRE' => 'SynkTime Demo Corporation',
    'RUC' => '12345678901'
];

$sedes = [
    ['ID_SEDE' => 1, 'NOMBRE' => 'Sede Principal'],
    ['ID_SEDE' => 2, 'NOMBRE' => 'Sede Norte']
];

$establecimientos = [
    ['ID_ESTABLECIMIENTO' => 1, 'NOMBRE' => 'Oficina Central', 'ID_SEDE' => 1],
    ['ID_ESTABLECIMIENTO' => 2, 'NOMBRE' => 'Almacén', 'ID_SEDE' => 1]
];

$fechaDashboard = date('Y-m-d');
$sedeDefaultId = 1;
$establecimientoDefaultId = 1;

// Mock statistics
$estadisticas = [
    'llegadas_temprano' => 15,
    'llegadas_tiempo' => 45,
    'llegadas_tarde' => 8,
    'faltas' => 12,
    'horas_trabajadas' => 320.5
];

// Mock chart data
$asistenciasPorHora = [
    'categories' => ['07:00', '08:00', '09:00', '10:00', '11:00', '12:00'],
    'data' => [5, 25, 18, 12, 8, 2]
];

$distribucionAsistencias = [
    'series' => [15, 45, 8, 12]
];

// Mock recent activity
$actividadReciente = [
    [
        'ID_EMPLEADO' => 1,
        'NOMBRE' => 'Juan',
        'APELLIDO' => 'Pérez',
        'HORA' => '08:15:00',
        'TIPO' => 'ENTRADA',
        'TARDANZA' => 'N',
        'SEDE_NOMBRE' => 'Sede Principal'
    ],
    [
        'ID_EMPLEADO' => 2,
        'NOMBRE' => 'María',
        'APELLIDO' => 'González',
        'HORA' => '08:30:00',
        'TIPO' => 'ENTRADA',
        'TARDANZA' => 'S',
        'SEDE_NOMBRE' => 'Sede Principal'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SynkTime - Dashboard Demo</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --primary: #4B96FA;
            --primary-dark: #3A85E9;
            --surface: #ffffff;
            --background: #f8fafc;
            --border: #e2e8f0;
            --border-radius: 12px;
            --border-radius-sm: 6px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --text-tertiary: #718096;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            margin: 0;
            color: var(--text-primary);
        }
        
        .demo-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .demo-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: var(--border-radius);
        }
        
        .demo-header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2.5rem;
            font-weight: 600;
        }
        
        .demo-header p {
            margin: 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .features-list {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }
        
        .features-list h3 {
            margin: 0 0 1rem 0;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .features-list ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .features-list li {
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1><i class="fas fa-chart-line"></i> SynkTime Dashboard Demo</h1>
            <p>Dashboard refactorizado con interactividad completa</p>
        </div>
        
        <div class="features-list">
            <h3><i class="fas fa-rocket text-primary"></i> Funcionalidades Implementadas</h3>
            <ul>
                <li><strong>Tarjetas de Estadísticas Interactivas:</strong> Haz clic en cualquier tarjeta para ver detalles</li>
                <li><strong>Gráficos Interactivos:</strong> Haz clic en los segmentos del gráfico circular o en el gráfico de área</li>
                <li><strong>Popups Detallados:</strong> Información filtrada con exportación a Excel</li>
                <li><strong>Cálculos Basados en ASISTENCIA:</strong> Lógica actualizada usando tabla ASISTENCIA y tolerancias HORARIO</li>
                <li><strong>APIs con Tablas en Mayúsculas:</strong> Compatibilidad con servidor garantizada</li>
            </ul>
        </div>

        <div class="dashboard-container">
            <!-- Filtros -->
            <div class="filters-section">
                <div class="company-info">
                    <h2><?php echo htmlspecialchars($empresaInfo['NOMBRE']); ?></h2>
                    <p class="company-details"><i class="fas fa-building"></i> <?php echo htmlspecialchars($empresaInfo['RUC']); ?></p>
                </div>
                <div class="location-filters">
                    <div class="filter-group">
                        <label for="selectSede">Sede:</label>
                        <select id="selectSede" class="filter-select">
                            <option value="">Todos</option>
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
                            <option value="">Todos</option>
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
                <div class="stat-card clickable" id="stat-card-temprano" data-type="temprano" title="Click para ver detalles">
                    <div class="stat-icon info"><i class="fas fa-user-clock"></i></div>
                    <div class="stat-info">
                        <h3>Llegadas Tempranas</h3>
                        <div class="stat-value" id="llegadasTemprano"><?php echo $estadisticas['llegadas_temprano']; ?></div>
                    </div>
                </div>
                <div class="stat-card clickable" id="stat-card-atiempo" data-type="aTiempo" title="Click para ver detalles">
                    <div class="stat-icon success"><i class="fas fa-user-check"></i></div>
                    <div class="stat-info">
                        <h3>A Tiempo</h3>
                        <div class="stat-value" id="llegadasTiempo"><?php echo $estadisticas['llegadas_tiempo']; ?></div>
                    </div>
                </div>
                <div class="stat-card clickable" id="stat-card-tarde" data-type="tarde" title="Click para ver detalles">
                    <div class="stat-icon warning"><i class="fas fa-user-clock"></i></div>
                    <div class="stat-info">
                        <h3>Llegadas Tarde</h3>
                        <div class="stat-value" id="llegadasTarde"><?php echo $estadisticas['llegadas_tarde']; ?></div>
                    </div>
                </div>
                <div class="stat-card clickable" id="stat-card-faltas" data-type="faltas" title="Click para ver detalles">
                    <div class="stat-icon danger"><i class="fas fa-user-times"></i></div>
                    <div class="stat-info">
                        <h3>Faltas</h3>
                        <div class="stat-value" id="faltas"><?php echo $estadisticas['faltas']; ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3>Horas Trabajadas</h3>
                        <div class="stat-value" id="horasTrabajadas"><?php echo $estadisticas['horas_trabajadas']; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Grid -->
            <div class="charts-grid">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Asistencia por Hora</h3>
                        <div class="chart-actions">
                            <button class="btn-icon" title="Gráfico interactivo - Haz clic">
                                <i class="fas fa-mouse-pointer"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chart-container" id="hourlyAttendanceChart"></div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Distribución de Asistencias</h3>
                        <div class="chart-actions">
                            <button class="btn-icon" title="Gráfico interactivo - Haz clic en los segmentos">
                                <i class="fas fa-mouse-pointer"></i>
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
                    <span class="btn-primary" style="pointer-events: none;">Demo Data</span>
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
                        <tbody>
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
                                    </td>
                                    <td>
                                        <div class="location-column">
                                            <i class="fas fa-building"></i>
                                            <?php echo htmlspecialchars($actividad['SEDE_NOMBRE']); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Demo Modals -->
    <div class="modal" id="temprano-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-clock text-success"></i> Llegadas Tempranas</h3>
                <span class="modal-close" onclick="cerrarModal('temprano-modal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="modal-filters">
                    <div class="filter-info">
                        <i class="fas fa-calendar"></i> Fecha: <span id="temprano-modal-fecha">Demo</span>
                    </div>
                    <div class="filter-info">
                        <i class="fas fa-building"></i> Ubicación: <span id="temprano-modal-ubicacion">Demo</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <p style="text-align: center; padding: 2rem; color: #6c757d;">
                        <i class="fas fa-info-circle"></i> Esta es una demostración. En el sistema real, aquí se mostrarían los empleados con llegadas tempranas.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="cerrarModal('temprano-modal')">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
    // Variables globales para los datos iniciales
    const initialData = {
        sedeId: <?php echo json_encode($sedeDefaultId); ?>,
        establecimientoId: <?php echo json_encode($establecimientoDefaultId); ?>,
        fecha: <?php echo json_encode($fechaDashboard); ?>,
        hourlyAttendanceData: <?php echo json_encode($asistenciasPorHora); ?>,
        distributionData: {
            tempranos: <?php echo $estadisticas['llegadas_temprano']; ?>,
            atiempo: <?php echo $estadisticas['llegadas_tiempo']; ?>,
            tarde: <?php echo $estadisticas['llegadas_tarde']; ?>,
            faltas: <?php echo $estadisticas['faltas']; ?>
        }
    };

    // Demo popup function
    window.mostrarModalAsistencias = function(tipo) {
        const modal = document.getElementById('temprano-modal');
        const fechaElement = document.getElementById('temprano-modal-fecha');
        const ubicacionElement = document.getElementById('temprano-modal-ubicacion');
        
        if (modal && fechaElement && ubicacionElement) {
            const tipoNames = {
                'temprano': 'Llegadas Tempranas',
                'aTiempo': 'Llegadas A Tiempo', 
                'tarde': 'Llegadas Tarde',
                'faltas': 'Faltas'
            };
            
            modal.querySelector('.modal-header h3').innerHTML = `<i class="fas fa-chart-pie"></i> ${tipoNames[tipo] || 'Demo'}`;
            fechaElement.textContent = initialData.fecha;
            ubicacionElement.textContent = 'Sede Principal - Demo';
            modal.classList.add('show');
        }
    };

    // Cerrar modal function
    window.cerrarModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Add click handlers for stat cards
        document.querySelectorAll('.stat-card.clickable').forEach(card => {
            card.addEventListener('click', function() {
                const tipo = this.getAttribute('data-type');
                if (tipo) {
                    mostrarModalAsistencias(tipo);
                }
            });
        });

        // Initialize charts
        // Hourly attendance chart
        const hourlyOptions = {
            series: [{
                name: 'Entradas',
                data: initialData.hourlyAttendanceData.data
            }],
            chart: {
                type: 'area',
                height: 350,
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeinout', speed: 800 },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        mostrarModalAsistencias('aTiempo');
                    }
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
            stroke: { curve: 'smooth', width: 3 },
            xaxis: { 
                categories: initialData.hourlyAttendanceData.categories,
                labels: { style: { colors: '#718096' } } 
            },
            yaxis: { 
                labels: { style: { colors: '#718096' } } 
            },
            tooltip: { 
                theme: 'light', 
                y: { formatter: value => value + ' empleados' } 
            },
            grid: { 
                borderColor: '#e0e6ed', 
                strokeDashArray: 5, 
                xaxis: { lines: { show: true } }, 
                yaxis: { lines: { show: true } } 
            }
        };

        // Distribution chart  
        const distributionOptions = {
            series: [
                initialData.distributionData.tempranos,
                initialData.distributionData.atiempo,
                initialData.distributionData.tarde,
                initialData.distributionData.faltas
            ],
            chart: { 
                type: 'donut', 
                height: 350,
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        const tipoMap = ['temprano', 'aTiempo', 'tarde', 'faltas'];
                        const tipoSeleccionado = tipoMap[config.dataPointIndex];
                        
                        if (tipoSeleccionado) {
                            mostrarModalAsistencias(tipoSeleccionado);
                        }
                    }
                }
            },
            colors: ['#28A745', '#48BB78', '#F6AD55', '#F56565'],
            labels: ['Tempranos', 'A Tiempo', 'Tardanzas', 'Faltas'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                            }
                        }
                    }
                }
            },
            legend: { position: 'bottom', horizontalAlign: 'center' },
            dataLabels: { 
                enabled: true, 
                formatter: (val, opts) => opts.w.config.series[opts.seriesIndex] 
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: { width: 300 },
                    legend: { position: 'bottom' }
                }
            }]
        };

        const hourlyChart = new ApexCharts(document.querySelector("#hourlyAttendanceChart"), hourlyOptions);
        const distributionChart = new ApexCharts(document.querySelector("#attendanceDistributionChart"), distributionOptions);
        
        hourlyChart.render();
        distributionChart.render();

        // Modal close events
        window.addEventListener('click', function(event) {
            const modales = document.querySelectorAll('.modal');
            modales.forEach(function(modal) {
                if (event.target === modal) {
                    cerrarModal(modal.id);
                }
            });
        });

        window.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modalesAbiertos = document.querySelectorAll('.modal.show');
                modalesAbiertos.forEach(function(modal) {
                    cerrarModal(modal.id);
                });
            }
        });
    });
    </script>
</body>
</html>