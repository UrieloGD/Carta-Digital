// ============================================
// ADMIN PANEL - JavaScript Completo
// Panel de administración de invitaciones
// ============================================

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
        this.applyInitialStyles();
        
        console.log('Panel de administración inicializado:', {
            invitaciones: this.allInvitations.length,
            filtros: {
                search: !!this.searchInput,
                estado: !!this.estadoFilter,
                fecha: !!this.fechaFilter
            }
        });
    }

    applyInitialStyles() {
        // Aplicar estilos de transición a todas las cards
        this.allInvitations.forEach(card => {
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
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
        
        const activasCards = visibleCards.filter(card => {
            const badge = card.querySelector('.badge');
            return badge && badge.textContent.trim().toLowerCase().includes('activa');
        });
        
        const finalizadasCards = visibleCards.filter(card => {
            const badge = card.querySelector('.badge');
            return badge && badge.textContent.trim().toLowerCase().includes('finalizada');
        });
        
        const proximasCountEl = document.getElementById('proximasCount');
        const activasCountEl = document.getElementById('activasCount');
        const finalizadasCountEl = document.getElementById('finalizadasCount');
        
        if (proximasCountEl) proximasCountEl.textContent = proximasCards.length;
        if (activasCountEl) activasCountEl.textContent = activasCards.length;
        if (finalizadasCountEl) finalizadasCountEl.textContent = finalizadasCards.length;
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
            
            // Aplicar visibilidad con transición suave
            if (shouldShow) {
                cardContainer.style.display = 'block';
                setTimeout(() => {
                    cardContainer.style.opacity = '1';
                    cardContainer.style.transform = 'translateY(0)';
                }, 10);
                cardContainer.removeAttribute('data-hidden');
            } else {
                cardContainer.style.opacity = '0';
                cardContainer.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    cardContainer.style.display = 'none';
                }, 300);
                cardContainer.setAttribute('data-hidden', 'true');
            }
        });
        
        setTimeout(() => {
            this.updateCounters();
            this.showNoResultsMessage();
        }, 320);
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
            
            const getEstado = (cardContainer) => {
                const badge = cardContainer.querySelector('.badge');
                if (!badge) return 'activa';
                const texto = badge.textContent.trim().toLowerCase();
                if (texto.includes('próxima')) return 'proxima';
                if (texto.includes('finalizada')) return 'finalizada';
                return 'activa';
            };
            
            if (sortBy.includes('evento')) {
                // Primero ordenar por estado (próximas, activas, finalizadas)
                const estadoA = getEstado(a);
                const estadoB = getEstado(b);
                const ordenEstado = { proxima: 0, activa: 1, finalizada: 2 };
                
                if (ordenEstado[estadoA] !== ordenEstado[estadoB]) {
                    return ordenEstado[estadoA] - ordenEstado[estadoB];
                }
                
                // Luego por fecha
                const dateA = getEventDate(a);
                const dateB = getEventDate(b);
                return sortBy.includes('asc') ? dateA - dateB : dateB - dateA;
            }
            
            return 0;
        });
        
        // Reordenar elementos en el DOM con animación
        sortedCards.forEach((card, index) => {
            if (card && container.contains(card)) {
                card.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                card.style.transform = 'translateY(-10px)';
                card.style.opacity = '0';
                
                setTimeout(() => {
                    container.appendChild(card);
                    setTimeout(() => {
                        card.style.transform = 'translateY(0)';
                        card.style.opacity = '1';
                    }, 50);
                }, index * 30);
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
                <div style="opacity: 0; transition: opacity 0.3s ease;" class="fade-in-message">
                    <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">No se encontraron invitaciones</h5>
                    <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                    <button class="btn btn-outline-primary" onclick="adminPanel.clearFilters()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Limpiar filtros
                    </button>
                </div>
            `;
            container.appendChild(message);
            
            // Trigger fade-in animation
            setTimeout(() => {
                const fadeInEl = message.querySelector('.fade-in-message');
                if (fadeInEl) fadeInEl.style.opacity = '1';
            }, 100);
        } else if (visibleCards.length > 0 && existingMessage) {
            const fadeInEl = existingMessage.querySelector('.fade-in-message');
            if (fadeInEl) {
                fadeInEl.style.opacity = '0';
                setTimeout(() => existingMessage.remove(), 300);
            } else {
                existingMessage.remove();
            }
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
                card.style.transform = 'translateY(0)';
                card.removeAttribute('data-hidden');
            }
        });
        
        this.updateCounters();
        
        const existingMessage = document.getElementById('no-results-message');
        if (existingMessage) {
            const fadeInEl = existingMessage.querySelector('.fade-in-message');
            if (fadeInEl) {
                fadeInEl.style.opacity = '0';
                setTimeout(() => existingMessage.remove(), 300);
            } else {
                existingMessage.remove();
            }
        }
        
        // Mostrar notificación
        this.showNotification('Filtros limpiados', 'info');
    }

    showNotification(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';
        
        const iconMap = {
            success: 'check-circle',
            info: 'info-circle',
            warning: 'exclamation-triangle',
            danger: 'x-circle'
        };
        
        alertDiv.innerHTML = `
            <i class="bi bi-${iconMap[type]} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => alertDiv.remove(), 150);
        }, 3000);
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
                    alert.style.opacity = '0';
                    setTimeout(() => alert.style.display = 'none', 300);
                }
            });
        }, 5000);
    }
}

// ============================================
// DELETE HANDLER - Módulo de eliminación
// ============================================

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
        }, true);
    }

    showDeleteConfirmation(nombre, form) {
        Swal.fire({
            title: '¿Estás seguro?',
            html: `
                <div class="text-start">
                    <p class="mb-3">¿Quieres eliminar la invitación de <strong>${nombre}</strong>?</p>
                    <div class="alert alert-warning mb-0" style="font-size: 0.9rem;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Advertencia:</strong> Esta acción eliminará también todos los archivos asociados y no se puede deshacer.
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash me-1"></i> Sí, eliminar',
            cancelButtonText: '<i class="bi bi-x-circle me-1"></i> Cancelar',
            reverseButtons: true,
            buttonsStyling: true,
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'animate__animated animate__fadeInDown animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp animate__faster'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Eliminando...',
                    html: 'Por favor espera mientras se elimina la invitación',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                if (form) {
                    form.submit();
                }
            }
        });
    }
}

// ============================================
// UTILITIES - Funciones auxiliares
// ============================================

class AdminUtilities {
    static formatDate(dateString) {
        const date = new Date(dateString);
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('es-ES', options);
    }

    static copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('Copiado al portapapeles', 'success');
            }).catch(err => {
                console.error('Error al copiar:', err);
                this.fallbackCopyToClipboard(text);
            });
        } else {
            this.fallbackCopyToClipboard(text);
        }
    }

    static fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            this.showToast('Copiado al portapapeles', 'success');
        } catch (err) {
            this.showToast('Error al copiar', 'danger');
        }
        
        document.body.removeChild(textArea);
    }

    static showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.style.cssText = 'position: fixed; top: 80px; right: 20px; z-index: 9999;';
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }
}

// ============================================
// INICIALIZACIÓN
// ============================================

document.addEventListener('DOMContentLoaded', function() {    
    // Inicializar módulos principales
    window.adminPanel = new AdminPanel();
    window.deleteHandler = new DeleteHandler();
    window.adminUtils = AdminUtilities;
    
    // Agregar funcionalidad de copiar slug
    document.querySelectorAll('code').forEach(codeElement => {
        codeElement.style.cursor = 'pointer';
        codeElement.title = 'Click para copiar';
        
        codeElement.addEventListener('click', function(e) {
            e.preventDefault();
            const text = this.textContent;
            AdminUtilities.copyToClipboard(text);
        });
    });
});

// ============================================
// FIN DEL CÓDIGO
// ============================================
