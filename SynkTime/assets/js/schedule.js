// Variables globales
let horarios = [];
let diasSemana = [];
let sedesCache = [];
let establecimientosCache = [];

// 1. Carga inicial
document.addEventListener('DOMContentLoaded', async function() {
    await cargarDiasSemana();
    await cargarSedesFiltro();
    await cargarEstablecimientosFiltro();
    await loadHorarios();
    renderScheduleTable(horarios);

    // Actualiza establecimientos al cambiar sede en filtros
    document.getElementById('q_sede').addEventListener('change', function() {
        cargarEstablecimientosFiltro(this.value);
    });
});

// -- Cargar días de la semana para checkboxes/modals --
async function cargarDiasSemana() {
    await fetch('api/get-dias-semana.php')
        .then(r => r.json())
        .then(res => { diasSemana = res.dias || []; });
}

// -- Cargar sedes y establecimientos para filtros --
async function cargarSedesFiltro() {
    const sedeSel = document.getElementById('q_sede');
    sedeSel.innerHTML = '<option value="">Todas</option>';
    await fetch('api/get-sedes.php')
        .then(r=>r.json())
        .then(res=>{
            sedesCache = res.sedes || [];
            sedesCache.forEach(s=>{
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
                establecimientosCache = res.establecimientos || [];
                establecimientosCache.forEach(e=>{
                    estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
                });
            });
    } else {
        await fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(idSede))
            .then(r=>r.json())
            .then(res=>{
                establecimientosCache = res.establecimientos || [];
                establecimientosCache.forEach(e=>{
                    estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
                });
            });
    }
}

// -- Cargar horarios (con filtros) --
async function loadHorarios(filtros = {}) {
    let params = new URLSearchParams(filtros).toString();
    await fetch('api/horario/list.php' + (params ? '?' + params : ''))
        .then(r=>r.json())
        .then(res=>{
            horarios = res.horarios || [];
        });
}

// -- Render tabla de horarios --
function renderScheduleTable(data) {
    const tbody = document.getElementById('scheduleTableBody');
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;">No se encontraron horarios</td></tr>';
        return;
    }
    data.forEach(hor => {
        tbody.innerHTML += `
            <tr>
                <td>${hor.ID_HORARIO}</td>
                <td>${hor.NOMBRE}</td>
                <td>${hor.SEDE || ''}</td>
                <td>${hor.ESTABLECIMIENTO || ''}</td>
                <td>${(hor.DIAS || []).join(', ')}</td>
                <td>${hor.HORA_ENTRADA}</td>
                <td>${hor.HORA_SALIDA}</td>
                <td>${hor.TOLERANCIA ?? 0}</td>
                <td>
                    <button class="btn-icon btn-edit" title="Editar" onclick="editarHorario(${hor.ID_HORARIO})"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon btn-delete" title="Eliminar" onclick="eliminarHorario(${hor.ID_HORARIO})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `;
    });
}

// -- Helper: Buscar nombre de sede por ID_ESTABLECIMIENTO --
function buscarNombreSedePorEstablecimiento(idEst) {
    let est = establecimientosCache.find(e => e.ID_ESTABLECIMIENTO == idEst);
    if (!est) return '';
    let sede = sedesCache.find(s => s.ID_SEDE == est.ID_SEDE);
    return sede ? sede.NOMBRE : '';
}

// -- Filtros (form) --
document.getElementById('scheduleQueryForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const filtros = {
        id_horario: document.getElementById('q_id_horario')?.value,
        nombre: document.getElementById('q_nombre')?.value,
        establecimiento: document.getElementById('q_establecimiento')?.value,
        hora_entrada: document.getElementById('q_hora_entrada')?.value,
        hora_salida: document.getElementById('q_hora_salida')?.value,
        dia: document.getElementById('q_dia')?.value
    };
    await loadHorarios(filtros);
    renderScheduleTable(horarios);
});

// -- Limpiar filtros --
document.getElementById('btnClearScheduleQuery').addEventListener('click', async function(e) {
    e.preventDefault();
    document.getElementById('scheduleQueryForm').reset();
    await loadHorarios();
    renderScheduleTable(horarios);
});

// -- Abrir popup Registrar --
document.getElementById('btnAddSchedule').addEventListener('click', function() {
    openScheduleModal();
});

// -- Exportar tabla a XLS --
document.getElementById('btnExportXLS').addEventListener('click', function () {
    const table = document.querySelector('.schedule-table');
    if (!table) return;
    const wb = XLSX.utils.table_to_book(table, {sheet:"Horarios"});
    XLSX.writeFile(wb, 'horarios.xlsx');
});

// -------- Modal Registrar/Editar --------
function renderDiasCheckboxes(selected=[]) {
    const cont = document.getElementById('modal_dias');
    cont.innerHTML = '';
    diasSemana.forEach(dia => {
        cont.innerHTML += `<label>
            <input type="checkbox" name="dias[]" value="${dia.ID_DIA}" ${selected.includes(dia.ID_DIA) ? 'checked' : ''}> ${dia.NOMBRE}
        </label>`;
    });
}

// Cargar sedes y establecimientos en modal
async function cargarSedesYEstablecimientosModal(selectedSede = '', selectedEst = '') {
    // Sedes
    const sedeSel = document.getElementById('modal_sede');
    const estSel = document.getElementById('modal_establecimiento');
    sedeSel.innerHTML = '<option value="">Seleccione sede</option>';
    await fetch('api/get-sedes.php')
        .then(r=>r.json())
        .then(res=>{
            (res.sedes||[]).forEach(s=>{
                sedeSel.innerHTML += `<option value="${s.ID_SEDE}" ${selectedSede==s.ID_SEDE?'selected':''}>${s.NOMBRE}</option>`;
            });
        });
    sedeSel.addEventListener('change', function() {
        cargarEstablecimientosModal(this.value);
    });
    cargarEstablecimientosModal(selectedSede, selectedEst);
}
async function cargarEstablecimientosModal(idSede, selectedEst = '') {
    const estSel = document.getElementById('modal_establecimiento');
    estSel.innerHTML = '<option value="">Seleccione establecimiento</option>';
    if (!idSede) return;
    await fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(idSede))
        .then(r=>r.json())
        .then(res=>{
            (res.establecimientos||[]).forEach(e=>{
                estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}" ${selectedEst==e.ID_ESTABLECIMIENTO?'selected':''}>${e.NOMBRE}</option>`;
            });
        });
}

function openScheduleModal(horario = null) {
    document.getElementById('scheduleModal').classList.add('show');
    document.getElementById('scheduleForm').reset();
    if (horario) {
        document.getElementById('scheduleModalTitle').textContent = "Editar Horario";
        document.getElementById('modal_id_horario').value = horario.ID_HORARIO;
        document.getElementById('modal_nombre').value = horario.NOMBRE;

        // 1. Cargar sedes y seleccionar la sede correcta
        fetch('api/get-sedes.php')
            .then(r => r.json())
            .then(res => {
                const sedeSel = document.getElementById('modal_sede');
                sedeSel.innerHTML = '<option value="">Seleccione sede</option>';
                (res.sedes || []).forEach(s => {
                    sedeSel.innerHTML += `<option value="${s.ID_SEDE}" ${s.ID_SEDE == horario.ID_SEDE ? 'selected' : ''}>${s.NOMBRE}</option>`;
                });

                // 2. Cargar establecimientos solo de esa sede y seleccionar el correcto
                fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(horario.ID_SEDE))
                    .then(r2 => r2.json())
                    .then(res2 => {
                        const estSel = document.getElementById('modal_establecimiento');
                        estSel.innerHTML = '<option value="">Seleccione establecimiento</option>';
                        (res2.establecimientos || []).forEach(e => {
                            estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}" ${e.ID_ESTABLECIMIENTO == horario.ID_ESTABLECIMIENTO ? 'selected' : ''}>${e.NOMBRE}</option>`;
                        });
                    });
            });

        document.getElementById('modal_tolerancia').value = horario.TOLERANCIA ?? 0;
        document.getElementById('modal_hora_entrada').value = horario.HORA_ENTRADA;
        document.getElementById('modal_hora_salida').value = horario.HORA_SALIDA;
        renderDiasCheckboxes(horario.DIAS_ID);

    } else {
        document.getElementById('modal_tolerancia').value = 0;
        document.getElementById('scheduleModalTitle').textContent = "Registrar Horario";
        document.getElementById('modal_id_horario').value = '';
        // Cargar sedes y limpiar establecimientos
        fetch('api/get-sedes.php')
            .then(r => r.json())
            .then(res => {
                const sedeSel = document.getElementById('modal_sede');
                sedeSel.innerHTML = '<option value="">Seleccione sede</option>';
                (res.sedes || []).forEach(s => {
                    sedeSel.innerHTML += `<option value="${s.ID_SEDE}">${s.NOMBRE}</option>`;
                });
                document.getElementById('modal_establecimiento').innerHTML = '<option value="">Seleccione establecimiento</option>';
            });
        renderDiasCheckboxes([]);
    }
}
document.getElementById('modal_sede').addEventListener('change', function() {
    const sedeId = this.value;
    fetch('api/get-establecimientos.php?sede_id=' + encodeURIComponent(sedeId))
        .then(r => r.json())
        .then(res => {
            const estSel = document.getElementById('modal_establecimiento');
            estSel.innerHTML = '<option value="">Seleccione establecimiento</option>';
            (res.establecimientos || []).forEach(e => {
                estSel.innerHTML += `<option value="${e.ID_ESTABLECIMIENTO}">${e.NOMBRE}</option>`;
            });
        });
});
function buscarSedeIdPorEstablecimiento(idEst) {
    let est = establecimientosCache.find(e => e.ID_ESTABLECIMIENTO == idEst);
    return est ? est.ID_SEDE : '';
}
window.closeScheduleModal = function() {
    document.getElementById('scheduleModal').classList.remove('show');
}

// Guardar nuevo horario / editar horario
document.getElementById('scheduleForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    // Enviar días seleccionados como array
    const dias = [];
    form.querySelectorAll('input[name="dias[]"]:checked').forEach(cb => dias.push(cb.value));
    data.append('dias', JSON.stringify(dias));
    let url = 'api/horario/' + (form.modal_id_horario.value ? 'update.php' : 'register.php');
    let method = 'POST';
    await fetch(url, { method, body: data })
        .then(r=>r.json())
        .then(async res=>{
            if (res.success) {
                await loadHorarios();
                renderScheduleTable(horarios);
                closeScheduleModal();
            } else {
                alert(res.message || "Error al guardar horario");
            }
        });
});

// -- Editar
window.editarHorario = async function(id) {
    let horario = horarios.find(h=>h.ID_HORARIO==id);
    if (!horario) return;
    openScheduleModal(horario);
}

// -- Eliminar (modal confirmación)
let horarioAEliminar = null;
window.eliminarHorario = function(id) {
    horarioAEliminar = id;
    document.getElementById('deleteScheduleModal').classList.add('show');
};
window.closeDeleteScheduleModal = function() {
    document.getElementById('deleteScheduleModal').classList.remove('show');
    horarioAEliminar = null;
};
document.getElementById('confirmDeleteScheduleBtn').addEventListener('click', async function() {
    if (!horarioAEliminar) return;
    await fetch('api/horario/delete.php', {
        method: 'POST',
        body: new URLSearchParams({id_horario: horarioAEliminar})
    })
    .then(r=>r.json())
    .then(async res=>{
        if(res.success){
            await loadHorarios();
            renderScheduleTable(horarios);
        } else {
            alert(res.message || "Error al eliminar horario");
        }
        closeDeleteScheduleModal();
    });
});

// -- Cerrar modal al hacer click fuera
document.addEventListener('mousedown', function(e) {
    document.querySelectorAll('.modal.show').forEach(modal => {
        if (e.target === modal) modal.classList.remove('show');
    });
});