<!-- Modal para Registrar/Editar Horario -->
<div class="modal" id="scheduleModal">
    <div class="modal-content modal-content-md">
        <span class="modal-close" onclick="closeScheduleModal()"><i class="fas fa-times"></i></span>
        <h3 id="scheduleModalTitle">Registrar Horario</h3>
        <form id="scheduleForm">
            <input type="hidden" id="modal_id_horario" name="id_horario">
            <div class="form-group">
                <label for="modal_nombre">Nombre del horario</label>
                <input type="text" id="modal_nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="modal_sede">Sede</label>
                <select id="modal_sede" name="sede" required></select>
            </div>
            <div class="form-group">
                <label for="modal_establecimiento">Establecimiento</label>
                <select id="modal_establecimiento" name="establecimiento" required></select>
            </div>
            <div class="form-group">
                <label for="modal_hora_entrada">Hora entrada</label>
                <input type="time" id="modal_hora_entrada" name="hora_entrada" required>
            </div>
            <div class="form-group">
                <label for="modal_hora_salida">Hora salida</label>
                <input type="time" id="modal_hora_salida" name="hora_salida" required>
            </div>
            <div class="form-group">
                <label for="modal_tolerancia">Tolerancia (min)</label>
                <input type="number" id="modal_tolerancia" name="tolerancia" min="0" value="0" required>
            </div>
            <div class="form-group">
                <label>Días de la semana</label>
                <div id="modal_dias" class="dias-checkboxes">
                    <!-- Se llenan dinámicamente -->
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Guardar</button>
                <button type="button" class="btn-secondary" onclick="closeScheduleModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Eliminar Horario -->
<div class="modal" id="deleteScheduleModal">
    <div class="modal-content modal-content-sm">
        <span class="modal-close" onclick="closeDeleteScheduleModal()"><i class="fas fa-times"></i></span>
        <h3>Eliminar horario</h3>
        <p>¿Estás seguro de que deseas eliminar este horario? Se eliminarán también todas las vinculaciones de empleados a este horario.</p>
        <div class="form-actions">
            <button class="btn-danger" id="confirmDeleteScheduleBtn">Eliminar</button>
            <button class="btn-secondary" onclick="closeDeleteScheduleModal()">Cancelar</button>
        </div>
    </div>
</div>