// Galería con rotación automática y estructura estable
class GaleriaRotativa {
    constructor(imagenes, contenedor = 'galeria-grid') {
        this.imagenes = imagenes || [];
        this.contenedor = document.getElementById(contenedor);
        
        if (!this.contenedor || this.imagenes.length === 0) {
            console.log('No se puede inicializar la galería: contenedor o imágenes no encontrados');
            return;
        }
        
        this.imagenesPorPagina = this.calcularImagenesPorPagina();
        this.paginaActual = 0;
        this.totalPaginas = Math.ceil(this.imagenes.length / this.imagenesPorPagina);
        this.autoRotacion = null;
        this.tiempoRotacion = 4000; // 4 segundos
        
        // Elementos mínimos para mantener estructura estable
        this.elementosMinimos = this.calcularElementosMinimos();
        
        console.log(`Galería automática: ${this.imagenes.length} imágenes, ${this.totalPaginas} páginas, ${this.elementosMinimos} elementos mínimos`);
        
        this.init();
    }
    
    calcularImagenesPorPagina() {
        const width = window.innerWidth;
        if (width < 480) return 4; // Aumentado para mejor estabilidad
        if (width < 768) return 6;
        return 8;
    }
    
    calcularElementosMinimos() {
        const width = window.innerWidth;
        if (width < 480) return 4; // Siempre mostrar mínimo 4 elementos en móvil
        if (width < 768) return 6;
        return 8;
    }
    
    init() {
        this.crearEstructuraEstable();
        this.mostrarPagina(0);
        this.configurarEventos();
        
        // Iniciar auto-rotación si hay múltiples páginas
        if (this.totalPaginas > 1) {
            this.iniciarAutoRotacion();
        }
        
        // Recalcular en resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const nuevasImagenesPorPagina = this.calcularImagenesPorPagina();
                const nuevosElementosMinimos = this.calcularElementosMinimos();
                
                if (nuevasImagenesPorPagina !== this.imagenesPorPagina || nuevosElementosMinimos !== this.elementosMinimos) {
                    this.imagenesPorPagina = nuevasImagenesPorPagina;
                    this.elementosMinimos = nuevosElementosMinimos;
                    this.totalPaginas = Math.ceil(this.imagenes.length / this.imagenesPorPagina);
                    this.paginaActual = 0;
                    this.crearEstructuraEstable();
                    this.mostrarPagina(0);
                    this.reiniciarAutoRotacion();
                }
            }, 250);
        });
    }
    
    crearEstructuraEstable() {
        if (!this.contenedor) return;
        
        this.contenedor.innerHTML = '';
        
        // Crear el número mínimo de elementos para mantener estructura estable
        for (let i = 0; i < this.elementosMinimos; i++) {
            const item = document.createElement('div');
            item.className = 'galeria-item';
            item.style.setProperty('--item-delay', i);
            item.innerHTML = `
                <div class="galeria-overlay">
                    <div class="galeria-icon">🔍</div>
                </div>
                <img src="" alt="" style="opacity: 0;" />
                <div class="image-overlay"></div>
            `;
            
            this.contenedor.appendChild(item);
        }
    }
    
    mostrarPagina(numeroPagina) {
        if (!this.contenedor) return;
        
        // Calcular rango de imágenes para esta página
        const inicio = numeroPagina * this.imagenesPorPagina;
        const fin = Math.min(inicio + this.imagenesPorPagina, this.imagenes.length);
        const imagenesActuales = this.imagenes.slice(inicio, fin);
        
        const items = this.contenedor.querySelectorAll('.galeria-item');
        
        // Animar salida de imágenes actuales
        items.forEach((item, index) => {
            const img = item.querySelector('img');
            const overlay = item.querySelector('.galeria-overlay');
            
            setTimeout(() => {
                if (img) {
                    img.style.opacity = '0';
                    img.style.transform = 'scale(0.95)';
                }
            }, index * 50);
        });
        
        // Cambiar contenido después de la animación de salida
        setTimeout(() => {
            items.forEach((item, index) => {
                const img = item.querySelector('img');
                const overlay = item.querySelector('.galeria-overlay');
                
                if (index < imagenesActuales.length) {
                    // Mostrar imagen real
                    const imagenSrc = imagenesActuales[index];
                    
                    if (img) {
                        img.src = imagenSrc;
                        img.alt = `Momento especial ${inicio + index + 1}`;
                        img.style.opacity = '0';
                        img.style.transform = 'scale(0.95)';
                        
                        // Manejar errores de carga
                        img.onerror = function() {
                            console.error('Error cargando imagen:', this.src);
                            this.style.display = 'none';
                        };
                        
                        img.onload = function() {
                            this.style.display = 'block';
                        };
                    }
                    
                    // Hacer el item clickeable
                    item.onclick = () => this.abrirImagenModal(imagenSrc);
                    item.style.cursor = 'pointer';
                    item.classList.remove('galeria-placeholder');
                    
                    // Animación de entrada
                    setTimeout(() => {
                        if (img) {
                            img.style.opacity = '1';
                            img.style.transform = 'scale(1)';
                        }
                    }, index * 100);
                    
                } else {
                    // Convertir en placeholder
                    if (img) {
                        img.src = '';
                        img.alt = '';
                        img.style.opacity = '0';
                    }
                    
                    item.onclick = null;
                    item.style.cursor = 'default';
                    item.classList.add('galeria-placeholder');
                }
            });
        }, 300);
        
        this.paginaActual = numeroPagina;
    }
    
    abrirImagenModal(imagenSrc) {
        // Crear modal si no existe
        let modal = document.getElementById('galeria-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'galeria-modal';
            modal.className = 'galeria-modal';
            modal.innerHTML = `
                <img src="" alt="Imagen ampliada" id="modal-imagen">
                <button class="galeria-close" onclick="this.parentElement.classList.remove('active')">&times;</button>
            `;
            document.body.appendChild(modal);
            
            // Cerrar con ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    modal.classList.remove('active');
                }
            });
            
            // Cerrar al hacer clic fuera de la imagen
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.remove('active');
                }
            });
        }
        
        const modalImg = modal.querySelector('#modal-imagen');
        if (modalImg) {
            modalImg.src = imagenSrc;
            modalImg.alt = 'Imagen ampliada';
        }
        
        modal.classList.add('active');
        
        // Pausar auto-rotación mientras el modal está abierto
        this.pausarAutoRotacion();
        
        // Reanudar auto-rotación cuando se cierre el modal
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (!modal.classList.contains('active')) {
                        this.iniciarAutoRotacion();
                        observer.disconnect();
                    }
                }
            });
        });
        
        observer.observe(modal, { attributes: true });
    }
    
    siguientePagina() {
        const siguiente = (this.paginaActual + 1) % this.totalPaginas;
        this.mostrarPagina(siguiente);
    }
    
    configurarEventos() {
        // Pausar auto-rotación al hacer hover en la galería
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
        
        // Pausar auto-rotación cuando el usuario interactúa
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pausarAutoRotacion();
            } else if (!document.querySelector('.galeria-modal.active')) {
                this.iniciarAutoRotacion();
            }
        });
    }
    
    iniciarAutoRotacion() {
        if (this.totalPaginas <= 1) return;
        
        this.pausarAutoRotacion(); // Limpiar cualquier intervalo existente
        
        this.autoRotacion = setInterval(() => {
            // Verificar si el modal está abierto
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
        if (!document.querySelector('.galeria-modal.active')) {
            this.iniciarAutoRotacion();
        }
    }
    
    // Método para destruir la galería si es necesario
    destruir() {
        this.pausarAutoRotacion();
        if (this.contenedor) {
            this.contenedor.innerHTML = '';
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof galeriaImagenes !== 'undefined' && galeriaImagenes.length > 0) {
        // Pequeño delay para asegurar que el CSS esté cargado
        setTimeout(() => {
            window.galeriaInstance = new GaleriaRotativa(galeriaImagenes);
        }, 100);
    } else {
        console.log('No hay imágenes de galería definidas');
    }
});

// Limpiar al salir de la página
window.addEventListener('beforeunload', () => {
    if (window.galeriaInstance) {
        window.galeriaInstance.destruir();
    }
});