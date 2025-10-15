// mesa-regalos.js - Manejo de interacciones para la sección de Mesa de Regalos

document.addEventListener('DOMContentLoaded', function() {
    inicializarMesaRegalos();
});

function inicializarMesaRegalos() {
    const regaloCards = document.querySelectorAll('.regalo-card');
    
    console.log('Tarjetas de regalos encontradas:', regaloCards.length); // Debug
    
    regaloCards.forEach((card, index) => {
        const url = card.getAttribute('data-url');
        
        console.log(`Tarjeta ${index + 1}:`, { url: url, tiene_url: !!url }); // Debug
        
        if (url) {
            card.style.cursor = 'pointer';
            
            card.addEventListener('click', function(e) {
                e.preventDefault();
                abrirEnlaceMesaRegalos(url, card);
            });
        }
    });
}

function abrirEnlaceMesaRegalos(url, cardElement) {
    // Efecto visual de click
    cardElement.style.transform = 'translateY(-4px) scale(0.98)';
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
        
        fetch('./plantillas/plantilla-2/api/estadisticas.php', {
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

// Exportar funciones para uso global
window.mesaRegalos = {
    inicializar: inicializarMesaRegalos,
    registrarClick: registrarClickMesaRegalos
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarMesaRegalos);
} else {
    inicializarMesaRegalos();
}