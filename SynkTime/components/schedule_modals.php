<!-- Modal para Registrar/Editar Horario -->
<div class="modal" id="scheduleModal">
    <div class="modal-content modal-content-md">
        <span class="modal-close" onclick="closeScheduleModal()"><i class="fas fa-times"></i></span>
        <h3 id="scheduleModalTitle">Registrar Horario</h3>
        <form id="scheduleForm">
            <div class="form-group">
                <label for="modal_empleado">Empleado</label>
                <input type="text" id="modal_empleado" name="empleado" required>
            </div>
            <div class="form-group">
                <label for="modal_departamento">Departamento</label>
                <input type="text" id="modal_departamento" name="departamento" required>
            </div>
            <div class="form-group">
                <label for="modal_sede">Sede</label>
                <input type="text" id="modal_sede" name="sede" required>
            </div>
            <div class="form-group">
                <label for="modal_dia">Día</label>
                <select id="modal_dia" name="dia" required>
                    <option value="">Seleccione</option>
                    <option value="Lunes">Lunes</option>
                    <option value="Martes">Martes</option>
                    <option value="Miércoles">Miércoles</option>
                    <option value="Jueves">Jueves</option>
                    <option value="Viernes">Viernes</option>
                    <option value="Sábado">Sábado</option>
                    <option value="Domingo">Domingo</option>
                </select>
            </div>
            <div class="form-group">
                <label for="modal_hora_entrada">Hora entrada</label>
                <input type="time" id="modal_hora_entrada" name="hora_entrada" required>
            </div>
            <div class="form-group">
                <label for="modal_hora_salida">Hora salida</label>
                <input type="time" id="modal_hora_salida" name="hora_salida" required>
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
        <p>¿Estás seguro de que deseas eliminar este horario?</p>
        <div class="form-actions">
            <button class="btn-danger" id="confirmDeleteScheduleBtn">Eliminar</button>
            <button class="btn-secondary" onclick="closeDeleteScheduleModal()">Cancelar</button>
        </div>
    </div>
</div>