// dashboard.js - Funcionalidades del Dashboard de Invitaciones (ACTUALIZADO)

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
        this.inicializarToasts();
    }

    // Inicializar eventos
    initializeEvents() {
        // Auto-focus en modales
        document.addEventListener('shown.bs.modal', function (e) {
            const firstInput = e.target.querySelector('input[type="text"], input[type="number"]');
            if (firstInput) firstInput.focus();
        });
    }

    // Inicializar sistema de toasts
    inicializarToasts() {
        // Agregar estilos CSS para toasts si no existen
        if (!document.getElementById('toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
                
                .toast-notification {
                    animation: slideInRight 0.3s ease;
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Sistema de Toasts mejorado
    mostrarToast(mensaje, tipo = 'success') {
        let toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
            `;
            document.body.appendChild(toastContainer);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${tipo}`;
        
        const iconos = {
            'success': '<i class="fas fa-check-circle"></i>',
            'error': '<i class="fas fa-exclamation-circle"></i>',
            'info': '<i class="fas fa-info-circle"></i>',
            'warning': '<i class="fas fa-exclamation-triangle"></i>'
        };
        
        toast.innerHTML = `
            ${iconos[tipo] || iconos.success}
            <span>${mensaje}</span>
        `;
        
        toast.style.cssText = `
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            color: white;
            font-size: 14px;
            font-weight: 500;
            min-width: 250px;
            max-width: 400px;
        `;
        
        const colores = {
            'success': 'background: #28a745;',
            'error': 'background: #dc3545;',
            'info': 'background: #17a2b8;',
            'warning': 'background: #ffc107; color: #333;'
        };
        
        toast.style.cssText += colores[tipo] || colores.success;
        
        toastContainer.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                toastContainer.removeChild(toast);
            }, 300);
        }, 3000);
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
        this.currentFilters.search = document.getElementById('search-input').value.toLowerCase();
        this.currentFilters.status = document.getElementById('status-filter').value;
        this.currentFilters.sortBy = document.getElementById('sort-by').value;
        this.currentFilters.itemsPerPage = document.getElementById('items-per-page').value;
        this.currentFilters.currentPage = 1;
        
        this.filterGroups();
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
            this.allGroups = this.getGroupsFromDOM();
        }
        
        this.filteredGroups = this.allGroups.filter(group => {
            const matchesSearch = this.currentFilters.search === '' || 
                                group.nombre_grupo.toLowerCase().includes(this.currentFilters.search) ||
                                (group.nombres_acompanantes && group.nombres_acompanantes.toLowerCase().includes(this.currentFilters.search));
            
            const matchesStatus = this.currentFilters.status === 'all' || 
                                group.estado === this.currentFilters.status;
            
            return matchesSearch && matchesStatus;
        });
        
        this.sortGroups();
        
        if (this.currentFilters.itemsPerPage !== 'all') {
            const itemsPerPage = parseInt(this.currentFilters.itemsPerPage);
            const startIndex = (this.currentFilters.currentPage - 1) * itemsPerPage;
            this.filteredGroups = this.filteredGroups.slice(startIndex, startIndex + itemsPerPage);
        }
        
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

    // Parsear fechas desde el formato mostrado en el DOM
    parseDateFromDOM(dateText) {
        if (!dateText || dateText === 'Sin respuesta' || dateText === '') {
            return null;
        }
        
        try {
            const parts = dateText.split(' ');
            if (parts.length === 2) {
                const dateParts = parts[0].split('/');
                const timeParts = parts[1].split(':');
                
                if (dateParts.length === 3 && timeParts.length === 2) {
                    return `${dateParts[2]}-${dateParts[1]}-${dateParts[0]} ${timeParts[0]}:${timeParts[1]}:00`;
                }
            }
            return dateText;
        } catch (error) {
            console.error('Error parseando fecha del DOM:', error);
            return dateText;
        }
    }

    // Obtener grupos desde el DOM
    getGroupsFromDOM() {
        const grupos = [];
        
        const rows = document.querySelectorAll('table tbody tr');
        if (rows.length > 0) {
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 6) {
                    const nombreElement = cells[0].querySelector('strong');
                    const acompanantesElement = cells[0].querySelector('small');
                    const boletosElement = cells[1].querySelector('.badge');
                    const confirmadosElement = cells[1].querySelector('small');
                    const estadoElement = cells[2].querySelector('.status-badge');
                    const tokenElement = cells[3].querySelector('code');
                    const fechaElement = cells[4].querySelector('small:not(.text-info)');
                    
                    const editBtn = cells[5].querySelector('button[onclick*="editarGrupo"]');
                    let id_grupo = null;
                    if (editBtn) {
                        const onclickAttr = editBtn.getAttribute('onclick');
                        const idMatch = onclickAttr.match(/editarGrupo\((\d+),/);
                        id_grupo = idMatch ? parseInt(idMatch[1]) : null;
                    }
                    
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
                        fecha_creacion: new Date().toISOString(),
                        comentarios: cells[4].querySelector('.text-info') ? 'Sí' : null
                    });
                }
            });
            return grupos;
        }
        
        const mobileCards = document.querySelectorAll('.mobile-guest-card');
        if (mobileCards.length > 0) {
            mobileCards.forEach(card => {
                const nombreElement = card.querySelector('.guest-name');
                const estadoElement = card.querySelector('.status-badge');
                const boletosElement = card.querySelector('.detail-item');
                const tokenElement = card.querySelector('code');
                
                const editBtn = card.querySelector('button[onclick*="editarGrupo"]');
                let id_grupo = null;
                if (editBtn) {
                    const onclickAttr = editBtn.getAttribute('onclick');
                    const idMatch = onclickAttr.match(/editarGrupo\((\d+),/);
                    id_grupo = idMatch ? parseInt(idMatch[1]) : null;
                }
                
                let estado = 'pendiente';
                if (estadoElement) {
                    if (estadoElement.classList.contains('status-aceptado')) estado = 'aceptado';
                    else if (estadoElement.classList.contains('status-rechazado')) estado = 'rechazado';
                }
                
                let boletos = 0;
                if (boletosElement) {
                    const boletosMatch = boletosElement.textContent.match(/(\d+)\s+boletos/);
                    boletos = boletosMatch ? parseInt(boletosMatch[1]) : 0;
                }
                
                grupos.push({
                    id_grupo: id_grupo,
                    nombre_grupo: nombreElement ? nombreElement.textContent.trim() : '',
                    nombres_acompanantes: '',
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
        if (!container) return;
        
        container.innerHTML = '';
        
        if (this.currentFilters.search) {
            container.innerHTML += `
                <span class="filter-badge">
                    Búsqueda: "${this.currentFilters.search}"
                    <span class="close" onclick="removeFilter('search')">&times;</span>
                </span>
            `;
        }
        
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

    // Actualizar tabla de grupos
    actualizarTablaGrupos(grupos) {
        if (grupos.length === 0) {
            const noResultsHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; border: none; padding: 2rem;">
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <h5>No se encontraron grupos</h5>
                            <p>Intenta ajustar tus filtros de búsqueda o crea un nuevo grupo</p>
                            <div class="no-results-actions">
                                <button class="btn btn-secondary" onclick="resetFilters()">
                                    Restablecer filtros
                                </button>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearGrupo">
                                    Crear Nuevo Grupo
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
            
            const tableBody = document.querySelector('table tbody');
            if (tableBody) {
                tableBody.innerHTML = noResultsHTML;
            }

            const noResultsMobileHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h5>No se encontraron grupos</h5>
                    <p>Intenta ajustar tus filtros de búsqueda o crea un nuevo grupo</p>
                    <div class="no-results-actions">
                        <button class="btn btn-secondary" onclick="resetFilters()">
                            Restablecer filtros
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearGrupo">
                            Crear Nuevo Grupo
                        </button>
                    </div>
                </div>
            `;
            
            const mobileContainer = document.querySelector('.mobile-guests');
            if (mobileContainer) {
                mobileContainer.innerHTML = noResultsMobileHTML;
            }
            
            return;
        }
        
        const tableBody = document.querySelector('table tbody');
        if (tableBody) {
            tableBody.innerHTML = this.generarFilasTabla(grupos);
        }

        const mobileContainer = document.querySelector('.mobile-guests');
        if (mobileContainer) {
            mobileContainer.innerHTML = this.generarTarjetasMobile(grupos);
        }
    }

    // ========================================
    // ACTUALIZAR DATOS (BOTÓN ACTUALIZAR) - CORREGIDO
    // ========================================
    async actualizarDatos() {
        const btnActualizar = document.getElementById('btn-actualizar');
        const iconoActualizar = document.getElementById('icono-actualizar');
        
        if (!btnActualizar || !iconoActualizar) {
            console.error('No se encontró el botón actualizar o su icono');
            return;
        }
        
        btnActualizar.disabled = true;
        iconoActualizar.classList.add('fa-spin');
        
        try {
            if (typeof cargarTodosLosGrupos === 'function') {
                await cargarTodosLosGrupos();
                this.mostrarToast('Datos actualizados correctamente', 'success');
            } else {
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
                    throw new Error(`Error HTTP: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    this.allGroups = data.grupos || [];
                    
                    if (data.stats) {
                        this.actualizarEstadisticas(data.stats);
                    }
                    
                    this.filterGroups();
                    this.mostrarToast('Datos actualizados correctamente', 'success');
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            }
        } catch (error) {
            console.error('Error actualizando datos:', error);
            this.mostrarToast('Error al actualizar los datos', 'error');
        } finally {
            setTimeout(() => {
                btnActualizar.disabled = false;
                iconoActualizar.classList.remove('fa-spin');
            }, 500);
        }
    }
    
    // Actualizar estadísticas con animación
    actualizarEstadisticas(newStats) {
        const statsElements = {
            'stat-total': newStats.total_boletos || 0,
            'stat-confirmados': newStats.confirmados || 0,
            'stat-rechazados': newStats.rechazados || 0,
            'stat-pendientes': newStats.pendientes || 0
        };

        Object.keys(statsElements).forEach(id => {
            const element = document.getElementById(id);
            if (element && element.textContent != statsElements[id]) {
                element.classList.add('updated');
                element.textContent = statsElements[id];
                
                setTimeout(() => {
                    element.classList.remove('updated');
                }, 600);
            }
        });
    }

    // Función para formatear fechas de manera segura
    formatDate(dateString) {
        if (!dateString || dateString === '0000-00-00 00:00:00' || dateString === 'null') {
            return 'Sin respuesta';
        }
        
        try {
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
                        de class="text-dark">${this.htmlEscape(tokenUnico)}</code>
                        <button class="btn btn-sm btn-outline-secondary ms-1" 
                                onclick="copiarToken('${this.htmlEscape(tokenUnico)}')">
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
                                onclick="compartirInvitacion('${this.htmlEscape(nombreGrupo)}', '${this.htmlEscape(tokenUnico)}')">
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
                            Token: <code class="text-dark">${this.htmlEscape(grupo.token_unico)}</code>
                            <button class="btn btn-sm btn-outline-secondary ms-1" 
                                    onclick="copiarToken('${this.htmlEscape(grupo.token_unico)}')">
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
                        <button class="btn btn-sm btn-secondary btn-action" 
                                onclick="editarGrupo(${grupo.id_grupo}, '${this.htmlEscape(grupo.nombre_grupo)}', ${grupo.boletos_asignados})">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <button class="btn btn-sm btn-info btn-action" 
                                onclick="compartirInvitacion('${this.htmlEscape(grupo.nombre_grupo)}', '${this.htmlEscape(grupo.token_unico)}')">
                            <i class="fas fa-share"></i>
                        </button>
                        
                        ${grupo.estado && grupo.estado !== 'pendiente' ?
                            `<button class="btn btn-sm btn-success btn-action" 
                                    onclick="verDetallesRespuesta(${grupo.id_grupo})">
                                <i class="fas fa-eye"></i>
                            </button>` : ''}
                        
                        <button class="btn btn-sm btn-danger btn-action" 
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

    htmlUnescape(str) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = str;
        return textarea.value;
    }

    // ========================================
    // COPIAR URL PRINCIPAL - CORREGIDO
    // ========================================
    copiarURL() {
        const urlField = document.getElementById('invitacion-url');
        if (!urlField) return;
        
        urlField.select();
        
        try {
            document.execCommand('copy');
            this.mostrarToast('URL copiada al portapapeles', 'success');
        } catch (err) {
            console.error('Error al copiar:', err);
            this.mostrarToast('Error al copiar la URL', 'error');
        }
    }

    // ========================================
    // COPIAR TOKEN - CORREGIDO
    // ========================================
    copiarToken(token) {
        const tempInput = document.createElement('input');
        tempInput.value = token;
        document.body.appendChild(tempInput);
        tempInput.select();
        
        try {
            document.execCommand('copy');
            this.mostrarToast('Token copiado al portapapeles', 'success');
        } catch (err) {
            console.error('Error al copiar:', err);
            this.mostrarToast('Error al copiar el token', 'error');
        }
        
        document.body.removeChild(tempInput);
    }

    // Editar grupo de invitados
    editarGrupo(id, nombre, boletos) {
        document.getElementById('edit_id_grupo').value = id;
        document.getElementById('edit_nombre_grupo').value = nombre;
        document.getElementById('edit_boletos_asignados').value = boletos;
        
        const modal = new bootstrap.Modal(document.getElementById('modalEditarGrupo'));
        modal.show();
    }

    // ========================================
    // ELIMINAR GRUPO CON SWEETALERT2 - NUEVO
    // ========================================
    eliminarGrupo(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            html: `Se eliminará el grupo <strong>"${nombre}"</strong> y todas sus respuestas.<br><br>Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-2"></i>Sí, eliminar',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Cancelar',
            reverseButtons: true,
            focusCancel: true,
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="eliminar_grupo">
                    <input type="hidden" name="id_grupo" value="${id}">
                `;
                document.body.appendChild(form);
                
                Swal.fire({
                    title: 'Eliminando...',
                    html: 'Por favor espera',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                form.submit();
            }
        });
    }

    // ========================================
    // COMPARTIR INVITACIÓN - CORREGIDO
    // ========================================
    compartirInvitacion(nombreGrupo, token) {
        const modal = new bootstrap.Modal(document.getElementById('modalCompartir'));
        
        // Mostrar nombre del grupo
        const grupoNombreElement = document.getElementById('grupo-nombre');
        if (grupoNombreElement) {
            grupoNombreElement.textContent = nombreGrupo;
        }
        
        // CORRECCIÓN: Usar la URL pública de la invitación (sin token)
        const linkInvitacion = window.dashboardConfig.invitacionUrl;
        
        // Actualizar campo del link
        const linkInput = document.getElementById('link-invitacion');
        if (linkInput) {
            linkInput.value = linkInvitacion;
        }
        
        // Mostrar token
        const tokenDisplay = document.getElementById('token-display');
        if (tokenDisplay) {
            tokenDisplay.textContent = token;
        }
        
        // Guardar datos para compartir
        window.compartirData = {
            nombreGrupo: nombreGrupo,
            token: token,
            linkInvitacion: linkInvitacion
        };
        
        modal.show();
    }

    // Copiar link de invitación
    copiarLinkInvitacion() {
        const input = document.getElementById('link-invitacion');
        if (!input) return;
        
        input.select();
        
        try {
            document.execCommand('copy');
            this.mostrarToast('Link copiado al portapapeles', 'success');
        } catch (err) {
            console.error('Error al copiar:', err);
            this.mostrarToast('Error al copiar el link', 'error');
        }
    }

    // Copiar token desde modal
    copiarTokenDisplay() {
        const tokenElement = document.getElementById('token-display');
        if (!tokenElement) return;
        
        const token = tokenElement.textContent;
        
        const tempInput = document.createElement('input');
        tempInput.value = token;
        document.body.appendChild(tempInput);
        tempInput.select();
        
        try {
            document.execCommand('copy');
            this.mostrarToast('Token copiado al portapapeles', 'success');
        } catch (err) {
            console.error('Error al copiar:', err);
            this.mostrarToast('Error al copiar el token', 'error');
        }
        
        document.body.removeChild(tempInput);
    }

    // Compartir por WhatsApp
    compartirWhatsApp() {
        if (!window.compartirData) return;
        
        // ✅ Detectar tipo de evento
        const tipoEvento = window.dashboardConfig.tipoEvento || 'boda';
        
        let mensaje;
        if (tipoEvento === 'xv') {
            mensaje = `¡Estás invitado(a) a mis XV años! 🎉✨\n\nConfirma tu asistencia aquí:\n${window.compartirData.linkInvitacion}\n\nTu código de acceso: ${window.compartirData.token}`;
        } else {
            mensaje = `¡Estás invitado(a) a nuestra boda! 💍💕\n\nConfirma tu asistencia aquí:\n${window.compartirData.linkInvitacion}\n\nTu código de acceso: ${window.compartirData.token}`;
        }
        
        const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(mensaje)}`;
        window.open(whatsappUrl, '_blank');
    }

    // Compartir por Telegram
    compartirTelegram() {
        if (!window.compartirData) return;
        
        // ✅ Detectar tipo de evento
        const tipoEvento = window.dashboardConfig.tipoEvento || 'boda';
        
        let mensaje;
        if (tipoEvento === 'xv') {
            mensaje = `¡Estás invitado(a) a mis XV años! 🎉✨\n\nConfirma tu asistencia aquí:\n${window.compartirData.linkInvitacion}\n\nTu código de acceso: ${window.compartirData.token}`;
        } else {
            mensaje = `¡Estás invitado(a) a nuestra boda! 💍💕\n\nConfirma tu asistencia aquí:\n${window.compartirData.linkInvitacion}\n\nTu código de acceso: ${window.compartirData.token}`;
        }
        
        const telegramUrl = `https://t.me/share/url?url=${encodeURIComponent(window.compartirData.linkInvitacion)}&text=${encodeURIComponent(mensaje)}`;
        window.open(telegramUrl, '_blank');
    }

    // Copiar mensaje completo
    copiarMensajeCompleto() {
        if (!window.compartirData) return;
        
        // ✅ Detectar tipo de evento
        const tipoEvento = window.dashboardConfig.tipoEvento || 'boda';
        
        let mensaje;
        if (tipoEvento === 'xv') {
            mensaje = `¡Estás invitado(a) a mis XV años! 🎉✨\n\nConfirma tu asistencia aquí:\n${window.compartirData.linkInvitacion}\n\nTu código de acceso: ${window.compartirData.token}`;
        } else {
            mensaje = `¡Estás invitado(a) a nuestra boda! 💍💕\n\nConfirma tu asistencia aquí:\n${window.compartirData.linkInvitacion}\n\nTu código de acceso: ${window.compartirData.token}`;
        }
        
        const tempTextarea = document.createElement('textarea');
        tempTextarea.value = mensaje;
        document.body.appendChild(tempTextarea);
        tempTextarea.select();
        
        try {
            document.execCommand('copy');
            this.mostrarToast('Mensaje copiado al portapapeles', 'success');
        } catch (err) {
            console.error('Error al copiar', err);
            this.mostrarToast('Error al copiar el mensaje', 'error');
        }
        
        document.body.removeChild(tempTextarea);
    }


    // Ver detalles de respuesta RSVP
    verDetallesRespuesta(id_grupo) {
        document.getElementById('detalles-respuesta-content').innerHTML = 
            '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-3 text-muted">Cargando detalles...</p></div>';
        
        const modal = new bootstrap.Modal(document.getElementById('modalDetallesRespuesta'));
        modal.show();
        
        fetch(`./plantillas/plantilla-1/api/rsvp.php?action=cargar_respuesta&id_grupo=${id_grupo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.mostrarDetallesRespuesta(data.respuesta, data.nombres_invitados);
            } else {
                document.getElementById('detalles-respuesta-content').innerHTML = 
                    `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error: ${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detalles-respuesta-content').innerHTML = 
                '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error al cargar los detalles. Por favor, intenta nuevamente.</div>';
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
                        ${this.htmlEscape(respuesta.nombre_grupo)}
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
        
        if (nombresInvitados && nombresInvitados.length > 0) {
            html += `
                <hr>
                <div class="mb-3">
                    <strong><i class="fas fa-user-check me-2 text-success"></i>Invitados confirmados:</strong>
                    <ul class="list-unstyled mt-2">`;
            
            nombresInvitados.forEach(nombre => {
                html += `
                    <li class="mb-1">
                        <i class="fas fa-user me-2 text-primary"></i>
                        ${this.htmlEscape(nombre)}
                    </li>`;
            });
            
            html += `</ul></div>`;
        }
        
        if (respuesta.comentarios && respuesta.comentarios.trim()) {
            html += `
                <hr>
                <div class="mb-3">
                    <strong><i class="fas fa-comment-dots me-2 text-info"></i>Comentarios:</strong>
                    <div class="alert alert-light mt-2 mb-0">
                        <i class="fas fa-quote-left me-2 text-muted"></i>
                        ${this.htmlEscape(respuesta.comentarios)}
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

    dashboardManager.allGroups = dashboardManager.getGroupsFromDOM();
    dashboardManager.filterGroups();

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
    
    window.toggleFilters = () => dashboardManager.toggleFilters();
    window.applyFilters = () => dashboardManager.applyFilters();
    window.resetFilters = () => dashboardManager.resetFilters();
    window.removeFilter = (type) => dashboardManager.removeFilter(type);
    window.actualizarDatos = () => dashboardManager.actualizarDatos();
});
