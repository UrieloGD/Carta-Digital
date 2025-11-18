// ================================================
// GALERÍA OCEAN TWILIGHT - CON NAVEGACIÓN MÓVIL
// ================================================

class ElegantGallery {
    constructor(images, containerId = 'galeria-grid') {
        this.images = images || [];
        this.container = document.getElementById(containerId);
        
        this.modal = document.getElementById('galeria-lightbox') || document.getElementById('galeria-modal');
        this.modalImage = document.getElementById('lightbox-image') || document.getElementById('modal-image');
        this.counterText = document.getElementById('lightbox-counter-text') || document.getElementById('modal-counter-text');
        
        if (!this.container || this.images.length === 0) {
            console.warn('ElegantGallery: Container not found or no images provided');
            return;
        }
        
        if (!this.modal) {
            console.error('ElegantGallery: Modal not found');
            return;
        }
        
        this.currentIndex = 0;
        this.isModalOpen = false;
        this.touchStartX = 0;
        this.touchEndX = 0;
        this.isMobile = window.innerWidth < 768;
        
        // Desktop: rotación
        this.desktopMaxImages = 6;
        this.desktopRotationInterval = null;
        this.desktopCurrentOffset = 0;
        this.rotationSpeed = 6000;
        
        // Mobile: índice del carrusel
        this.mobileCurrentIndex = 0;
        
        this.init();
    }
    
    init() {
        this.createGalleryItems();
        this.bindEvents();
        
        // Solo rotar en desktop
        if (!this.isMobile && this.images.length > this.desktopMaxImages) {
            this.startDesktopRotation();
        }
        
        // Agregar controles de navegación en mobile
        if (this.isMobile) {
            this.createMobileNavigation();
        }
    }
    
    createGalleryItems() {
        this.container.innerHTML = '';
        const fragment = document.createDocumentFragment();
        
        if (this.isMobile) {
            // Mobile: mostrar todas las imágenes
            this.images.forEach((src, index) => {
                const item = this.createGalleryItem(src, index);
                fragment.appendChild(item);
            });
        } else {
            // Desktop: mostrar solo 6 imágenes
            const imagesToShow = this.images.slice(0, this.desktopMaxImages);
            imagesToShow.forEach((src, index) => {
                const item = this.createGalleryItem(src, index);
                fragment.appendChild(item);
            });
        }
        
        this.container.appendChild(fragment);
    }
    
    createGalleryItem(src, index) {
        const item = document.createElement('div');
        item.className = 'galeria-item';
        item.setAttribute('role', 'button');
        item.setAttribute('tabindex', '0');
        item.setAttribute('data-index', index);
        item.setAttribute('aria-label', `Ver imagen ${index + 1}`);
        
        const img = document.createElement('img');
        img.className = 'galeria-image';
        img.alt = `Momento especial ${index + 1}`;
        img.src = src;
        img.loading = 'lazy';
        
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
    
    createMobileNavigation() {
        // Crear contenedor de navegación móvil
        const navContainer = document.createElement('div');
        navContainer.className = 'galeria-mobile-nav';
        
        // Botón anterior
        const prevBtn = document.createElement('button');
        prevBtn.className = 'galeria-mobile-btn galeria-mobile-prev';
        prevBtn.setAttribute('aria-label', 'Imagen anterior');
        prevBtn.innerHTML = '‹';
        
        // Contador
        const counter = document.createElement('div');
        counter.className = 'galeria-mobile-counter';
        counter.id = 'galeria-mobile-counter';
        counter.textContent = `1 / ${this.images.length}`;
        
        // Botón siguiente
        const nextBtn = document.createElement('button');
        nextBtn.className = 'galeria-mobile-btn galeria-mobile-next';
        nextBtn.setAttribute('aria-label', 'Imagen siguiente');
        nextBtn.innerHTML = '›';
        
        navContainer.appendChild(prevBtn);
        navContainer.appendChild(counter);
        navContainer.appendChild(nextBtn);
        
        // Insertar después del grid
        this.container.parentElement.insertBefore(navContainer, this.container.nextSibling);
        
        // Eventos de navegación móvil
        prevBtn.addEventListener('click', () => this.navigateMobileCarousel(-1));
        nextBtn.addEventListener('click', () => this.navigateMobileCarousel(1));
    }
    
    navigateMobileCarousel(direction) {
        if (!this.isMobile) return;
        
        // Calcular nuevo índice
        this.mobileCurrentIndex += direction;
        
        // Límites
        if (this.mobileCurrentIndex < 0) {
            this.mobileCurrentIndex = 0;
        } else if (this.mobileCurrentIndex >= this.images.length) {
            this.mobileCurrentIndex = this.images.length - 1;
        }
        
        // Scroll al elemento
        const items = this.container.querySelectorAll('.galeria-item');
        const targetItem = items[this.mobileCurrentIndex];
        
        if (targetItem) {
            targetItem.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center'
            });
        }
        
        // Actualizar contador
        const counter = document.getElementById('galeria-mobile-counter');
        if (counter) {
            counter.textContent = `${this.mobileCurrentIndex + 1} / ${this.images.length}`;
        }
        
        // Actualizar estado de botones
        this.updateMobileButtons();
    }
    
    updateMobileButtons() {
        const prevBtn = document.querySelector('.galeria-mobile-prev');
        const nextBtn = document.querySelector('.galeria-mobile-next');
        
        if (prevBtn) {
            prevBtn.disabled = this.mobileCurrentIndex === 0;
            prevBtn.style.opacity = this.mobileCurrentIndex === 0 ? '0.3' : '1';
        }
        
        if (nextBtn) {
            nextBtn.disabled = this.mobileCurrentIndex === this.images.length - 1;
            nextBtn.style.opacity = this.mobileCurrentIndex === this.images.length - 1 ? '0.3' : '1';
        }
    }
    
    startDesktopRotation() {
        this.desktopRotationInterval = setInterval(() => {
            this.rotateDesktopImages();
        }, this.rotationSpeed);
    }
    
    rotateDesktopImages() {
        if (this.isMobile || this.images.length <= this.desktopMaxImages) return;
        
        this.desktopCurrentOffset = (this.desktopCurrentOffset + 1) % this.images.length;
        
        const items = this.container.querySelectorAll('.galeria-item');
        items.forEach((item, index) => {
            const newIndex = (this.desktopCurrentOffset + index) % this.images.length;
            const img = item.querySelector('.galeria-image');
            
            img.style.opacity = '0';
            
            setTimeout(() => {
                img.src = this.images[newIndex];
                img.alt = `Momento especial ${newIndex + 1}`;
                item.setAttribute('data-index', newIndex);
                img.style.opacity = '1';
            }, 300);
        });
    }
    
    bindEvents() {
        // Click en galería
        this.container.addEventListener('click', (e) => {
            const item = e.target.closest('.galeria-item');
            if (item) {
                const index = parseInt(item.getAttribute('data-index'));
                this.openModal(index);
            }
        });
        
        // Keyboard
        this.container.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const item = e.target.closest('.galeria-item');
                if (item) {
                    const index = parseInt(item.getAttribute('data-index'));
                    this.openModal(index);
                }
            }
        });
        
        // Eventos del modal
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal || e.target.classList.contains('lightbox-backdrop') || e.target.classList.contains('modal-backdrop')) {
                    this.closeModal();
                }
            });
            
            // Touch swipe
            this.modal.addEventListener('touchstart', (e) => {
                this.touchStartX = e.touches[0].clientX;
            }, { passive: true });
            
            this.modal.addEventListener('touchend', (e) => {
                this.touchEndX = e.changedTouches[0].clientX;
                const diffX = this.touchStartX - this.touchEndX;
                
                if (Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        this.navigateModal(1);
                    } else {
                        this.navigateModal(-1);
                    }
                }
            }, { passive: true });
            
            // Botones del modal
            const prevBtn = this.modal.querySelector('.lightbox-prev') || this.modal.querySelector('.modal-prev');
            const nextBtn = this.modal.querySelector('.lightbox-next') || this.modal.querySelector('.modal-next');
            const closeBtn = this.modal.querySelector('.lightbox-close') || this.modal.querySelector('.modal-close');
            
            if (prevBtn) {
                prevBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.navigateModal(-1);
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.navigateModal(1);
                });
            }
            
            if (closeBtn) {
                closeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.closeModal();
                });
            }
        }
        
        // Keyboard modal
        document.addEventListener('keydown', (e) => {
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
        });
        
        // Scroll observer para mobile
        if (this.isMobile) {
            this.container.addEventListener('scroll', this.debounce(() => {
                this.updateMobileIndexFromScroll();
            }, 100));
        }
        
        // Resize observer
        if (window.ResizeObserver) {
            const resizeObserver = new ResizeObserver(
                this.debounce(() => {
                    const wasMobile = this.isMobile;
                    this.isMobile = window.innerWidth < 768;
                    
                    if (wasMobile !== this.isMobile) {
                        this.destroy();
                        this.init();
                    }
                }, 300)
            );
            resizeObserver.observe(document.body);
        }
    }
    
    updateMobileIndexFromScroll() {
        if (!this.isMobile) return;
        
        const containerRect = this.container.getBoundingClientRect();
        const containerCenter = containerRect.left + containerRect.width / 2;
        
        const items = this.container.querySelectorAll('.galeria-item');
        let closestIndex = 0;
        let closestDistance = Infinity;
        
        items.forEach((item, index) => {
            const itemRect = item.getBoundingClientRect();
            const itemCenter = itemRect.left + itemRect.width / 2;
            const distance = Math.abs(containerCenter - itemCenter);
            
            if (distance < closestDistance) {
                closestDistance = distance;
                closestIndex = index;
            }
        });
        
        if (closestIndex !== this.mobileCurrentIndex) {
            this.mobileCurrentIndex = closestIndex;
            
            const counter = document.getElementById('galeria-mobile-counter');
            if (counter) {
                counter.textContent = `${this.mobileCurrentIndex + 1} / ${this.images.length}`;
            }
            
            this.updateMobileButtons();
        }
    }
    
    openModal(index) {
        if (!this.modal || !this.modalImage) {
            console.error('Modal o modalImage no encontrado');
            return;
        }
        
        if (index < 0 || index >= this.images.length) {
            console.error('Índice fuera de rango:', index);
            return;
        }
        
        this.currentIndex = index;
        this.isModalOpen = true;
        
        if (this.desktopRotationInterval) {
            clearInterval(this.desktopRotationInterval);
            this.desktopRotationInterval = null;
        }
        
        this.modalImage.src = this.images[index];
        this.modalImage.alt = `Momento especial ${index + 1}`;
        
        this.updateModalUI();
        
        this.modal.setAttribute('aria-hidden', 'false');
        this.modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    closeModal() {
        if (!this.modal) return;
        
        this.isModalOpen = false;
        this.modal.setAttribute('aria-hidden', 'true');
        this.modal.style.display = 'none';
        document.body.style.overflow = '';
        
        if (!this.isMobile && this.images.length > this.desktopMaxImages) {
            this.startDesktopRotation();
        }
    }
    
    navigateModal(direction) {
        let newIndex = this.currentIndex + direction;
        
        if (newIndex < 0) {
            newIndex = this.images.length - 1;
        } else if (newIndex >= this.images.length) {
            newIndex = 0;
        }
        
        this.currentIndex = newIndex;
        
        if (this.modalImage) {
            this.modalImage.style.opacity = '0';
            
            setTimeout(() => {
                this.modalImage.src = this.images[this.currentIndex];
                this.modalImage.alt = `Momento especial ${this.currentIndex + 1}`;
                this.modalImage.style.opacity = '1';
            }, 150);
        }
        
        this.updateModalUI();
        this.preloadAdjacentImages(this.currentIndex);
    }
    
    updateModalUI() {
        if (this.counterText) {
            this.counterText.textContent = `${this.currentIndex + 1} de ${this.images.length}`;
        }
        
        const prevBtn = this.modal?.querySelector('.lightbox-prev') || this.modal?.querySelector('.modal-prev');
        const nextBtn = this.modal?.querySelector('.lightbox-next') || this.modal?.querySelector('.modal-next');
        
        if (prevBtn) {
            prevBtn.style.display = 'flex';
            prevBtn.disabled = false;
        }
        
        if (nextBtn) {
            nextBtn.style.display = 'flex';
            nextBtn.disabled = false;
        }
    }
    
    preloadAdjacentImages(index) {
        const prevIndex = index === 0 ? this.images.length - 1 : index - 1;
        const nextIndex = (index + 1) % this.images.length;
        
        [prevIndex, nextIndex].forEach(i => {
            const img = new Image();
            img.src = this.images[i];
        });
    }
    
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
    
    destroy() {
        if (this.desktopRotationInterval) {
            clearInterval(this.desktopRotationInterval);
        }
        
        // Eliminar navegación móvil
        const mobileNav = document.querySelector('.galeria-mobile-nav');
        if (mobileNav) {
            mobileNav.remove();
        }
        
        this.closeModal();
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    if (typeof galeriaImagenes !== 'undefined' && Array.isArray(galeriaImagenes) && galeriaImagenes.length > 0) {
        window.elegantGallery = new ElegantGallery(galeriaImagenes);
    } else {
        console.warn('galeriaImagenes no está definido');
    }
});

window.addEventListener('beforeunload', () => {
    window.elegantGallery?.destroy();
});
