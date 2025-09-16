// dashboard.js - Funcionalidades del Dashboard de Invitaciones (Versión sin Auto-refresh)
class DashboardManager {
    constructor() {
        this.currentFilters = {
            search: '',
            status: 'all',
            sortBy: 'newest',
            itemsPerPage: '10',
            currentPage: 1
        };
        this.filteredGroups = [];
        this.initializeEvents();
    }

    // Inicializar eventos
    initializeEvents() {
        // Auto-focus en modales
        document.addEventListener('shown.bs.modal', function (e) {
            const firstInput = e.target.querySelector('input[type="text"], input[type="number"]');
            if (firstInput) firstInput.focus();
        });
    }

    // Alternar visibilidad de filtros
    toggleFilters() {
        const filtersContent = document.getElementById('filters-content');
        const chevron = document.getElementById('filters-chevron');
        
        if (filtersContent.classList.contains('filters-collapsed')) {
            filtersContent.classList.remove('filters-collapsed');
            filtersContent.classList.add('filters-expanded');
            chevron.classList.replace('fa-chevron-down', 'fa-chevron-up');
        } else {
            filtersContent.classList.remove('filters-expanded');
            filtersContent.classList.add('filters-collapsed');
            chevron.classList.replace('fa-chevron-up', 'fa-chevron-down');
        }
    }

    // Aplicar filtros
    applyFilters() {
        // Obtener valores actuales de los filtros
        this.currentFilters.search = document.getElementById('search-input').value.toLowerCase();
        this.currentFilters.status = document.getElementById('status-filter').value;
        this.currentFilters.sortBy = document.getElementById('sort-by').value;
        this.currentFilters.itemsPerPage = document.getElementById('items-per-page').value;
        this.currentFilters.currentPage = 1;
        
        // Aplicar filtros
        this.filterGroups();
        
        // Actualizar badges de filtros activos
        this.updateActiveFiltersBadges();
    }

    // Restablecer filtros
    resetFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('status-filter').value = 'all';
        document.getElementById('sort-by').value = 'newest';
        document.getElementById('items-per-page').value = '10';
        
        this.currentFilters = {
            search: '',
            status: 'all',
            sortBy: 'newest',
            itemsPerPage: '10',
            currentPage: 1
        };
        
        this.filterGroups();
        this.updateActiveFiltersBadges();
    }

    // Filtrar grupos según los criterios
    filterGroups() {
        if (!this.allGroups || this.allGroups.length === 0) {
            // Si no tenemos los grupos, usar los que están en el DOM
            this.allGroups = this.getGroupsFromDOM();
        }
        
        // Aplicar filtro de búsqueda
        this.filteredGroups = this.allGroups.filter(group => {
            const matchesSearch = this.currentFilters.search === '' || 
                                group.nombre_grupo.toLowerCase().includes(this.currentFilters.search) ||
                                (group.nombres_acompanantes && group.nombres_acompanantes.toLowerCase().includes(this.currentFilters.search));
            
            const matchesStatus = this.currentFilters.status === 'all' || 
                                group.estado === this.currentFilters.status;
            
            return matchesSearch && matchesStatus;
        });
        
        // Aplicar ordenamiento
        this.sortGroups();
        
        // Aplicar paginación si es necesario
        if (this.currentFilters.itemsPerPage !== 'all') {
            const itemsPerPage = parseInt(this.currentFilters.itemsPerPage);
            const startIndex = (this.currentFilters.currentPage - 1) * itemsPerPage;
            this.filteredGroups = this.filteredGroups.slice(startIndex, startIndex + itemsPerPage);
        }
        
        // Actualizar la visualización
        this.actualizarTablaGrupos(this.filteredGroups);
    }

    // Ordenar grupos
    sortGroups() {
        switch (this.currentFilters.sortBy) {
            case 'newest':
                this.filteredGroups.sort((a, b) => new Date(b.fecha_creacion) - new Date(a.fecha_creacion));
                break;
            case 'oldest':
                this.filteredGroups.sort((a, b) => new Date(a.fecha_creacion) - new Date(b.fecha_creacion));
                break;
            case 'name_asc':
                this.filteredGroups.sort((a, b) => a.nombre_grupo.localeCompare(b.nombre_grupo));
                break;
            case 'name_desc':
                this.filteredGroups.sort((a, b) => b.nombre_grupo.localeCompare(a.nombre_grupo));
                break;
        }
    }

    // Función para parsear fechas desde el formato mostrado en el DOM
    parseDateFromDOM(dateText) {
        if (!dateText || dateText === 'Sin respuesta' || dateText === '') {
            return null;
        }
        
        try {
            // El formato en el DOM es "d/m/Y H:i" pero necesitamos convertirlo a formato ISO
            const parts = dateText.split(' ');
            if (parts.length === 2) {
                const dateParts = parts[0].split('/');
                const timeParts = parts[1].split(':');
                
                if (dateParts.length === 3 && timeParts.length === 2) {
                    // Crear fecha en formato YYYY-MM-DD HH:MM:SS
                    return `${dateParts[2]}-${dateParts[1]}-${dateParts[0]} ${timeParts[0]}:${timeParts[1]}:00`;
                }
            }
            return dateText; // Si no puede parsear, devolver el texto original
        } catch (error) {
            console.error('Error parseando fecha del DOM:', error);
            return dateText;
        }
    }

    // Obtener grupos desde el DOM (para cuando no hay datos AJAX)
    getGroupsFromDOM() {
        const grupos = [];
        
        // Intentar obtener de la tabla desktop
        const rows = document.querySelectorAll('table tbody tr');
        if (rows.length > 0) {
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 6) {
                    // Extraer datos de cada celda
                    const nombreElement = cells[0].querySelector('strong');
                    const acompanantesElement = cells[0].querySelector('small');
                    const boletosElement = cells[1].querySelector('.badge');
                    const confirmadosElement = cells[1].querySelector('small');
                    const estadoElement = cells[2].querySelector('.status-badge');
                    const tokenElement = cells[3].querySelector('code');
                    const fechaElement = cells[4].querySelector('small:not(.text-info)');
                    
                    // Obtener botones para extraer IDs de onclick
                    const editBtn = cells[5].querySelector('button[onclick*="editarGrupo"]');
                    let id_grupo = null;
                    if (editBtn) {
                        const onclickAttr = editBtn.getAttribute('onclick');
                        const idMatch = onclickAttr.match(/editarGrupo\((\d+),/);
                        id_grupo = idMatch ? parseInt(idMatch[1]) : null;
                    }
                    
                    // Determinar estado
                    let estado = 'pendiente';
                    if (estadoElement) {
                        if (estadoElement.classList.contains('status-aceptado')) estado = 'aceptado';
                        else if (estadoElement.classList.contains('status-rechazado')) estado = 'rechazado';
                    }
                    
                    grupos.push({
                        id_grupo: id_grupo,
                        nombre_grupo: nombreElement ? nombreElement.textContent.trim() : '',
                        nombres_acompanantes: acompanantesElement ? acompanantesElement.textContent.replace('Con: ', '').trim() : '',
                        boletos_asignados: boletosElement ? parseInt(boletosElement.textContent) : 0,
                        boletos_confirmados: confirmadosElement ? parseInt(confirmadosElement.textContent.match(/\d+/)?.[0] || 0) : 0,
                        estado: estado,
                        token_unico: tokenElement ? tokenElement.textContent.trim() : '',
                        fecha_respuesta: fechaElement ? this.parseDateFromDOM(fechaElement.textContent.trim()) : null,
                        fecha_creacion: new Date().toISOString(), // Fecha por defecto
                        comentarios: cells[4].querySelector('.text-info') ? 'Sí' : null
                    });
                }
            });
            return grupos;
        }
        
        // Intentar obtener de la vista móvil
        const mobileCards = document.querySelectorAll('.mobile-guest-card');
        if (mobileCards.length > 0) {
            mobileCards.forEach(card => {
                const nombreElement = card.querySelector('.guest-name');
                const estadoElement = card.querySelector('.status-badge');
                const boletosElement = card.querySelector('.detail-item');
                const tokenElement = card.querySelector('code');
                const acompanantesElement = card.querySelector('.detail-item:nth-child(2) .fas.fa-users');
                
                // Extraer ID del botón editar
                const editBtn = card.querySelector('button[onclick*="editarGrupo"]');
                let id_grupo = null;
                if (editBtn) {
                    const onclickAttr = editBtn.getAttribute('onclick');
                    const idMatch = onclickAttr.match(/editarGrupo\((\d+),/);
                    id_grupo = idMatch ? parseInt(idMatch[1]) : null;
                }
                
                // Determinar estado
                let estado = 'pendiente';
                if (estadoElement) {
                    if (estadoElement.classList.contains('status-aceptado')) estado = 'aceptado';
                    else if (estadoElement.classList.contains('status-rechazado')) estado = 'rechazado';
                }
                
                // Extraer número de boletos
                let boletos = 0;
                if (boletosElement) {
                    const boletosMatch = boletosElement.textContent.match(/(\d+)\s+boletos/);
                    boletos = boletosMatch ? parseInt(boletosMatch[1]) : 0;
                }
                
                grupos.push({
                    id_grupo: id_grupo,
                    nombre_grupo: nombreElement ? nombreElement.textContent.trim() : '',
                    nombres_acompanantes: acompanantesElement ? acompanantesElement.parentElement.textContent.trim() : '',
                    boletos_asignados: boletos,
                    boletos_confirmados: 0,
                    estado: estado,
                    token_unico: tokenElement ? tokenElement.textContent.trim() : '',
                    fecha_respuesta: null,
                    fecha_creacion: new Date().toISOString(),
                    comentarios: null
                });
            });
        }
        
        return grupos;
    }

    // Actualizar badges de filtros activos
    updateActiveFiltersBadges() {
        const container = document.getElementById('active-filters');
        container.innerHTML = '';
        
        // Badge para búsqueda
        if (this.currentFilters.search) {
            container.innerHTML += `
                <span class="filter-badge">
                    Búsqueda: "${this.currentFilters.search}"
                    <span class="close" onclick="removeFilter('search')">&times;</span>
                </span>
            `;
        }
        
        // Badge para estado
        if (this.currentFilters.status !== 'all') {
            const statusText = {
                'pendiente': 'Sin respuesta',
                'aceptado': 'Confirmados',
                'rechazado': 'No asistirán'
            }[this.currentFilters.status];
            
            container.innerHTML += `
                <span class="filter-badge">
                    Estado: ${statusText}
                    <span class="close" onclick="removeFilter('status')">&times;</span>
                </span>
            `;
        }
        
        // Badge para ordenamiento
        if (this.currentFilters.sortBy !== 'newest') {
            const sortText = {
                'oldest': 'Más antiguos primero',
                'name_asc': 'Nombre (A-Z)',
                'name_desc': 'Nombre (Z-A)'
            }[this.currentFilters.sortBy];
            
            container.innerHTML += `
                <span class="filter-badge">
                    Orden: ${sortText}
                    <span class="close" onclick="removeFilter('sort')">&times;</span>
                </span>
            `;
        }
        
        // Mostrar u ocultar contenedor
        if (container.innerHTML === '') {
            container.style.display = 'none';
        } else {
            container.style.display = 'flex';
        }
    }

    // Eliminar filtro específico
    removeFilter(type) {
        switch (type) {
            case 'search':
                document.getElementById('search-input').value = '';
                this.currentFilters.search = '';
                break;
            case 'status':
                document.getElementById('status-filter').value = 'all';
                this.currentFilters.status = 'all';
                break;
            case 'sort':
                document.getElementById('sort-by').value = 'newest';
                this.currentFilters.sortBy = 'newest';
                break;
        }
        
        this.applyFilters();
    }

    // Modificar la función actualizarTablaGrupos para manejar resultados vacíos
    actualizarTablaGrupos(grupos) {
        // Si no hay resultados, mostrar mensaje
        if (grupos.length === 0) {
            const noResultsHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h5>No se encontraron grupos</h5>
                    <p>Intenta ajustar tus filtros de búsqueda</p>
                    <button class="btn btn-primary mt-2" onclick="resetFilters()">
                        Restablecer filtros
                    </button>
                </div>
            `;
            
            // Actualizar vista desktop
            const tableBody = document.querySelector('table tbody');
            if (tableBody) {
                tableBody.innerHTML = noResultsHTML;
            }

            // Actualizar vista mobile
            const mobileContainer = document.querySelector('.mobile-guests');
            if (mobileContainer) {
                mobileContainer.innerHTML = noResultsHTML;
            }
            
            return;
        }
        
        // Resto del código original para mostrar grupos...
        const tableBody = document.querySelector('table tbody');
        if (tableBody) {
            tableBody.innerHTML = this.generarFilasTabla(grupos);
        }

        const mobileContainer = document.querySelector('.mobile-guests');
        if (mobileContainer) {
            mobileContainer.innerHTML = this.generarTarjetasMobile(grupos);
        }
    }

    // Modificar la función actualizarDatos para manejar elementos que pueden no existir
    actualizarDatos() {
        const btnActualizar = document.getElementById('btn-actualizar');
        
        if (!btnActualizar) {
            console.error('No se encontró el botón actualizar');
            return;
        }
        
        // Mostrar loading
        btnActualizar.disabled = true;
        btnActualizar.innerHTML = '<i class="fas fa-sync-alt fa-spin me-2"></i>Actualizando...';

        // Hacer petición AJAX
        const url = new URL(window.location.href);
        url.searchParams.set('ajax', '1');
        
        fetch(url.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Guardar todos los grupos para filtrar
                    this.allGroups = data.grupos;
                    
                    this.actualizarEstadisticas(data.stats);
                    this.filterGroups();
                    
                    // Feedback de éxito
                    btnActualizar.innerHTML = '<i class="fas fa-check me-2"></i>Actualizado';
                    btnActualizar.className = 'btn-action btn-success'; // Mantiene btn-action, cambia a success
                    
                    setTimeout(() => {
                        btnActualizar.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Actualizar';
                        btnActualizar.className = 'btn-action btn-secondary'; // Vuelve a las clases originales
                        btnActualizar.disabled = false;
                    }, 2000);
                } else {
                    throw new Error('Error en la respuesta');
                }
            })
            .catch(error => {
                console.error('Error actualizando datos:', error);
                
                // Feedback de error
                btnActualizar.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error';
                btnActualizar.className = 'btn-action btn-danger'; // Mantiene btn-action, cambia a danger
                
                setTimeout(() => {
                    btnActualizar.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Actualizar';
                    btnActualizar.className = 'btn-action btn-secondary'; // Vuelve a las clases originales
                    btnActualizar.disabled = false;
                }, 2000);
            });
    }
    
    // Actualizar estadísticas con animación
    actualizarEstadisticas(newStats) {
        const statsMapping = [
            { selector: '.stats-row .col-6:nth-child(1) .stats-number', value: newStats.total_grupos },
            { selector: '.stats-row .col-6:nth-child(2) .stats-number', value: newStats.confirmados },
            { selector: '.stats-row .col-6:nth-child(3) .stats-number', value: newStats.rechazados },
            { selector: '.stats-row .col-6:nth-child(4) .stats-number', value: newStats.pendientes }
        ];

        statsMapping.forEach(stat => {
            const element = document.querySelector(stat.selector);
            if (element && element.textContent != stat.value) {
                element.style.animation = 'none';
                element.offsetHeight; // Forzar reflow
                element.textContent = stat.value || 0;
                element.style.animation = 'pulse 0.6s ease-in-out';
            }
        });
    }

    // Función para formatear fechas de manera segura
    formatDate(dateString) {
        if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === 'null') {
            return 'Sin respuesta';
        }
        
        try {
            // Si ya está en formato legible (viene del DOM), devolverlo tal cual
            if (typeof dateString === 'string' && dateString.includes('/')) {
                return dateString;
            }
            
            const date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return 'Sin respuesta';
            }
            return date.toLocaleString('es-ES');
        } catch (error) {
            console.error('Error formateando fecha:', error, dateString);
            return 'Sin respuesta';
        }
    }

    // Generar filas de tabla para desktop
    generarFilasTabla(grupos) {
        return grupos.map(grupo => {
            // Validaciones y valores por defecto
            const nombreGrupo = grupo.nombre_grupo || 'Sin nombre';
            const boletosAsignados = grupo.boletos_asignados || 0;
            const boletosConfirmados = grupo.boletos_confirmados || 0;
            const estado = grupo.estado || 'pendiente';
            const tokenUnico = grupo.token_unico || '';
            const idGrupo = grupo.id_grupo || 0;
            const nombresAcompanantes = grupo.nombres_acompanantes || '';
            const fechaRespuesta = grupo.fecha_respuesta;
            const comentarios = grupo.comentarios;
            
            const estados = {
                'pendiente': 'Sin respuesta', 
                'aceptado': 'Confirmado', 
                'rechazado': 'No asistirá'
            };
            const estadoTexto = estados[estado] || 'Pendiente';
            
            return `
                <tr>
                    <td>
                        <strong>${this.htmlEscape(nombreGrupo)}</strong>
                        ${nombresAcompanantes ? `<br><small class="text-muted">Con: ${this.htmlEscape(nombresAcompanantes)}</small>` : ''}
                    </td>
                    <td>
                        <span class="badge bg-secondary">${boletosAsignados}</span>
                        ${estado == 'aceptado' ? `<br><small class="text-success">Confirmados: ${boletosConfirmados}</small>` : ''}
                    </td>
                    <td>
                        <span class="status-badge status-${estado}">
                            ${estadoTexto}
                        </span>
                    </td>
                    <td>
                        <code>${tokenUnico}</code>
                        <button class="btn btn-sm btn-outline-secondary ms-1" 
                                onclick="copiarToken('${tokenUnico}')">
                            <i class="fas fa-copy"></i>
                        </button>
                    </td>
                    <td>
                        ${fechaRespuesta && fechaRespuesta !== '0000-00-00 00:00:00' && fechaRespuesta !== null ? 
                            `<small>${this.formatDate(fechaRespuesta)}</small>` : 
                            '<small class="text-muted">Sin respuesta</small>'}
                        ${comentarios ? '<br><small class="text-info"><i class="fas fa-comment"></i> Con comentarios</small>' : ''}
                    </td>
                    <td class="table-actions">
                        <button class="btn btn-sm btn-secondary btn-action" 
                                onclick="editarGrupo(${idGrupo}, '${this.htmlEscape(nombreGrupo)}', ${boletosAsignados})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-info btn-action" 
                                onclick="compartirInvitacion('${this.htmlEscape(nombreGrupo)}', '${tokenUnico}')">
                            <i class="fas fa-share"></i>
                        </button>
                        ${estado && estado !== 'pendiente' ? 
                            `<button class="btn btn-sm btn-success btn-action" 
                                    onclick="verDetallesRespuesta(${idGrupo})">
                                <i class="fas fa-eye"></i>
                            </button>` : ''}
                        <button class="btn btn-sm btn-danger btn-action" 
                                onclick="eliminarGrupo(${idGrupo}, '${this.htmlEscape(nombreGrupo)}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Generar tarjetas para mobile
    generarTarjetasMobile(grupos) {
        return grupos.map(grupo => {
            const estados = {
                'pendiente': 'Sin respuesta', 
                'aceptado': 'Confirmado', 
                'rechazado': 'No asistirá'
            };
            const estadoTexto = estados[grupo.estado] || 'Pendiente';
            
            return `
                <div class="mobile-guest-card">
                    <div class="guest-header">
                        <div class="guest-name">
                            ${this.htmlEscape(grupo.nombre_grupo)}
                        </div>
                        <div class="guest-status">
                            <span class="status-badge status-${grupo.estado || 'pendiente'}">
                                ${estadoTexto}
                            </span>
                        </div>
                    </div>
                    
                    <div class="guest-details">
                        <div class="detail-item">
                            <i class="fas fa-ticket-alt me-2"></i>
                            ${grupo.boletos_asignados} boletos
                            ${grupo.estado == 'aceptado' ? `<small class="text-success">(${grupo.boletos_confirmados} confirmados)</small>` : ''}
                        </div>
                        
                        ${grupo.nombres_acompanantes ? 
                            `<div class="detail-item">
                                <i class="fas fa-users me-2"></i>
                                ${this.htmlEscape(grupo.nombres_acompanantes)}
                            </div>` : ''}
                        
                        <div class="detail-item">
                            <i class="fas fa-key me-2"></i>
                            Token: <code>${grupo.token_unico}</code>
                            <button class="btn btn-sm btn-outline-secondary ms-1" 
                                    onclick="copiarToken('${grupo.token_unico}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        
                        ${grupo.fecha_respuesta && grupo.fecha_respuesta !== '0000-00-00 00:00:00' && grupo.fecha_respuesta !== null ? 
                        `<div class="detail-item">
                            <i class="fas fa-calendar-check me-2"></i>
                            ${this.formatDate(grupo.fecha_respuesta)}
                        </div>` : ''}
                    </div>
                    
                    <div class="guest-actions">
                        <button class="btn btn-sm btn-primary" 
                                onclick="editarGrupo(${grupo.id_grupo}, '${this.htmlEscape(grupo.nombre_grupo)}', ${grupo.boletos_asignados})">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <button class="btn btn-sm btn-info" 
                                onclick="compartirInvitacion('${this.htmlEscape(grupo.nombre_grupo)}', '${grupo.token_unico}')">
                            <i class="fas fa-share"></i>
                        </button>
                        
                        ${grupo.estado && grupo.estado !== 'pendiente' ?
                            `<button class="btn btn-sm btn-success" 
                                    onclick="verDetallesRespuesta(${grupo.id_grupo})">
                                <i class="fas fa-eye"></i>
                            </button>` : ''}
                        
                        <button class="btn btn-sm btn-danger" 
                                onclick="eliminarGrupo(${grupo.id_grupo}, '${this.htmlEscape(grupo.nombre_grupo)}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Función auxiliar para escapar HTML
    htmlEscape(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Copiar URL principal de invitación
    copiarURL() {
        const urlField = document.getElementById('invitacion-url');
        urlField.select();
        document.execCommand('copy');
        
        // Mostrar feedback
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Copiado!';
        btn.classList.replace('btn-primary', 'btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-success', 'btn-primary');
        }, 2000);
    }

    // Copiar token individual
    copiarToken(token) {
        navigator.clipboard.writeText(token).then(() => {
            // Mostrar feedback
            const btn = event.target.closest('button');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy"></i>';
                btn.classList.replace('btn-success', 'btn-outline-secondary');
            }, 2000);
        });
    }

    // Editar grupo de invitados
    editarGrupo(id, nombre, boletos) {
        document.getElementById('edit_id_grupo').value = id;
        document.getElementById('edit_nombre_grupo').value = nombre;
        document.getElementById('edit_boletos_asignados').value = boletos;
        
        const modal = new bootstrap.Modal(document.getElementById('modalEditarGrupo'));
        modal.show();
    }

    // Eliminar grupo con confirmación
    eliminarGrupo(id, nombre) {
        if (confirm(`¿Estás seguro de eliminar el grupo "${nombre}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="eliminar_grupo">
                <input type="hidden" name="id_grupo" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Compartir invitación
    compartirInvitacion(nombreGrupo, token) {
        // URL base sin token
        const baseUrl = window.dashboardConfig.invitacionUrl;
        
        // Llenar modal con información
        document.getElementById('grupo-nombre').textContent = nombreGrupo;
        document.getElementById('link-invitacion').value = baseUrl;
        document.getElementById('token-display').textContent = token;
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalCompartir'));
        modal.show();
    }

    // Copiar link de invitación sin token
    copiarLinkInvitacion() {
        const urlField = document.getElementById('link-invitacion');
        urlField.select();
        document.execCommand('copy');
        
        // Feedback
        const btn = event.target;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.replace('btn-outline-primary', 'btn-success');
        
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy"></i>';
            btn.classList.replace('btn-success', 'btn-outline-primary');
        }, 1500);
    }

    // Copiar token del modal
    copiarTokenDisplay() {
        const token = document.getElementById('token-display').textContent;
        navigator.clipboard.writeText(token).then(() => {
            const btn = event.target.closest('button');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy"></i>';
                btn.classList.replace('btn-success', 'btn-outline-secondary');
            }, 1500);
        });
    }

    // Compartir por WhatsApp
    compartirWhatsApp() {
        const nombreGrupo = document.getElementById('grupo-nombre').textContent;
        const url = document.getElementById('link-invitacion').value;
        const token = document.getElementById('token-display').textContent;
        const nombresNovios = this.htmlUnescape(window.dashboardConfig.nombresNovios);
        
        // Mensaje más compacto para evitar problemas de formato en WhatsApp
        const mensaje = `¡Estas invitado a nuestra boda! ${nombresNovios}

Confirma tu asistencia aqui: ${url}

Tu codigo de acceso es: ${token}

¡Esperamos verte en nuestro dia especial!`;

        const mensajeCodificado = encodeURIComponent(mensaje);
        window.open(`https://wa.me/?text=${mensajeCodificado}`, '_blank');
    }

    htmlUnescape(str) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = str;
        return textarea.value;
    }

    // Compartir por Telegram
    compartirTelegram() {
        const nombreGrupo = document.getElementById('grupo-nombre').textContent;
        const url = document.getElementById('link-invitacion').value;
        const token = document.getElementById('token-display').textContent;
        const nombresNovios = this.htmlUnescape(window.dashboardConfig.nombresNovios);
        
        const mensaje = `¡Estas invitado a nuestra boda! ${nombresNovios}

Confirma tu asistencia aqui: ${url}

Tu codigo de acceso es: ${token}

¡Esperamos verte en nuestro dia especial!`;

        const mensajeCodificado = encodeURIComponent(mensaje);
        window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${mensajeCodificado}`, '_blank');
    }

    // Copiar mensaje completo
    copiarMensajeCompleto() {
        const nombreGrupo = document.getElementById('grupo-nombre').textContent;
        const url = document.getElementById('link-invitacion').value;
        const token = document.getElementById('token-display').textContent;
        const nombresNovios = this.htmlUnescape(window.dashboardConfig.nombresNovios);
        
        const mensaje = `¡Estas invitado a nuestra boda! ${nombresNovios}

Confirma tu asistencia en: ${url}

Tu codigo de acceso es: ${token}

¡Esperamos verte en nuestro dia especial!`;

        navigator.clipboard.writeText(mensaje).then(() => {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2"></i>¡Copiado!';
            btn.classList.replace('btn-info', 'btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.replace('btn-success', 'btn-info');
            }, 2000);
        });
    }

    // Ver detalles de respuesta RSVP
    verDetallesRespuesta(id_grupo) {
        // Mostrar loading
        document.getElementById('detalles-respuesta-content').innerHTML = 
            '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando detalles...</div>';
        
        const modal = new bootstrap.Modal(document.getElementById('modalDetallesRespuesta'));
        modal.show();
        
        // Cargar detalles
        fetch(`./plantillas/plantilla-1/api/rsvp.php?action=cargar_respuesta&id_grupo=${id_grupo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.mostrarDetallesRespuesta(data.respuesta, data.nombres_invitados);
            } else {
                document.getElementById('detalles-respuesta-content').innerHTML = 
                    `<div class="alert alert-danger">Error: ${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detalles-respuesta-content').innerHTML = 
                '<div class="alert alert-danger">Error al cargar los detalles</div>';
        });
    }

    // Mostrar detalles formateados de la respuesta
    mostrarDetallesRespuesta(respuesta, nombresInvitados) {
        const estadoTexto = {
            'aceptado': 'Confirmados',
            'rechazado': 'No asistirán',
            'pendiente': 'Sin respuesta'
        };

        let html = `
            <div class="card border-0">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-users me-2 text-primary"></i>
                        ${respuesta.nombre_grupo}
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="info-item mb-2">
                                <strong>Estado:</strong>
                                <span class="status-badge status-${respuesta.estado} ms-2">
                                    ${estadoTexto[respuesta.estado] || 'Sin respuesta'}
                                </span>
                            </div>
                            <div class="info-item mb-2">
                                <strong>Boletos asignados:</strong> ${respuesta.boletos_asignados}
                            </div>
                            ${respuesta.estado === 'aceptado' ? 
                                `<div class="info-item">
                                    <strong>Boletos confirmados:</strong> 
                                    <span class="badge bg-success">${respuesta.boletos_confirmados}</span>
                                </div>` : ''}
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Fecha de respuesta:</strong><br>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    ${this.formatDate(respuesta.fecha_respuesta)}
                                </small>
                            </div>
                        </div>
                    </div>
        `;
        
        // Mostrar nombres de invitados confirmados
        if (nombresInvitados && nombresInvitados.length > 0) {
            html += `
                <hr>
                <div class="mb-3">
                    <strong>Invitados confirmados:</strong>
                    <ul class="list-unstyled mt-2">`;
            
            nombresInvitados.forEach(nombre => {
                html += `
                    <li class="mb-1">
                        <i class="fas fa-user me-2 text-primary"></i>
                        ${nombre}
                    </li>`;
            });
            
            html += `</ul></div>`;
        }
        
        // Mostrar comentarios si existen
        if (respuesta.comentarios && respuesta.comentarios.trim()) {
            html += `
                <hr>
                <div class="mb-3">
                    <strong>Comentarios:</strong>
                    <div class="alert alert-light mt-2">
                        <i class="fas fa-quote-left me-2"></i>
                        ${respuesta.comentarios}
                    </div>
                </div>`;
        }
        
        html += `</div></div>`;
        
        document.getElementById('detalles-respuesta-content').innerHTML = html;
    }
}

// Funciones globales para compatibilidad con el HTML existente
let dashboardManager;

document.addEventListener('DOMContentLoaded', function() {
    dashboardManager = new DashboardManager();

    // Obtener grupos iniciales del DOM si no hay datos AJAX
    dashboardManager.allGroups = dashboardManager.getGroupsFromDOM();
    dashboardManager.filterGroups();

    // Inicializar filtros después de cargar los datos
    if (typeof grupos !== 'undefined' && grupos.length > 0) {
        dashboardManager.allGroups = grupos;
        dashboardManager.filterGroups();
    }
    
    
    // Exponer funciones globalmente para el HTML
    window.copiarURL = () => dashboardManager.copiarURL();
    window.copiarToken = (token) => dashboardManager.copiarToken(token);
    window.editarGrupo = (id, nombre, boletos) => dashboardManager.editarGrupo(id, nombre, boletos);
    window.eliminarGrupo = (id, nombre) => dashboardManager.eliminarGrupo(id, nombre);
    window.compartirInvitacion = (nombre, token) => dashboardManager.compartirInvitacion(nombre, token);
    window.verDetallesRespuesta = (id) => dashboardManager.verDetallesRespuesta(id);
    window.copiarLinkInvitacion = () => dashboardManager.copiarLinkInvitacion();
    window.copiarTokenDisplay = () => dashboardManager.copiarTokenDisplay();
    window.compartirWhatsApp = () => dashboardManager.compartirWhatsApp();
    window.compartirTelegram = () => dashboardManager.compartirTelegram();
    window.copiarMensajeCompleto = () => dashboardManager.copiarMensajeCompleto();
    // Funciones globales para los eventos del HTML
    window.toggleFilters = () => dashboardManager.toggleFilters();
    window.applyFilters = () => dashboardManager.applyFilters();
    window.resetFilters = () => dashboardManager.resetFilters();
    window.removeFilter = (type) => dashboardManager.removeFilter(type);
    
    // Nueva función global para actualizar
    window.actualizarDatos = () => dashboardManager.actualizarDatos();
});