function inicializarGraficos() {
    var ctxLine1 = document.getElementById("lineChart1").getContext("2d");
    lineChart1 = new Chart(ctxLine1, {
        type: "bar",
        data: {
            labels: [],
            datasets: [{
                label: "Total",
                data: [],
                borderColor: "rgba(75, 192, 192, 1)",
                backgroundColor: "rgba(75, 192, 192, 0.2)",
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            }
        }
    });

    var ctxPie1 = document.getElementById("pieChart1").getContext("2d");
    pieChart1 = new Chart(ctxPie1, {
        type: "pie",
        data: {
            labels: [],
            datasets: [{
                label: "Total",
                data: [],
                backgroundColor: ["rgba(255, 99, 132, 0.2)", "rgba(54, 162, 235, 0.2)"],
                borderColor: ["rgba(255, 99, 132, 1)", "rgba(54, 162, 235, 1)"],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return '$' + tooltipItem.raw;
                        }
                    }
                }
            }
        }
    });

    var ctxPie2 = document.getElementById("pieChart2").getContext("2d");
    pieChart2 = new Chart(ctxPie2, {
        type: "pie",
        data: {
            labels: ["Gasto", "Ahorro"],
            datasets: [{
                label: "Categorias",
                data: [0, 0],
                backgroundColor: ["rgba(255, 0, 0, 0.2)", "rgba(0, 255, 0, 0.2)"],
                borderColor: ["rgba(255, 0, 0, 1)", "rgba(0, 255, 0, 1)"],
                borderWidth: 1
            }]
        }
    });
}

async function cargarDatos() {
    const response = await fetch('/api/datos');
    const data = await response.json();

    // Procesa los datos y actualiza los gráficos
    actualizarGraficos(data.presupuestos, data.transacciones);
}

document.addEventListener('DOMContentLoaded', () => {
    cargarDatos();
});
