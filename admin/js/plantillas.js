// admin/js/plantillas.js - JavaScript modularizado para el gestor de plantillas

class PlantillasManager {
    constructor() {
        this.searchInput = document.getElementById('searchInput');
        this.estadoFilter = document.getElementById('estadoFilter');
        this.ordenFilter = document.getElementById('ordenFilter');
        this.plantillaCards = document.querySelectorAll('.plantilla-card');
        this.allPlantillas = Array.from(this.plantillaCards).map(card => 
            card.closest('.col-xl-3, .col-lg-4, .col-md-6')
        ).filter(col => col !== null);
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.bindDeleteEvents();
        this.updateCounters();
        this.setupAutoCloseAlerts();
        
        console.log('Gestor de plantillas inicializado:', {
            plantillas: this.allPlantillas.length,
            filtros: {
                search: !!this.searchInput,
                estado: !!this.estadoFilter,
                orden: !!this.ordenFilter
            }
        });
    }

    bindEvents() {
        if (this.searchInput) {
            this.searchInput.addEventListener('input', () => this.filterPlantillas());
        }
        
        if (this.estadoFilter) {
            this.estadoFilter.addEventListener('change', () => this.filterPlantillas());
        }
        
        if (this.ordenFilter) {
            this.ordenFilter.addEventListener('change', () => this.sortPlantillas());
        }
    }

    bindDeleteEvents() {
        // Enlazar eventos a los botones de eliminar para usar SweetAlert2
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-outline-danger') || 
                e.target.classList.contains('btn-outline-danger')) {
                e.preventDefault();
                const button = e.target.closest('.btn-outline-danger');
                const card = button.closest('.plantilla-card');
                const plantillaId = this.getPlantillaIdFromCard(card);
                const plantillaNombre = this.getPlantillaNombreFromCard(card);
                
                if (plantillaId && plantillaNombre) {
                    this.showDeleteConfirmation(plantillaId, plantillaNombre);
                }
            }
        });
    }

    getPlantillaIdFromCard(card) {
        // Buscar el ID en el modal asociado o en el formulario
        const modalId = card.closest('.col-xl-3, .col-lg-4, .col-md-6')
            .querySelector('.modal')?.id;
        
        if (modalId) {
            const match = modalId.match(/deleteModal(\d+)/);
            return match ? match[1] : null;
        }
        return null;
    }

    getPlantillaNombreFromCard(card) {
        const titleElement = card.querySelector('.card-title');
        return titleElement ? titleElement.textContent.trim() : 'esta plantilla';
    }

    showDeleteConfirmation(plantillaId, plantillaNombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            html: `Vas a eliminar la plantilla <strong>"${plantillaNombre}"</strong>. Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                this.submitDeleteForm(plantillaId);
            }
        });
    }

    submitDeleteForm(plantillaId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'eliminar';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = plantillaId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }

    updateCounters() {
        const visibleCards = this.allPlantillas.filter(card => 
            card && card.style.display !== 'none' && !card.hasAttribute('data-hidden')
        );
        
        const activasCards = visibleCards.filter(card => {
            const badge = card.querySelector('.badge');
            return badge && badge.textContent.trim().toLowerCase().includes('activa');
        });
        
        const totalCountEl = document.getElementById('totalCount');
        const activasCountEl = document.getElementById('activasCount');
        
        if (totalCountEl) totalCountEl.textContent = visibleCards.length;
        if (activasCountEl) activasCountEl.textContent = activasCards.length;
    }

    filterPlantillas() {
        const searchTerm = this.searchInput ? this.searchInput.value.toLowerCase() : '';
        const selectedEstado = this.estadoFilter ? this.estadoFilter.value : '';
        
        this.allPlantillas.forEach(cardContainer => {
            if (!cardContainer) return;
            
            const card = cardContainer.querySelector('.plantilla-card');
            if (!card) return;
            
            // Buscar en título y descripción
            const titleElement = card.querySelector('.card-title');
            const title = titleElement ? titleElement.textContent.toLowerCase() : '';
            
            const descElement = card.querySelector('.card-text');
            const description = descElement ? descElement.textContent.toLowerCase() : '';
            
            const folderElement = card.querySelector('.bi-folder').parentElement;
            const folder = folderElement ? folderElement.textContent.toLowerCase() : '';
            
            // Buscar estado
            const badge = card.querySelector('.badge');
            const estado = badge ? badge.textContent.trim().toLowerCase() : '';
            
            let shouldShow = true;
            
            // Filtro de búsqueda
            if (searchTerm && !title.includes(searchTerm) && !description.includes(searchTerm) && !folder.includes(searchTerm)) {
                shouldShow = false;
            }
            
            // Filtro de estado
            if (selectedEstado) {
                if (selectedEstado === 'activa' && !estado.includes('activa')) {
                    shouldShow = false;
                } else if (selectedEstado === 'inactiva' && !estado.includes('inactiva')) {
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

    sortPlantillas() {
        const sortBy = this.ordenFilter ? this.ordenFilter.value : '';
        if (!sortBy) return;
        
        const container = document.querySelector('.row.g-4');
        if (!container) return;
        
        const sortedCards = [...this.allPlantillas].sort((a, b) => {
            const getTitleA = a.querySelector('.card-title').textContent.toLowerCase();
            const getTitleB = b.querySelector('.card-title').textContent.toLowerCase();
            
            if (sortBy === 'nombre_asc') {
                return getTitleA.localeCompare(getTitleB);
            } else if (sortBy === 'nombre_desc') {
                return getTitleB.localeCompare(getTitleA);
            } else if (sortBy === 'reciente') {
                // Ordenar por ID (asumiendo que ID mayor = más reciente)
                const idA = this.getPlantillaIdFromCard(a.querySelector('.plantilla-card'));
                const idB = this.getPlantillaIdFromCard(b.querySelector('.plantilla-card'));
                return parseInt(idB) - parseInt(idA);
            }
            
            return 0;
        });
        
        sortedCards.forEach(card => {
            if (card && container.contains(card)) {
                container.appendChild(card);
            }
        });
        
        this.allPlantillas = sortedCards.filter(card => card !== null);
    }

    showNoResultsMessage() {
        const visibleCards = this.allPlantillas.filter(card => 
            card && card.style.display !== 'none' && !card.hasAttribute('data-hidden')
        );
        
        const container = document.querySelector('.row.g-4');
        if (!container) return;
        
        const existingMessage = document.getElementById('no-results-message');
        
        if (visibleCards.length === 0 && !existingMessage && this.allPlantillas.length > 0) {
            const message = document.createElement('div');
            message.id = 'no-results-message';
            message.className = 'col-12 text-center py-5';
            message.innerHTML = `
                <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">No se encontraron plantillas</h5>
                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                <button class="btn btn-outline-primary" onclick="plantillasManager.clearFilters()">
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
        if (this.ordenFilter) this.ordenFilter.value = '';
        
        this.allPlantillas.forEach(card => {
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

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.plantillasManager = new PlantillasManager();
});