// mesa-regalos.js - Plantilla 4

document.addEventListener('DOMContentLoaded', function() {
    inicializarMesaRegalos();
});

function inicializarMesaRegalos() {
    const regaloCards = document.querySelectorAll('.regalo-card');
    
    regaloCards.forEach((card, index) => {
        const url = card.getAttribute('href');
        
        if (url) {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                abrirEnlaceMesaRegalos(url, card);
            });
        }
    });
}

function abrirEnlaceMesaRegalos(url, cardElement) {
    // Efecto visual de click
    cardElement.style.transform = 'translateY(-8px) scale(0.98)';
    setTimeout(() => {
        cardElement.style.transform = '';
    }, 150);
    
    // Abrir enlace en nueva pestaña
    window.open(url, '_blank', 'noopener,noreferrer');
    
    // Registrar el click en estadísticas
    registrarClickMesaRegalos(url);
}

function registrarClickMesaRegalos(urlTienda) {
    if (typeof invitacionData !== 'undefined') {
        const datosEstadisticas = {
            invitacion_id: invitacionData.id,
            tipo_evento: 'mesa_regalos_click',
            datos_adicionales: {
                url_tienda: urlTienda,
                timestamp: new Date().toISOString(),
                user_agent: navigator.userAgent
            }
        };
        
        fetch('./plantillas/plantilla-4/api/estadisticas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(datosEstadisticas)
        })
        .catch(error => {
            console.warn('No se pudo registrar el click en mesa de regalos:', error);
        });
    }
}

// Exportar funciones
window.mesaRegalos = {
    inicializar: inicializarMesaRegalos,
    registrarClick: registrarClickMesaRegalos
};