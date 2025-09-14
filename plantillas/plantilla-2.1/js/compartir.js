function shareWhatsApp() {
    const url = window.location.href;
    const texto = encodeURIComponent(`¡Estás invitado a la boda de ${invitacionData.nombres}! ${url}`);
    window.open(`https://wa.me/?text=${texto}`, '_blank');
    
    // Registrar estadística
    fetch('./api/estadisticas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            invitacion_id: invitacionData.id,
            tipo_evento: 'compartir',
            datos_adicionales: {tipo: 'whatsapp'}
        })
    });
}

function copyLink() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(window.location.href).then(() => {
            showTemporaryMessage('¡Enlace copiado al portapapeles!');
        }).catch(() => {
            fallbackCopyLink();
        });
    } else {
        fallbackCopyLink();
    }
    
    // Registrar estadística
    fetch('./api/estadisticas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            invitacion_id: invitacionData.id,
            tipo_evento: 'compartir',
            datos_adicionales: {tipo: 'copy_link'}
        })
    });
}

// Fallback para copiar enlace en navegadores antiguos
function fallbackCopyLink() {
    const textArea = document.createElement('textarea');
    textArea.value = window.location.href;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showTemporaryMessage('¡Enlace copiado al portapapeles!');
    } catch (err) {
        showTemporaryMessage('No se pudo copiar el enlace. Inténtalo manualmente.');
    }
    
    document.body.removeChild(textArea);
}