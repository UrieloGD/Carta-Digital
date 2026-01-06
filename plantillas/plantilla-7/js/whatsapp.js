// Función para confirmar asistencia por WhatsApp
function confirmarAsistenciaWhatsApp() {
    // Obtener el número de teléfono de la configuración
    const numeroWhatsApp = window.numeroWhatsAppRSVP || '3339047672'; // Número por defecto
    
    // Crear el mensaje
    const mensaje = `Hola! confirmo mi asistencia a la boda de ${invitacionData.nombres}.`;
    const mensajeCodificado = encodeURIComponent(mensaje);
    
    // Abrir WhatsApp
    const urlWhatsApp = `https://wa.me/${numeroWhatsApp}?text=${mensajeCodificado}`;
    window.open(urlWhatsApp, '_blank');
    
    // Verificar si el RSVP está habilitado
    if (typeof RSVP_HABILITADO !== 'undefined' && !RSVP_HABILITADO) {
        mostrarModalFechaLimite();
        return;
    }
}

// Inicializar RSVP (función simplificada)
function initRSVP() {
    console.log('RSVP WhatsApp inicializado para número:', window.numeroWhatsAppRSVP);
}