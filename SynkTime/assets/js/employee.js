// Demo empleados
let employees = [
  {
    codigo: "EMP0101",
    identificacion: "CC123456",
    tipo_identificacion: "CC",
    nombre: "Juan",
    apellido: "Pérez",
    email: "juan.perez@email.com",
    telefono: "123456789",
    direccion: "Calle Principal 123",
    fecha_nacimiento: "1990-01-01",
    fecha_contratacion: "2021-08-01",
    genero: "M",
    estado: "Activo",
    departamento: "Recursos Humanos",
    sede: "Sede Central",
    foto: ""
  }
];
window.employees = employees;
let editingIndex = null;
let deleteIndex = null;

// Render Tabla
function renderEmployeeTable(data = employees) {
  const tbody = document.getElementById('employeeTableBody');
  tbody.innerHTML = '';
  if (!data.length) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;">No se encontraron empleados</td></tr>';
    return;
  }
  data.forEach((emp, idx) => {
    tbody.innerHTML += `
      <tr>
        <td>${emp.codigo}</td>
        <td>${emp.identificacion}</td>
        <td>${emp.nombre} ${emp.apellido}</td>
        <td>${emp.email}</td>
        <td>${emp.departamento}</td>
        <td>${emp.sede}</td>
        <td>${emp.fecha_contratacion}</td>
        <td>
          <span class="${emp.estado === 'Activo' ? 'status-active' : 'status-inactive'}">${emp.estado}</span>
        </td>
        <td>
          <button class="btn-icon btn-edit" data-edit-idx="${idx}" title="Editar"><i class="fas fa-edit"></i></button>
          <button class="btn-icon btn-delete" data-delete-idx="${idx}" title="Eliminar"><i class="fas fa-trash"></i></button>
        </td>
      </tr>
    `;
  });
}

// Eventos tabla para editar/eliminar (delegación)
document.addEventListener('click', function(e) {
  // Editar
  if (e.target.closest('.btn-edit')) {
    const idx = e.target.closest('.btn-edit').getAttribute('data-edit-idx');
    openEmployeeEdit(idx);
  }
  // Eliminar
  if (e.target.closest('.btn-delete')) {
    const idx = e.target.closest('.btn-delete').getAttribute('data-delete-idx');
    openEmployeeDelete(idx);
  }
});

// Registrar empleado
document.getElementById('btnAddEmployee').addEventListener('click', () => {
  editingIndex = null;
  document.getElementById('employeeForm').reset();
  document.getElementById('employeeModalTitle').textContent = "Registrar empleado";
  document.getElementById('employeeModal').classList.add('show');
  document.body.style.overflow = 'hidden';
});

// Editar empleado
function openEmployeeEdit(idx) {
  editingIndex = parseInt(idx);
  const emp = employees[editingIndex];
  document.getElementById('employeeModalTitle').textContent = "Editar empleado";
  const form = document.getElementById('employeeForm');
  form.codigo.value = emp.codigo;
  form.identificacion.value = emp.identificacion;
  form.tipo_identificacion.value = emp.tipo_identificacion;
  form.nombre.value = emp.nombre;
  form.apellido.value = emp.apellido;
  form.email.value = emp.email;
  form.telefono.value = emp.telefono;
  form.direccion.value = emp.direccion;
  form.fecha_nacimiento.value = emp.fecha_nacimiento || "";
  form.fecha_contratacion.value = emp.fecha_contratacion;
  form.genero.value = emp.genero;
  form.estado.value = emp.estado === "Activo" ? "1" : "0";
  form.departamento.value = emp.departamento;
  form.sede.value = emp.sede;
  document.getElementById('employeeModal').classList.add('show');
  document.body.style.overflow = 'hidden';
}

// Eliminar empleado
function openEmployeeDelete(idx) {
  deleteIndex = parseInt(idx);
  document.getElementById('deleteEmployeeModal').classList.add('show');
  document.body.style.overflow = 'hidden';
}

// Cerrar modal de empleado
function closeEmployeeModal() {
  document.getElementById('employeeModal').classList.remove('show');
  document.body.style.overflow = '';
}

// Cerrar modal de eliminar
function closeDeleteEmployeeModal() {
  document.getElementById('deleteEmployeeModal').classList.remove('show');
  document.body.style.overflow = '';
}

// Guardar empleado (registrar o editar)
document.getElementById('employeeForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const form = e.target;
  const emp = {
    codigo: form.codigo.value.trim(),
    identificacion: form.identificacion.value.trim(),
    tipo_identificacion: form.tipo_identificacion.value,
    nombre: form.nombre.value.trim(),
    apellido: form.apellido.value.trim(),
    email: form.email.value.trim(),
    telefono: form.telefono.value.trim(),
    direccion: form.direccion.value.trim(),
    fecha_nacimiento: form.fecha_nacimiento.value,
    fecha_contratacion: form.fecha_contratacion.value,
    genero: form.genero.value,
    estado: form.estado.value === "1" ? "Activo" : "Inactivo",
    departamento: form.departamento.value,
    sede: form.sede.value,
    foto: form.foto.files[0]?.name || ""
  };
  if (editingIndex === null) {
    employees.push(emp);
  } else {
    employees[editingIndex] = emp;
  }
  renderEmployeeTable(employees);
  closeEmployeeModal();
});

// Confirmar eliminar
document.getElementById('confirmDeleteEmployeeBtn').addEventListener('click', function() {
  if (deleteIndex !== null && deleteIndex >= 0) {
    employees.splice(deleteIndex, 1);
    renderEmployeeTable(employees);
    deleteIndex = null;
  }
  closeDeleteEmployeeModal();
});

// Búsqueda de empleados
document.getElementById('employeeQueryForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const codigo = document.getElementById('q_codigo').value.trim().toLowerCase();
  const identificacion = document.getElementById('q_identificacion').value.trim().toLowerCase();
  const nombre = document.getElementById('q_nombre').value.trim().toLowerCase();
  const departamento = document.getElementById('q_departamento').value.trim().toLowerCase();
  const sede = document.getElementById('q_sede').value.trim().toLowerCase();
  const filtered = employees.filter(emp =>
    (codigo === '' || emp.codigo.toLowerCase().includes(codigo)) &&
    (identificacion === '' || emp.identificacion.toLowerCase().includes(identificacion)) &&
    (nombre === '' || (`${emp.nombre} ${emp.apellido}`.toLowerCase().includes(nombre))) &&
    (departamento === '' || emp.departamento.toLowerCase().includes(departamento)) &&
    (sede === '' || emp.sede.toLowerCase().includes(sede))
  );
  renderEmployeeTable(filtered);
});

// Limpiar búsqueda
document.querySelector('#employeeQueryForm .btn-secondary').addEventListener('click', function(e) {
  e.preventDefault();
  document.getElementById('employeeQueryForm').reset();
  renderEmployeeTable(employees);
});

// Exportar a .xls
document.getElementById('btnExportXLS').addEventListener('click', function () {
  const table = document.querySelector('.employee-table');
  if (!table) return;
  if (typeof XLSX === 'undefined') {
    alert("SheetJS no está cargado.");
    return;
  }
  const wb = XLSX.utils.table_to_book(table, { sheet: "Empleados" });
  XLSX.writeFile(wb, "empleados.xlsx");
});

// Cerrar modal al hacer click fuera
document.addEventListener('mousedown', function(e) {
  document.querySelectorAll('.modal.show').forEach(modal => {
    if (e.target === modal) {
      modal.classList.remove('show');
      document.body.style.overflow = '';
    }
  });
});

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
  renderEmployeeTable(employees);
});
