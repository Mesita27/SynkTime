<div id="employeeModal" class="modal">
  <div class="modal-content modal-content-md" id="employeeModalContent">
    <button type="button" class="modal-close" id="closeEmployeeModal">&times;</button>
    <h3 id="employeeModalTitle" style="margin-top:0;">Registrar Empleado</h3>
    <form id="employeeRegisterForm" autocomplete="off">
      <input type="hidden" name="modo" id="modoEmpleado" value="crear">
      <div class="form-row">
        <div class="form-group">
          <label for="id_empleado">Código empleado</label>
          <input type="number" name="id_empleado" id="id_empleado" required>
        </div>
        <div class="form-group">
          <label for="dni">Cédula</label>
          <input type="text" name="dni" id="dni" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="nombre">Nombre</label>
          <input type="text" name="nombre" id="nombre" required>
        </div>
        <div class="form-group">
          <label for="apellido">Apellido</label>
          <input type="text" name="apellido" id="apellido" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="correo">Correo</label>
          <input type="email" name="correo" id="correo" required>
        </div>
        <div class="form-group">
          <label for="telefono">Teléfono</label>
          <input type="text" name="telefono" id="telefono">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="sedeEmpleado">Sede</label>
          <select name="sede" id="sedeEmpleado" required>
            <option value="">Seleccione sede</option>
          </select>
        </div>
        <div class="form-group">
          <label for="departamentoEmpleado">Departamento</label>
          <select name="establecimiento" id="departamentoEmpleado" required>
            <option value="">Seleccione departamento</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="fecha_ingreso">Fecha de ingreso</label>
          <input type="date" name="fecha_ingreso" id="fecha_ingreso" required>
        </div>
        <div class="form-group">
          <label for="estado">Estado</label>
          <select name="estado" id="estado" required>
            <option value="A">Activo</option>
            <option value="I">Inactivo</option>
          </select>
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-primary" id="employeeModalSubmitBtn">Registrar</button>
        <button type="button" class="btn-secondary" id="cancelEmployeeModal">Cancelar</button>
      </div>
      <div id="employeeFormError" style="color:#e53e3e;text-align:center;margin-top:10px;display:none;"></div>
    </form>
  </div>
</div>

<!-- Modal para confirmación de eliminación -->
<div id="employeeDeleteModal" class="modal">
  <div class="modal-content modal-content-sm">
    <button type="button" class="modal-close" id="closeEmployeeDeleteModal">&times;</button>
    <h3>Eliminar empleado</h3>
    <p id="deleteStep1">¿Estás seguro que deseas eliminar este empleado?</p>
    <p id="deleteStep2" style="display:none;">Esta acción es irreversible. ¿Confirmas eliminarlo?</p>
    <div class="form-actions" style="margin-top:1.5rem;">
      <button type="button" class="btn-danger" id="confirmDeleteEmployeeBtn" style="display:none;">Eliminar definitivamente</button>
      <button type="button" class="btn-primary" id="verifyDeleteEmployeeBtn">Sí, eliminar</button>
      <button type="button" class="btn-secondary" id="cancelDeleteEmployeeBtn">Cancelar</button>
    </div>
  </div>
</div>