// admin-panel.js - JavaScript modularizado para el panel de administración

class AdminPanel {
    constructor() {
        this.searchInput = document.getElementById('searchInput');
        this.estadoFilter = document.getElementById('estadoFilter');
        this.fechaFilter = document.getElementById('fechaFilter');
        this.invitacionCards = document.querySelectorAll('.card');
        this.allInvitations = Array.from(this.invitacionCards).map(card => 
            card.closest('.col-xl-3, .col-lg-4, .col-md-6')
        ).filter(col => col !== null);
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.updateCounters();
        this.setupAutoCloseAlerts();
        
        console.log('Panel de administración inicializado:', {
            invitaciones: this.allInvitations.length,
            filtros: {
                search: !!this.searchInput,
                estado: !!this.estadoFilter,
                fecha: !!this.fechaFilter
            }
        });
    }

    bindEvents() {
        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => this.filterInvitations());
        }
        
        if (this.estadoFilter) {
            this.estadoFilter.addEventListener('change', () => this.filterInvitations());
        }
        
        if (this.fechaFilter) {
            this.fechaFilter.addEventListener('change', () => this.sortInvitations());
        }
    }

    updateCounters() {
        const visibleCards = this.allInvitations.filter(card => 
            card && card.style.display !== 'none' && !card.hasAttribute('data-hidden')
        );
        
        const proximasCards = visibleCards.filter(card => {
            const badge = card.querySelector('.badge');
            return badge && badge.textContent.trim().toLowerCase().includes('próxima');
        });
        
        const totalCountEl = document.getElementById('totalCount');
        const proximasCountEl = document.getElementById('proximasCount');
        
        if (totalCountEl) totalCountEl.textContent = visibleCards.length;
        if (proximasCountEl) proximasCountEl.textContent = proximasCards.length;
    }

    filterInvitations() {
        const searchTerm = this.searchInput ? this.searchInput.value.toLowerCase() : '';
        const selectedEstado = this.estadoFilter ? this.estadoFilter.value : '';
        
        this.allInvitations.forEach(cardContainer => {
            if (!cardContainer) return;
            
            const card = cardContainer.querySelector('.card');
            if (!card) return;
            
            const titleElement = card.querySelector('.card-title, h5');
            const title = titleElement ? titleElement.textContent.toLowerCase() : '';
            
            const badge = card.querySelector('.badge');
            const estado = badge ? badge.textContent.trim().toLowerCase() : '';
            
            const slugElement = card.querySelector('code');
            const slug = slugElement ? slugElement.textContent.toLowerCase() : '';
            
            let shouldShow = true;
            
            // Filtro de búsqueda
            if (searchTerm && !title.includes(searchTerm) && !slug.includes(searchTerm)) {
                shouldShow = false;
            }
            
            // Filtro de estado
            if (selectedEstado) {
                const estadoMap = {
                    'proxima': ['próxima', 'proxima'],
                    'activa': ['activa'],
                    'finalizada': ['finalizada']
                };
                
                const estadosValidos = estadoMap[selectedEstado] || [];
                const estadoCoincide = estadosValidos.some(est => estado.includes(est));
                
                if (!estadoCoincide) {
                    shouldShow = false;
                }
            }
            
            // Aplicar visibilidad
            if (shouldShow) {
                cardContainer.style.display = 'block';
                cardContainer.style.opacity = '1';
                cardContainer.removeAttribute('data-hidden');
            } else {
                cardContainer.style.display = 'none';
                cardContainer.style.opacity = '0';
                cardContainer.setAttribute('data-hidden', 'true');
            }
        });
        
        this.updateCounters();
        this.showNoResultsMessage();
    }

    sortInvitations() {
        const sortBy = this.fechaFilter ? this.fechaFilter.value : '';
        if (!sortBy) return;
        
        const container = document.querySelector('.row.g-4');
        if (!container) return;
        
        const sortedCards = [...this.allInvitations].sort((a, b) => {
            const getEventDate = (cardContainer) => {
                const dateElement = cardContainer.querySelector('.bi-calendar-event');
                if (!dateElement || !dateElement.parentElement) return new Date(0);
                
                const dateText = dateElement.parentElement.textContent;
                const match = dateText.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
                return match ? new Date(match[3], match[2] - 1, match[1]) : new Date(0);
            };
            
            if (sortBy.includes('evento')) {
                const dateA = getEventDate(a);
                const dateB = getEventDate(b);
                return sortBy.includes('asc') ? dateA - dateB : dateB - dateA;
            }
            
            return 0;
        });
        
        // Reordenar elementos en el DOM
        sortedCards.forEach(card => {
            if (card && container.contains(card)) {
                container.appendChild(card);
            }
        });
        
        this.allInvitations = sortedCards.filter(card => card !== null);
    }

    showNoResultsMessage() {
        const visibleCards = this.allInvitations.filter(card => 
            card && card.style.display !== 'none' && !card.hasAttribute('data-hidden')
        );
        
        const container = document.querySelector('.row.g-4');
        if (!container) return;
        
        const existingMessage = document.getElementById('no-results-message');
        
        if (visibleCards.length === 0 && !existingMessage && this.allInvitations.length > 0) {
            const message = document.createElement('div');
            message.id = 'no-results-message';
            message.className = 'col-12 text-center py-5';
            message.innerHTML = `
                <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No se encontraron invitaciones</h5>
                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                <button class="btn btn-outline-primary" onclick="adminPanel.clearFilters()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Limpiar filtros
                </button>
            `;
            container.appendChild(message);
        } else if (visibleCards.length > 0 && existingMessage) {
            existingMessage.remove();
        }
    }

    clearFilters() {
        if (this.searchInput) this.searchInput.value = '';
        if (this.estadoFilter) this.estadoFilter.value = '';
        if (this.fechaFilter) this.fechaFilter.value = '';
        
        this.allInvitations.forEach(card => {
            if (card) {
                card.style.display = 'block';
                card.style.opacity = '1';
                card.removeAttribute('data-hidden');
            }
        });
        
        this.updateCounters();
        
        const existingMessage = document.getElementById('no-results-message');
        if (existingMessage) {
            existingMessage.remove();
        }
    }

    setupAutoCloseAlerts() {
        // Auto-ocultar alertas después de 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
            alerts.forEach((alert) => {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch (e) {
                    // Si falla, ocultar manualmente
                    alert.style.display = 'none';
                }
            });
        }, 5000);
    }
}

// Módulo para manejar confirmaciones de eliminación
class DeleteHandler {
    constructor() {
        this.preventBootstrapModals();
        this.bindDeleteEvents();
    }

    preventBootstrapModals() {
        // Prevenir que los modales de Bootstrap se abran
        document.addEventListener('show.bs.modal', (e) => {
            if (e.target.id && e.target.id.startsWith('deleteModal')) {
                e.preventDefault();
                e.stopPropagation();
            }
        }, true);
    }

    bindDeleteEvents() {
        // Interceptar clics en botones de eliminar
        document.addEventListener('click', (e) => {
            const deleteButton = e.target.closest('.btn-outline-danger');
            
            if (deleteButton && deleteButton.hasAttribute('data-bs-target')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const modalId = deleteButton.getAttribute('data-bs-target');
                const modal = document.querySelector(modalId);
                
                if (modal) {
                    const nombreElement = modal.querySelector('.modal-body strong');
                    const nombre = nombreElement ? nombreElement.textContent : 'esta invitación';
                    const form = modal.querySelector('form');
                    
                    this.showDeleteConfirmation(nombre, form);
                }
                
                return false;
            }
        }, true); // true para captura en fase de captura
    }

    showDeleteConfirmation(nombre, form) {
        Swal.fire({
            title: '¿Estás seguro?',
            html: `¿Quieres eliminar la invitación de <strong>${nombre}</strong>?<br><br>
                   <div class="alert alert-warning mb-0 mt-3" style="font-size: 0.9rem;">
                       <i class="bi bi-exclamation-triangle me-1"></i>
                       Esta acción eliminará también todos los archivos asociados.
                   </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash me-1"></i> Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            buttonsStyling: true
        }).then((result) => {
            if (result.isConfirmed) {
                if (form) {
                    form.submit();
                }
            }
        });
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.adminPanel = new AdminPanel();
    new DeleteHandler();
});