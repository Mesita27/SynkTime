// assets/js/employee.js

// 1. Llenar selectores de sede y establecimiento en "Todas" y cargar empleados

function cargarSedesEmpleado() {
    fetch('api/get-sedes.php')
        .then(r => r.json())
        .then(res => {
            const sedeSelect = document.getElementById('q_sede');
            sedeSelect.innerHTML = '<option value="">Todas</option>';
            if (res.sedes && res.sedes.length > 0) {
                res.sedes.forEach(sede => {
                    sedeSelect.innerHTML += `<option value="${sede.ID_SEDE}">${sede.NOMBRE}</option>`;
                });
            }
            // Al cargar sedes, deja el selector en "Todas"
            sedeSelect.value = "";
            cargarEstablecimientosEmpleado(); // Llenar establecimientos en "Todos" por defecto
        });
}

function cargarEstablecimientosEmpleado() {
    const sedeId = document.getElementById('q_sede').value;
    const establecimientoSelect = document.getElementById('q_establecimiento');
    establecimientoSelect.innerHTML = '<option value="">Todos</option>';
    if (!sedeId) return; // Si está en "Todas", no cargar nada más
    fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(sedeId))
        .then(r => r.json())
        .then(res => {
            if (res.establecimientos && res.establecimientos.length > 0) {
                res.establecimientos.forEach(est => {
                    establecimientoSelect.innerHTML += `<option value="${est.ID_ESTABLECIMIENTO}">${est.NOMBRE}</option>`;
                });
            }
            // Al cargar establecimientos, deja el selector en "Todos"
            establecimientoSelect.value = "";
        });
}

// 2. Cargar empleados (con/sin filtros)
function loadEmployees() {
    const form = document.getElementById('employeeQueryForm');
    let params = "";
    if(form){
        params = new URLSearchParams(new FormData(form)).toString();
    }
    fetch('api/employee/list.php' + (params ? ('?' + params) : ''))
        .then(resp => resp.json())
        .then(res => {
            if (res.success) renderEmployeeTable(res.data);
            else renderEmployeeTable([]);
        })
        .catch(() => renderEmployeeTable([]));
}

// 3. Dibujar tabla
function renderEmployeeTable(data) {
    const tbody = document.getElementById('employeeTableBody');
    tbody.innerHTML = '';
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;">No se encontraron empleados</td></tr>';
        return;
    }
    data.forEach(emp => {
        tbody.innerHTML += `
            <tr>
                <td>${emp.id ?? ''}</td>
                <td>${emp.identificacion ?? ''}</td>
                <td>${emp.nombre ?? ''} ${emp.apellido ?? ''}</td>
                <td>${emp.email ?? ''}</td>
                <td>${emp.establecimiento ?? ''}</td> <!-- Departamento = Establecimiento -->
                <td>${emp.sede ?? ''}</td>
                <td>${emp.fecha_contratacion ?? ''}</td>
                <td>
                  <span class="${emp.estado === 'A' ? 'status-active' : 'status-inactive'}">
                  ${emp.estado === 'A' ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td>
                  <!-- Botones de acción -->
                </td>
            </tr>
        `;
    });
}

// 4. Eventos para filtros y carga inicial
document.addEventListener('DOMContentLoaded', function () {
    cargarSedesEmpleado();
    // Cargar empleados al inicio (todos)
    loadEmployees();

    // Si el formulario tiene filtros, recargar al hacer submit
    const form = document.getElementById('employeeQueryForm');
    if (form) {
        form.addEventListener('submit', function(e){
            e.preventDefault();
            loadEmployees();
        });
    }

    // Si cambias sede o establecimiento, recarga establecimientos y empleados
    const sedeSel = document.getElementById('q_sede');
    const estSel = document.getElementById('q_establecimiento');
    if (sedeSel) sedeSel.addEventListener('change', function() {
        cargarEstablecimientosEmpleado();
        setTimeout(loadEmployees, 100); // Espera a que se actualice select y luego carga empleados
    });
    if (estSel) estSel.addEventListener('change', loadEmployees);
});

// Modal helpers
// --- EXISTENTE: Cargar y filtrar empleados (NO SE MUESTRA AQUÍ por brevedad) ---
// --- Modal helpers ---
function openEmployeeModal(mode, empleado = null) {
    document.getElementById('employeeModal').classList.add('show');
    document.getElementById('employeeRegisterForm').reset();
    document.getElementById('employeeFormError').style.display = 'none';

    // Editar o Crear
    if (mode === 'editar' && empleado) {
        document.getElementById('employeeModalTitle').textContent = 'Editar Empleado';
        document.getElementById('employeeModalSubmitBtn').textContent = 'Guardar Cambios';
        document.getElementById('modoEmpleado').value = 'editar';
        document.getElementById('id_empleado').value = empleado.id;
        document.getElementById('id_empleado').readOnly = true;
        document.getElementById('dni').value = empleado.identificacion || '';
        document.getElementById('nombre').value = empleado.nombre || '';
        document.getElementById('apellido').value = empleado.apellido || '';
        document.getElementById('correo').value = empleado.email || '';
        document.getElementById('telefono').value = empleado.telefono || '';
        document.getElementById('fecha_ingreso').value = empleado.fecha_contratacion || '';
        document.getElementById('estado').value = empleado.estado || 'A';

        cargarSedesRegistro(empleado.sede, empleado.departamento);
    } else {
        document.getElementById('employeeModalTitle').textContent = 'Registrar Empleado';
        document.getElementById('employeeModalSubmitBtn').textContent = 'Registrar';
        document.getElementById('modoEmpleado').value = 'crear';
        document.getElementById('id_empleado').readOnly = false;
        cargarSedesRegistro();
    }
}
function closeEmployeeModal() {
    document.getElementById('employeeModal').classList.remove('show');
}
document.getElementById('btnAddEmployee').addEventListener('click', function() {
    openEmployeeModal('crear');
});
document.getElementById('closeEmployeeModal').addEventListener('click', closeEmployeeModal);
document.getElementById('cancelEmployeeModal').addEventListener('click', closeEmployeeModal);
document.getElementById('employeeModal').addEventListener('mousedown', function(e) {
    if (e.target === this) closeEmployeeModal();
});

// Cargar sedes y departamentos en modal (con selección predefinida si edita)
function cargarSedesRegistro(selectedSede = '', selectedDept = '') {
    fetch('api/get-sedes.php')
        .then(r => r.json())
        .then(res => {
            const sedeSelect = document.getElementById('sedeEmpleado');
            sedeSelect.innerHTML = '<option value="">Seleccione sede</option>';
            if (res.sedes && res.sedes.length > 0) {
                res.sedes.forEach(sede => {
                    sedeSelect.innerHTML += `<option value="${sede.ID_SEDE}" ${selectedSede == sede.NOMBRE ? "selected" : ""}>${sede.NOMBRE}</option>`;
                });
            }
            cargarDepartamentosRegistro(selectedDept);
        });
}
function cargarDepartamentosRegistro(selectedDept = '') {
    const sedeId = document.getElementById('sedeEmpleado').value;
    const depSelect = document.getElementById('departamentoEmpleado');
    depSelect.innerHTML = '<option value="">Seleccione departamento</option>';
    if (!sedeId) return;
    fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(sedeId))
        .then(r => r.json())
        .then(res => {
            if (res.establecimientos && res.establecimientos.length > 0) {
                res.establecimientos.forEach(dep => {
                    depSelect.innerHTML += `<option value="${dep.ID_ESTABLECIMIENTO}" ${selectedDept == dep.NOMBRE ? "selected" : ""}>${dep.NOMBRE}</option>`;
                });
            }
        });
}
document.getElementById('sedeEmpleado').addEventListener('change', function() {
    cargarDepartamentosRegistro();
});

// Registrar o editar empleado
document.getElementById('employeeRegisterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const errorDiv = document.getElementById('employeeFormError');
    errorDiv.style.display = 'none';

    // Validación de campos
    if (!form.id_empleado.value || !form.nombre.value || !form.apellido.value || !form.dni.value || !form.correo.value || !form.sede.value || !form.establecimiento.value || !form.fecha_ingreso.value || !form.estado.value) {
        errorDiv.textContent = "Todos los campos requeridos deben estar completos.";
        errorDiv.style.display = 'block';
        return;
    }

    const modo = form.modo.value;
    const url = modo === 'editar' ? 'api/employee/update.php' : 'api/employee/register.php';
    fetch(url, {
        method: 'POST',
        body: new FormData(form)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            closeEmployeeModal();
            loadEmployees();
        } else {
            errorDiv.textContent = res.message || "No se pudo registrar/editar el empleado.";
            errorDiv.style.display = 'block';
        }
    })
    .catch(() => {
        errorDiv.textContent = "Error de conexión con el servidor.";
        errorDiv.style.display = 'block';
    });
});

// --- Iconos de acción y cargar datos para editar/eliminar ---
function renderEmployeeTable(data) {
    const tbody = document.getElementById('employeeTableBody');
    tbody.innerHTML = '';
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;">No se encontraron empleados</td></tr>';
        return;
    }
    data.forEach(emp => {
        tbody.innerHTML += `
            <tr>
                <td>${emp.id ?? ''}</td>
                <td>${emp.identificacion ?? ''}</td>
                <td>${emp.nombre ?? ''} ${emp.apellido ?? ''}</td>
                <td>${emp.email ?? ''}</td>
                <td>${emp.establecimiento ?? ''}</td>
                <td>${emp.sede ?? ''}</td>
                <td>${emp.fecha_contratacion ?? ''}</td>
                <td>
                  <span class="${emp.estado === 'A' ? 'status-active' : 'status-inactive'}">
                  ${emp.estado === 'A' ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td>
                  <button class="btn-icon btn-edit" title="Editar" onclick="editarEmpleado('${emp.id}')"><i class="fas fa-edit"></i></button>
                  <button class="btn-icon btn-delete" title="Eliminar" onclick="eliminarEmpleado('${emp.id}','${emp.nombre} ${emp.apellido}')"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

// Editar empleado
window.editarEmpleado = function(id) {
    fetch('api/employee/get.php?id=' + encodeURIComponent(id))
      .then(r => r.json())
      .then(res => {
          if (res.success && res.data) {
              openEmployeeModal('editar', res.data);
          }
      });
}

// Eliminar empleado (doble confirmación)
let empleadoAEliminar = null;
window.eliminarEmpleado = function(id, nombre) {
    empleadoAEliminar = id;
    document.getElementById('employeeDeleteModal').classList.add('show');
    document.getElementById('deleteStep1').style.display = '';
    document.getElementById('deleteStep2').style.display = 'none';
    document.getElementById('verifyDeleteEmployeeBtn').style.display = '';
    document.getElementById('confirmDeleteEmployeeBtn').style.display = 'none';
}
function closeEmployeeDeleteModal() {
    document.getElementById('employeeDeleteModal').classList.remove('show');
    empleadoAEliminar = null;
}
document.getElementById('closeEmployeeDeleteModal').addEventListener('click', closeEmployeeDeleteModal);
document.getElementById('cancelDeleteEmployeeBtn').addEventListener('click', closeEmployeeDeleteModal);
document.getElementById('employeeDeleteModal').addEventListener('mousedown', function(e) {
    if (e.target === this) closeEmployeeDeleteModal();
});
document.getElementById('verifyDeleteEmployeeBtn').addEventListener('click', function() {
    document.getElementById('deleteStep1').style.display = 'none';
    document.getElementById('deleteStep2').style.display = '';
    document.getElementById('verifyDeleteEmployeeBtn').style.display = 'none';
    document.getElementById('confirmDeleteEmployeeBtn').style.display = '';
});
document.getElementById('confirmDeleteEmployeeBtn').addEventListener('click', function() {
    if (!empleadoAEliminar) return;
    fetch('api/employee/delete.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id_empleado=' + encodeURIComponent(empleadoAEliminar)
    }).then(r=>r.json()).then(res=>{
        if(res.success){
            closeEmployeeDeleteModal();
            loadEmployees();
        } else {
            alert(res.message || 'No se pudo eliminar el empleado');
        }
    });
});

document.getElementById('btnExportXLS').addEventListener('click', function () {
    // Selecciona la tabla completa (incluye thead y tbody)
    var table = document.getElementById('employeeTable');
    if (!table) return alert('No se encontró la tabla para exportar.');

    // Clona la tabla para no afectar la original si hay que limpiar botones, etc.
    var tableClone = table.cloneNode(true);

    // OPCIONAL: Quitar la columna de botones de acción (editar/eliminar)
    // Si tu columna de acciones es la última, puedes quitarla así:
    for (let row of tableClone.rows) {
        if (row.cells.length > 0) row.deleteCell(-1);
    }

    // Arma el HTML para Excel
    var html = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" 
              xmlns:x="urn:schemas-microsoft-com:office:excel" 
              xmlns="http://www.w3.org/TR/REC-html40">
        <head><!--[if gte mso 9]>
        <xml>
        <x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
            <x:Name>Empleados</x:Name>
            <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
        </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook>
        </xml><![endif]-->
        </head>
        <body>
            ${tableClone.outerHTML}
        </body>
        </html>
    `;

    // Crea el archivo y lo descarga
    var blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'empleados.xls';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
});