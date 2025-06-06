class Dashboard {
    constructor() {
        this.initializeCharts();
    }

    initializeCharts() {
        // Gráfica de Asistencia por Hora
        const hourlyOptions = {
            series: [{
                name: 'Entradas',
                data: [30, 45, 75, 90, 45, 30, 15, 5]
            }],
            chart: {
                type: 'area',
                height: 350,
                toolbar: {
                    show: false
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            colors: ['#4B96FA'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'vertical',
                    shadeIntensity: 0.3,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 90, 100]
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            xaxis: {
                categories: ['6:00', '7:00', '8:00', '9:00', '10:00', '11:00', '12:00', '13:00'],
                labels: {
                    style: {
                        colors: '#718096'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#718096'
                    }
                }
            },
            tooltip: {
                theme: 'light',
                y: {
                    formatter: function(value) {
                        return value + ' empleados'
                    }
                }
            }
        };

        // Gráfica de Distribución de Asistencias
        const distributionOptions = {
            series: [182, 12, 5],
            chart: {
                type: 'donut',
                height: 350
            },
            colors: ['#48BB78', '#F6AD55', '#F56565'],
            labels: ['A Tiempo', 'Tardanzas', 'Faltas'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%'
                    }
                }
            },
            legend: {
                position: 'bottom'
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 300
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        const hourlyChart = new ApexCharts(
            document.querySelector("#hourlyAttendanceChart"), 
            hourlyOptions
        );
        
        const distributionChart = new ApexCharts(
            document.querySelector("#attendanceDistributionChart"), 
            distributionOptions
        );

        hourlyChart.render();
        distributionChart.render(); 
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new Dashboard();
});