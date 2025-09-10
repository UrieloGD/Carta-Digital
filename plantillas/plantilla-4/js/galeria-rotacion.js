// Galería Gótica con Rotación Automática
class GaleriaGotica {
    constructor(imagenes, contenedor = 'galeria-grid') {
        this.imagenes = imagenes || [];
        this.contenedor = document.getElementById(contenedor);
        
        if (!this.contenedor || this.imagenes.length === 0) {
            console.log('Galería gótica: Sin contenedor o imágenes');
            return;
        }
        
        this.imagenesPorPagina = this.calcularImagenesPorPagina();
        this.paginaActual = 0;
        this.totalPaginas = Math.max(1, Math.ceil(this.imagenes.length / this.imagenesPorPagina));
        this.autoRotacion = null;
        this.tiempoRotacion = 5000; // 5 segundos
        this.elementosMinimos = Math.max(this.imagenesPorPagina, 6);
        
        console.log(`Galería gótica iniciada: ${this.imagenes.length} imágenes`);
        
        this.init();
    }
    
    calcularImagenesPorPagina() {
        const width = window.innerWidth;
        if (width < 480) return 4;
        if (width < 768) return 6;
        if (width < 1200) return 8;
        return 9;
    }
    
    getImagenCiclica(indice) {
        if (this.imagenes.length === 0) return null;
        return this.imagenes[indice % this.imagenes.length];
    }
    
    init() {
        this.crearEstructuraGotica();
        this.mostrarPagina(0);
        this.configurarEventos();
        
        if (this.imagenes.length > this.imagenesPorPagina) {
            setTimeout(() => this.iniciarAutoRotacion(), 2000);
        }
        
        this.configurarResponsive();
    }
    
    crearEstructuraGotica() {
        if (!this.contenedor) return;
        
        this.contenedor.innerHTML = '';
        
        for (let i = 0; i < this.elementosMinimos; i++) {
            const item = document.createElement('div');
            item.className = 'galeria-item loading';
            item.style.setProperty('--item-delay', i);
            
            item.innerHTML = `
                <div class="galeria-overlay">
                    <div class="galeria-icon">◊</div>
                </div>
                <img src="" alt="" loading="lazy" />
                <div class="image-overlay"></div>
            `;
            
            this.contenedor.appendChild(item);
        }
    }
    
    mostrarPagina(numeroPagina) {
        if (!this.contenedor || this.imagenes.length === 0) return;
        
        const items = this.contenedor.querySelectorAll('.galeria-item');
        
        // Animar salida
        items.forEach((item, index) => {
            const img = item.querySelector('img');
            item.classList.add('loading');
            
            setTimeout(() => {
                if (img) {
                    img.style.opacity = '0';
                    img.style.transform = 'scale(0.9)';
                }
            }, index * 30);
        });
        
        // Cambiar contenido
        setTimeout(() => {
            items.forEach((item, index) => {
                const img = item.querySelector('img');
                const indiceImagen = numeroPagina * this.imagenesPorPagina + index;
                const imagenSrc = this.getImagenCiclica(indiceImagen);
                
                if (img && imagenSrc) {
                    img.src = imagenSrc;
                    img.alt = `Momento especial ${(indiceImagen % this.imagenes.length) + 1}`;
                    
                    img.onload = () => {
                        item.classList.remove('loading');
                        item.classList.add('loaded');
                        img.style.opacity = '1';
                        img.style.transform = 'scale(1)';
                    };
                    
                    img.onerror = () => {
                        console.error('Error cargando imagen:', imagenSrc);
                        if (this.imagenes[0] && img.src !== this.imagenes[0]) {
                            img.src = this.imagenes[0];
                        }
                    };
                    
                    // Hacer clickeable
                    item.onclick = () => this.abrirModal(imagenSrc);
                    item.style.cursor = 'pointer';
                    
                    // Animación de entrada con delay
                    setTimeout(() => {
                        if (img && item.classList.contains('loaded')) {
                            img.style.opacity = '1';
                            img.style.transform = 'scale(1)';
                        }
                    }, index * 80);
                }
            });
        }, 400);
        
        this.paginaActual = numeroPagina;
    }
    
    abrirModal(imagenSrc) {
        // Crear modal gótico si no existe
        let modal = document.getElementById('galeria-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'galeria-modal';
            modal.className = 'galeria-modal';
            modal.innerHTML = `
                <img src="" alt="Imagen ampliada" id="modal-imagen">
                <button class="galeria-close">&times;</button>
            `;
            document.body.appendChild(modal);
            
            // Eventos del modal
            modal.querySelector('.galeria-close').onclick = () => this.cerrarModal();
            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.cerrarModal();
            });
            
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('active')) {
                    this.cerrarModal();
                }
            });
        }
        
        const modalImg = modal.querySelector('#modal-imagen');
        if (modalImg) {
            modalImg.src = imagenSrc;
            modalImg.alt = 'Imagen ampliada';
        }
        
        modal.classList.add('active');
        this.pausarAutoRotacion();
        document.body.style.overflow = 'hidden';
    }
    
    cerrarModal() {
        const modal = document.getElementById('galeria-modal');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            this.iniciarAutoRotacion();
        }
    }
    
    siguientePagina() {
        const siguiente = (this.paginaActual + 1) % Math.max(1, Math.ceil(this.imagenes.length / this.imagenesPorPagina));
        this.mostrarPagina(siguiente);
    }
    
    configurarEventos() {
        if (this.contenedor) {
            this.contenedor.addEventListener('mouseenter', () => {
                this.pausarAutoRotacion();
            });
            
            this.contenedor.addEventListener('mouseleave', () => {
                if (!document.querySelector('.galeria-modal.active')) {
                    this.iniciarAutoRotacion();
                }
            });
        }
        
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pausarAutoRotacion();
            } else if (!document.querySelector('.galeria-modal.active')) {
                this.iniciarAutoRotacion();
            }
        });
    }
    
    configurarResponsive() {
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const nuevasImagenes = this.calcularImagenesPorPagina();
                if (nuevasImagenes !== this.imagenesPorPagina) {
                    this.imagenesPorPagina = nuevasImagenes;
                    this.elementosMinimos = Math.max(nuevasImagenes, 6);
                    this.totalPaginas = Math.max(1, Math.ceil(this.imagenes.length / this.imagenesPorPagina));
                    this.paginaActual = 0;
                    this.crearEstructuraGotica();
                    this.mostrarPagina(0);
                    this.reiniciarAutoRotacion();
                }
            }, 300);
        });
    }
    
    iniciarAutoRotacion() {
        if (this.imagenes.length <= this.imagenesPorPagina) return;
        
        this.pausarAutoRotacion();
        this.autoRotacion = setInterval(() => {
            if (!document.querySelector('.galeria-modal.active')) {
                this.siguientePagina();
            }
        }, this.tiempoRotacion);
    }
    
    pausarAutoRotacion() {
        if (this.autoRotacion) {
            clearInterval(this.autoRotacion);
            this.autoRotacion = null;
        }
    }
    
    reiniciarAutoRotacion() {
        this.pausarAutoRotacion();
        if (!document.querySelector('.galeria-modal.active') && this.imagenes.length > this.imagenesPorPagina) {
            setTimeout(() => this.iniciarAutoRotacion(), 1000);
        }
    }
    
    destruir() {
        this.pausarAutoRotacion();
        if (this.contenedor) {
            this.contenedor.innerHTML = '';
        }
        const modal = document.getElementById('galeria-modal');
        if (modal) {
            modal.remove();
        }
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    if (typeof galeriaImagenes !== 'undefined' && galeriaImagenes.length > 0) {
        setTimeout(() => {
            window.galeriaGotica = new GaleriaGotica(galeriaImagenes);
        }, 200);
    }
});

window.addEventListener('beforeunload', () => {
    if (window.galeriaGotica) {
        window.galeriaGotica.destruir();
    }
});