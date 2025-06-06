<!-- Modal Comentario/Acción -->
<div class="modal" id="attendanceCommentModal">
    <div class="modal-content modal-content-md">
        <span class="modal-close" onclick="closeAttendanceCommentModal()"><i class="fas fa-times"></i></span>
        <h3>Agregar Comentario o Acción</h3>
        <form id="attendanceCommentForm">
            <div class="form-group">
                <label for="comentario">Comentario / Acción</label>
                <textarea id="comentario" name="comentario" rows="4" required></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Guardar</button>
                <button type="button" class="btn-secondary" onclick="closeAttendanceCommentModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Registrar Asistencia -->
<div class="modal" id="attendanceRegisterModal">
  <div class="modal-content modal-content-md">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeAttendanceRegisterModal()">
      <i class="fas fa-times"></i>
    </button>
    <h3>Registrar Asistencia</h3>
    <div class="form-group">
      <label>Seleccione un trabajador para registrar asistencia</label>
      <table class="employee-table" style="margin-bottom:0;">
        <thead>
          <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Departamento</th>
            <th>Sede</th>
            <th>Registrar</th>
          </tr>
        </thead>
        <tbody id="attendanceEmployeeTableBody"></tbody>
      </table>
    </div>
  </div>
</div>