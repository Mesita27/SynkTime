/**
 * Schedule Details Module
 * Handles schedule details popup functionality
 */
class ScheduleDetailsModal {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Modal close events
        const closeButtons = document.querySelectorAll('[onclick="closeScheduleDetailsModal()"]');
        closeButtons.forEach(button => {
            button.onclick = null; // Remove inline onclick
            button.addEventListener('click', () => this.closeModal());
        });

        // Modal outside click close
        document.getElementById('scheduleDetailsModal').addEventListener('click', (e) => {
            if (e.target.id === 'scheduleDetailsModal') {
                this.closeModal();
            }
        });
    }

    async openModal(scheduleId) {
        if (!scheduleId) {
            this.showError('ID de horario no válido');
            return;
        }

        // Show modal
        document.getElementById('scheduleDetailsModal').style.display = 'block';
        
        // Show loading state
        this.showLoading();
        
        try {
            const response = await fetch(`api/horario/details.php?id=${scheduleId}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                this.displayScheduleDetails(data.data);
            } else {
                this.showError(data.message || 'Error al cargar los detalles del horario');
            }
        } catch (error) {
            console.error('Error loading schedule details:', error);
            this.showError('Error al cargar los detalles del horario');
        }
    }

    closeModal() {
        document.getElementById('scheduleDetailsModal').style.display = 'none';
        this.clearContent();
    }

    showLoading() {
        document.getElementById('scheduleDetailName').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        document.getElementById('scheduleDetailSede').textContent = 'Cargando...';
        document.getElementById('scheduleDetailEstablecimiento').textContent = 'Cargando...';
        document.getElementById('scheduleDetailDays').innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        document.getElementById('scheduleDetailEntrada').textContent = 'Cargando...';
        document.getElementById('scheduleDetailSalida').textContent = 'Cargando...';
        document.getElementById('scheduleDetailTolerancia').textContent = 'Cargando...';
        document.getElementById('scheduleEmployeesTable').innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 2rem;">
                    <i class="fas fa-spinner fa-spin"></i> Cargando empleados...
                </td>
            </tr>
        `;
    }

    displayScheduleDetails(scheduleData) {
        // Schedule basic info
        document.getElementById('scheduleDetailName').textContent = scheduleData.NOMBRE || 'Sin nombre';
        document.getElementById('scheduleDetailSede').textContent = scheduleData.sede || 'No especificada';
        document.getElementById('scheduleDetailEstablecimiento').textContent = scheduleData.establecimiento || 'No especificado';
        document.getElementById('scheduleDetailEntrada').textContent = this.formatTime(scheduleData.HORA_ENTRADA);
        document.getElementById('scheduleDetailSalida').textContent = this.formatTime(scheduleData.HORA_SALIDA);
        document.getElementById('scheduleDetailTolerancia').textContent = this.formatTolerance(scheduleData.TOLERANCIA);

        // Schedule days
        this.displayScheduleDays(scheduleData.dias);

        // Assigned employees
        this.displayEmployees(scheduleData.empleados || []);
    }

    displayScheduleDays(daysString) {
        const daysContainer = document.getElementById('scheduleDetailDays');
        
        if (!daysString) {
            daysContainer.innerHTML = '<span class="text-muted">No especificados</span>';
            return;
        }

        const dayNames = {
            '1': 'Lunes',
            '2': 'Martes', 
            '3': 'Miércoles',
            '4': 'Jueves',
            '5': 'Viernes',
            '6': 'Sábado',
            '7': 'Domingo'
        };

        const days = daysString.split(',').map(day => day.trim());
        const dayBadges = days.map(day => {
            const dayName = dayNames[day] || `Día ${day}`;
            return `<span class="day-badge">${dayName}</span>`;
        }).join('');

        daysContainer.innerHTML = dayBadges || '<span class="text-muted">No especificados</span>';
    }

    displayEmployees(employees) {
        const tableBody = document.getElementById('scheduleEmployeesTable');
        
        if (!employees || employees.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                        <i class="fas fa-info-circle"></i> No hay empleados asignados a este horario
                    </td>
                </tr>
            `;
            return;
        }

        const employeesHTML = employees.map(emp => `
            <tr>
                <td>${emp.codigo || 'N/A'}</td>
                <td>
                    <div class="employee-name-cell">
                        <strong>${this.escapeHtml(emp.nombre || '')} ${this.escapeHtml(emp.apellido || '')}</strong>
                        <div class="employee-dni">DNI: ${emp.dni || 'N/A'}</div>
                    </div>
                </td>
                <td>${this.formatDate(emp.fecha_desde)}</td>
                <td>${this.formatDate(emp.fecha_hasta) || 'Vigente'}</td>
            </tr>
        `).join('');

        tableBody.innerHTML = employeesHTML;
    }

    formatTime(timeString) {
        if (!timeString) return 'No especificada';
        
        try {
            // Handle both HH:MM:SS and HH:MM formats
            const timeParts = timeString.split(':');
            if (timeParts.length >= 2) {
                return `${timeParts[0]}:${timeParts[1]}`;
            }
            return timeString;
        } catch (error) {
            return timeString;
        }
    }

    formatTolerance(toleranceMinutes) {
        if (!toleranceMinutes || toleranceMinutes === '0') {
            return 'Sin tolerancia';
        }
        
        const minutes = parseInt(toleranceMinutes);
        if (isNaN(minutes)) {
            return 'No especificada';
        }
        
        if (minutes === 1) {
            return '1 minuto';
        }
        
        return `${minutes} minutos`;
    }

    formatDate(dateString) {
        if (!dateString) return null;
        
        try {
            const date = new Date(dateString + 'T00:00:00');
            return date.toLocaleDateString('es-CO', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        } catch (error) {
            return dateString;
        }
    }

    showError(message) {
        document.getElementById('scheduleDetailName').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
        document.getElementById('scheduleDetailSede').textContent = '';
        document.getElementById('scheduleDetailEstablecimiento').textContent = '';
        document.getElementById('scheduleDetailDays').innerHTML = '';
        document.getElementById('scheduleDetailEntrada').textContent = '';
        document.getElementById('scheduleDetailSalida').textContent = '';
        document.getElementById('scheduleDetailTolerancia').textContent = '';
        
        document.getElementById('scheduleEmployeesTable').innerHTML = `
            <tr>
                <td colspan="4" style="text-align: center; padding: 2rem; color: var(--danger-color);">
                    <i class="fas fa-exclamation-triangle"></i> ${this.escapeHtml(message)}
                </td>
            </tr>
        `;
    }

    clearContent() {
        document.getElementById('scheduleDetailName').textContent = '';
        document.getElementById('scheduleDetailSede').textContent = '';
        document.getElementById('scheduleDetailEstablecimiento').textContent = '';
        document.getElementById('scheduleDetailDays').innerHTML = '';
        document.getElementById('scheduleDetailEntrada').textContent = '';
        document.getElementById('scheduleDetailSalida').textContent = '';
        document.getElementById('scheduleDetailTolerancia').textContent = '';
        document.getElementById('scheduleEmployeesTable').innerHTML = '';
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global instance
let scheduleDetailsModal = null;

// Global function for backward compatibility
function closeScheduleDetailsModal() {
    if (scheduleDetailsModal) {
        scheduleDetailsModal.closeModal();
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    scheduleDetailsModal = new ScheduleDetailsModal();
});