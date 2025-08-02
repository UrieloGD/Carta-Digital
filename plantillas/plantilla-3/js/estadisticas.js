function registrarEstadistica(tipoEvento, datosAdicionales = {}) {
    fetch('./api/estadisticas.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            invitacion_id: invitacionData.id,
            tipo_evento: tipoEvento,
            datos_adicionales: datosAdicionales
        })
    });
}

function initEstadisticas() {
    // Registrar clics en galería
    document.querySelectorAll('.galeria-item img').forEach(img => {
        img.addEventListener('click', () => {
            registrarEstadistica('galeria_click');
        });
    });

    // Registrar clics en ubicaciones
    document.querySelectorAll('.ubicacion-maps').forEach(link => {
        link.addEventListener('click', () => {
            registrarEstadistica('ubicacion_click');
        });
    });
}