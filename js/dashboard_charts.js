// ========================================
// DASHBOARD GRÁFICAS - Chart.js
// ========================================

// Registrar el plugin de datalabels globalmente
if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
    Chart.register(ChartDataLabels);
}

class DashboardCharts {
    constructor() {
        this.chartEstado = null;
        this.chartBoletos = null;
        this.chartTimeline = null;
    }

    // Inicializar todas las gráficas
    inicializarGraficas(stats, grupos) {
        this.crearGraficaEstado(stats);
        this.crearGraficaBoletos(stats);
        this.crearGraficaTimeline(grupos);
    }

    // Gráfica de Pastel - Estado de Respuestas
    crearGraficaEstado(stats) {
        const ctx = document.getElementById('chartEstadoRSVP');
        if (!ctx) return;

        const confirmados = parseInt(stats.confirmados) || 0;
        const rechazados = parseInt(stats.rechazados) || 0;
        const pendientes = parseInt(stats.pendientes) || 0;
        const total = confirmados + rechazados + pendientes;

        // Destruir gráfica anterior si existe
        if (this.chartEstado) {
            this.chartEstado.destroy();
        }

        this.chartEstado = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Confirmados', 'No Asistirán', 'Pendientes'],
                datasets: [{
                    data: [confirmados, rechazados, pendientes],
                    backgroundColor: [
                        '#28a745', // Verde
                        '#dc3545', // Rojo
                        '#ffc107'  // Amarillo
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // ✅ Permite controlar altura
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 12,
                            font: {
                                size: 11,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} boletos (${percentage}%)`;
                            }
                        }
                    },
                    datalabels: {
                        display: true,
                        color: '#fff',
                        font: {
                            size: 13,
                            weight: 'bold'
                        },
                        formatter: function(value, context) {
                            if (value === 0) return '';
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return percentage + '%';
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10
                    }
                }
            }
        });
    }

    // Gráfica de Barras - Distribución de Boletos
    crearGraficaBoletos(stats) {
        const ctx = document.getElementById('chartBoletos');
        if (!ctx) return;

        const confirmados = parseInt(stats.confirmados) || 0;
        const rechazados = parseInt(stats.rechazados) || 0;
        const pendientes = parseInt(stats.pendientes) || 0;

        // Destruir gráfica anterior si existe
        if (this.chartBoletos) {
            this.chartBoletos.destroy();
        }

        this.chartBoletos = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Confirmados', 'No Asistirán', 'Pendientes'],
                datasets: [{
                    label: 'Boletos',
                    data: [confirmados, rechazados, pendientes],
                    backgroundColor: [
                        '#28a745', // Verde
                        '#dc3545', // Rojo
                        '#ffc107'  // Amarillo
                    ],
                    borderWidth: 0,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // ✅ Permite controlar altura
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y} boletos`;
                            }
                        }
                    },
                    datalabels: {
                        display: true,
                        color: '#333',
                        anchor: 'end',
                        align: 'end',
                        offset: -5, // ✅ Separación del borde
                        font: {
                            size: 12,
                            weight: 'bold'
                        },
                        formatter: function(value) {
                            return value > 0 ? value : '';
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5,
                            font: {
                                size: 10
                            },
                            padding: 5
                        },
                        grid: {
                            color: '#f0f0f0'
                        },
                        // ✅ Agregar espacio extra arriba para los valores
                        suggestedMax: function(context) {
                            const max = Math.max(...context.chart.data.datasets[0].data);
                            return max + (max * 0.15); // 15% más de espacio
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10,
                                weight: '500'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 25, // ✅ Espacio superior para valores
                        bottom: 10,
                        left: 10,
                        right: 10
                    }
                }
            }
        });
    }

    // Gráfica de Línea - Timeline de Confirmaciones
    crearGraficaTimeline(grupos) {
        const ctx = document.getElementById('chartTimeline');
        if (!ctx) return;

        // Filtrar solo grupos con respuesta
        const gruposConRespuesta = grupos.filter(g => 
            g.fecha_respuesta && g.fecha_respuesta !== '0000-00-00 00:00:00'
        );

        // Si no hay respuestas, mostrar mensaje
        if (gruposConRespuesta.length === 0) {
            if (this.chartTimeline) {
                this.chartTimeline.destroy();
            }
            ctx.parentElement.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <p>Aún no hay respuestas registradas</p>
                </div>
            `;
            return;
        }

        // Agrupar por fecha
        const respuestasPorFecha = {};
        gruposConRespuesta.forEach(grupo => {
            const fecha = grupo.fecha_respuesta.split(' ')[0];
            if (!respuestasPorFecha[fecha]) {
                respuestasPorFecha[fecha] = {
                    confirmados: 0,
                    rechazados: 0,
                    total: 0
                };
            }
            
            if (grupo.estado === 'aceptado') {
                respuestasPorFecha[fecha].confirmados += parseInt(grupo.boletos_confirmados) || 0;
            } else if (grupo.estado === 'rechazado') {
                respuestasPorFecha[fecha].rechazados += parseInt(grupo.boletos_asignados) || 0;
            }
            
            respuestasPorFecha[fecha].total++;
        });

        const fechasOrdenadas = Object.keys(respuestasPorFecha).sort();
        
        const labels = fechasOrdenadas.map(fecha => {
            const d = new Date(fecha + 'T00:00:00');
            return d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
        });

        const dataConfirmados = [];
        const dataRechazados = [];
        let acumuladoConfirmados = 0;
        let acumuladoRechazados = 0;

        fechasOrdenadas.forEach(fecha => {
            acumuladoConfirmados += respuestasPorFecha[fecha].confirmados;
            acumuladoRechazados += respuestasPorFecha[fecha].rechazados;
            dataConfirmados.push(acumuladoConfirmados);
            dataRechazados.push(acumuladoRechazados);
        });

        if (this.chartTimeline) {
            this.chartTimeline.destroy();
        }

        this.chartTimeline = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Confirmados',
                        data: dataConfirmados,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Rechazados',
                        data: dataRechazados,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.1)',
                        tension: 0.3,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 15,
                            font: {
                                size: 11,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    },
                    datalabels: {
                        display: false // No mostrar valores en timeline
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 10
                            }
                        },
                        grid: {
                            color: '#f0f0f0'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 10
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 10,
                        bottom: 10
                    }
                }
            }
        });
    }

    // Actualizar todas las gráficas
    actualizarGraficas(stats, grupos) {
        this.inicializarGraficas(stats, grupos);
    }
}

// Instancia global
let dashboardCharts = null;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    dashboardCharts = new DashboardCharts();
});
