<!-- Modal Registrar Asistencia -->
<div class="modal" id="attendanceRegisterModal">
  <div class="modal-content responsive-modal large-modal">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeAttendanceRegisterModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Navigation Bar -->
    <div class="modal-navbar">
      <div class="modal-nav-breadcrumb">
        <i class="fas fa-calendar-check"></i>
        <span>Asistencias</span>
        <i class="fas fa-chevron-right"></i>
        <span>Registrar Asistencia</span>
      </div>
      <div class="modal-nav-actions">
        <button type="button" class="btn-nav" id="btnModalRefresh" onclick="cargarEmpleadosParaRegistro()" title="Actualizar">
          <i class="fas fa-sync-alt"></i>
        </button>
        <button type="button" class="btn-nav" id="btnModalFullscreen" title="Pantalla completa">
          <i class="fas fa-expand"></i>
        </button>
      </div>
    </div>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-user-check"></i> Registrar Asistencia</h3>
      <p class="modal-subtitle">Fecha: <span id="reg_fecha"></span></p>
    </div>
    
    <div class="modal-body">
      <!-- Filtros de búsqueda estilizados pero manteniendo IDs originales -->
      <div class="attendance-query-box">
        <form class="attendance-query-form" autocomplete="off">
          <div class="query-row">
            <div class="form-group">
              <label for="reg_sede">Sede</label>
              <select id="reg_sede" name="sede" class="form-control"></select>
            </div>
            <div class="form-group">
              <label for="reg_establecimiento">Establecimiento</label>
              <select id="reg_establecimiento" name="establecimiento" class="form-control"></select>
            </div>
            <div class="form-group">
              <label for="codigoRegistroBusqueda">Código</label>
              <input type="text" id="codigoRegistroBusqueda" name="codigo" class="form-control" placeholder="Ingrese código">
            </div>
            <div class="form-group query-btns">
              <button type="button" id="btnBuscarCodigoRegistro" class="btn-primary">
                <i class="fas fa-search"></i> Buscar
              </button>
            </div>
        </div>
      </div>
      
      <!-- Información de filtro y botones rápidos -->
      <div id="filtroInfo" class="filter-info">
        <div class="filter-info-text">
          <i class="fas fa-info-circle"></i> Empleados con horarios asignados para hoy
        </div>
        
        <!-- Botones rápidos para filtrar por estado biométrico -->
        <div class="quick-filter-buttons">
          <button type="button" class="btn-quick" id="btnAllEmployees" onclick="setQuickBiometricFilter('all')">
            <i class="fas fa-users"></i> Todos
          </button>
          <button type="button" class="btn-quick active" id="btnPartialBiometric" onclick="setQuickBiometricFilter('partial')">
            <i class="fas fa-user-clock"></i> Biometría Parcial
          </button>
          <button type="button" class="btn-quick" id="btnNoBiometric" onclick="setQuickBiometricFilter('none')">
            <i class="fas fa-user-times"></i> Sin Biometría
          </button>
          <button type="button" class="btn-quick" id="btnCompleteBiometric" onclick="setQuickBiometricFilter('complete')">
            <i class="fas fa-user-check"></i> Biometría Completa
          </button>
        </div>
      </div>

      <!-- Tabla de empleados con estilo mejorado -->
      <div class="employee-table-container">
        <table class="attendance-table">
          <thead>
            <tr>
              <th>Código</th>
              <th>Nombre</th>
              <th>Establecimiento</th>
              <th>Sede</th>
              <th>Estado Biométrico</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody id="attendanceRegisterTableBody">
            <!-- Aquí se cargan los empleados disponibles -->
            <tr>
              <td colspan="6" class="loading-text">
                <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>