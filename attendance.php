<?php
require_once 'auth/session.php';
requireAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistencias | SynkTime</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Dashboard Styles -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/attendance.css">
    <link rel="stylesheet" href="assets/css/pagination.css">
    <link rel="stylesheet" href="assets/css/attendance-pagination.css">
</head>
<body>
<div class="app-container">
    <?php include 'components/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'components/header.php'; ?>
        <main class="main-content">
            <div class="attendance-header">
                <h2 class="page-title"><i class="fas fa-calendar-check"></i> Asistencias</h2>
                <button type="button" class="btn-primary" onclick="openAttendanceRegisterModal()">
                    <i class="fas fa-plus"></i> Registrar Asistencia
                </button>
            </div>
            
            <!-- Formulario de búsqueda estilizado -->
            <div class="attendance-query-box">
                <form id="filtrosForm" class="attendance-query-form" autocomplete="off">
                    <div class="query-row">
                        <div class="form-group">
                            <label for="filtro_sede">Sede</label>
                            <select id="filtro_sede" name="sede" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_establecimiento">Establecimiento</label>
                            <select id="filtro_establecimiento" name="establecimiento" class="form-control"></select>
                        </div>
                        <div class="form-group">
                            <label for="filtro_tipo">Tipo</label>
                            <select id="filtro_tipo" name="tipo" class="form-control">
                                <option value="">Todos</option>
                                <option value="ENTRADA">Solo Entradas</option>
                                <option value="SALIDA">Solo Salidas</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="codigoBusqueda">Código Empleado</label>
                            <input type="text" id="codigoBusqueda" name="codigo" class="form-control" placeholder="Ingrese código">
                        </div>
                        <div class="form-group query-btns">
                            <button type="button" id="btnBuscarCodigo" class="btn-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <button type="button" id="btnLimpiar" class="btn-secondary">
                                <i class="fas fa-redo"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Los controles de paginación se insertan aquí automáticamente -->
            
            <!-- Tabla de asistencias -->
            <div class="attendance-table-container">
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Establecimiento</th>
                            <th>Sede</th>
                            <th>Fecha</th>
                            <th>Horario</th>
                            <th>Estado</th>
                            <th>Foto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <!-- JS: aquí se cargan las asistencias -->
                    </tbody>
                </table>
            </div>
            
            <?php include 'components/attendance_register_modal.php'; ?>
            <?php include 'components/attendance_photo_modal.php'; ?>
            <?php include 'components/attendance_observation_modal.php'; ?>
        </main>
    </div>
</div>
<script src="assets/js/layout.js"></script>
<script src="assets/js/attendance.js"></script>
</body>
</html>