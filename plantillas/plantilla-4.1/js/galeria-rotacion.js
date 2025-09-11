class ElegantGallery {
    constructor(images, containerId = 'galeria-grid') {
        this.images = images || [];
        this.container = document.getElementById(containerId);
        this.modal = document.getElementById('galeria-modal');
        this.modalImage = document.getElementById('modal-image');
        this.counterText = document.getElementById('modal-counter-text');
        
        if (!this.container || this.images.length === 0) {
            console.warn('ElegantGallery: Container not found or no images provided');
            return;
        }
        
        this.currentIndex = 0;
        this.isModalOpen = false;
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.isMobile = window.innerWidth < 768;
        
        // Intersection Observer para lazy loading (solo desktop)
        if (!this.isMobile) {
            this.observer = new IntersectionObserver(
                this.handleIntersection.bind(this),
                { rootMargin: '50px' }
            );
        }
        
        this.init();
    }
    
    init() {
        this.createGalleryItems();
        this.bindEvents();
        if (!this.isMobile) {
            this.preloadFirstImages();
        } else {
            // En móvil, cargar todas las imágenes para el carrusel
            this.loadAllImages();
        }
    }
    
    createGalleryItems() {
        const fragment = document.createDocumentFragment();
        
        // Para móvil: repetir las imágenes 3 veces para efecto infinito
        const imagesToRender = this.isMobile ? 
            [...this.images, ...this.images, ...this.images] : 
            this.images;
        
        imagesToRender.forEach((src, index) => {
            const originalIndex = this.isMobile ? index % this.images.length : index;
            const item = this.createGalleryItem(src, originalIndex, index);
            fragment.appendChild(item);
            
            // Stagger animation solo en desktop
            if (!this.isMobile) {
                setTimeout(() => {
                    item.style.animationDelay = `${index * 0.1}s`;
                }, 50);
            }
        });
        
        this.container.appendChild(fragment);
    }
    
    createGalleryItem(src, originalIndex, renderIndex) {
        const item = document.createElement('div');
        item.className = this.isMobile ? 'galeria-item' : 'galeria-item loading';
        item.setAttribute('role', 'gridcell');
        item.setAttribute('tabindex', '0');
        item.setAttribute('data-index', originalIndex);
        item.setAttribute('data-render-index', renderIndex);
        
        const img = document.createElement('img');
        img.className = 'galeria-image';
        img.alt = `Momento especial ${originalIndex + 1}`;
        
        // En móvil, cargar imagen inmediatamente
        if (this.isMobile) {
            img.src = src;
            img.loading = 'eager';
        } else {
            img.setAttribute('data-src', src);
            img.loading = 'lazy';
        }
        
        const overlay = document.createElement('div');
        overlay.className = 'galeria-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        
        const zoomIcon = document.createElement('div');
        zoomIcon.className = 'galeria-zoom-icon';
        zoomIcon.innerHTML = '⊕';
        
        overlay.appendChild(zoomIcon);
        item.appendChild(img);
        item.appendChild(overlay);
        
        // Observe for lazy loading solo en desktop
        if (!this.isMobile && this.observer) {
            this.observer.observe(item);
        }
        
        return item;
    }
    
    loadAllImages() {
        const items = this.container.querySelectorAll('.galeria-item');
        items.forEach(item => {
            const img = item.querySelector('.galeria-image');
            const src = img.getAttribute('data-src');
            if (src) {
                this.loadImage(img, item);
            }
        });
    }
    
    handleIntersection(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const item = entry.target;
                const img = item.querySelector('.galeria-image');
                const src = img.getAttribute('data-src');
                
                if (src && !img.src) {
                    this.loadImage(img, item);
                    this.observer.unobserve(item);
                }
            }
        });
    }
    
    loadImage(img, item) {
        const src = img.getAttribute('data-src');
        
        img.addEventListener('load', () => {
            item.classList.remove('loading');
            img.style.opacity = '1';
        }, { once: true });
        
        img.addEventListener('error', () => {
            item.classList.remove('loading');
            item.classList.add('error');
            console.error(`Failed to load image: ${src}`);
        }, { once: true });
        
        img.src = src;
    }
    
    preloadFirstImages() {
        // Solo para desktop
        const firstItems = this.container.querySelectorAll('.galeria-item:nth-child(-n+4)');
        firstItems.forEach(item => {
            const img = item.querySelector('.galeria-image');
            const src = img.getAttribute('data-src');
            if (src) {
                this.loadImage(img, item);
            }
        });
    }

    // Agregar después del método bindEvents()
    handleGalleryKeydown(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const item = e.target.closest('.galeria-item');
            if (item) {
                const index = parseInt(item.getAttribute('data-index'));
                this.openModal(index);
            }
        }
    }

    handleModalClick(e) {
        // Cerrar al hacer clic en el backdrop
        if (e.target.classList.contains('modal-backdrop') || 
            e.target.classList.contains('galeria-modal')) {
            this.closeModal();
        }
    }

    handleModalKeydown(e) {
        if (!this.isModalOpen) return;
        
        switch(e.key) {
            case 'Escape':
                e.preventDefault();
                this.closeModal();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                this.navigateModal(-1);
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.navigateModal(1);
                break;
        }
    }

    handleTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
    }

    handleTouchEnd(e) {
        this.touchEndX = e.changedTouches[0].clientX;
        const diffX = this.touchStartX - this.touchEndX;
        
        // Swipe threshold
        if (Math.abs(diffX) > 50) {
            if (diffX > 0) {
                // Swipe left - next image
                this.navigateModal(1);
            } else {
                // Swipe right - previous image
                this.navigateModal(-1);
            }
        }
    }
    
    bindEvents() {
        // Eventos de galería
        this.container.addEventListener('click', this.handleGalleryClick.bind(this));
        this.container.addEventListener('keydown', this.handleGalleryKeydown.bind(this));
        
        // Eventos de modal
        this.modal?.addEventListener('click', this.handleModalClick.bind(this));
        document.addEventListener('keydown', this.handleModalKeydown.bind(this));
        
        // Touch events para swipe
        this.modal?.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        this.modal?.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
        
        // Navegación del modal
        const prevBtn = this.modal?.querySelector('.modal-prev');
        const nextBtn = this.modal?.querySelector('.modal-next');
        const closeBtn = this.modal?.querySelector('.modal-close');
        
        prevBtn?.addEventListener('click', () => this.navigateModal(-1));
        nextBtn?.addEventListener('click', () => this.navigateModal(1));
        closeBtn?.addEventListener('click', () => this.closeModal());
        
        // Resize observer para responsive
        if (window.ResizeObserver) {
            const resizeObserver = new ResizeObserver(
                this.debounce(this.handleResize.bind(this), 300)
            );
            resizeObserver.observe(this.container);
        }
    }
    
    handleGalleryClick(e) {
        const item = e.target.closest('.galeria-item');
        if (item) {
            const index = parseInt(item.getAttribute('data-index'));
            this.openModal(index);
        }
    }
    
    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth < 768;
        
        // Si cambió de móvil a desktop o viceversa, recrear galería
        if (wasMobile !== this.isMobile) {
            this.destroy();
            this.init();
        }
    }
    
    openModal(index) {
        if (!this.modal || !this.images[index]) return;
        
        this.currentIndex = index;
        this.isModalOpen = true;
        
        // Set image
        this.modalImage.src = this.images[index];
        this.modalImage.alt = `Momento especial ${index + 1}`;
        
        // Update counter
        this.updateCounter();
        
        // Show modal
        this.modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        
        // Focus management
        const closeBtn = this.modal.querySelector('.modal-close');
        setTimeout(() => closeBtn?.focus(), 100);
        
        // Preload adjacent images
        this.preloadAdjacentImages(index);
    }
    
    closeModal() {
        if (!this.modal) return;
        
        this.isModalOpen = false;
        this.modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        
        // Return focus to gallery item
        const galleryItem = this.container.querySelector(`[data-index="${this.currentIndex}"]`);
        setTimeout(() => galleryItem?.focus(), 100);
    }
    
    navigateModal(direction) {
        const newIndex = this.currentIndex + direction;
        
        if (newIndex >= 0 && newIndex < this.images.length) {
            this.currentIndex = newIndex;
            this.modalImage.src = this.images[newIndex];
            this.modalImage.alt = `Momento especial ${newIndex + 1}`;
            this.updateCounter();
            this.preloadAdjacentImages(newIndex);
        }
    }
    
    updateCounter() {
        if (this.counterText) {
            this.counterText.textContent = `${this.currentIndex + 1} de ${this.images.length}`;
        }
    }
    
    preloadAdjacentImages(index) {
        const preloadIndexes = [index - 1, index + 1].filter(i => 
            i >= 0 && i < this.images.length
        );
        
        preloadIndexes.forEach(i => {
            const img = new Image();
            img.src = this.images[i];
        });
    }
    
    // Utility function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    
    // Public methods
    destroy() {
        this.observer?.disconnect();
        this.closeModal();
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
    
    addImages(newImages) {
        const startIndex = this.images.length;
        this.images.push(...newImages);
        
        // Recrear toda la galería para mantener el patrón del carrusel
        if (this.isMobile) {
            this.destroy();
            this.init();
        } else {
            // En desktop, solo agregar las nuevas
            const fragment = document.createDocumentFragment();
            newImages.forEach((src, i) => {
                const item = this.createGalleryItem(src, startIndex + i, startIndex + i);
                fragment.appendChild(item);
            });
            this.container.appendChild(fragment);
        }
    }
}

// Inicialización automática
document.addEventListener('DOMContentLoaded', () => {
    // Verificar si existen las imágenes de galería
    if (typeof galeriaImagenes !== 'undefined' && Array.isArray(galeriaImagenes) && galeriaImagenes.length > 0) {
        window.elegantGallery = new ElegantGallery(galeriaImagenes);
    }
});

// Cleanup
window.addEventListener('beforeunload', () => {
    window.elegantGallery?.destroy();
});