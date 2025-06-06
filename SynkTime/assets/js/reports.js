// Utilidades para generación de datos aleatorios
function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
function randomItem(arr) {
    return arr[randomInt(0, arr.length - 1)];
}
function pad(n) {
    return n < 10 ? "0" + n : n.toString();
}
// Lista de trabajadores simulados
const empleadosSimulados = [
    { nombre: "Juan Pérez", departamento: "Recursos Humanos", sede: "Sede Central" },
    { nombre: "María García", departamento: "TI", sede: "Sede Norte" },
    { nombre: "Carlos López", departamento: "Administración", sede: "Sede Central" },
    { nombre: "Ana Torres", departamento: "Ventas", sede: "Sede Este" },
    { nombre: "Luis Gómez", departamento: "TI", sede: "Sede Norte" },
    { nombre: "Marta Díaz", departamento: "Contabilidad", sede: "Sede Central" },
    { nombre: "Jorge Herrera", departamento: "Recursos Humanos", sede: "Sede Oeste" },
    { nombre: "Patricia Ruiz", departamento: "Administración", sede: "Sede Central" },
    { nombre: "José Ramírez", departamento: "Ventas", sede: "Sede Este" },
    { nombre: "Lucía Castro", departamento: "TI", sede: "Sede Central" },
    { nombre: "Diego Silva", departamento: "Contabilidad", sede: "Sede Norte" },
    { nombre: "Sofía Morales", departamento: "Recursos Humanos", sede: "Sede Oeste" },
    { nombre: "Raúl Paredes", departamento: "Administración", sede: "Sede Central" },
    { nombre: "Gabriela Vargas", departamento: "Ventas", sede: "Sede Este" },
    { nombre: "Miguel Castro", departamento: "TI", sede: "Sede Norte" }
];
const estadosEntrada = ["Puntual", "Tardanza", "Temprano"];
const dispositivos = ["Bio-RFID 1", "Bio-RFID 2", "Bio-RFID 3"];

// Generar 100 asistencias simuladas repartidas entre 2024 y 2025
const attendanceReports = [];
let total = 0;
while (attendanceReports.length < 100) {
    const emp = empleadosSimulados[randomInt(0, empleadosSimulados.length - 1)];
    const year = randomInt(2024, 2025);
    const month = randomInt(1, 12);
    const daysInMonth = new Date(year, month, 0).getDate();
    const day = randomInt(1, daysInMonth);
    const fecha = `${year}-${pad(month)}-${pad(day)}`;
    const estado_entrada = randomItem(estadosEntrada);
    let hora_entrada, hora_salida;
    if (estado_entrada === "Puntual") {
        hora_entrada = `08:${pad(randomInt(0,5))}`;
        hora_salida = `17:${pad(randomInt(0,10))}`;
    } else if (estado_entrada === "Tardanza") {
        hora_entrada = `08:${pad(randomInt(10,30))}`;
        hora_salida = `17:${pad(randomInt(0,10))}`;
    } else { // Temprano
        hora_entrada = `07:${pad(randomInt(30,59))}`;
        hora_salida = `16:${pad(randomInt(0,20))}`;
    }
    // Evitar duplicados exactos de empleado+fecha
    if (!attendanceReports.some(
        a => a.empleado === emp.nombre && a.fecha === fecha
    )) {
        attendanceReports.push({
            empleado: emp.nombre,
            departamento: emp.departamento,
            sede: emp.sede,
            fecha: fecha,
            hora_entrada: hora_entrada,
            hora_salida: hora_salida,
            estado_entrada: estado_entrada,
            dispositivo: randomItem(dispositivos)
        });
    }
    total++;
    if (total > 1000) break;
}

// Función para ordenar por día, mes, año (de más reciente a más lejano)
function sortByDateDesc(arr) {
    return arr.slice().sort((a, b) => {
        const [ay, am, ad] = a.fecha.split('-').map(Number);
        const [by, bm, bd] = b.fecha.split('-').map(Number);
        if (ay !== by) return by - ay;
        if (am !== bm) return bm - am;
        return bd - ad;
    });
}

// Renderizar la tabla de reportes
function renderReportsTable(data) {
    const sorted = sortByDateDesc(data);
    const tbody = document.getElementById('reportsTableBody');
    tbody.innerHTML = '';
    if (sorted.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;">No se encontraron asistencias</td></tr>';
        return;
    }
    sorted.forEach(rep => {
        tbody.innerHTML += `
            <tr>
                <td>${rep.empleado}</td>
                <td>${rep.departamento}</td>
                <td>${rep.sede}</td>
                <td>${rep.fecha}</td>
                <td>${rep.hora_entrada}</td>
                <td>${rep.hora_salida}</td>
                <td><span class="status-in ${rep.estado_entrada}">${rep.estado_entrada}</span></td>
                <td>${rep.dispositivo}</td>
            </tr>
        `;
    });
}

// Filtro por rango de fechas
function filterByDateRange(data, start, end) {
    return data.filter(rep => rep.fecha >= start && rep.fecha <= end);
}

// Inicial: mostrar todos los datos
document.addEventListener('DOMContentLoaded', function() {
    renderReportsTable(attendanceReports);
});

// Consultar por rango personalizado
document.getElementById('customRangeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const start = document.getElementById('customStart').value;
    const end = document.getElementById('customEnd').value;
    if (!start || !end) {
        alert("Selecciona ambas fechas del rango.");
        return;
    }
    if (start > end) {
        alert("La fecha de inicio no puede ser mayor que la final.");
        return;
    }
    renderReportsTable(filterByDateRange(attendanceReports, start, end));
});

// Limpiar filtro y mostrar todo
document.getElementById('btnLimpiar').addEventListener('click', function() {
    document.getElementById('customRangeForm').reset();
    renderReportsTable(attendanceReports);
});

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
// === FILTROS RÁPIDOS DE ASISTENCIA ===

// Día actual
document.getElementById('btnToday').addEventListener('click', function() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = (today.getMonth() + 1).toString().padStart(2, '0');
    const dd = today.getDate().toString().padStart(2, '0');
    const current = `${yyyy}-${mm}-${dd}`;
    const filtrados = attendanceReports.filter(rep => rep.fecha === current);
    renderReportsTable(filtrados);
});

// Semana actual
document.getElementById('btnWeek').addEventListener('click', function() {
    const today = new Date();
    // Obtener el primer día de la semana (lunes)
    const dayOfWeek = today.getDay() || 7;
    const monday = new Date(today);
    monday.setDate(today.getDate() - dayOfWeek + 1);
    const yyyy = monday.getFullYear();
    const mm = (monday.getMonth() + 1).toString().padStart(2, '0');
    const dd = monday.getDate().toString().padStart(2, '0');
    const startOfWeek = `${yyyy}-${mm}-${dd}`;
    // Último día de la semana (domingo)
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);
    const yyyy2 = sunday.getFullYear();
    const mm2 = (sunday.getMonth() + 1).toString().padStart(2, '0');
    const dd2 = sunday.getDate().toString().padStart(2, '0');
    const endOfWeek = `${yyyy2}-${mm2}-${dd2}`;
    renderReportsTable(filterByDateRange(attendanceReports, startOfWeek, endOfWeek));
});

// Mes actual
document.getElementById('btnMonth').addEventListener('click', function() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = (today.getMonth() + 1).toString().padStart(2, '0');
    // Primer día del mes
    const start = `${yyyy}-${mm}-01`;
    // Último día del mes
    const lastDay = new Date(yyyy, today.getMonth() + 1, 0).getDate();
    const end = `${yyyy}-${mm}-${lastDay.toString().padStart(2, '0')}`;
    renderReportsTable(filterByDateRange(attendanceReports, start, end));
});