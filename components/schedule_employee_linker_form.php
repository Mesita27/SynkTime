<form id="employeeScheduleQueryForm" class="schedule-query-form" autocomplete="off" style="margin-bottom: 1.5rem;">
    <div class="query-row">
        <div class="form-group">
            <label for="q_emp_codigo">Código</label>
            <input type="text" id="q_emp_codigo" name="codigo" placeholder="Código empleado">
        </div>
        <div class="form-group">
            <label for="q_emp_identificacion">Identificación</label>
            <input type="text" id="q_emp_identificacion" name="identificacion" placeholder="DNI">
        </div>
        <div class="form-group">
            <label for="q_emp_nombre">Nombre</label>
            <input type="text" id="q_emp_nombre" name="nombre" placeholder="Nombre o Apellido">
        </div>
        <div class="form-group">
            <label for="q_emp_sede">Sede</label>
            <select id="q_emp_sede" name="sede"></select>
        </div>
        <div class="form-group">
            <label for="q_emp_establecimiento">Establecimiento</label>
            <select id="q_emp_establecimiento" name="establecimiento"></select>
        </div>
        <div class="form-group query-btns">
            <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Consultar</button>
            <button type="button" class="btn-secondary" id="btnClearEmployeeScheduleQuery"><i class="fas fa-redo"></i> Limpiar</button>
        </div>
    </div>
</form>