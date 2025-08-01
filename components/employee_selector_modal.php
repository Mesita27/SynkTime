<!-- Modal para selección de empleados -->
<div id="employeeSelectorModal" class="modal">
    <div class="modal-content modal-content-lg">
        <div class="modal-header">
            <h3><i class="fas fa-users"></i> Seleccionar Empleados</h3>
            <button class="modal-close" id="closeEmployeeSelectorModal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="employee-selector-content">
                <!-- Búsqueda -->
                <div class="search-section">
                    <div class="search-group">
                        <input type="text" id="searchEmployee" class="search-input" 
                               placeholder="Buscar por nombre, apellido o DNI...">
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                
                <!-- Filtros activos -->
                <div class="active-filters">
                    <span class="filter-label">Filtros aplicados:</span>
                    <div id="activeFiltersDisplay" class="filters-display">
                        <span class="filter-tag">Todos los empleados</span>
                    </div>
                </div>
                
                <!-- Selección múltiple -->
                <div class="selection-controls">
                    <div class="selection-buttons">
                        <button type="button" class="btn-small btn-secondary" id="selectAllEmployees">
                            <i class="fas fa-check-square"></i> Seleccionar Todos
                        </button>
                        <button type="button" class="btn-small btn-secondary" id="deselectAllEmployees">
                            <i class="fas fa-square"></i> Deseleccionar Todos
                        </button>
                    </div>
                    <div class="selection-count">
                        <span id="selectedCount">0</span> empleados seleccionados
                    </div>
                </div>
                
                <!-- Lista de empleados -->
                <div class="employees-list-container">
                    <div id="employeesList" class="employees-list">
                        <div class="loading-employees">
                            <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="cancelEmployeeSelection">Cancelar</button>
            <button type="button" class="btn-primary" id="confirmEmployeeSelection">
                <i class="fas fa-check"></i> Aplicar Selección
            </button>
        </div>
    </div>
</div>

<style>
/* Employee Selector Modal Styles */
.modal-content-lg {
    max-width: 800px;
    width: 90%;
}

.employee-selector-content {
    min-height: 400px;
    max-height: 600px;
    display: flex;
    flex-direction: column;
}

.search-section {
    margin-bottom: 1rem;
}

.search-group {
    position: relative;
}

.search-input {
    width: 100%;
    padding: 0.75rem 2.5rem 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-primary);
    color: var(--text-primary);
    font-size: 0.875rem;
    transition: border-color 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.search-icon {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    pointer-events: none;
}

.active-filters {
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: var(--bg-secondary);
    border-radius: 6px;
    border: 1px solid var(--border-color);
}

.filter-label {
    font-weight: 500;
    color: var(--text-secondary);
    margin-right: 0.5rem;
    white-space: nowrap;
}

.filters-display {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.filter-tag {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.selection-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.selection-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-small {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.selection-count {
    font-weight: 500;
    color: var(--text-primary);
}

.employees-list-container {
    flex: 1;
    min-height: 300px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-primary);
    overflow-y: auto;
}

.employees-list {
    padding: 0.5rem;
}

.loading-employees {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
}

.employee-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.employee-item:hover {
    background: var(--bg-hover);
    border-color: var(--border-color);
}

.employee-item.selected {
    background: rgba(59, 130, 246, 0.1);
    border-color: var(--primary-color);
}

.employee-checkbox {
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.employee-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
}

.employee-info {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.employee-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
    flex-shrink: 0;
}

.employee-details {
    flex: 1;
    min-width: 0;
}

.employee-name {
    font-weight: 500;
    color: var(--text-primary);
    margin-bottom: 0.125rem;
}

.employee-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--text-secondary);
}

.employee-dni {
    font-weight: 500;
}

.employee-establishment {
    color: var(--primary-color);
}

.no-employees {
    text-align: center;
    padding: 2rem;
    color: var(--text-secondary);
    font-style: italic;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    background: var(--bg-secondary);
}

@media (max-width: 768px) {
    .modal-content-lg {
        width: 95%;
        max-width: none;
    }
    
    .selection-controls {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    
    .selection-buttons {
        justify-content: center;
    }
    
    .employee-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>