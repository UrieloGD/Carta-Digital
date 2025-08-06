// Galería con rotación automática y estructura estable - SIN PLACEHOLDERS
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
        // Cambio importante: calculamos páginas basándose en las imágenes visibles
        this.totalPaginas = Math.max(1, Math.ceil(this.imagenes.length / this.imagenesPorPagina));
        this.autoRotacion = null;
        this.tiempoRotacion = 4000; // 4 segundos
        
        // Elementos mínimos para mantener estructura estable
        this.elementosMinimos = this.calcularElementosMinimos();
        
        console.log(`Galería automática: ${this.imagenes.length} imágenes, ${this.totalPaginas} páginas, ${this.elementosMinimos} elementos mínimos`);
        
        this.init();
    }
    
    calcularImagenesPorPagina() {
        const width = window.innerWidth;
        if (width < 480) return 4;
        if (width < 768) return 6;
        return 8;
    }
    
    calcularElementosMinimos() {
        const width = window.innerWidth;
        if (width < 480) return 4;
        if (width < 768) return 6;
        return 8;
    }
    
    // NUEVA FUNCIÓN: Obtener imagen cíclica para evitar placeholders
    getImagenCiclica(indice) {
        if (this.imagenes.length === 0) return null;
        return this.imagenes[indice % this.imagenes.length];
    }
    
    init() {
        this.crearEstructuraEstable();
        this.mostrarPagina(0);
        this.configurarEventos();
        
        // Solo iniciar auto-rotación si hay suficientes imágenes para mostrar diferentes páginas
        if (this.imagenes.length > this.imagenesPorPagina) {
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
                    this.totalPaginas = Math.max(1, Math.ceil(this.imagenes.length / this.imagenesPorPagina));
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
    
    // FUNCIÓN MODIFICADA: Siempre mostrar imágenes reales
    mostrarPagina(numeroPagina) {
        if (!this.contenedor || this.imagenes.length === 0) return;
        
        const items = this.contenedor.querySelectorAll('.galeria-item');
        
        // Animar salida de imágenes actuales
        items.forEach((item, index) => {
            const img = item.querySelector('img');
            
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
                
                // CAMBIO CLAVE: Calcular índice de imagen de forma cíclica
                const indiceImagen = (numeroPagina * this.imagenesPorPagina + index) % this.imagenes.length;
                const imagenSrc = this.getImagenCiclica(numeroPagina * this.imagenesPorPagina + index);
                
                if (img && imagenSrc) {
                    img.src = imagenSrc;
                    img.alt = `Momento especial ${indiceImagen + 1}`;
                    img.style.opacity = '0';
                    img.style.transform = 'scale(0.95)';
                    
                    // Manejar errores de carga
                    img.onerror = function() {
                        console.error('Error cargando imagen:', this.src);
                        // En caso de error, intentar con la primera imagen disponible
                        if (this.src !== this.imagenes[0]) {
                            this.src = this.imagenes[0];
                        }
                    };
                    
                    img.onload = function() {
                        this.style.display = 'block';
                    };
                    
                    // Hacer el item clickeable
                    item.onclick = () => this.abrirImagenModal(imagenSrc);
                    item.style.cursor = 'pointer';
                    
                    // ELIMINAR cualquier clase de placeholder
                    item.classList.remove('galeria-placeholder');
                    
                    // Animación de entrada
                    setTimeout(() => {
                        if (img) {
                            img.style.opacity = '1';
                            img.style.transform = 'scale(1)';
                        }
                    }, index * 100);
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
        // MODIFICACIÓN: Usar páginas infinitas basadas en imágenes disponibles
        const siguiente = (this.paginaActual + 1) % Math.max(1, Math.ceil(this.imagenes.length / this.imagenesPorPagina));
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
        // MODIFICACIÓN: Solo iniciar si hay suficientes imágenes para rotar
        if (this.imagenes.length <= this.imagenesPorPagina) return;
        
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
        if (!document.querySelector('.galeria-modal.active') && this.imagenes.length > this.imagenesPorPagina) {
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