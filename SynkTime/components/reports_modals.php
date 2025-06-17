<!-- Modal Detalle Empleado -->
<div class="modal" id="employeeAttendanceModal" style="display:none;">
    <div class="modal-content modal-content-md" style="position:relative;">
        <button class="modal-close" onclick="closeEmployeeAttendanceModal()"><i class="fas fa-times"></i></button>
        <div class="employee-popup-header">
            <i class="fas fa-user-circle"></i>
            <div>
                <h3 style="margin:0 0 0.5em 0;">Detalle de Empleado</h3>
                <div id="employeeBasicInfo" class="employee-popup-details"></div>
            </div>
        </div>
        <form id="employeeAttendanceRangeForm" style="display:flex;align-items:center;gap:0.7em;margin-bottom:0.7em;">
            <label style="font-size:0.97em;color:#2B7DE9;">Historial por rango:</label>
            <input type="date" id="empHistStart" name="desde" style="width:120px;">
            <span>a</span>
            <input type="date" id="empHistEnd" name="hasta" style="width:120px;">
            <button class="btn-secondary" type="submit" title="Filtrar"><i class="fas fa-search"></i></button>
        </form>
        <span id="employeeLatePercent" style="margin-left:1em;color:#e53e3e;font-weight:600;"></span>
        <div style="margin-top:1.3em;">
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Estado</th>
                        <th>Observación</th>
                        <th>Ver</th>
                    </tr>
                </thead>
                <tbody id="employeeAttendanceHistory"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detalle de Asistencia -->
<div class="modal" id="attendanceDetailModal" style="display:none;">
    <div class="modal-content modal-content-lg" style="position:relative;">
        <button class="modal-close" onclick="closeAttendanceDetailModal()"><i class="fas fa-times"></i></button>
        <h3 style="margin-top:0;">Detalle de Asistencia</h3>
        <div style="display: flex; gap: 2rem; margin-top: 1.2em;">    
            <div id="attendanceDetailContent" style="flex: 1; min-width: 300px;"></div>
            <div id="attendancePhotoContainer" style="flex: 1; text-align: center;">
                <!-- Aquí se mostrará la foto -->
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver foto en grande -->
<div class="modal" id="photoModal" style="display:none;">
    <div class="modal-content modal-content-xl" style="position:relative; max-width: 90vw; max-height: 90vh;">
        <button class="modal-close" onclick="closePhotoModal()" style="z-index: 1001;"><i class="fas fa-times"></i></button>
        <div style="text-align: center;">
            <img id="photoModalImage" src="" alt="Foto de asistencia" style="max-width: 100%; max-height: 80vh; object-fit: contain;">
        </div>
    </div>
</div>