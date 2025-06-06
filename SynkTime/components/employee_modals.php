<!-- Modal Registrar/Editar Empleado -->
<div class="modal" id="employeeModal">
  <div class="modal-content modal-content-md">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeEmployeeModal()">
      <i class="fas fa-times"></i>
    </button>
    <h3 id="employeeModalTitle">Registrar empleado</h3>
    <form id="employeeForm" autocomplete="off">
      <div class="form-row">
        <div class="form-group">
          <label for="codigo">Código</label>
          <input type="text" id="codigo" name="codigo" required>
        </div>
        <div class="form-group">
          <label for="identificacion">Identificación</label>
          <input type="text" id="identificacion" name="identificacion" required>
        </div>
        <div class="form-group">
          <label for="tipo_identificacion">Tipo identificación</label>
          <select id="tipo_identificacion" name="tipo_identificacion" required>
            <option value="CC">CC</option>
            <option value="TI">TI</option>
            <option value="Pasaporte">Pasaporte</option>
          </select>
        </div>
        <div class="form-group">
          <label for="nombre">Nombre</label>
          <input type="text" id="nombre" name="nombre" required>
        </div>
        <div class="form-group">
          <label for="apellido">Apellido</label>
          <input type="text" id="apellido" name="apellido" required>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email">
        </div>
        <div class="form-group">
          <label for="telefono">Teléfono</label>
          <input type="text" id="telefono" name="telefono">
        </div>
        <div class="form-group">
          <label for="direccion">Dirección</label>
          <input type="text" id="direccion" name="direccion">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="fecha_nacimiento">Fecha de nacimiento</label>
          <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
        </div>
        <div class="form-group">
          <label for="fecha_contratacion">Fecha de contratación</label>
          <input type="date" id="fecha_contratacion" name="fecha_contratacion" required>
        </div>
        <div class="form-group">
          <label for="genero">Género</label>
          <select id="genero" name="genero">
            <option value="">Seleccione</option>
            <option value="M">Masculino</option>
            <option value="F">Femenino</option>
            <option value="Otro">Otro</option>
          </select>
        </div>
        <div class="form-group">
          <label for="estado">Estado</label>
          <select id="estado" name="estado">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <div class="form-group">
          <label for="departamento">Departamento</label>
          <input type="text" id="departamento" name="departamento">
        </div>
        <div class="form-group">
          <label for="sede">Sede</label>
          <input type="text" id="sede" name="sede">
        </div>
        <div class="form-group">
          <label for="foto">Foto</label>
          <input type="file" id="foto" name="foto" accept="image/*">
        </div>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn-primary" id="saveEmployeeBtn">Guardar</button>
        <button type="button" class="btn-secondary" onclick="closeEmployeeModal()">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Eliminar Empleado -->
<div class="modal" id="deleteEmployeeModal">
  <div class="modal-content modal-content-sm">
    <button type="button" class="modal-close" aria-label="Cerrar" onclick="closeDeleteEmployeeModal()"><i class="fas fa-times"></i></button>
    <h3>Eliminar empleado</h3>
    <p>¿Estás seguro de que deseas eliminar este empleado?</p>
    <div class="form-actions">
      <button class="btn-danger" id="confirmDeleteEmployeeBtn">Eliminar</button>
      <button class="btn-secondary" onclick="closeDeleteEmployeeModal()">Cancelar</button>
    </div>
  </div>
</div>