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
                    <!-- Stats Grid -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-info">
                                <h3>A Tiempo</h3>
                                <div class="stat-value">182</div>
                                <div class="stat-trend up">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+5% vs ayer</span>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Llegadas Tarde</h3>
                                <div class="stat-value">12</div>
                                <div class="stat-trend down">
                                    <i class="fas fa-arrow-down"></i>
                                    <span>-3% vs ayer</span>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon danger">
                                <i class="fas fa-user-times"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Faltas</h3>
                                <div class="stat-value">5</div>
                                <div class="stat-trend neutral">
                                    <i class="fas fa-minus"></i>
                                    <span>Sin cambios</span>
                                </div>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon info">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3>Horas Trabajadas</h3>
                                <div class="stat-value">1,456</div>
                                <div class="stat-trend up">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+2% vs promedio</span>
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
                            <button class="btn-primary">Ver Todo</button>
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
                                    <tr>
                                        <td>
                                            <div class="employee-column">
                                                <div class="employee-avatar">JP</div>
                                                <div class="employee-details">
                                                    <span class="employee-name">Juan Pérez</span>
                                                    <span class="employee-id">#EMP001</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>08:00:15</td>
                                        <td>Entrada</td>
                                        <td>
                                            <span class="status-badge ontime">
                                                <i class="fas fa-check-circle"></i>
                                                A tiempo
                                            </span>
                                        </td>
                                        <td>
                                            <div class="location-column">
                                                <i class="fas fa-building"></i>
                                                Sede Principal
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="employee-column">
                                                <div class="employee-avatar">MG</div>
                                                <div class="employee-details">
                                                    <span class="employee-name">María García</span>
                                                    <span class="employee-id">#EMP002</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>08:15:32</td>
                                        <td>Entrada</td>
                                        <td>
                                            <span class="status-badge late">
                                                <i class="fas fa-clock"></i>
                                                Tarde
                                            </span>
                                        </td>
                                        <td>
                                            <div class="location-column">
                                                <i class="fas fa-building"></i>
                                                Sede Norte
                                            </div>
                                        </td>
                                    </tr>
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
    <script src="assets/js/dashboard.js"></script>
</body>
</html>