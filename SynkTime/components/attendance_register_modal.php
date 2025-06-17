<div class="modal" id="attendanceRegisterModal">
  <div class="modal-content modal-content-md" onclick="event.stopPropagation()">
    <button type="button" class="modal-close" onclick="closeAttendanceRegisterModal()">
      <i class="fas fa-times"></i>
    </button>
    <h3>Registrar Asistencia</h3>
    <form autocomplete="off" style="margin-bottom:1em;">
      <div class="form-group">
        <label for="reg_sede">Sede</label>
        <select id="reg_sede" name="sede" required></select>
      </div>
      <div class="form-group">
        <label for="reg_establecimiento">Establecimiento</label>
        <select id="reg_establecimiento" name="establecimiento" required></select>
      </div>
    </form>
    <div style="margin-bottom:1.2em;">
      <span style="color:#2B7DE9;font-weight:500;">Fecha:</span>
      <span id="reg_fecha"></span>
    </div>
    <table class="attendance-table" style="margin-bottom:0.6em;">
      <thead>
        <tr>
          <th>Código</th>
          <th>Nombre</th>
          <th>Establecimiento</th>
          <th>Sede</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody id="attendanceRegisterTableBody"></tbody>
    </table>
  </div>
</div>