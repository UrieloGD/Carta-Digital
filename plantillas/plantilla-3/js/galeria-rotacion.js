// ================================================
// GALERÍA ELEGANTE CON ROTACIÓN DE 3 IMÁGENES
// ================================================

class GaleriaEleganteRotativa {
    constructor(images, containerId = 'galeria-grid') {
        this.images = images || [];
        this.container = document.getElementById(containerId);
        this.modal = document.getElementById('galeria-modal');
        this.modalImage = document.getElementById('modal-image');
        this.counterText = document.getElementById('modal-counter-text');
        
        if (!this.container || this.images.length === 0) {
            console.warn('GaleriaEleganteRotativa: Container not found or no images provided');
            return;
        }
        
        // Configuración del carrusel
        this.imagenesMostradas = 3; // Siempre mostrar 3 imágenes
        this.indiceInicial = 0;
        this.autoRotacion = null;
        this.tiempoRotacion = 4000; // 4 segundos
        this.isMobile = window.innerWidth < 768;
        
        // Modal
        this.currentIndex = 0;
        this.isModalOpen = false;
        this.touchStartX = 0;
        this.touchEndX = 0;
        
        // Preparar imágenes para mostrar
        this.prepararImagenes();
        
        console.log(`Galería: ${this.images.length} imágenes, mostrando ${this.imagenesMostradas}`);
        
        this.init();
    }
    
    prepararImagenes() {
        // Si tenemos menos de 3 imágenes, las repetimos hasta completar 3
        this.imagenesParaMostrar = [];
        
        if (this.images.length === 0) return;
        
        for (let i = 0; i < this.imagenesMostradas; i++) {
            const imagen = this.images[i % this.images.length];
            this.imagenesParaMostrar.push({
                src: imagen,
                originalIndex: i % this.images.length,
                displayIndex: i
            });
        }
    }
    
    init() {
        this.crearElementosGaleria();
        this.configurarEventos();
        this.iniciarAutoRotacion();
        
        // Recalcular en resize
        window.addEventListener('resize', this.manejarResize.bind(this));
    }
    
    crearElementosGaleria() {
        this.container.innerHTML = '';
        const fragment = document.createDocumentFragment();
        
        this.imagenesParaMostrar.forEach((imagen, index) => {
            const item = this.crearItemGaleria(imagen, index);
            fragment.appendChild(item);
        });
        
        this.container.appendChild(fragment);
        
        // Animación inicial escalonada
        setTimeout(() => {
            const items = this.container.querySelectorAll('.galeria-item');
            items.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 150);
            });
        }, 100);
    }
    
    crearItemGaleria(imagen, index) {
        const item = document.createElement('div');
        item.className = 'galeria-item';
        item.setAttribute('role', 'gridcell');
        item.setAttribute('tabindex', '0');
        item.setAttribute('data-original-index', imagen.originalIndex);
        item.setAttribute('data-display-index', index);
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';
        item.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        
        const img = document.createElement('img');
        img.className = 'galeria-image';
        img.src = imagen.src;
        img.alt = `Momento especial ${imagen.originalIndex + 1}`;
        img.loading = 'eager';
        
        const overlay = document.createElement('div');
        overlay.className = 'galeria-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        
        const zoomIcon = document.createElement('div');
        zoomIcon.className = 'galeria-zoom-icon';
        zoomIcon.innerHTML = '⊕';
        
        overlay.appendChild(zoomIcon);
        item.appendChild(img);
        item.appendChild(overlay);
        
        return item;
    }
    
    rotarImagenes() {
        // Obtener las próximas 3 imágenes
        this.indiceInicial = (this.indiceInicial + 1) % this.images.length;
        
        // Actualizar las imágenes a mostrar
        for (let i = 0; i < this.imagenesMostradas; i++) {
            const indiceImagen = (this.indiceInicial + i) % this.images.length;
            this.imagenesParaMostrar[i] = {
                src: this.images[indiceImagen],
                originalIndex: indiceImagen,
                displayIndex: i
            };
        }
        
        // Actualizar DOM con animación
        const items = this.container.querySelectorAll('.galeria-item');
        
        // Animación de salida
        items.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '0.3';
                item.style.transform = 'translateY(-10px) scale(0.98)';
            }, index * 50);
        });
        
        // Cambiar imágenes y animar entrada
        setTimeout(() => {
            items.forEach((item, index) => {
                const nuevaImagen = this.imagenesParaMostrar[index];
                const img = item.querySelector('.galeria-image');
                
                item.setAttribute('data-original-index', nuevaImagen.originalIndex);
                img.src = nuevaImagen.src;
                img.alt = `Momento especial ${nuevaImagen.originalIndex + 1}`;
                
                // Animación de entrada
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0) scale(1)';
                }, index * 80);
            });
        }, 300);
    }
    
    configurarEventos() {
        // Eventos de galería
        this.container.addEventListener('click', this.manejarClickGaleria.bind(this));
        this.container.addEventListener('keydown', this.manejarTeclaGaleria.bind(this));
        
        // Pausar rotación en hover
        this.container.addEventListener('mouseenter', () => {
            this.pausarAutoRotacion();
        });
        
        this.container.addEventListener('mouseleave', () => {
            this.iniciarAutoRotacion();
        });
        
        // Eventos de modal
        this.configurarEventosModal();
    }
    
    configurarEventosModal() {
        if (!this.modal) return;
        
        this.modal.addEventListener('click', this.manejarClickModal.bind(this));
        document.addEventListener('keydown', this.manejarTeclaModal.bind(this));
        
        // Touch events
        this.modal.addEventListener('touchstart', this.manejarTouchStart.bind(this), { passive: true });
        this.modal.addEventListener('touchend', this.manejarTouchEnd.bind(this), { passive: true });
        
        // Navegación
        const prevBtn = this.modal.querySelector('.modal-prev');
        const nextBtn = this.modal.querySelector('.modal-next');
        const closeBtn = this.modal.querySelector('.modal-close');
        
        prevBtn?.addEventListener('click', () => this.navegarModal(-1));
        nextBtn?.addEventListener('click', () => this.navegarModal(1));
        closeBtn?.addEventListener('click', () => this.cerrarModal());
    }
    
    manejarClickGaleria(e) {
        const item = e.target.closest('.galeria-item');
        if (item) {
            const originalIndex = parseInt(item.getAttribute('data-original-index'));
            this.abrirModal(originalIndex);
        }
    }
    
    manejarTeclaGaleria(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const item = e.target.closest('.galeria-item');
            if (item) {
                const originalIndex = parseInt(item.getAttribute('data-original-index'));
                this.abrirModal(originalIndex);
            }
        }
    }
    
    manejarResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth < 768;
        
        if (wasMobile !== this.isMobile) {
            this.pausarAutoRotacion();
            this.crearElementosGaleria();
            this.iniciarAutoRotacion();
        }
    }
    
    // Métodos de modal
    abrirModal(index) {
        if (!this.modal || !this.images[index]) return;
        
        this.currentIndex = index;
        this.isModalOpen = true;
        
        this.modalImage.src = this.images[index];
        this.modalImage.alt = `Momento especial ${index + 1}`;
        
        this.actualizarContador();
        
        this.modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        const closeBtn = this.modal.querySelector('.modal-close');
        setTimeout(() => closeBtn?.focus(), 100);
        
        // Pausar rotación mientras el modal está abierto
        this.pausarAutoRotacion();
    }
    
    cerrarModal() {
        if (!this.modal) return;
        
        this.isModalOpen = false;
        this.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        
        // Reanudar rotación
        this.iniciarAutoRotacion();
    }
    
    navegarModal(direccion) {
        const nuevoIndex = this.currentIndex + direccion;
        
        if (nuevoIndex >= 0 && nuevoIndex < this.images.length) {
            this.currentIndex = nuevoIndex;
            this.modalImage.src = this.images[nuevoIndex];
            this.modalImage.alt = `Momento especial ${nuevoIndex + 1}`;
            this.actualizarContador();
        }
    }
    
    actualizarContador() {
        if (this.counterText) {
            this.counterText.textContent = `${this.currentIndex + 1} de ${this.images.length}`;
        }
    }
    
    manejarClickModal(e) {
        if (e.target.classList.contains('modal-backdrop') || 
            e.target.classList.contains('galeria-modal')) {
            this.cerrarModal();
        }
    }
    
    manejarTeclaModal(e) {
        if (!this.isModalOpen) return;
        
        switch(e.key) {
            case 'Escape':
                e.preventDefault();
                this.cerrarModal();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                this.navegarModal(-1);
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.navegarModal(1);
                break;
        }
    }
    
    manejarTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
    }
    
    manejarTouchEnd(e) {
        this.touchEndX = e.changedTouches[0].clientX;
        const diffX = this.touchStartX - this.touchEndX;
        
        if (Math.abs(diffX) > 50) {
            if (diffX > 0) {
                this.navegarModal(1);
            } else {
                this.navegarModal(-1);
            }
        }
    }
    
    // Métodos de rotación automática
    iniciarAutoRotacion() {
        if (this.images.length <= 1 || this.isModalOpen) return;
        
        this.pausarAutoRotacion();
        
        this.autoRotacion = setInterval(() => {
            this.rotarImagenes();
        }, this.tiempoRotacion);
    }
    
    pausarAutoRotacion() {
        if (this.autoRotacion) {
            clearInterval(this.autoRotacion);
            this.autoRotacion = null;
        }
    }
    
    // Métodos públicos
    destruir() {
        this.pausarAutoRotacion();
        this.cerrarModal();
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
    
    agregarImagenes(nuevasImagenes) {
        this.images.push(...nuevasImagenes);
        this.prepararImagenes();
        this.crearElementosGaleria();
        this.iniciarAutoRotacion();
    }
    
    cambiarTiempoRotacion(nuevoTiempo) {
        this.tiempoRotacion = nuevoTiempo;
        if (this.autoRotacion) {
            this.iniciarAutoRotacion();
        }
    }
}

// Inicialización automática
document.addEventListener('DOMContentLoaded', () => {
    if (typeof galeriaImagenes !== 'undefined' && Array.isArray(galeriaImagenes) && galeriaImagenes.length > 0) {
        window.galeriaElegante = new GaleriaEleganteRotativa(galeriaImagenes);
    }
});

// Cleanup
window.addEventListener('beforeunload', () => {
    window.galeriaElegante?.destruir();
});