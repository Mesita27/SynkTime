// =========== FILTROS PRINCIPALES ===========
async function cargarFiltros() {
    let sedes = await fetch('api/get-sedes.php').then(r => r.json());
    let sedeSel = document.getElementById('filtro_sede');
    sedeSel.innerHTML = '<option value="">Todos</option>'; // <-- AGREGADO: opción Todos
    sedes.sedes.forEach(s => {
        sedeSel.innerHTML += `<option value="${s.ID_SEDE}">${s.NOMBRE}</option>`;
    });
    sedeSel.onchange = cargarEstablecimientosFiltro2;
    await cargarEstablecimientosFiltro2();
}
async function cargarEstablecimientosFiltro2() {
    let sedeId = document.getElementById('filtro_sede').value;
    let estSel = document.getElementById('filtro_establecimiento');
    estSel.innerHTML = '<option value="">Todos</option>'; // <-- AGREGADO: opción Todos
    let url = 'api/get-establecimientos.php';
    if (sedeId) url += '?sede_id=' + encodeURIComponent(sedeId);
    let res = await fetch(url).then(r => r.json());
    if (res.establecimientos) {
        res.establecimientos.forEach(e => {
            estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
        });
    }
    loadAttendanceDay();
}
// Recargar empleados al buscar por código en el modal
document.addEventListener('DOMContentLoaded', function() {
    cargarFiltros();
    const btnBuscarCodigoRegistro = document.getElementById('btnBuscarCodigoRegistro');
    const codigoRegistroBusqueda = document.getElementById('codigoRegistroBusqueda');
    if (btnBuscarCodigoRegistro && codigoRegistroBusqueda) {
        btnBuscarCodigoRegistro.onclick = cargarEmpleadosParaRegistro;
        codigoRegistroBusqueda.addEventListener('keyup', function(e) {
            if (e.key === "Enter") cargarEmpleadosParaRegistro();
        });
    }
});

// =========== TABLA PRINCIPAL ASISTENCIAS ===========
function loadAttendanceDay() {
    const sede = document.getElementById('filtro_sede').value;
    const establecimiento = document.getElementById('filtro_establecimiento').value;
    const codigo = document.getElementById('codigoBusqueda') ? document.getElementById('codigoBusqueda').value.trim() : '';
    let url = `api/attendance/list-day.php?`;
    if (sede) url += `sede=${encodeURIComponent(sede)}&`;
    if (establecimiento) url += `establecimiento=${encodeURIComponent(establecimiento)}&`;
    if (codigo) url += `codigo=${encodeURIComponent(codigo)}&`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('attendanceTableBody');
            tbody.innerHTML = '';
            if (!data.success || !data.data.length) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;">No se encontraron asistencias</td></tr>';
                return;
            }
            data.data.forEach(att => {
                let accion = '';
                if (!att.SALIDA_HORA) {
                    accion = `<button type="button" class="btn-primary" onclick="registrarSalida(${att.ID_EMPLEADO}, '${att.FECHA}')">Registrar Salida</button>`;
                }
                tbody.innerHTML += `
                    <tr>
                        <td>${att.ID_EMPLEADO}</td>
                        <td>${att.NOMBRE} ${att.APELLIDO}</td>
                        <td>${att.establecimiento}</td>
                        <td>${att.sede}</td>
                        <td>${att.FECHA}</td>
                        <td>
                            <div><b>Entrada:</b> ${att.ENTRADA_HORA || '--:--'}</div>
                            <div><b>Salida:</b> ${att.SALIDA_HORA || '--:--'}</div>
                        </td>
                        <td>
                            <div><b>Entrada:</b> ${att.ENTRADA_ESTADO || '--'}</div>
                            <div><b>Salida:</b> ${att.SALIDA_ESTADO || '--'}</div>
                        </td>
                        <td>${att.FOTO ? `<img src="uploads/${att.FOTO}" width="50">` : '--'}</td>
                        <td>${accion}</td>
                    </tr>
                `;
            });
        });
}
document.getElementById('filtro_sede').onchange = loadAttendanceDay;
document.getElementById('filtro_establecimiento').onchange = loadAttendanceDay;

// =========== MODAL REGISTRAR ASISTENCIA (ENTRADA) ===========
window.openAttendanceRegisterModal = async function() {
    document.getElementById('attendanceRegisterModal').classList.add('show');
    document.getElementById('reg_fecha').textContent = new Date().toISOString().split('T')[0];
    await cargarSedesRegistro();
    await cargarEstablecimientosRegistro();
    await cargarEmpleadosParaRegistro();
};
window.closeAttendanceRegisterModal = function() {
    document.getElementById('attendanceRegisterModal').classList.remove('show');
};

// Cargar sedes en modal
async function cargarSedesRegistro() {
    const sedeSel = document.getElementById('reg_sede');
    sedeSel.innerHTML = '<option>Cargando...</option>';
    const res = await fetch('api/get-sedes.php').then(r => r.json());
    sedeSel.innerHTML = '';
    if (res.sedes && res.sedes.length) {
        res.sedes.forEach(s => {
            sedeSel.innerHTML += `<option value="${s.ID_SEDE}">${s.NOMBRE}</option>`;
        });
    } else {
        sedeSel.innerHTML = '<option value="">(Sin sedes)</option>';
    }
}

// Cargar establecimientos en modal
async function cargarEstablecimientosRegistro() {
    const sedeId = document.getElementById('reg_sede').value;
    const estSel = document.getElementById('reg_establecimiento');
    estSel.innerHTML = '<option>Cargando...</option>';
    let url = 'api/get-establecimientos.php';
    if (sedeId) url += '?sede_id=' + encodeURIComponent(sedeId);
    const res = await fetch(url).then(r => r.json());
    estSel.innerHTML = '';
    if (res.establecimientos && res.establecimientos.length) {
        res.establecimientos.forEach(e => {
            estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
        });
    } else {
        estSel.innerHTML = '<option value="">(Sin establecimientos)</option>';
    }
}

// Cargar empleados disponibles
async function cargarEmpleadosParaRegistro() {
    const sede = document.getElementById('reg_sede').value;
    const establecimiento = document.getElementById('reg_establecimiento').value;
    const codigo = document.getElementById('codigoRegistroBusqueda') ? document.getElementById('codigoRegistroBusqueda').value.trim() : "";
    const tbody = document.getElementById('attendanceRegisterTableBody');

    // Permitir "Todos" en ambos filtros (value vacío)
    let url = `api/employee/list.php?`;
    if (sede) url += `sede=${encodeURIComponent(sede)}&`;
    if (establecimiento) url += `establecimiento=${encodeURIComponent(establecimiento)}&`;
    if (codigo) url += `codigo=${encodeURIComponent(codigo)}&`;

    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Cargando empleados...</td></tr>';
    let res = await fetch(url).then(r => r.json());
    tbody.innerHTML = '';
    if (!res.data || !res.data.length) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;">No hay empleados disponibles</td></tr>`;
        return;
    }
    res.data.forEach(emp => {
        tbody.innerHTML += `
            <tr>
                <td>${emp.id}</td>
                <td>${emp.nombre} ${emp.apellido}</td>
                <td>${emp.establecimiento}</td>
                <td>${emp.sede}</td>
                <td>
                    <button type="button" class="btn-primary" onclick="openAttendancePhotoModal(${emp.id})">
                        Registrar Entrada
                    </button>
                </td>
            </tr>
        `;
    });
}

// Recargar establecimientos y empleados al cambiar sede
document.getElementById('reg_sede').addEventListener('change', async function() {
    await cargarEstablecimientosRegistro();
    await cargarEmpleadosParaRegistro();
});
document.getElementById('reg_establecimiento').addEventListener('change', cargarEmpleadosParaRegistro);

// Cerrar modal al hacer click fuera del contenido
document.getElementById('attendanceRegisterModal').addEventListener('mousedown', function(e) {
    if (e.target === this) closeAttendanceRegisterModal();
});

// =========== MODAL FOTO SOLO PARA ENTRADA ===========
let empleadoSeleccionado = null;
let imageBase64 = "";

window.openAttendancePhotoModal = function(id_empleado) {
    empleadoSeleccionado = id_empleado;
    document.getElementById('attendancePhotoModal').classList.add('show');
    const video = document.getElementById('video');
    navigator.mediaDevices.getUserMedia({ video: true }).then(stream => {
        video.srcObject = stream;
    });
    document.getElementById('canvas').style.display = 'none';
    document.getElementById('photoPreview').innerHTML = '';
    document.getElementById('saveAttendanceBtn').disabled = true;
};
window.closeAttendancePhotoModal = function() {
    document.getElementById('attendancePhotoModal').classList.remove('show');
    const video = document.getElementById('video');
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
};
document.getElementById('takePhotoBtn').onclick = function() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    canvas.style.display = 'block';
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    imageBase64 = canvas.toDataURL('image/jpeg');
    document.getElementById('photoPreview').innerHTML = `<img src="${imageBase64}" width="160" style="border-radius:6px;">`;
    document.getElementById('saveAttendanceBtn').disabled = false;
};
document.getElementById('saveAttendanceBtn').onclick = function() {
    fetch('api/attendance/register.php', {
        method: 'POST',
        body: new URLSearchParams({
            id_empleado: empleadoSeleccionado,
            tipo: 'ENTRADA',
            foto_base64: imageBase64
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Asistencia registrada');
            closeAttendancePhotoModal();
            closeAttendanceRegisterModal();
            loadAttendanceDay();
        } else {
            alert('Error: ' + res.message);
        }
    });
};
document.getElementById('attendancePhotoModal').addEventListener('mousedown', function(e) {
    if (e.target === this) closeAttendancePhotoModal();
});

// =========== REGISTRAR SALIDA (SIN FOTO) ===========
window.registrarSalida = function(id_empleado, fecha) {
    if(!confirm("¿Está seguro de registrar la salida?")) return;
    fetch('api/attendance/register.php', {
        method: 'POST',
        body: new URLSearchParams({
            id_empleado: id_empleado,
            tipo: 'SALIDA',
            fecha: fecha
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('Salida registrada');
            loadAttendanceDay();
        } else {
            alert('Error: ' + (res.message || 'No se pudo registrar la salida.'));
        }
    });
};