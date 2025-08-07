<!-- Modal para ver foto ampliada -->
<div id="photoModal" class="modal">
  <div class="photo-modal-content">
    <h3 id="photoModalTitle"></h3>
    <img id="photoModalImage" src="" alt="Foto de asistencia">
    <button class="photo-modal-close" onclick="closePhotoModal()">
      <i class="fas fa-times"></i>
    </button>
  </div>
</div>

<!-- Modal mejorado para navegación de empleados con tabla -->
<div id="employeeNavigationModal" class="modal">
  <div class="modal-content large-modal">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeEmployeeNavigationModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <div class="modal-header">
      <h3 class="modal-title"><i class="fas fa-users"></i> Seleccionar Empleado</h3>
      <p class="modal-subtitle">Buscar y seleccionar empleado para registro de asistencia</p>
    </div>
    
    <div class="modal-body">
      <!-- Navegación/Tab superior -->
      <div class="employee-nav-tabs">
        <button class="nav-tab active" onclick="switchEmployeeTab('search')">
          <i class="fas fa-search"></i> Búsqueda
        </button>
        <button class="nav-tab" onclick="switchEmployeeTab('recent')">
          <i class="fas fa-clock"></i> Recientes
        </button>
        <button class="nav-tab" onclick="switchEmployeeTab('all')">
          <i class="fas fa-list"></i> Todos
        </button>
      </div>
      
      <!-- Contenido de búsqueda -->
      <div id="searchTab" class="tab-content active">
        <!-- Filtros de búsqueda AJAX -->
        <div class="employee-search-filters">
          <form class="search-form" autocomplete="off">
            <div class="filter-row">
              <div class="form-group">
                <label for="nav_search_sede">Sede</label>
                <select id="nav_search_sede" class="form-control">
                  <option value="">Todas las sedes</option>
                </select>
              </div>
              <div class="form-group">
                <label for="nav_search_establecimiento">Establecimiento</label>
                <select id="nav_search_establecimiento" class="form-control">
                  <option value="">Todos los establecimientos</option>
                </select>
              </div>
              <div class="form-group">
                <label for="nav_search_codigo">Código Empleado</label>
                <input type="text" id="nav_search_codigo" class="form-control" placeholder="Ej: 001">
              </div>
              <div class="form-group">
                <label for="nav_search_nombre">Nombre</label>
                <input type="text" id="nav_search_nombre" class="form-control" placeholder="Buscar por nombre">
              </div>
              <div class="form-group filter-actions">
                <button type="button" class="btn-primary" onclick="searchEmployeesForNavigation()">
                  <i class="fas fa-search"></i> Buscar
                </button>
                <button type="button" class="btn-secondary" onclick="clearEmployeeNavigationFilters()">
                  <i class="fas fa-redo"></i> Limpiar
                </button>
              </div>
            </div>
          </form>
        </div>
        
        <!-- Tabla de empleados -->
        <div class="employee-navigation-table-container">
          <table class="employee-navigation-table">
            <thead>
              <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Establecimiento</th>
                <th>Sede</th>
                <th>Horarios</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody id="employeeNavigationTableBody">
              <tr>
                <td colspan="6" class="loading-text">
                  <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Paginación -->
        <div class="employee-navigation-pagination" id="employeeNavigationPagination">
          <!-- Paginación se genera dinámicamente -->
        </div>
      </div>
      
      <!-- Contenido de recientes -->
      <div id="recentTab" class="tab-content">
        <div class="recent-employees">
          <h4><i class="fas fa-clock"></i> Empleados con actividad reciente</h4>
          <div class="recent-employees-grid" id="recentEmployeesGrid">
            <!-- Se carga dinámicamente -->
          </div>
        </div>
      </div>
      
      <!-- Contenido de todos -->
      <div id="allTab" class="tab-content">
        <div class="all-employees">
          <h4><i class="fas fa-list"></i> Todos los empleados activos</h4>
          <div class="employee-navigation-table-container">
            <table class="employee-navigation-table">
              <thead>
                <tr>
                  <th>Código</th>
                  <th>Nombre</th>
                  <th>Establecimiento</th>
                  <th>Sede</th>
                  <th>Estado</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody id="allEmployeesTableBody">
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
  </div>
</div>