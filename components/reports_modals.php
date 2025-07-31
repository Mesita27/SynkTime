<?php
/**
 * SynkTime - Modales del módulo de reportes
 * 
 * Este componente contiene los modales necesarios para visualizar detalles
 * de asistencias y empleados desde el módulo de reportes.
 */
?>

<!-- Modal para detalles de asistencia -->
<div class="modal" id="reportAttendanceModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-clipboard-check"></i> Detalles de Asistencia</h3>
            <button type="button" class="modal-close" onclick="closeReportModal('reportAttendanceModal')">&times;</button>
        </div>
        
        <!-- Contenedor de pestañas -->
        <div class="modal-tabs" id="attendanceTabs"></div>
        
        <!-- Cuerpo del modal -->
        <div class="modal-body" id="attendanceModalBody">
            <div class="loader-container">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Cargando detalles de asistencia...</p>
            </div>
        </div>
        
        <!-- Pie del modal -->
        <div class="modal-footer" id="attendanceModalFooter">
            <button type="button" class="btn-secondary" onclick="closeReportModal('reportAttendanceModal')">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal para detalles de empleado -->
<div class="modal" id="reportEmployeeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user"></i> Información del Empleado</h3>
            <button type="button" class="modal-close" onclick="closeReportModal('reportEmployeeModal')">&times;</button>
        </div>
        
        <!-- Contenedor de pestañas -->
        <div class="modal-tabs" id="employeeTabs"></div>
        
        <!-- Cuerpo del modal -->
        <div class="modal-body" id="employeeModalBody">
            <div class="loader-container">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Cargando información del empleado...</p>
            </div>
        </div>
        
        <!-- Pie del modal -->
        <div class="modal-footer" id="employeeModalFooter">
            <button type="button" class="btn-secondary" onclick="closeReportModal('reportEmployeeModal')">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</div>