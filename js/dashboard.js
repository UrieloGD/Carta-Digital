// dashboard.js - Funcionalidades del Dashboard de Invitaciones
class DashboardManager {
    constructor() {
        this.initializeEvents();
        this.startAutoRefresh();
    }

    // Inicializar eventos
    initializeEvents() {
        // Auto-focus en modales
        document.addEventListener('shown.bs.modal', function (e) {
            const firstInput = e.target.querySelector('input[type="text"], input[type="number"]');
            if (firstInput) firstInput.focus();
        });
    }

    // Copiar URL principal de invitación
    copiarURL() {
        const urlField = document.getElementById('invitacion-url');
        urlField.select();
        document.execCommand('copy');
        
        // Mostrar feedback
        const btn = event.target.closest('button');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Copiado!';
        btn.classList.replace('btn-primary', 'btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.replace('btn-success', 'btn-primary');
        }, 2000);
    }

    // Copiar token individual
    copiarToken(token) {
        navigator.clipboard.writeText(token).then(() => {
            // Mostrar feedback
            const btn = event.target.closest('button');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy"></i>';
                btn.classList.replace('btn-success', 'btn-outline-secondary');
            }, 2000);
        });
    }

    // Editar grupo de invitados
    editarGrupo(id, nombre, boletos) {
        document.getElementById('edit_id_grupo').value = id;
        document.getElementById('edit_nombre_grupo').value = nombre;
        document.getElementById('edit_boletos_asignados').value = boletos;
        
        const modal = new bootstrap.Modal(document.getElementById('modalEditarGrupo'));
        modal.show();
    }

    // Eliminar grupo con confirmación
    eliminarGrupo(id, nombre) {
        if (confirm(`¿Estás seguro de eliminar el grupo "${nombre}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="eliminar_grupo">
                <input type="hidden" name="id_grupo" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Compartir invitación (nueva función mejorada)
    compartirInvitacion(nombreGrupo, token) {
        // URL base sin token
        const baseUrl = window.dashboardConfig.invitacionUrl;
        
        // Llenar modal con información
        document.getElementById('grupo-nombre').textContent = nombreGrupo;
        document.getElementById('link-invitacion').value = baseUrl;
        document.getElementById('token-display').textContent = token;
        
        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalCompartir'));
        modal.show();
    }

    // Copiar link de invitación sin token
    copiarLinkInvitacion() {
        const urlField = document.getElementById('link-invitacion');
        urlField.select();
        document.execCommand('copy');
        
        // Feedback
        const btn = event.target;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.replace('btn-outline-primary', 'btn-success');
        
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-copy"></i>';
            btn.classList.replace('btn-success', 'btn-outline-primary');
        }, 1500);
    }

    // Copiar token del modal
    copiarTokenDisplay() {
        const token = document.getElementById('token-display').textContent;
        navigator.clipboard.writeText(token).then(() => {
            const btn = event.target.closest('button');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            btn.classList.replace('btn-outline-secondary', 'btn-success');
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy"></i>';
                btn.classList.replace('btn-success', 'btn-outline-secondary');
            }, 1500);
        });
    }

    // Compartir por WhatsApp
    compartirWhatsApp() {
        const nombreGrupo = document.getElementById('grupo-nombre').textContent;
        const url = document.getElementById('link-invitacion').value;
        const token = document.getElementById('token-display').textContent;
        const nombresNovios = window.dashboardConfig.nombresNovios;
        
        const mensaje = `¡Estás invitado a nuestra boda!

        ${nombresNovios}

        Confirma tu asistencia aquí:
        ${url}

        Tu código de acceso es: ${token}

        ¡Esperamos verte en nuestro día especial!`;

        const mensajeCodificado = encodeURIComponent(mensaje);
        window.open(`https://wa.me/?text=${mensajeCodificado}`, '_blank');
    }

    // Compartir por Telegram
    compartirTelegram() {
        const nombreGrupo = document.getElementById('grupo-nombre').textContent;
        const url = document.getElementById('link-invitacion').value;
        const token = document.getElementById('token-display').textContent;
        const nombresNovios = window.dashboardConfig.nombresNovios;
        
        const mensaje = `¡Estás invitado a nuestra boda!

        ${nombresNovios}

        Confirma tu asistencia aquí:
        ${url}

        Tu código de acceso es: ${token}

        ¡Esperamos verte en nuestro día especial!`;

        const mensajeCodificado = encodeURIComponent(mensaje);
        window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}&text=${mensajeCodificado}`, '_blank');
    }

    // Copiar mensaje completo
    copiarMensajeCompleto() {
        const nombreGrupo = document.getElementById('grupo-nombre').textContent;
        const url = document.getElementById('link-invitacion').value;
        const token = document.getElementById('token-display').textContent;
        const nombresNovios = window.dashboardConfig.nombresNovios;
        
        const mensaje = `¡Estás invitado a nuestra boda!

        ${nombresNovios}

        Confirma tu asistencia en: ${url}

        Tu código de acceso es: ${token}

        ¡Esperamos verte en nuestro día especial!`;

        navigator.clipboard.writeText(mensaje).then(() => {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2"></i>¡Copiado!';
            btn.classList.replace('btn-info', 'btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.replace('btn-success', 'btn-info');
            }, 2000);
        });
    }

    // Ver detalles de respuesta RSVP
    verDetallesRespuesta(id_grupo) {
        // Mostrar loading
        document.getElementById('detalles-respuesta-content').innerHTML = 
            '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando detalles...</div>';
        
        const modal = new bootstrap.Modal(document.getElementById('modalDetallesRespuesta'));
        modal.show();
        
        // Cargar detalles
        fetch(`./plantillas/plantilla-1/api/rsvp.php?action=cargar_respuesta&id_grupo=${id_grupo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.mostrarDetallesRespuesta(data.respuesta, data.nombres_invitados);
            } else {
                document.getElementById('detalles-respuesta-content').innerHTML = 
                    `<div class="alert alert-danger">Error: ${data.message}</div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('detalles-respuesta-content').innerHTML = 
                '<div class="alert alert-danger">Error al cargar los detalles</div>';
        });
    }

    // Mostrar detalles formateados de la respuesta
    mostrarDetallesRespuesta(respuesta, nombresInvitados) {
        const estadoTexto = {
            'aceptado': 'Confirmados',
            'rechazado': 'No asistirán',
            'pendiente': 'Sin respuesta'
        };

        let html = `
            <div class="card border-0">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-users me-2 text-primary"></i>
                        ${respuesta.nombre_grupo}
                    </h5>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="info-item mb-2">
                                <strong>Estado:</strong>
                                <span class="status-badge status-${respuesta.estado} ms-2">
                                    ${estadoTexto[respuesta.estado] || 'Sin respuesta'}
                                </span>
                            </div>
                            <div class="info-item mb-2">
                                <strong>Boletos asignados:</strong> ${respuesta.boletos_asignados}
                            </div>
                            ${respuesta.estado === 'aceptado' ? 
                                `<div class="info-item">
                                    <strong>Boletos confirmados:</strong> 
                                    <span class="badge bg-success">${respuesta.boletos_confirmados}</span>
                                </div>` : ''}
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Fecha de respuesta:</strong><br>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    ${new Date(respuesta.fecha_respuesta).toLocaleString('es-ES')}
                                </small>
                            </div>
                        </div>
                    </div>
        `;
        
        // Mostrar nombres de invitados confirmados
        if (nombresInvitados && nombresInvitados.length > 0) {
            html += `
                <hr>
                <div class="mb-3">
                    <strong>Invitados confirmados:</strong>
                    <ul class="list-unstyled mt-2">`;
            
            nombresInvitados.forEach(nombre => {
                html += `
                    <li class="mb-1">
                        <i class="fas fa-user me-2 text-primary"></i>
                        ${nombre}
                    </li>`;
            });
            
            html += `</ul></div>`;
        }
        
        // Mostrar comentarios si existen
        if (respuesta.comentarios && respuesta.comentarios.trim()) {
            html += `
                <hr>
                <div class="mb-3">
                    <strong>Comentarios:</strong>
                    <div class="alert alert-light mt-2">
                        <i class="fas fa-quote-left me-2"></i>
                        ${respuesta.comentarios}
                    </div>
                </div>`;
        }
        
        html += `</div></div>`;
        
        document.getElementById('detalles-respuesta-content').innerHTML = html;
    }

    // Auto-refresh de estadísticas
    startAutoRefresh() {
        setInterval(() => {
            this.refreshStats();
        }, 30000); // Cada 30 segundos
    }

    // Refrescar estadísticas automáticamente
    refreshStats() {
        const url = new URL(window.location.href);
        url.searchParams.set('ajax', '1');
        
        fetch(url.toString())
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Actualizar números de estadísticas con animación
                const statsNumbers = document.querySelectorAll('.stats-number');
                const newStatsNumbers = doc.querySelectorAll('.stats-number');
                
                statsNumbers.forEach((stat, index) => {
                    if (newStatsNumbers[index] && stat.textContent !== newStatsNumbers[index].textContent) {
                        // Animación de cambio
                        stat.style.animation = 'none';
                        stat.offsetHeight; // Forzar reflow
                        stat.textContent = newStatsNumbers[index].textContent;
                        stat.style.animation = 'pulse 0.6s ease-in-out';
                    }
                });
                
                // Actualizar tabla si hay cambios
                const currentTableBody = document.querySelector('tbody');
                const newTableBody = doc.querySelector('tbody');
                if (newTableBody && currentTableBody && 
                    currentTableBody.innerHTML !== newTableBody.innerHTML) {
                    currentTableBody.innerHTML = newTableBody.innerHTML;
                }
            })
            .catch(error => console.error('Error refreshing stats:', error));
    }
}

// Funciones globales para compatibilidad con el HTML existente
let dashboardManager;

document.addEventListener('DOMContentLoaded', function() {
    dashboardManager = new DashboardManager();
    
    // Exponer funciones globalmente para el HTML
    window.copiarURL = () => dashboardManager.copiarURL();
    window.copiarToken = (token) => dashboardManager.copiarToken(token);
    window.editarGrupo = (id, nombre, boletos) => dashboardManager.editarGrupo(id, nombre, boletos);
    window.eliminarGrupo = (id, nombre) => dashboardManager.eliminarGrupo(id, nombre);
    window.compartirInvitacion = (nombre, token) => dashboardManager.compartirInvitacion(nombre, token);
    window.verDetallesRespuesta = (id) => dashboardManager.verDetallesRespuesta(id);
    window.copiarLinkInvitacion = () => dashboardManager.copiarLinkInvitacion();
    window.copiarTokenDisplay = () => dashboardManager.copiarTokenDisplay();
    window.compartirWhatsApp = () => dashboardManager.compartirWhatsApp();
    window.compartirTelegram = () => dashboardManager.compartirTelegram();
    window.copiarMensajeCompleto = () => dashboardManager.copiarMensajeCompleto();
});