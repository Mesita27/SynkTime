let attendanceData = [];
let selectedAttendances = [];

document.addEventListener('DOMContentLoaded', function() {
    cargarSedesFiltro();
    cargarEstablecimientosFiltro();
    loadAttendance();
    document.getElementById('btnToday').onclick = () => filtroRangoHoy();
    document.getElementById('btnWeek').onclick = () => filtroRangoSemana();
    document.getElementById('btnMonth').onclick = () => filtroRangoMes();
    document.getElementById('btnLimpiar').onclick = () => { document.getElementById('customRangeForm').reset(); loadAttendance(); };
});

// Filtros por sede/establecimiento
async function cargarSedesFiltro() {
    const sedeSel = document.getElementById('q_sede');
    sedeSel.innerHTML = '<option value="">Todas</option>';
    await fetch('api/get-sedes.php')
        .then(r=>r.json())
        .then(res=>{
            (res.sedes||[]).forEach(s=>{
                sedeSel.innerHTML += `<option value="${s.ID_SEDE}">${s.NOMBRE}</option>`;
            });
        });
}
async function cargarEstablecimientosFiltro(idSede = '') {
    const estSel = document.getElementById('q_establecimiento');
    estSel.innerHTML = '<option value="">Todos</option>';
    if (!idSede) {
        await fetch('api/get-establecimientos.php')
            .then(r=>r.json())
            .then(res=>{
                (res.establecimientos||[]).forEach(e=>{
                    estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
                });
            });
    } else {
        await fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(idSede))
            .then(r=>r.json())
            .then(res=>{
                (res.establecimientos||[]).forEach(e=>{
                    estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
                });
            });
    }
}
document.getElementById('q_sede').addEventListener('change', function() {
    cargarEstablecimientosFiltro(this.value);
});

// Cargar asistencias con filtros
async function loadAttendance(filtros = {}) {
    let params = new URLSearchParams(filtros).toString();
    await fetch('api/attendance/list.php' + (params ? '?' + params : ''))
        .then(r=>r.json())
        .then(res=>{
            attendanceData = res.data || [];
            renderReportsTable(attendanceData);
        });
}

// Render tabla de asistencias
function renderReportsTable(data) {
    const tbody = document.getElementById('reportsTableBody');
    tbody.innerHTML = '';
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;">No se encontraron asistencias</td></tr>';
        return;
    }
    data.forEach((row, idx) => {
        tbody.innerHTML += `
            <tr>
                <td>
                    <button class="btn-icon btn-info" title="Ver empleado" onclick="openEmployeeAttendanceModal('${row.codigo}')">
                        <i class="fas fa-user"></i> ${row.codigo}
                    </button>
                </td>
                <td>${row.nombre}</td>
                <td>${row.sede}</td>
                <td>${row.establecimiento}</td>
                <td>${row.fecha}</td>
                <td>${row.hora_entrada}</td>
                <td><span class="status-in ${row.estado_entrada}">${row.estado_entrada}</span></td>
                <td>${row.observacion || ""}</td>
                <td>
                  <button class="btn-icon btn-main" title="Ver detalle asistencia" onclick="openAttendanceDetailModal(${row.id})">
                    <i class="fas fa-info-circle"></i>
                  </button>
                </td>

            </tr>
        `;
    });
}

// Filtros del formulario principal
document.getElementById('attendanceQueryForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const filtros = {
        codigo: document.getElementById('q_codigo').value,
        nombre: document.getElementById('q_nombre').value,
        sede: document.getElementById('q_sede').value,
        establecimiento: document.getElementById('q_establecimiento').value,
        estado: document.getElementById('q_estado').value,
    };
    await loadAttendance(filtros);
});
document.getElementById('btnClearAttendanceQuery').addEventListener('click', function(e){
    e.preventDefault();
    document.getElementById('attendanceQueryForm').reset();
    loadAttendance();
});

// Filtros por rango de fechas
document.getElementById('customRangeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const desde = document.getElementById('customStart').value;
    const hasta = document.getElementById('customEnd').value;
    if (!desde || !hasta) {
        alert("Selecciona ambas fechas.");
        return;
    }
    loadAttendance({ fecha_desde: desde, fecha_hasta: hasta });
});

// Botones rápidos
function filtroRangoHoy() {
    const hoy = new Date().toISOString().split('T')[0];
    loadAttendance({ fecha_desde: hoy, fecha_hasta: hoy });
}
function filtroRangoSemana() {
    const hoy = new Date();
    const diaSemana = hoy.getDay() || 7;
    const inicio = new Date(hoy);
    inicio.setDate(hoy.getDate() - diaSemana + 1);
    const fin = new Date(inicio);
    fin.setDate(inicio.getDate() + 6);
    const desde = inicio.toISOString().split('T')[0];
    const hasta = fin.toISOString().split('T')[0];
    loadAttendance({ fecha_desde: desde, fecha_hasta: hasta });
}
function filtroRangoMes() {
    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth() + 1).padStart(2, '0');
    const desde = `${yyyy}-${mm}-01`;
    const hasta = new Date(yyyy, hoy.getMonth() + 1, 0).toISOString().split('T')[0];
    loadAttendance({ fecha_desde: desde, fecha_hasta: hasta });
}

// Abre popup de empleado
window.openEmployeeAttendanceModal = function(codigo) {
    fetch('api/employee/get.php?id=' + encodeURIComponent(codigo))
        .then(r=>r.json())
        .then(res=>{
            if (!res.success) return;
            const data = res.data;
            document.getElementById('employeeBasicInfo').innerHTML = `
                <b>Código:</b> ${data.id}<br>
                <b>Nombre:</b> ${data.nombre} ${data.apellido}<br>
                <b>Identificación:</b> ${data.identificacion}<br>
                <b>Sede:</b> ${data.sede}<br>
                <b>Establecimiento:</b> ${data.departamento}<br>
                <b>Contratación:</b> ${data.fecha_contratacion || '-'}<br>
                <b>Estado:</b> ${(data.estado === 'A') ? 'Activo' : 'Inactivo'}
            `;
            cargarHistorialEmpleado(codigo);
            document.getElementById('employeeAttendanceModal').classList.add('show');
        });
};
window.closeEmployeeAttendanceModal = function() {
    document.getElementById('employeeAttendanceModal').classList.remove('show');
};

// Cargar historial de asistencias por empleado con % de tardanza
function cargarHistorialEmpleado(codigo, desde = '', hasta = '') {
    let url = `api/attendance/employee_history.php?id_empleado=${encodeURIComponent(codigo)}`;
    if (desde && hasta) url += `&desde=${desde}&hasta=${hasta}`;
    fetch(url)
        .then(r=>r.json())
        .then(res=>{
            const tbody = document.getElementById('employeeAttendanceHistory');
            tbody.innerHTML = '';
            if (!res.data.length) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Sin asistencias</td></tr>';
                document.getElementById('employeeLatePercent').textContent = '';
                return;
            }
            res.data.forEach(a=>{
                tbody.innerHTML += `
                    <tr>
                        <td>${a.fecha}</td>
                        <td>${a.hora_entrada}</td>
                        <td><span class="status-in ${a.estado_entrada}">${a.estado_entrada}</span></td>
                        <td>${a.observacion||''}</td>
                        <td><button class="btn-icon btn-main" onclick="openAttendanceDetailModal(${a.id})" title="Ver detalle"><i class="fas fa-info-circle"></i></button></td>
                    </tr>
                `;
            });
            document.getElementById('employeeLatePercent').textContent = `Porcentaje de tardanzas: ${res.percentLate}%`;
        });
}

// Filtro por rango en historial de empleado
document.getElementById('employeeAttendanceRangeForm').addEventListener('submit', function(e){
    e.preventDefault();
    const codigo = document.getElementById('employeeBasicInfo').innerHTML.match(/Código:\s*(\d+)/);
    const desde = document.getElementById('empHistStart').value;
    const hasta = document.getElementById('empHistEnd').value;
    if (!codigo) return;
    cargarHistorialEmpleado(codigo[1], desde, hasta);
});

// --- Popup Detalle de Asistencia CON FOTO ---
window.openAttendanceDetailModal = function(id) {
    fetch('api/attendance/detail.php?id=' + encodeURIComponent(id))
        .then(r=>r.json())
        .then(res=>{
            if (!res.success) return;
            const d = res.data;
            
            // Llenar la información básica
            document.getElementById('attendanceDetailContent').innerHTML = `
                <b>Código:</b> ${d.codigo}<br>
                <b>Nombre:</b> ${d.nombre}<br>
                <b>Sede:</b> ${d.sede}<br>
                <b>Establecimiento:</b> ${d.establecimiento}<br>
                <b>Fecha:</b> ${d.fecha}<br>
                <b>Hora entrada:</b> ${d.hora_entrada}<br>
                <b>Estado entrada:</b> <span class="status-in ${d.estado_entrada}">${d.estado_entrada}</span><br>
                <b>Observación:</b> ${d.observacion||'-'}
            `;
            
            // Llenar el contenedor de foto
            const photoContainer = document.getElementById('attendancePhotoContainer');
            if (d.foto) {
                photoContainer.innerHTML = `
                    <div class="photo-container">
                        <div class="photo-label">Foto de asistencia</div>
                        <img src="${d.foto}" alt="Foto de asistencia" class="attendance-photo" onclick="openPhotoModal('${d.foto}')">
                        <div class="photo-expand-hint">
                            <i class="fas fa-expand-arrows-alt"></i> Clic para ampliar
                        </div>
                    </div>
                `;
            } else {
                photoContainer.innerHTML = `
                    <div class="no-photo-message">
                        <i class="fas fa-camera-retro" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 1rem;"></i><br>
                        No hay foto disponible para esta asistencia
                    </div>
                `;
            }
            
            document.getElementById('attendanceDetailModal').classList.add('show');
        });
};

window.closeAttendanceDetailModal = function() {
    document.getElementById('attendanceDetailModal').classList.remove('show');
};

// --- Modal para ver foto en grande ---
window.openPhotoModal = function(photoUrl) {
    document.getElementById('photoModalImage').src = photoUrl;
    document.getElementById('photoModal').classList.add('show');
};

window.closePhotoModal = function() {
    document.getElementById('photoModal').classList.remove('show');
};

// Selección de asistencias (para ver detalle masivo si quieres)
function toggleAttendanceSelection(id) {
    const idx = selectedAttendances.indexOf(id);
    if (idx === -1) selectedAttendances.push(id);
    else selectedAttendances.splice(idx,1);
}

// Exportar XLS
document.getElementById('btnExportXLS').addEventListener('click', function() {
    const table = document.getElementById('tablaReportes');
    if (!table) {
        alert("No hay datos para exportar.");
        return;
    }
    const ws = XLSX.utils.table_to_sheet(table);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Reportes");
    XLSX.writeFile(wb, "reportes.xlsx");
});

// Mejora: cierra modales al hacer click fuera
document.addEventListener('mousedown', function(e) {
    document.querySelectorAll('.modal.show').forEach(modal => {
        if (e.target === modal) modal.classList.remove('show');
    });
});

// Cerrar modal de foto con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal.show').forEach(modal => {
            modal.classList.remove('show');
        });
    }
});