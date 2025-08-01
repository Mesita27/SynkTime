/**
 * Employee Selector Module
 * Handles employee selection popup with multi-select functionality
 */
class EmployeeSelector {
    constructor() {
        this.selectedEmployees = new Set();
        this.allEmployees = [];
        this.filteredEmployees = [];
        this.currentFilters = null;
        this.onSelectionChangeCallback = null;
        
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Modal controls
        document.getElementById('closeEmployeeSelectorModal').addEventListener('click', () => {
            this.closeModal();
        });
        
        document.getElementById('cancelEmployeeSelection').addEventListener('click', () => {
            this.closeModal();
        });
        
        document.getElementById('confirmEmployeeSelection').addEventListener('click', () => {
            this.confirmSelection();
        });

        // Search functionality
        document.getElementById('searchEmployee').addEventListener('input', (e) => {
            this.filterEmployees(e.target.value);
        });

        // Selection controls
        document.getElementById('selectAllEmployees').addEventListener('click', () => {
            this.selectAll();
        });

        document.getElementById('deselectAllEmployees').addEventListener('click', () => {
            this.deselectAll();
        });

        // Modal outside click close
        document.getElementById('employeeSelectorModal').addEventListener('click', (e) => {
            if (e.target.id === 'employeeSelectorModal') {
                this.closeModal();
            }
        });
    }

    async openModal(currentFilters, selectedEmployeeIds = []) {
        this.currentFilters = currentFilters;
        this.selectedEmployees.clear();
        
        // Add currently selected employee IDs
        selectedEmployeeIds.forEach(id => this.selectedEmployees.add(id.toString()));
        
        // Show modal
        document.getElementById('employeeSelectorModal').style.display = 'block';
        document.getElementById('searchEmployee').focus();
        
        // Update active filters display
        this.updateActiveFiltersDisplay();
        
        // Load employees
        await this.loadEmployees();
    }

    closeModal() {
        document.getElementById('employeeSelectorModal').style.display = 'none';
        document.getElementById('searchEmployee').value = '';
        this.filteredEmployees = [];
    }

    async loadEmployees() {
        const employeesListContainer = document.getElementById('employeesList');
        employeesListContainer.innerHTML = `
            <div class="loading-employees">
                <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
            </div>
        `;

        try {
            let url = 'api/horas-trabajadas/get-empleados.php?';
            if (this.currentFilters.sede) url += `sede_id=${this.currentFilters.sede}&`;
            if (this.currentFilters.establecimiento) url += `establecimiento_id=${this.currentFilters.establecimiento}&`;

            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.empleados) {
                this.allEmployees = data.empleados;
                this.filteredEmployees = [...this.allEmployees];
                this.renderEmployeesList();
                this.updateSelectionCount();
            } else {
                this.showError(data.message || 'Error al cargar empleados');
            }
        } catch (error) {
            console.error('Error loading employees:', error);
            this.showError('Error al cargar empleados');
        }
    }

    filterEmployees(searchTerm) {
        if (!searchTerm.trim()) {
            this.filteredEmployees = [...this.allEmployees];
        } else {
            const term = searchTerm.toLowerCase();
            this.filteredEmployees = this.allEmployees.filter(emp => 
                emp.NOMBRE.toLowerCase().includes(term) ||
                emp.APELLIDO.toLowerCase().includes(term) ||
                emp.DNI.includes(term)
            );
        }
        this.renderEmployeesList();
    }

    renderEmployeesList() {
        const employeesListContainer = document.getElementById('employeesList');
        
        if (this.filteredEmployees.length === 0) {
            employeesListContainer.innerHTML = `
                <div class="no-employees">
                    <i class="fas fa-info-circle"></i> No se encontraron empleados.
                </div>
            `;
            return;
        }

        const employeesHTML = this.filteredEmployees.map(emp => {
            const employeeId = emp.ID_EMPLEADO.toString();
            const isSelected = this.selectedEmployees.has(employeeId);
            const initials = (emp.NOMBRE.charAt(0) + emp.APELLIDO.charAt(0)).toUpperCase();
            
            return `
                <div class="employee-item ${isSelected ? 'selected' : ''}" data-employee-id="${employeeId}">
                    <div class="employee-checkbox">
                        <input type="checkbox" ${isSelected ? 'checked' : ''} 
                               onchange="employeeSelector.toggleEmployee('${employeeId}')">
                    </div>
                    <div class="employee-info">
                        <div class="employee-avatar">${initials}</div>
                        <div class="employee-details">
                            <div class="employee-name">${this.escapeHtml(emp.NOMBRE + ' ' + emp.APELLIDO)}</div>
                            <div class="employee-meta">
                                <span class="employee-dni">DNI: ${emp.DNI}</span>
                                <span class="employee-establishment">${this.escapeHtml(emp.ESTABLECIMIENTO_NOMBRE || '')}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        employeesListContainer.innerHTML = employeesHTML;

        // Add click handlers for employee items
        document.querySelectorAll('.employee-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.type !== 'checkbox') {
                    const checkbox = item.querySelector('input[type="checkbox"]');
                    checkbox.click();
                }
            });
        });
    }

    toggleEmployee(employeeId) {
        if (this.selectedEmployees.has(employeeId)) {
            this.selectedEmployees.delete(employeeId);
        } else {
            this.selectedEmployees.add(employeeId);
        }
        
        this.updateEmployeeItemState(employeeId);
        this.updateSelectionCount();
    }

    updateEmployeeItemState(employeeId) {
        const employeeItem = document.querySelector(`[data-employee-id="${employeeId}"]`);
        const checkbox = employeeItem.querySelector('input[type="checkbox"]');
        const isSelected = this.selectedEmployees.has(employeeId);
        
        checkbox.checked = isSelected;
        employeeItem.classList.toggle('selected', isSelected);
    }

    selectAll() {
        this.filteredEmployees.forEach(emp => {
            this.selectedEmployees.add(emp.ID_EMPLEADO.toString());
        });
        this.renderEmployeesList();
        this.updateSelectionCount();
    }

    deselectAll() {
        // Only deselect employees that are currently visible
        this.filteredEmployees.forEach(emp => {
            this.selectedEmployees.delete(emp.ID_EMPLEADO.toString());
        });
        this.renderEmployeesList();
        this.updateSelectionCount();
    }

    updateSelectionCount() {
        document.getElementById('selectedCount').textContent = this.selectedEmployees.size;
    }

    updateActiveFiltersDisplay() {
        const filtersDisplay = document.getElementById('activeFiltersDisplay');
        const filters = [];
        
        if (this.currentFilters.sede) {
            const sedeSelect = document.getElementById('selectSede');
            const sedeText = sedeSelect.options[sedeSelect.selectedIndex]?.text || 'Sede seleccionada';
            filters.push(sedeText);
        }
        
        if (this.currentFilters.establecimiento) {
            const establecimientoSelect = document.getElementById('selectEstablecimiento');
            const establecimientoText = establecimientoSelect.options[establecimientoSelect.selectedIndex]?.text || 'Establecimiento seleccionado';
            filters.push(establecimientoText);
        }
        
        if (filters.length === 0) {
            filters.push('Todos los empleados');
        }
        
        filtersDisplay.innerHTML = filters.map(filter => 
            `<span class="filter-tag">${this.escapeHtml(filter)}</span>`
        ).join('');
    }

    confirmSelection() {
        const selectedEmployeesArray = Array.from(this.selectedEmployees);
        
        if (this.onSelectionChangeCallback) {
            this.onSelectionChangeCallback(selectedEmployeesArray);
        }
        
        this.closeModal();
    }

    getSelectedEmployees() {
        return Array.from(this.selectedEmployees);
    }

    setSelectionChangeCallback(callback) {
        this.onSelectionChangeCallback = callback;
    }

    showError(message) {
        const employeesListContainer = document.getElementById('employeesList');
        employeesListContainer.innerHTML = `
            <div class="no-employees">
                <i class="fas fa-exclamation-triangle"></i> ${this.escapeHtml(message)}
            </div>
        `;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global instance
let employeeSelector = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    employeeSelector = new EmployeeSelector();
});