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