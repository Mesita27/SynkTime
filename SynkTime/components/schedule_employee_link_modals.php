<!-- Modal para vincular horario a empleado -->
<div class="modal" id="linkScheduleModal">
    <div class="modal-content modal-content-md">
        <span class="modal-close" onclick="closeLinkScheduleModal()"><i class="fas fa-times"></i></span>
        <h3 id="linkScheduleModalTitle">Vincular horario a empleado</h3>
        <form id="linkScheduleForm">
            <input type="hidden" id="link_emp_id" name="id_empleado">
            <div class="form-group">
                <label for="link_emp_name">Empleado</label>
                <input type="text" id="link_emp_name" readonly>
            </div>
            <div class="form-group">
                <label for="link_schedule_select">Horario</label>
                <select id="link_schedule_select" name="id_horario" required></select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Vincular</button>
                <button type="button" class="btn-secondary" onclick="closeLinkScheduleModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>