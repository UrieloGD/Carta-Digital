// ========================================
// DASHBOARD FILTROS Y PAGINACIÓN EN TIEMPO REAL
// ========================================

// Variables globales
let todosLosGrupos = [];
let gruposFiltrados = [];
let paginaActual = 1;
const registrosPorPagina = 10;
let timeoutBusqueda = null;

// ========================================
// INICIALIZACIÓN
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    cargarTodosLosGrupos();
    
    // Event listeners
    document.getElementById('busquedaInput')?.addEventListener('input', function() {
        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => {
            paginaActual = 1;
            aplicarFiltros();
        }, 300);
    });
    
    document.getElementById('filtroEstado')?.addEventListener('change', function() {
        paginaActual = 1;
        aplicarFiltros();
    });
});

// ========================================
// CARGAR TODOS LOS GRUPOS VÍA AJAX
// ========================================
async function cargarTodosLosGrupos() {
    mostrarLoading(true);
    
    try {
        const response = await fetch(
            `?ajax=1&slug=${window.dashboardConfig.invitacionSlug}`,
            {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );
        
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        
        const data = await response.json();
        
        if (data.success) {
            todosLosGrupos = data.grupos || [];
            gruposFiltrados = [...todosLosGrupos];
            
            // Actualizar estadísticas
            if (data.stats) {
                this.actualizarEstadisticas(data.stats);

                if (dashboardCharts) {
                    dashboardCharts.actualizarGraficas(data.stats, data.grupos);
                }
            }
            
            aplicarFiltros();
    
            // Inicializar gráficas
            if (dashboardCharts) {
                dashboardCharts.inicializarGraficas(data.stats, todosLosGrupos);
            }
        } else {
            console.error('Error:', data.message);
            mostrarMensajeError('Error al cargar los grupos');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarMensajeError('Error de conexión al cargar los datos');
    } finally {
        mostrarLoading(false);
    }
}

// ========================================
// APLICAR FILTROS
// ========================================
function aplicarFiltros() {
    const busqueda = document.getElementById('busquedaInput').value.toLowerCase().trim();
    const estado = document.getElementById('filtroEstado').value;
    
    // Filtrar grupos
    gruposFiltrados = todosLosGrupos.filter(grupo => {
        // Filtro de búsqueda
        if (busqueda) {
            const nombreGrupo = (grupo.nombre_grupo || '').toLowerCase();
            const nombreInvitado = (grupo.nombre_invitado_principal || '').toLowerCase();
            const acompanantes = (grupo.nombres_acompanantes || '').toLowerCase();
            
            const coincide = nombreGrupo.includes(busqueda) || 
                           nombreInvitado.includes(busqueda) || 
                           acompanantes.includes(busqueda);
            
            if (!coincide) return false;
        }
        
        // Filtro de estado
        if (estado) {
            const estadoGrupo = grupo.estado || 'pendiente';
            if (estadoGrupo !== estado) return false;
        }
        
        return true;
    });
    
    // Actualizar badges de filtros
    actualizarBadgesFiltros(busqueda, estado);
    
    // Actualizar badge de total de grupos
    document.getElementById('total-grupos-badge').textContent = gruposFiltrados.length;
    
    // Renderizar
    renderizarPagina();
}

// ========================================
// RENDERIZAR PÁGINA ACTUAL
// ========================================
function renderizarPagina() {
    const inicio = (paginaActual - 1) * registrosPorPagina;
    const fin = inicio + registrosPorPagina;
    const gruposPagina = gruposFiltrados.slice(inicio, fin);
    
    // Renderizar grupos
    renderizarGrupos(gruposPagina);
    
    // Renderizar paginación
    renderizarPaginacion();
}

// ========================================
// RENDERIZAR GRUPOS EN TABLA/MOBILE
// ========================================
function renderizarGrupos(grupos) {
    // Vista Desktop
    const tbody = document.getElementById('tabla-grupos');
    if (tbody) {
        if (grupos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No se encontraron resultados</h5>
                        <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                        <button class="btn btn-primary" onclick="limpiarFiltros()">Limpiar Filtros</button>
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = grupos.map(grupo => generarFilaGrupo(grupo)).join('');
        }
    }
    
    // Vista Móvil
    const mobileContainer = document.getElementById('mobile-container');
    if (mobileContainer) {
        if (grupos.length === 0) {
            mobileContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No se encontraron grupos</p>
                    <button class="btn btn-primary" onclick="limpiarFiltros()">Limpiar Filtros</button>
                </div>
            `;
        } else {
            mobileContainer.innerHTML = grupos.map(grupo => generarTarjetaGrupo(grupo)).join('');
        }
    }
}

// ========================================
// GENERAR HTML DE FILA (DESKTOP)
// ========================================
function generarFilaGrupo(grupo) {
    const estados = {
        'pendiente': 'Sin respuesta',
        'aceptado': 'Confirmado',
        'rechazado': 'No asistirá'
    };
    
    const estado = grupo.estado || 'pendiente';
    
    return `
        <tr>
            <td>
                <strong>${escapeHtml(grupo.nombre_grupo)}</strong>
                ${grupo.nombres_acompanantes ? `<br><small class="text-muted">Con: ${escapeHtml(grupo.nombres_acompanantes)}</small>` : ''}
            </td>
            <td>
                <span class="badge bg-secondary">${grupo.boletos_asignados}</span>
                ${grupo.estado === 'aceptado' ? `<br><small class="text-success">Confirmados: ${grupo.boletos_confirmados}</small>` : ''}
            </td>
            <td>
                <span class="status-badge status-${estado}">
                    ${estados[estado] || 'Pendiente'}
                </span>
            </td>
            <td>
                <code class="text-dark">${grupo.token_unico}</code>
                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copiarToken('${grupo.token_unico}')">
                    <i class="fas fa-copy"></i>
                </button>
            </td>
            <td>
                ${grupo.fecha_respuesta ? `
                    <small>${formatearFecha(grupo.fecha_respuesta)}</small>
                    ${grupo.comentarios ? '<br><small class="text-info"><i class="fas fa-comment"></i> Con comentarios</small>' : ''}
                ` : '<small class="text-muted">Sin respuesta</small>'}
            </td>
            <td class="table-actions">
                <button class="btn btn-sm btn-action btn-secondary" onclick="editarGrupo(${grupo.id_grupo}, '${escapeHtml(grupo.nombre_grupo)}', ${grupo.boletos_asignados})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-action btn-info" onclick="compartirInvitacion('${escapeHtml(grupo.nombre_grupo)}', '${grupo.token_unico}')">
                    <i class="fas fa-share"></i>
                </button>
                ${grupo.estado && grupo.estado !== 'pendiente' ? `
                    <button class="btn btn-sm btn-action btn-success" onclick="verDetallesRespuesta(${grupo.id_grupo})">
                        <i class="fas fa-eye"></i>
                    </button>
                ` : ''}
                <button class="btn btn-sm btn-action btn-danger" onclick="eliminarGrupo(${grupo.id_grupo}, '${escapeHtml(grupo.nombre_grupo)}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
}

// ========================================
// GENERAR HTML DE TARJETA (MOBILE)
// ========================================
function generarTarjetaGrupo(grupo) {
    const estados = {
        'pendiente': 'Sin respuesta',
        'aceptado': 'Confirmado',
        'rechazado': 'No asistirá'
    };
    const estado = grupo.estado || 'pendiente';
    
    return `
        <div class="mobile-guest-card">
            <div class="guest-header">
                <div class="guest-name">${escapeHtml(grupo.nombre_grupo)}</div>
                <div class="guest-status">
                    <span class="status-badge status-${estado}">${estados[estado]}</span>
                </div>
            </div>
            <div class="guest-details">
                <div class="detail-item">
                    <i class="fas fa-ticket-alt me-2"></i>
                    ${grupo.boletos_asignados} boletos
                    ${grupo.estado === 'aceptado' ? `<small class="text-success">(${grupo.boletos_confirmados} confirmados)</small>` : ''}
                </div>
                ${grupo.nombres_acompanantes ? `
                    <div class="detail-item">
                        <i class="fas fa-users me-2"></i>
                        ${escapeHtml(grupo.nombres_acompanantes)}
                    </div>
                ` : ''}
                <div class="detail-item">
                    <i class="fas fa-key me-2"></i>
                    Token: <code class="text-dark">${grupo.token_unico}</code>
                    <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copiarToken('${grupo.token_unico}')">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                ${grupo.fecha_respuesta ? `
                    <div class="detail-item">
                        <i class="fas fa-calendar-check me-2"></i>
                        ${formatearFecha(grupo.fecha_respuesta)}
                    </div>
                ` : ''}
            </div>
            <div class="guest-actions">
                <button class="btn btn-sm btn-action btn-secondary" onclick="editarGrupo(${grupo.id_grupo}, '${escapeHtml(grupo.nombre_grupo)}', ${grupo.boletos_asignados})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-action btn-info" onclick="compartirInvitacion('${escapeHtml(grupo.nombre_grupo)}', '${grupo.token_unico}')">
                    <i class="fas fa-share"></i>
                </button>
                ${grupo.estado && grupo.estado !== 'pendiente' ? `
                    <button class="btn btn-sm btn-action btn-success" onclick="verDetallesRespuesta(${grupo.id_grupo})">
                        <i class="fas fa-eye"></i>
                    </button>
                ` : ''}
                <button class="btn btn-sm btn-action btn-danger" onclick="eliminarGrupo(${grupo.id_grupo}, '${escapeHtml(grupo.nombre_grupo)}')">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
}

// ========================================
// RENDERIZAR PAGINACIÓN (DOBLE)
// ========================================
function renderizarPaginacion() {
    const totalPaginas = Math.ceil(gruposFiltrados.length / registrosPorPagina);
    
    // Contenedores
    const containerTop = document.getElementById('paginacion-container-top');
    const containerBottom = document.getElementById('paginacion-container-bottom');
    
    if (totalPaginas <= 1) {
        if (containerTop) containerTop.style.display = 'none';
        if (containerBottom) containerBottom.style.display = 'none';
        return;
    }
    
    // Mostrar ambos contenedores
    if (containerTop) containerTop.style.display = 'block';
    if (containerBottom) containerBottom.style.display = 'block';
    
    // Generar HTML de paginación
    const paginacionHTML = generarHTMLPaginacion(totalPaginas);
    const infoHTML = generarInfoPaginacion();
    
    // Actualizar paginación superior
    const listTop = document.getElementById('pagination-list-top');
    const infoTop = document.getElementById('pagination-info-top');
    if (listTop) listTop.innerHTML = paginacionHTML;
    if (infoTop) infoTop.textContent = infoHTML;
    
    // Actualizar paginación inferior
    const listBottom = document.getElementById('pagination-list-bottom');
    const infoBottom = document.getElementById('pagination-info-bottom');
    if (listBottom) listBottom.innerHTML = paginacionHTML;
    if (infoBottom) infoBottom.textContent = infoHTML;
}

function generarHTMLPaginacion(totalPaginas) {
    let html = '';
    
    // Botón anterior (siempre visible)
    const anteriorDisabled = paginaActual === 1;
    html += `
        <li class="page-item ${anteriorDisabled ? 'disabled' : ''}">
            <a class="page-link ${anteriorDisabled ? 'disabled' : ''}" 
               href="#" 
               onclick="${anteriorDisabled ? 'return false;' : `cambiarPagina(${paginaActual - 1}); return false;`}"
               ${anteriorDisabled ? 'style="cursor: not-allowed; opacity: 0.5;"' : ''}>
                <i class="fas fa-chevron-left"></i> <span>Anterior</span>
            </a>
        </li>
    `;
    
    // Calcular rango de páginas a mostrar (siempre 5 páginas)
    let rangoInicio, rangoFin;
    
    if (totalPaginas <= 5) {
        // Si hay 5 o menos páginas, mostrar todas
        rangoInicio = 1;
        rangoFin = totalPaginas;
    } else {
        // Calcular ventana de 5 páginas centrada en la página actual
        rangoInicio = Math.max(1, paginaActual - 2);
        rangoFin = rangoInicio + 4;
        
        // Ajustar si nos pasamos del final
        if (rangoFin > totalPaginas) {
            rangoFin = totalPaginas;
            rangoInicio = Math.max(1, rangoFin - 4);
        }
    }
    
    // Botón primera página (si no está en el rango)
    if (rangoInicio > 1) {
        html += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPagina(1); return false;">
                    1
                </a>
            </li>
        `;
        
        // Puntos suspensivos
        if (rangoInicio > 2) {
            html += `
                <li class="page-item disabled">
                    <span class="page-link" style="cursor: default;">...</span>
                </li>
            `;
        }
    }
    
    // Números de página (ventana de 5)
    for (let i = rangoInicio; i <= rangoFin; i++) {
        html += `
            <li class="page-item ${i === paginaActual ? 'active' : ''}">
                <a class="page-link" href="#" onclick="cambiarPagina(${i}); return false;">
                    ${i}
                </a>
            </li>
        `;
    }
    
    // Botón última página (si no está en el rango)
    if (rangoFin < totalPaginas) {
        // Puntos suspensivos
        if (rangoFin < totalPaginas - 1) {
            html += `
                <li class="page-item disabled">
                    <span class="page-link" style="cursor: default;">...</span>
                </li>
            `;
        }
        
        html += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="cambiarPagina(${totalPaginas}); return false;">
                    ${totalPaginas}
                </a>
            </li>
        `;
    }
    
    // Botón siguiente (siempre visible)
    const siguienteDisabled = paginaActual === totalPaginas || totalPaginas === 0;
    html += `
        <li class="page-item ${siguienteDisabled ? 'disabled' : ''}">
            <a class="page-link ${siguienteDisabled ? 'disabled' : ''}" 
               href="#" 
               onclick="${siguienteDisabled ? 'return false;' : `cambiarPagina(${paginaActual + 1}); return false;`}"
               ${siguienteDisabled ? 'style="cursor: not-allowed; opacity: 0.5;"' : ''}>
                <span>Siguiente</span> <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;
    
    return html;
}

function generarInfoPaginacion() {
    const inicio = (paginaActual - 1) * registrosPorPagina + 1;
    const fin = Math.min(paginaActual * registrosPorPagina, gruposFiltrados.length);
    return `Mostrando ${inicio} - ${fin} de ${gruposFiltrados.length} grupos`;
}

// ========================================
// CAMBIAR PÁGINA
// ========================================
function cambiarPagina(numeroPagina) {
    paginaActual = numeroPagina;
    renderizarPagina();
    
    // Scroll al inicio de la tabla
    document.querySelector('.guests-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ========================================
// ACTUALIZAR BADGES DE FILTROS
// ========================================
function actualizarBadgesFiltros(busqueda, estado) {
    const container = document.getElementById('filtros-activos');
    if (!container) return;
    
    let badges = [];
    
    if (busqueda) {
        badges.push(`
            <span class="filter-badge">
                Búsqueda: "${busqueda}"
                <span class="close" onclick="document.getElementById('busquedaInput').value=''; aplicarFiltros();">&times;</span>
            </span>
        `);
    }
    
    if (estado) {
        const estadosNombres = {
            'aceptado': 'Confirmados',
            'rechazado': 'Rechazados',
            'pendiente': 'Pendientes'
        };
        badges.push(`
            <span class="filter-badge">
                Estado: ${estadosNombres[estado]}
                <span class="close" onclick="document.getElementById('filtroEstado').value=''; aplicarFiltros();">&times;</span>
            </span>
        `);
    }
    
    if (badges.length > 0) {
        badges.push(`<small class="text-muted ms-2">${gruposFiltrados.length} resultado(s)</small>`);
        container.innerHTML = badges.join('');
        container.style.display = 'flex';
    } else {
        container.innerHTML = '';
        container.style.display = 'none';
    }
}

// ========================================
// ACTUALIZAR ESTADÍSTICAS
// ========================================
function actualizarEstadisticas(stats) {
    document.getElementById('stat-total').textContent = stats.total_boletos || 0;
    document.getElementById('stat-confirmados').textContent = stats.confirmados || 0;
    document.getElementById('stat-rechazados').textContent = stats.rechazados || 0;
    document.getElementById('stat-pendientes').textContent = stats.pendientes || 0;
}

// ========================================
// LIMPIAR FILTROS
// ========================================
function limpiarFiltros() {
    document.getElementById('busquedaInput').value = '';
    document.getElementById('filtroEstado').value = '';
    paginaActual = 1;
    aplicarFiltros();
}

// ========================================
// UTILIDADES
// ========================================
function mostrarLoading(mostrar) {
    const spinner = document.getElementById('loading-spinner');
    const tablaContainer = document.querySelector('.table-responsive');
    const mobileContainer = document.getElementById('mobile-container');
    
    if (spinner) {
        spinner.style.display = mostrar ? 'block' : 'none';
    }
    
    if (tablaContainer && mobileContainer) {
        tablaContainer.style.opacity = mostrar ? '0.5' : '1';
        mobileContainer.style.opacity = mostrar ? '0.5' : '1';
    }
}

function mostrarMensajeError(mensaje) {
    alert(mensaje); // Puedes mejorar esto con un toast o modal bonito
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text || '').replace(/[&<>"']/g, m => map[m]);
}

function formatearFecha(fecha) {
    if (!fecha) return '';
    const d = new Date(fecha);
    const dia = String(d.getDate()).padStart(2, '0');
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const año = d.getFullYear();
    const horas = String(d.getHours()).padStart(2, '0');
    const minutos = String(d.getMinutes()).padStart(2, '0');
    return `${dia}/${mes}/${año} ${horas}:${minutos}`;
}
