// Simulación de datos de horarios
let schedules = [
    {
        empleado: "Juan Pérez",
        departamento: "Recursos Humanos",
        sede: "Sede Central",
        dia: "Lunes",
        hora_entrada: "08:00",
        hora_salida: "17:00"
    },
    {
        empleado: "Juan Pérez",
        departamento: "Recursos Humanos",
        sede: "Sede Central",
        dia: "Martes",
        hora_entrada: "08:00",
        hora_salida: "17:00"
    },
    {
        empleado: "María García",
        departamento: "TI",
        sede: "Sede Norte",
        dia: "Lunes",
        hora_entrada: "09:00",
        hora_salida: "18:00"
    },
    {
        empleado: "Carlos López",
        departamento: "Administración",
        sede: "Sede Central",
        dia: "Miércoles",
        hora_entrada: "07:30",
        hora_salida: "16:30"
    }
    // ...puedes agregar más horarios simulados
];

// Renderizar la tabla de horarios
function renderScheduleTable(data) {
    const tbody = document.getElementById('scheduleTableBody');
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No se encontraron horarios</td></tr>';
        return;
    }
    data.forEach((sch, idx) => {
        tbody.innerHTML += `
            <tr>
                <td>${sch.empleado}</td>
                <td>${sch.departamento}</td>
                <td>${sch.sede}</td>
                <td>${sch.dia}</td>
                <td>${sch.hora_entrada}</td>
                <td>${sch.hora_salida}</td>
                <td>
                    <button class="btn-icon btn-edit" title="Editar" onclick="openEditScheduleModal(${idx})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" title="Eliminar" onclick="openDeleteScheduleModal(${idx})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

// Render inicial con todos los datos
document.addEventListener('DOMContentLoaded', function() {
    renderScheduleTable(schedules);
});

// Filtrado al consultar
document.getElementById('scheduleQueryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const empleado = document.getElementById('q_empleado').value.trim().toLowerCase();
    const departamento = document.getElementById('q_departamento').value.trim().toLowerCase();
    const sede = document.getElementById('q_sede').value.trim().toLowerCase();
    const dia = document.getElementById('q_dia').value;

    const filtered = schedules.filter(sch =>
        (empleado === '' || sch.empleado.toLowerCase().includes(empleado)) &&
        (departamento === '' || sch.departamento.toLowerCase().includes(departamento)) &&
        (sede === '' || sch.sede.toLowerCase().includes(sede)) &&
        (dia === '' || sch.dia === dia)
    );
    renderScheduleTable(filtered);
});

// Limpiar el formulario y reiniciar la tabla al hacer click en limpiar
document.querySelector('#scheduleQueryForm .btn-secondary').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('scheduleQueryForm').reset();
    renderScheduleTable(schedules);
});

// Modal: Registrar/Editar horario
document.getElementById('btnAddSchedule').addEventListener('click', function() {
    openAddScheduleModal();
});

window.openAddScheduleModal = function() {
    document.getElementById('scheduleModalTitle').textContent = "Registrar Horario";
    document.getElementById('scheduleForm').reset();
    document.getElementById('scheduleModal').classList.add('show');
};
window.openEditScheduleModal = function(idx) {
    const sch = schedules[idx];
    document.getElementById('scheduleModalTitle').textContent = "Editar Horario";
    document.getElementById('modal_empleado').value = sch.empleado;
    document.getElementById('modal_departamento').value = sch.departamento;
    document.getElementById('modal_sede').value = sch.sede;
    document.getElementById('modal_dia').value = sch.dia;
    document.getElementById('modal_hora_entrada').value = sch.hora_entrada;
    document.getElementById('modal_hora_salida').value = sch.hora_salida;
    document.getElementById('scheduleModal').classList.add('show');
};
window.closeScheduleModal = function() {
    document.getElementById('scheduleModal').classList.remove('show');
};
// Cerrar modal al hacer click fuera
document.addEventListener('mousedown', function(e) {
    document.querySelectorAll('.modal.show').forEach(modal => {
        if (e.target === modal) modal.classList.remove('show');
    });
});
// Guardar horario (demo)
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const horario = {
        empleado: form.empleado.value,
        departamento: form.departamento.value,
        sede: form.sede.value,
        dia: form.dia.value,
        hora_entrada: form.hora_entrada.value,
        hora_salida: form.hora_salida.value
    };
    if (document.getElementById('scheduleModalTitle').textContent === "Registrar Horario") {
        schedules.push(horario);
    } else {
        // Buscar el índice del horario a editar
        const idx = schedules.findIndex(s => 
            s.empleado === form.empleado.value &&
            s.departamento === form.departamento.value &&
            s.sede === form.sede.value &&
            s.dia === form.dia.value
        );
        if (idx !== -1) schedules[idx] = horario;
    }
    renderScheduleTable(schedules);
    closeScheduleModal();
});

// Confirmación visual para eliminar horario
let deleteScheduleIdx = null;
window.openDeleteScheduleModal = function(idx) {
    deleteScheduleIdx = idx;
    document.getElementById('deleteScheduleModal').classList.add('show');
};
window.closeDeleteScheduleModal = function() {
    document.getElementById('deleteScheduleModal').classList.remove('show');
};
document.getElementById('confirmDeleteScheduleBtn').addEventListener('click', function() {
    if (deleteScheduleIdx !== null) {
        schedules.splice(deleteScheduleIdx, 1);
        renderScheduleTable(schedules);
    }
    closeDeleteScheduleModal();
});

// EXPORTAR A XLS
document.getElementById('btnExportXLS').addEventListener('click', function() {
    const table = document.querySelector('.schedule-table');
    if (!table) return;
    const wb = XLSX.utils.table_to_book(table, {sheet:"Horarios"});
    XLSX.writeFile(wb, 'horarios.xlsx');
});