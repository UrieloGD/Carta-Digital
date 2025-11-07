// ============================================
// CONFIGURACIÓN DE PLANES CON VENTAJAS
// ============================================
const PLANES = [
    { 
        nombre: 'Plan Escencial', 
        precio: '$699 MXN', 
        recomendado: false, 
        valor: 'escencial',
        ventajas: [
            'Portada, Bienvenida, Historia',
            'Contador simple',
            'Ubicación (info + botón)',
            'Galería 6 fotos',
            'Dresscode solo texto',
            'Reservación por WhatsApp',
            'Soporte 7 días'
        ]
    },
    { 
        nombre: 'Plan Premium', 
        precio: '$899 MXN', 
        recomendado: true, 
        valor: 'premium',
        ventajas: [
            'Todo lo del plan Escencial',
            'Contador con cuenta regresiva',
            'Cronograma del evento',
            'Ubicaciones con imágenes',
            'Galería 10 fotos',
            'Dresscode con imágenes',
            'Reservación con boletaje digital',
            'Reproductor musical',
            'Soporte 30 días'
        ]
    },
    { 
        nombre: 'Plan Exclusivo', 
        precio: '$1,199 MXN', 
        recomendado: false, 
        valor: 'exclusivo',
        ventajas: [
            'Todo lo del plan Premium',
            'Galería 15 fotos',
            'Sección para tu mesa de regalos',
            'Reservación con boletaje digital',
            'Límite de tiempo para confirmación',
            'Sección para eventos adultos',
            'Soporte hasta el evento',
            'Cambios de colores y tipografía'
        ]
    }
];


// ============================================
// GESTIÓN DEL MODAL CUSTOM CON ANIMACIÓN
// ============================================
class ModalPlanes {
    constructor() {
        this.modal = document.getElementById('modalOverlay');
        this.modalClose = document.getElementById('modalClose');
        this.modalTitle = document.getElementById('modalTitle');
        this.plansList = document.getElementById('plansList');
        this.plantillaActualId = null;
        
        this.init();
    }
    
    init() {
        // Cerrar modal al hacer click en la X
        if (this.modalClose) {
            this.modalClose.addEventListener('click', () => this.cerrar());
        }
        
        // Cerrar modal al hacer click en el overlay (fuera del contenido)
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.cerrar();
                }
            });
        }
        
        // Cerrar modal con la tecla ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('active')) {
                this.cerrar();
            }
        });
    }
    
    abrir(plantillaId, plantillaNombre = '') {
        
        this.plantillaActualId = plantillaId;
        
        // Actualizar título
        if (plantillaNombre) {
            this.modalTitle.textContent = `Selecciona tu plan - ${plantillaNombre}`;
        } else {
            this.modalTitle.textContent = 'Selecciona tu plan';
        }
        
        // Renderizar planes
        this.renderizarPlanes();
        
        // Mostrar modal con animación
        this.modal.classList.add('active');
        document.body.classList.add('modal-open');
    }
    
    cerrar() {
        this.modal.classList.remove('active');
        document.body.classList.remove('modal-open');
        this.plantillaActualId = null;
    }
    
    renderizarPlanes() {        
        this.plansList.innerHTML = PLANES.map((plan, index) => `
            <a href="./checkout.php?plan=${plan.valor}&plantilla=${this.plantillaActualId}" 
               class="plan-item plan-item-${index}"
               data-plan="${plan.valor}">
                <div class="plan-info">
                    <h6>
                        ${plan.nombre}
                        ${plan.recomendado ? '<span class="badge-small">Recomendado</span>' : ''}
                    </h6>
                    <p class="plan-price">${plan.precio}</p>
                    <ul class="plan-ventajas">
                        ${plan.ventajas.slice(0, 3).map(ventaja => `
                            <li><i class="fas fa-check-circle"></i>${ventaja}</li>
                        `).join('')}
                        ${plan.ventajas.length > 3 ? `<li class="mas-ventajas">+${plan.ventajas.length - 3} más</li>` : ''}
                    </ul>
                </div>
                <i class="fas fa-arrow-right"></i>
            </a>
        `).join('');
    }
}


// ============================================
// INICIALIZACIÓN DE LA PÁGINA
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar modal
    const modal = new ModalPlanes();
    
    // Navegación por categorías (código original)
    const navButtons = document.querySelectorAll('.nav-btn');
    const templateCards = document.querySelectorAll('.template-card');

    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            navButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.getAttribute('data-category');
            
            templateCards.forEach(card => {
                if (category === 'todas' || card.getAttribute('data-category') === category) {
                    card.classList.remove('hidden');
                    card.classList.add('visible');
                } else {
                    card.classList.add('hidden');
                    card.classList.remove('visible');
                }
            });
        });
    });
    
    // Botones de compra
    const botonesComprar = document.querySelectorAll('.btn-comprar');
    
    botonesComprar.forEach((boton, index) => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const plantillaId = this.getAttribute('data-plantilla-id');
            const plantillaNombre = this.getAttribute('data-plantilla-nombre');
            
            modal.abrir(plantillaId, plantillaNombre);
        });
    });
    
    // Actualización de URLs en selects (código original)
    document.querySelectorAll('.template-card').forEach(card => {
        const selectPlan = card.querySelector('.select-plan');
        const btnComprar = card.querySelector('.btn-primary.template-btn');

        if (selectPlan && btnComprar) {
            btnComprar.href = `./checkout.php?plan=${selectPlan.value}&plantilla=${card.querySelector('.open-modal, [data-plantilla-id]')?.getAttribute('data-plantilla-id') || ''}`;

            selectPlan.addEventListener('change', () => {
                btnComprar.href = `./checkout.php?plan=${selectPlan.value}&plantilla=${card.querySelector('.open-modal, [data-plantilla-id]')?.getAttribute('data-plantilla-id') || ''}`;
            });
        }
    });
});
