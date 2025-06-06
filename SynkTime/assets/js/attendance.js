// Datos simulados de asistencias
const attendanceData = [
    {
        codigo: "EMP0101",
        nombre: "Juan Pérez",
        departamento: "Recursos Humanos",
        sede: "Sede Central",
        fecha: "2025-05-10",
        hora_entrada: "08:01",
        hora_salida: "17:00",
        estado_entrada: "Puntual",
        estado_salida: "A tiempo",
        dispositivo: "Bio-RFID 1"
    },
    // ...otros registros
];

// Auxiliar para la clase del estado de salida
function estadoSalidaClass(estado) {
    switch (estado) {
        case 'A tiempo': return 'status-out-atiempo';
        case 'Tarde': return 'status-out-tarde';
        case 'Temprano': return 'status-out-temprano';
        default: return '';
    }
}

// Renderizar la tabla
window.renderAttendanceTable = function(data) {
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;">No se encontraron asistencias</td></tr>';
        return;
    }
    data.forEach((att, idx) => {
        tbody.innerHTML += `
            <tr>
                <td>${att.empleado}</td>
                <td>${att.departamento}</td>
                <td>${att.sede}</td>
                <td>${att.fecha}</td>
                <td>${att.hora_entrada}</td>
                <td>${att.hora_salida}</td>
                <td><span class="status-in ${att.estado_entrada}">${att.estado_entrada}</span></td>
                <td><span class="status-out ${estadoSalidaClass(att.estado_salida)}">${att.estado_salida}</span></td>
                <td>${att.dispositivo}</td>
                <td>
                    <button class="btn-icon btn-justify" title="Agregar comentario o acción" onclick="openAttendanceCommentModal(${idx})"><i class="fas fa-comment-dots"></i></button>
                </td>
            </tr>
        `;
    });
};

// Render inicial con todos los datos
window.renderAttendanceTable(attendanceData);

// Guardar referencia del índice de fila seleccionada para el comentario
let selectedAttendanceIndex = null;

// Filtrado por día
document.getElementById('attendanceQueryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const fecha = document.getElementById('q_fecha').value;
    const filtered = attendanceData.filter(att => att.fecha === fecha);
    window.renderAttendanceTable(filtered);
});

// Limpiar el formulario y reiniciar la tabla al hacer click en limpiar
document.querySelector('#attendanceQueryForm .btn-secondary').addEventListener('click', function() {
    document.getElementById('attendanceQueryForm').reset();
    window.renderAttendanceTable(attendanceData);
    document.getElementById('q_fecha').value = '';
});

// Al cambiar la fecha, filtra la tabla automáticamente
document.getElementById('q_fecha').addEventListener('change', function() {
    const fecha = this.value;
    if (fecha) {
        const filtered = attendanceData.filter(att => att.fecha === fecha);
        window.renderAttendanceTable(filtered);
    } else {
        window.renderAttendanceTable(attendanceData);
    }
});

// Modal de comentario/acción
window.openAttendanceCommentModal = function(idx) {
    selectedAttendanceIndex = idx;
    document.getElementById('attendanceCommentForm').reset();
    document.getElementById('attendanceCommentModal').classList.add('show');
};
window.closeAttendanceCommentModal = function() {
    document.getElementById('attendanceCommentModal').classList.remove('show');
    selectedAttendanceIndex = null;
};
// Cerrar modal al hacer click fuera
document.addEventListener('mousedown', function(e) {
    document.querySelectorAll('.modal.show').forEach(modal => {
        if (e.target === modal) modal.classList.remove('show');
    });
});
// Guardar comentario (demo)
document.getElementById('attendanceCommentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    window.closeAttendanceCommentModal();
    alert('Comentario/acción guardado (demo, sin backend)');
});
// Exportar a XLS (demo)
const btnExport = document.getElementById('btnExportXLS');
if (btnExport) {
    btnExport.addEventListener('click', function() {
        alert("Funcionalidad de exportar a .xls próximamente.");
    });
}

// --- MODAL DE REGISTRAR ASISTENCIA ---
window.openAttendanceRegisterModal = function() {
    renderAttendanceEmployeeTable();
    document.getElementById('attendanceRegisterModal').classList.add('show');
    document.body.style.overflow = 'hidden';
};

window.closeAttendanceRegisterModal = function() {
    document.getElementById('attendanceRegisterModal').classList.remove('show');
    document.body.style.overflow = '';
};

window.renderAttendanceEmployeeTable = function() {
    const tbody = document.getElementById('attendanceEmployeeTableBody');
    tbody.innerHTML = '';
    if (!window.employees || window.employees.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No hay empleados registrados</td></tr>';
        return;
    }
    window.employees.forEach((emp, idx) => {
        tbody.innerHTML += `
            <tr>
                <td>${emp.codigo}</td>
                <td>${emp.nombre} ${emp.apellido}</td>
                <td>${emp.departamento}</td>
                <td>${emp.sede}</td>
                <td>
                    <button type="button" class="btn-primary" onclick="registerAttendanceForEmployee(${idx})">
                        Registrar
                    </button>
                </td>
            </tr>
        `;
    });
};

window.registerAttendanceForEmployee = function(idx) {
    const emp = window.employees[idx];
    const now = new Date();
    const fecha = now.toISOString().split('T')[0];
    const hora_entrada = now.toTimeString().slice(0,5);
    // Puedes ajustar reglas para "estado_entrada" según la hora, aquí demo:
    const estado_entrada = (hora_entrada <= "08:10") ? "Puntual" : "Tardanza";
    attendanceData.push({
        codigo: emp.codigo,
        nombre: emp.nombre + " " + emp.apellido,
        departamento: emp.departamento,
        sede: emp.sede,
        fecha,
        hora_entrada,
        hora_salida: "--:--",
        estado_entrada,
        estado_salida: "--",
        dispositivo: "Manual"
    });
    window.renderAttendanceTable(attendanceData);
    closeAttendanceRegisterModal();
    alert(`Asistencia registrada para ${emp.nombre} ${emp.apellido}`);
};

// --- CORRIGE RENDER DE TABLA DE ASISTENCIA ---
window.renderAttendanceTable = function(data) {
    const tbody = document.getElementById('attendanceTableBody');
    tbody.innerHTML = '';
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;">No se encontraron asistencias</td></tr>';
        return;
    }
    data.forEach((att, idx) => {
        tbody.innerHTML += `
            <tr>
                <td>${att.codigo || ""}</td>
                <td>${att.nombre || ""}</td>
                <td>${att.departamento || ""}</td>
                <td>${att.sede || ""}</td>
                <td>${att.fecha || ""}</td>
                <td>${att.hora_entrada || ""}</td>
                <td>${att.hora_salida || ""}</td>
                <td><span class="status-in ${att.estado_entrada}">${att.estado_entrada || ""}</span></td>
                <td><span class="status-out ${estadoSalidaClass(att.estado_salida)}">${att.estado_salida || ""}</span></td>
                <td>${att.dispositivo || ""}</td>
            </tr>
        `;
    });
};