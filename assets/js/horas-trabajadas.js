/**
 * Horas Trabajadas Module
 * Handles worked hours management functionality
 */

class HorasTrabajadas {
    constructor() {
        this.currentFilters = {
            sede: '',
            establecimiento: '',
            empleados: [], // Changed from single empleado to array
            fechaDesde: new Date().toISOString().split('T')[0],
            fechaHasta: new Date().toISOString().split('T')[0]
        };
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupDateRestrictions();
        this.loadInitialData();
        this.setQuickFilter('hoy'); // Set default filter
        this.initializeEmployeeSelector();
    }

    initializeEmployeeSelector() {
        // Set up employee selector callback
        if (window.employeeSelector) {
            employeeSelector.setSelectionChangeCallback((selectedEmployeeIds) => {
                this.handleEmployeeSelectionChange(selectedEmployeeIds);
            });
        }
    }

    bindEvents() {
        // Quick filter buttons
        document.getElementById('btnHoy').addEventListener('click', () => this.setQuickFilter('hoy'));
        document.getElementById('btnAyer').addEventListener('click', () => this.setQuickFilter('ayer'));
        document.getElementById('btnSemanaActual').addEventListener('click', () => this.setQuickFilter('semanaActual'));
        document.getElementById('btnSemanaPasada').addEventListener('click', () => this.setQuickFilter('semanaPasada'));
        document.getElementById('btnMesActual').addEventListener('click', () => this.setQuickFilter('mesActual'));
        document.getElementById('btnMesPasado').addEventListener('click', () => this.setQuickFilter('mesPasado'));

        // Filter controls
        document.getElementById('selectSede').addEventListener('change', this.onSedeChange.bind(this));
        document.getElementById('selectEstablecimiento').addEventListener('change', this.onEstablecimientoChange.bind(this));
        
        // Employee selector button
        document.getElementById('btnSelectEmpleados').addEventListener('click', this.openEmployeeSelector.bind(this));
        
        document.getElementById('btnFiltrar').addEventListener('click', this.applyFilters.bind(this));
        document.getElementById('btnLimpiarFiltros').addEventListener('click', this.clearFilters.bind(this));
        document.getElementById('btnRefresh').addEventListener('click', this.refreshData.bind(this));

        // Export functionality
        document.getElementById('btnExportarExcel').addEventListener('click', this.exportToExcel.bind(this));

        // Civic day registration
        document.getElementById('btnRegistrarDiaCivico').addEventListener('click', this.showDiaCivicoModal.bind(this));
        document.getElementById('closeDiaCivico').addEventListener('click', this.hideDiaCivicoModal.bind(this));
        document.getElementById('cancelDiaCivico').addEventListener('click', this.hideDiaCivicoModal.bind(this));
        document.getElementById('formDiaCivico').addEventListener('submit', this.submitDiaCivico.bind(this));

        // Modal outside click close
        document.getElementById('modalDiaCivico').addEventListener('click', (e) => {
            if (e.target.id === 'modalDiaCivico') {
                this.hideDiaCivicoModal();
            }
        });
    }

    setupDateRestrictions() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fechaDesde').setAttribute('max', today);
        document.getElementById('fechaHasta').setAttribute('max', today);
        document.getElementById('fechaDiaCivico').setAttribute('min', today);
    }

    async loadInitialData() {
        this.applyFilters();
    }

    async onSedeChange() {
        const sedeId = document.getElementById('selectSede').value;
        await this.loadEstablecimientos(sedeId);
        this.clearEmployeeSelection(); // Clear employee selection when sede changes
    }

    async onEstablecimientoChange() {
        this.clearEmployeeSelection(); // Clear employee selection when establecimiento changes
    }

    openEmployeeSelector() {
        if (!window.employeeSelector) {
            this.showError('Selector de empleados no disponible');
            return;
        }

        const currentFilters = {
            sede: document.getElementById('selectSede').value,
            establecimiento: document.getElementById('selectEstablecimiento').value
        };

        // Get currently selected employee IDs
        const selectedEmployeeIds = this.currentFilters.empleados;

        employeeSelector.openModal(currentFilters, selectedEmployeeIds);
    }

    handleEmployeeSelectionChange(selectedEmployeeIds) {
        this.currentFilters.empleados = selectedEmployeeIds;
        this.updateEmployeeSelectionDisplay();
        
        // Auto-apply filters when employee selection changes
        this.applyFilters();
    }

    updateEmployeeSelectionDisplay() {
        const selectionText = document.getElementById('employeeSelectionText');
        const selectedCount = this.currentFilters.empleados.length;
        
        if (selectedCount === 0) {
            selectionText.textContent = 'Todos los empleados';
        } else if (selectedCount === 1) {
            selectionText.textContent = '1 empleado seleccionado';
        } else {
            selectionText.textContent = `${selectedCount} empleados seleccionados`;
        }
        
        // Store selected employees in hidden input for form processing
        document.getElementById('selectedEmployees').value = this.currentFilters.empleados.join(',');
    }

    clearEmployeeSelection() {
        this.currentFilters.empleados = [];
        this.updateEmployeeSelectionDisplay();
    }

    async loadEstablecimientos(sedeId) {
        const select = document.getElementById('selectEstablecimiento');
        select.innerHTML = '<option value="">Todos los establecimientos</option>';

        if (!sedeId) return;

        try {
            const response = await fetch(`api/get-establecimientos.php?sede_id=${sedeId}`);
            const data = await response.json();
            
            if (data.success && data.establecimientos) {
                data.establecimientos.forEach(establecimiento => {
                    const option = document.createElement('option');
                    option.value = establecimiento.ID_ESTABLECIMIENTO;
                    option.textContent = establecimiento.NOMBRE;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading establecimientos:', error);
            this.showError('Error al cargar establecimientos');
        }
    }


    setQuickFilter(filterType) {
        // Remove active class from all buttons
        document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
        
        const today = new Date();
        let fechaDesde, fechaHasta;

        switch (filterType) {
            case 'hoy':
                fechaDesde = fechaHasta = this.formatDate(today);
                document.getElementById('btnHoy').classList.add('active');
                break;
            case 'ayer':
                const yesterday = new Date(today);
                yesterday.setDate(yesterday.getDate() - 1);
                fechaDesde = fechaHasta = this.formatDate(yesterday);
                document.getElementById('btnAyer').classList.add('active');
                break;
            case 'semanaActual':
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay() + 1); // Monday
                fechaDesde = this.formatDate(startOfWeek);
                fechaHasta = this.formatDate(today);
                document.getElementById('btnSemanaActual').classList.add('active');
                break;
            case 'semanaPasada':
                const startOfLastWeek = new Date(today);
                startOfLastWeek.setDate(today.getDate() - today.getDay() - 6); // Last Monday
                const endOfLastWeek = new Date(startOfLastWeek);
                endOfLastWeek.setDate(startOfLastWeek.getDate() + 6); // Last Sunday
                fechaDesde = this.formatDate(startOfLastWeek);
                fechaHasta = this.formatDate(endOfLastWeek);
                document.getElementById('btnSemanaPasada').classList.add('active');
                break;
            case 'mesActual':
                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                fechaDesde = this.formatDate(startOfMonth);
                fechaHasta = this.formatDate(today);
                document.getElementById('btnMesActual').classList.add('active');
                break;
            case 'mesPasado':
                const startOfLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const endOfLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
                fechaDesde = this.formatDate(startOfLastMonth);
                fechaHasta = this.formatDate(endOfLastMonth);
                document.getElementById('btnMesPasado').classList.add('active');
                break;
        }

        document.getElementById('fechaDesde').value = fechaDesde;
        document.getElementById('fechaHasta').value = fechaHasta;
        
        this.applyFilters();
    }

    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    async applyFilters() {
        this.updateCurrentFilters();
        await this.loadHorasTrabajadas();
    }

    updateCurrentFilters() {
        this.currentFilters = {
            sede: document.getElementById('selectSede').value,
            establecimiento: document.getElementById('selectEstablecimiento').value,
            empleados: this.currentFilters.empleados, // Keep the current employee selection
            fechaDesde: document.getElementById('fechaDesde').value,
            fechaHasta: document.getElementById('fechaHasta').value
        };
    }

    async loadHorasTrabajadas() {
        this.showLoading(true);
        
        try {
            const params = new URLSearchParams();
            
            // Add simple filters
            if (this.currentFilters.sede) params.append('sede', this.currentFilters.sede);
            if (this.currentFilters.establecimiento) params.append('establecimiento', this.currentFilters.establecimiento);
            if (this.currentFilters.fechaDesde) params.append('fechaDesde', this.currentFilters.fechaDesde);
            if (this.currentFilters.fechaHasta) params.append('fechaHasta', this.currentFilters.fechaHasta);
            
            // Handle multiple employees
            if (this.currentFilters.empleados && this.currentFilters.empleados.length > 0) {
                // Send as comma-separated list
                params.append('empleados', this.currentFilters.empleados.join(','));
            }

            const response = await fetch(`api/horas-trabajadas/get-horas.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateStats(data.stats);
                this.updateTable(data.horas);
            } else {
                this.showError(data.message || 'Error al cargar las horas trabajadas');
            }
        } catch (error) {
            console.error('Error loading horas trabajadas:', error);
            this.showError('Error al cargar las horas trabajadas');
        } finally {
            this.showLoading(false);
        }
    }

    updateStats(stats) {
        document.getElementById('totalHoras').textContent = stats.total || '0';
        document.getElementById('horasRegular').textContent = stats.regular || '0';
        document.getElementById('horasExtra').textContent = stats.extra || '0';
        document.getElementById('horasDominicales').textContent = stats.dominicales || '0';
        document.getElementById('horasFestivos').textContent = stats.festivos || '0';
    }

    updateTable(horas) {
        const tbody = document.getElementById('horasTableBody');
        
        if (!horas || horas.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="no-data">
                        <i class="fas fa-info-circle"></i> No se encontraron registros para los filtros seleccionados.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = horas.map(hora => {
            const empleadoInitials = (hora.NOMBRE.charAt(0) + hora.APELLIDO.charAt(0)).toUpperCase();
            const dayName = this.getDayName(hora.FECHA);
            const dayClass = this.getDayClass(hora.FECHA, hora.ES_FESTIVO);
            
            return `
                <tr>
                    <td>
                        <div class="employee-info">
                            <div class="employee-avatar">${empleadoInitials}</div>
                            <div class="employee-details">
                                <div class="employee-name">${this.escapeHtml(hora.NOMBRE + ' ' + hora.APELLIDO)}</div>
                                <div class="employee-id">#EMP${String(hora.ID_EMPLEADO).padStart(3, '0')}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="date-info">${this.formatDisplayDate(hora.FECHA)}</div>
                    </td>
                    <td>
                        <div class="day-info ${dayClass}">${dayName}</div>
                        ${hora.ES_FESTIVO === 'S' ? '<div class="day-info holiday">Festivo</div>' : ''}
                    </td>
                    <td>${hora.ENTRADA_HORA || '--'}</td>
                    <td>${hora.SALIDA_HORA || '--'}</td>
                    <td class="hours-cell hours-regular">${hora.HORAS_REGULARES || '0'}</td>
                    <td class="hours-cell hours-extra">${hora.HORAS_EXTRAS || '0'}</td>
                    <td class="hours-cell hours-sunday">${hora.HORAS_DOMINICALES || '0'}</td>
                    <td class="hours-cell hours-holiday">${hora.HORAS_FESTIVOS || '0'}</td>
                    <td class="hours-cell"><strong>${hora.TOTAL_HORAS || '0'}</strong></td>
                    <td>${hora.OBSERVACIONES || '--'}</td>
                    <td>
                        ${hora.ID_HORARIO ? `
                            <button class="btn-schedule-consult" 
                                    data-schedule-id="${hora.ID_HORARIO}"
                                    title="Ver horario">
                                <i class="fas fa-clock"></i>
                            </button>
                        ` : '<span class="text-muted">--</span>'}
                    </td>
                </tr>
            `;
        }).join('');
        
        // Add event listeners for schedule consultation buttons
        document.querySelectorAll('.btn-schedule-consult').forEach(button => {
            button.addEventListener('click', (e) => {
                const scheduleId = e.currentTarget.getAttribute('data-schedule-id');
                this.openScheduleModal(scheduleId);
            });
        });
    }

    getDayName(dateStr) {
        const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        const date = new Date(dateStr + 'T00:00:00');
        return days[date.getDay()];
    }

    getDayClass(dateStr, esFestivo) {
        const date = new Date(dateStr + 'T00:00:00');
        if (esFestivo === 'S') return 'holiday';
        if (date.getDay() === 0) return 'sunday';
        return '';
    }

    formatDisplayDate(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        return date.toLocaleDateString('es-CO', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    clearFilters() {
        document.getElementById('selectSede').value = '';
        document.getElementById('selectEstablecimiento').value = '';
        
        // Clear employee selection
        this.clearEmployeeSelection();
        
        // Reset to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fechaDesde').value = today;
        document.getElementById('fechaHasta').value = today;
        
        // Clear active quick filter
        document.querySelectorAll('.btn-filter').forEach(btn => btn.classList.remove('active'));
        
        // Reset establishments
        this.loadEstablecimientos('');
        
        this.applyFilters();
    }

    openScheduleModal(scheduleId) {
        if (!scheduleId) {
            this.showError('ID de horario no válido');
            return;
        }
        
        if (window.scheduleDetailsModal) {
            scheduleDetailsModal.openModal(scheduleId);
        } else {
            this.showError('Modal de horario no disponible');
        }
    }

    refreshData() {
        this.applyFilters();
    }

    async exportToExcel() {
        this.updateCurrentFilters();
        
        try {
            const params = new URLSearchParams();
            
            // Add simple filters
            if (this.currentFilters.sede) params.append('sede', this.currentFilters.sede);
            if (this.currentFilters.establecimiento) params.append('establecimiento', this.currentFilters.establecimiento);
            if (this.currentFilters.fechaDesde) params.append('fechaDesde', this.currentFilters.fechaDesde);
            if (this.currentFilters.fechaHasta) params.append('fechaHasta', this.currentFilters.fechaHasta);
            
            // Handle multiple employees
            if (this.currentFilters.empleados && this.currentFilters.empleados.length > 0) {
                params.append('empleados', this.currentFilters.empleados.join(','));
            }

            // Create a temporary link to download the file
            const link = document.createElement('a');
            link.href = `api/horas-trabajadas/export-excel.php?${params}`;
            link.download = `horas_trabajadas_${new Date().toISOString().split('T')[0]}.xls`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showSuccess('Exportación iniciada. El archivo se descargará automáticamente.');
        } catch (error) {
            console.error('Error exporting to Excel:', error);
            this.showError('Error al exportar a Excel');
        }
    }

    showDiaCivicoModal() {
        document.getElementById('modalDiaCivico').style.display = 'block';
        document.getElementById('fechaDiaCivico').focus();
    }

    hideDiaCivicoModal() {
        document.getElementById('modalDiaCivico').style.display = 'none';
        document.getElementById('formDiaCivico').reset();
    }

    async submitDiaCivico(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('api/horas-trabajadas/register-dia-civico.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Día cívico registrado correctamente');
                this.hideDiaCivicoModal();
                this.refreshData();
            } else {
                this.showError(data.message || 'Error al registrar el día cívico');
            }
        } catch (error) {
            console.error('Error registering dia civico:', error);
            this.showError('Error al registrar el día cívico');
        }
    }

    showLoading(show) {
        const tbody = document.getElementById('horasTableBody');
        if (show) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="12" class="no-data">
                        <i class="fas fa-spinner fa-spin"></i> Cargando datos...
                    </td>
                </tr>
            `;
        }
    }

    showError(message) {
        // You can implement a toast notification system here
        console.error(message);
        alert(message); // Temporary implementation
    }

    showSuccess(message) {
        // You can implement a toast notification system here
        console.log(message);
        alert(message); // Temporary implementation
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new HorasTrabajadas();
});