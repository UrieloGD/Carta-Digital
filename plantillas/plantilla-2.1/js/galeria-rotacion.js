// Galería con solo rotación automática y estructura estable
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
        
        console.log(`Galería automática: ${this.imagenes.length} imágenes, ${this.totalPaginas} páginas`);
        
        this.init();
    }
    
    calcularImagenesPorPagina() {
        const width = window.innerWidth;
        if (width < 480) return 3;
        if (width < 768) return 4;
        return 6;
    }
    
    // NUEVA FUNCIÓN: Obtener imagen cíclica para llenar espacios vacíos
    getImagenCiclica(indice) {
        if (this.imagenes.length === 0) return null;
        return this.imagenes[indice % this.imagenes.length];
    }
    
    init() {
        this.mostrarPagina(0);
        this.configurarEventos();
        
        // Iniciar auto-rotación si hay múltiples páginas
        if (this.totalPaginas > 1) {
            this.iniciarAutoRotacion();
        }
        
        // Recalcular en resize
        window.addEventListener('resize', () => {
            const nuevasImagenesPorPagina = this.calcularImagenesPorPagina();
            if (nuevasImagenesPorPagina !== this.imagenesPorPagina) {
                this.imagenesPorPagina = nuevasImagenesPorPagina;
                this.totalPaginas = Math.ceil(this.imagenes.length / this.imagenesPorPagina);
                this.paginaActual = 0;
                this.mostrarPagina(0);
                this.reiniciarAutoRotacion();
            }
        });
    }
    
    mostrarPagina(numeroPagina) {
        if (!this.contenedor) return;
        
        // CAMBIO PRINCIPAL: Siempre mostrar el número fijo de imágenes por página
        const imagenesAMostrar = this.imagenesPorPagina;
        
        // Animación de salida para imágenes actuales
        const itemsActuales = this.contenedor.querySelectorAll('.galeria-item');
        itemsActuales.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(-20px) scale(0.95)';
            }, index * 50);
        });
        
        // Cambiar contenido después de la animación de salida
        setTimeout(() => {
            this.contenedor.innerHTML = '';
            
            // MODIFICACIÓN: Crear elementos usando distribución cíclica
            for (let i = 0; i < imagenesAMostrar; i++) {
                const indiceImagen = (numeroPagina * this.imagenesPorPagina + i);
                const imagenSrc = this.getImagenCiclica(indiceImagen);
                
                const item = document.createElement('div');
                item.className = 'galeria-item';
                item.innerHTML = `
                    <div class="image-overlay"></div>
                    <img src="${imagenSrc}" alt="Momento especial ${(indiceImagen % this.imagenes.length) + 1}" 
                         onerror="console.error('Error cargando imagen:', this.src)" />
                `;
                
                this.contenedor.appendChild(item);
                
                // Animación de entrada escalonada (manteniendo la original)
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0) scale(1)';
                }, i * 100);
            }
        }, 300);
        
        this.paginaActual = numeroPagina;
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
                this.iniciarAutoRotacion();
            });
        }
    }
    
    iniciarAutoRotacion() {
        if (this.totalPaginas <= 1) return;
        
        this.pausarAutoRotacion(); // Limpiar cualquier intervalo existente
        
        this.autoRotacion = setInterval(() => {
            this.siguientePagina();
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
        this.iniciarAutoRotacion();
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    if (typeof galeriaImagenes !== 'undefined' && galeriaImagenes.length > 0) {
        setTimeout(() => {
            new GaleriaRotativa(galeriaImagenes);
        }, 100);
    }
});