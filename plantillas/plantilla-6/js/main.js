// Variables globales para JavaScript
const invitacionData = {
    id: null, // Se asignará desde PHP
    nombres: '',
    fecha: '',
    hora: '',
    mostrarContador: false,
    musicaUrl: '',
    musicaAutoplay: false
};

// Función principal de inicialización
function initInvitacion() {
    // Inicializar contador si está habilitado
    if (invitacionData.mostrarContador) {
        initCountdown();
    }
    
    // Inicializar RSVP
    initRSVP();
    
    // Inicializar estadísticas
    initEstadisticas();
    
    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        const modal = document.getElementById('rsvpModal');
        if (event.target === modal) {
            closeRSVPModal();
        }
    }
    
    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRSVPModal();
        }
    });
}

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', initInvitacion);

/* ================================================
   PAUSA ANIMACIONES FUERA DEL VIEWPORT
   ================================================ */
document.addEventListener('DOMContentLoaded', () => {

    // Todas las secciones que tienen animaciones pesadas
    const animatedSections = document.querySelectorAll(
        '.contador, .rsvp, .footer, .hero, .detalles, .itinerario, .dresscode, .galeria'
    );

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            // Selecciona TODOS los elementos animados dentro de la sección
            const animated = entry.target.querySelectorAll(
                '[class*="dots"] span, ' +
                '[class*="sparkles"], ' +
                '[class*="orb"], ' +
                '.contador-unit, ' +
                '.contador-number, ' +
                '.contador-separator, ' +
                '.rsvp-button, ' +
                '.music-player-widget'
            );

            const state = entry.isIntersecting ? 'running' : 'paused';
            animated.forEach(el => el.style.animationPlayState = state);

            // También pausa/reanuda animaciones en ::before y ::after
            // aplicando la clase directamente a la sección
            if (entry.isIntersecting) {
                entry.target.classList.remove('animations-paused');
            } else {
                entry.target.classList.add('animations-paused');
            }
        });
    }, {
        threshold: 0.05  // Se activa cuando el 5% de la sección es visible
    });

    animatedSections.forEach(section => observer.observe(section));
});