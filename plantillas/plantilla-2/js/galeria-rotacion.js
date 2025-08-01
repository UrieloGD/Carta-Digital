// Galería con rotación automática
class GaleriaRotativa {
    constructor(imagenes, contenedor = 'galeria-grid') {
        this.imagenes = imagenes;
        this.contenedor = document.getElementById(contenedor);
        this.imagenesPorPagina = this.calcularImagenesPorPagina();
        this.paginaActual = 0;
        this.totalPaginas = Math.ceil(this.imagenes.length / this.imagenesPorPagina);
        this.autoRotacion = null;
        this.tiempoRotacion = 5000; // 5 segundos
        
        this.init();
    }
    
    calcularImagenesPorPagina() {
        const width = window.innerWidth;
        if (width < 480) return 3;
        if (width < 768) return 4;
        return 6;
    }
    
    init() {
        this.crearIndicadores();
        this.mostrarPagina(0);
        this.configurarEventos();
        this.iniciarAutoRotacion();
        
        // Recalcular en resize
        window.addEventListener('resize', () => {
            const nuevasImagenesPorPagina = this.calcularImagenesPorPagina();
            if (nuevasImagenesPorPagina !== this.imagenesPorPagina) {
                this.imagenesPorPagina = nuevasImagenesPorPagina;
                this.totalPaginas = Math.ceil(this.imagenes.length / this.imagenesPorPagina);
                this.paginaActual = 0;
                this.crearIndicadores();
                this.mostrarPagina(0);
            }
        });
    }
    
    crearIndicadores() {
        const indicadores = document.getElementById('galeria-indicators');
        if (!indicadores) return;
        
        indicadores.innerHTML = '';
        for (let i = 0; i < this.totalPaginas; i++) {
            const indicator = document.createElement('div');
            indicator.className = `galeria-indicator ${i === 0 ? 'active' : ''}`;
            indicator.addEventListener('click', () => this.irAPagina(i));
            indicadores.appendChild(indicator);
        }
    }
    
    mostrarPagina(numeroPagina) {
        // Limpiar contenedor
        this.contenedor.innerHTML = '';
        
        // Calcular rango de imágenes
        const inicio = numeroPagina * this.imagenesPorPagina;
        const fin = Math.min(inicio + this.imagenesPorPagina, this.imagenes.length);
        const imagenesActuales = this.imagenes.slice(inicio, fin);
        
        // Crear elementos con animación
        imagenesActuales.forEach((imagen, index) => {
            const item = document.createElement('div');
            item.className = 'galeria-item';
            item.innerHTML = `
                <div class="image-overlay"></div>
                <img src="${imagen}" alt="Momento especial ${inicio + index + 1}" />
            `;
            
            this.contenedor.appendChild(item);
            
            // Animación de entrada escalonada
            setTimeout(() => {
                item.classList.add('visible');
            }, index * 100);
        });
        
        // Actualizar indicadores
        this.actualizarIndicadores(numeroPagina);
        this.paginaActual = numeroPagina;
    }
    
    actualizarIndicadores(paginaActiva) {
        const indicadores = document.querySelectorAll('.galeria-indicator');
        indicadores.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === paginaActiva);
        });
    }
    
    siguientePagina() {
        const siguiente = (this.paginaActual + 1) % this.totalPaginas;
        this.irAPagina(siguiente);
    }
    
    paginaAnterior() {
        const anterior = this.paginaActual === 0 ? this.totalPaginas - 1 : this.paginaActual - 1;
        this.irAPagina(anterior);
    }
    
    irAPagina(numeroPagina) {
        if (numeroPagina === this.paginaActual) return;
        
        // Animación de salida
        const itemsActuales = this.contenedor.querySelectorAll('.galeria-item');
        itemsActuales.forEach(item => item.classList.add('exiting'));
        
        // Mostrar nueva página después de la animación
        setTimeout(() => {
            this.mostrarPagina(numeroPagina);
        }, 300);
        
        // Reiniciar auto-rotación
        this.reiniciarAutoRotacion();
    }
    
    configurarEventos() {
        const btnAnterior = document.getElementById('galeria-prev');
        const btnSiguiente = document.getElementById('galeria-next');
        
        if (btnAnterior) {
            btnAnterior.addEventListener('click', () => this.paginaAnterior());
        }
        
        if (btnSiguiente) {
            btnSiguiente.addEventListener('click', () => this.siguientePagina());
        }
        
        // Pausar auto-rotación al hacer hover
        this.contenedor.addEventListener('mouseenter', () => this.pausarAutoRotacion());
        this.contenedor.addEventListener('mouseleave', () => this.iniciarAutoRotacion());
    }
    
    iniciarAutoRotacion() {
        if (this.totalPaginas <= 1) return;
        
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
        new GaleriaRotativa(galeriaImagenes);
    }
});