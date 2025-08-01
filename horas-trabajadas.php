<?php
require_once 'auth/session.php';
requireAuth();
require_once 'config/database.php';

// Inicializar sesión si es necesario
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$usuarioInfo = null;
$empresaId = 1;

if (isset($_SESSION['username'])) {
    // Reutilizar función existente del dashboard
    require_once 'dashboard-controller.php';
    $usuarioInfo = getUsuarioInfo($_SESSION['username']);
    if ($usuarioInfo) {
        $empresaId = $usuarioInfo['ID_EMPRESA'];
        $_SESSION['id_empresa'] = $empresaId;
    }
} else {
    $empresaId = isset($_SESSION['id_empresa']) ? $_SESSION['id_empresa'] : 1;
}

// Obtener datos iniciales usando funciones existentes
$empresaInfo = getEmpresaInfo($empresaId);
$sedes = getSedesByEmpresa($empresaId);
$sedeDefault = count($sedes) > 0 ? $sedes[0] : null;
$sedeDefaultId = $sedeDefault ? $sedeDefault['ID_SEDE'] : null;
$establecimientos = $sedeDefaultId ? getEstablecimientosByEmpresa($empresaId, $sedeDefaultId) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Horas Trabajadas | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/horas-trabajadas.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="horas-trabajadas-header">
                <h2 class="page-title"><i class="fas fa-clock"></i> Gestión de Horas Trabajadas</h2>
                <div class="horas-trabajadas-actions">
                    <button class="btn-primary" id="btnExportarExcel">
                        <i class="fas fa-file-excel"></i> Exportar a Excel
                    </button>
                    <button class="btn-secondary" id="btnRegistrarDiaCivico">
                        <i class="fas fa-calendar-plus"></i> Registrar Día Cívico
                    </button>
                </div>
            </div>
            
            <!-- Filtros rápidos -->
            <div class="quick-filters">
                <button id="btnHoy" class="btn-filter active">
                    <i class="fas fa-calendar-day"></i> Hoy
                </button>
                <button id="btnAyer" class="btn-filter">
                    <i class="fas fa-calendar-minus"></i> Ayer
                </button>
                <button id="btnSemanaActual" class="btn-filter">
                    <i class="fas fa-calendar-week"></i> Semana actual
                </button>
                <button id="btnSemanaPasada" class="btn-filter">
                    <i class="fas fa-calendar-week"></i> Semana pasada
                </button>
                <button id="btnMesActual" class="btn-filter">
                    <i class="fas fa-calendar-alt"></i> Mes actual
                </button>
                <button id="btnMesPasado" class="btn-filter">
                    <i class="fas fa-calendar-alt"></i> Mes pasado
                </button>
            </div>
            
            <!-- Filtros detallados -->
            <div class="filters-section">
                <div class="filters-form">
                    <div class="filter-group">
                        <label for="selectSede">Sede:</label>
                        <select id="selectSede" class="filter-select">
                            <option value="">Todas las sedes</option>
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
                            <option value="">Todos los establecimientos</option>
                            <?php foreach ($establecimientos as $establecimiento): ?>
                                <option value="<?php echo $establecimiento['ID_ESTABLECIMIENTO']; ?>">
                                    <?php echo htmlspecialchars($establecimiento['NOMBRE']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="selectEmpleado">Empleado:</label>
                        <select id="selectEmpleado" class="filter-select">
                            <option value="">Todos los empleados</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="fechaDesde">Fecha desde:</label>
                        <input type="date" id="fechaDesde" class="filter-select" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="fechaHasta">Fecha hasta:</label>
                        <input type="date" id="fechaHasta" class="filter-select" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="filter-group">
                        <button class="btn-primary" id="btnFiltrar">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <button class="btn-secondary" id="btnLimpiarFiltros">
                            <i class="fas fa-times"></i> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fas fa-clock"></i></div>
                    <div class="stat-info">
                        <h3>Total Horas</h3>
                        <div class="stat-value" id="totalHoras">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fas fa-business-time"></i></div>
                    <div class="stat-info">
                        <h3>Horas Regular</h3>
                        <div class="stat-value" id="horasRegular">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fas fa-plus-circle"></i></div>
                    <div class="stat-info">
                        <h3>Horas Extra</h3>
                        <div class="stat-value" id="horasExtra">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon special"><i class="fas fa-church"></i></div>
                    <div class="stat-info">
                        <h3>Dominicales</h3>
                        <div class="stat-value" id="horasDominicales">0</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon holiday"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3>Festivos</h3>
                        <div class="stat-value" id="horasFestivos">0</div>
                    </div>
                </div>
            </div>

            <!-- Tabla de horas trabajadas -->
            <div class="table-section">
                <div class="table-header">
                    <h3>Detalle de Horas Trabajadas</h3>
                    <div class="table-actions">
                        <button class="btn-icon" id="btnRefresh" title="Actualizar">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table class="horas-table">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Fecha</th>
                                <th>Día</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>H. Regular</th>
                                <th>H. Extra</th>
                                <th>Dominical</th>
                                <th>Festivo</th>
                                <th>Total</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody id="horasTableBody">
                            <tr>
                                <td colspan="11" class="no-data">
                                    <i class="fas fa-info-circle"></i> Seleccione los filtros y presione "Filtrar" para ver las horas trabajadas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal para registrar día cívico -->
<div id="modalDiaCivico" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-plus"></i> Registrar Día Cívico</h3>
            <button class="modal-close" id="closeDiaCivico">&times;</button>
        </div>
        <div class="modal-body">
            <form id="formDiaCivico">
                <div class="form-group">
                    <label for="fechaDiaCivico">Fecha:</label>
                    <input type="date" id="fechaDiaCivico" name="fecha" required>
                </div>
                <div class="form-group">
                    <label for="nombreDiaCivico">Nombre del día cívico:</label>
                    <input type="text" id="nombreDiaCivico" name="nombre" placeholder="Ej: Día de la Madre" required>
                </div>
                <div class="form-group">
                    <label for="descripcionDiaCivico">Descripción:</label>
                    <textarea id="descripcionDiaCivico" name="descripcion" rows="3" placeholder="Descripción opcional"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" id="cancelDiaCivico">Cancelar</button>
                    <button type="submit" class="btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/layout.js"></script>
<script src="assets/js/horas-trabajadas.js"></script>
</body>
</html>