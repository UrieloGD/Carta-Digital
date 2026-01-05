// ============================================
// CONFIGURACIÓN DE PLANES CON VENTAJAS
// ============================================
const PLANES = [
    { 
        nombre: 'Plan Esencial', 
        precio: '$499 MXN', 
        recomendado: false, 
        valor: 'Esencial',
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
        precio: '$699 MXN', 
        recomendado: true, 
        valor: 'Premium',
        ventajas: [
            'Todo lo del plan Esencial',
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
        precio: '$999 MXN', 
        recomendado: false, 
        valor: 'Exclusivo',
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
    
    /**
     * Obtener precio formateado desde PLANES_DESDE_BD
     * Si no está disponible, usa el valor por defecto
     */
    obtenerPrecioFormateado(nombrePlan) {
        // Si viene precio desde PHP/BD
        if (typeof PLANES_DESDE_BD !== 'undefined' && PLANES_DESDE_BD[nombrePlan]) {
            // Convertir centavos a formato MXN: 89900 → 899.00
            const pesos = (PLANES_DESDE_BD[nombrePlan] / 100).toFixed(2);
            return `$${pesos} MXN`;
        }
        
        // Fallback: buscar en PLANES (hardcodeado)
        const plan = PLANES.find(p => p.valor === nombrePlan);
        return plan ? plan.precio : '$0.00 MXN';
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
                    <p class="plan-price">${this.obtenerPrecioFormateado(plan.valor)}</p>
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
    
    // Botones de compra
    const botonesComprar = document.querySelectorAll('.btn-comprar');
    
    botonesComprar.forEach((boton) => {
        boton.addEventListener('click', function(e) {
            e.preventDefault();
            
            const plantillaId = this.getAttribute('data-plantilla-id');
            const plantillaNombre = this.getAttribute('data-plantilla-nombre');
            
            modal.abrir(plantillaId, plantillaNombre);
        });
    });
});
