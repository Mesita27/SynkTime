<div class="employee-query-box">
    <form id="employeeQueryForm" class="employee-query-form" method="get" action="">
        <div class="query-row">
            <div class="form-group">
                <label for="q_codigo">Código</label>
                <input type="text" name="codigo" id="q_codigo" value="<?= isset($_GET['codigo']) ? htmlspecialchars($_GET['codigo']) : '' ?>" placeholder="Código">
            </div>
            <div class="form-group">
                <label for="q_identificacion">Identificación</label>
                <input type="text" name="identificacion" id="q_identificacion" value="<?= isset($_GET['identificacion']) ? htmlspecialchars($_GET['identificacion']) : '' ?>" placeholder="Identificación">
            </div>
            <div class="form-group">
                <label for="q_nombre">Nombre</label>
                <input type="text" name="nombre" id="q_nombre" value="<?= isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : '' ?>" placeholder="Nombre">
            </div>
            <div class="form-group">
                <label for="q_departamento">Departamento</label>
                <input type="text" name="departamento" id="q_departamento" value="<?= isset($_GET['departamento']) ? htmlspecialchars($_GET['departamento']) : '' ?>" placeholder="Departamento">
            </div>
            <div class="form-group">
                <label for="q_sede">Sede</label>
                <input type="text" name="sede" id="q_sede" value="<?= isset($_GET['sede']) ? htmlspecialchars($_GET['sede']) : '' ?>" placeholder="Sede">
            </div>
            <div class="form-group query-btns">
                <button type="submit" class="btn-primary"><i class="fas fa-search"></i> Consultar</button>
                <a href="employee.php" class="btn-secondary"><i class="fas fa-redo"></i> Limpiar</a>
            </div>
        </div>
    </form>
</div>