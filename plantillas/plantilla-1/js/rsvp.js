// Variables globales
let grupoActual = null;
let respuestaExistente = null;

// Abrir modal RSVP
function openRSVPModal() {
    document.getElementById('rsvpModal').style.display = 'flex';
    resetRSVPModal();
}

// Cerrar modal RSVP
function closeRSVPModal() {
    document.getElementById('rsvpModal').style.display = 'none';
    resetRSVPModal();
}

// Resetear modal al estado inicial
function resetRSVPModal() {
    // Ocultar todos los pasos
    document.querySelectorAll('.rsvp-step').forEach(step => {
        step.style.display = 'none';
    });
    
    // Mostrar solo el paso de código
    document.getElementById('step-codigo').style.display = 'block';
    
    // Limpiar formularios
    document.getElementById('codigoForm').reset();
    document.getElementById('rsvpForm').reset();
    
    // Limpiar alertas
    document.getElementById('codigo-alert').innerHTML = '';
    document.getElementById('form-alert').innerHTML = '';
    
    // Resetear variables globales
    grupoActual = null;
    respuestaExistente = null;
}

// Toggle campos de asistencia
function toggleAsistenciaFields() {
    const estado = document.getElementById('estado').value;
    const camposAsistencia = document.getElementById('campos-asistencia');
    
    if (estado === 'aceptado') {
        camposAsistencia.style.display = 'block';
        updateNombresFields(); // Generar campos iniciales
    } else {
        camposAsistencia.style.display = 'none';
        document.getElementById('nombres-container').innerHTML = '';
    }
}

// Función para generar campos de nombres dinámicamente
function updateNombresFields() {
    const boletos = parseInt(document.getElementById('boletos_confirmados').value);
    const container = document.getElementById('nombres-container');
    
    container.innerHTML = '';
    
    if (boletos > 0) {
        for (let i = 1; i <= boletos; i++) {
            const div = document.createElement('div');
            div.className = 'form-group';
            div.innerHTML = `
                <label for="nombre_invitado_${i}">Nombre y apellido del invitado ${i} *</label>
                <input type="text" id="nombre_invitado_${i}" name="nombre_invitado_${i}" required
                       placeholder="Nombre completo del invitado">
            `;
            container.appendChild(div);
        }
    }
}

// Volver al paso de código
function volverACodigo() {
    document.getElementById('step-formulario').style.display = 'none';
    document.getElementById('step-confirmacion').style.display = 'none';
    document.getElementById('step-ver-respuesta').style.display = 'none';
    document.getElementById('step-codigo').style.display = 'block';
    
    // Limpiar formulario
    document.getElementById('rsvpForm').reset();
    document.getElementById('form-alert').innerHTML = '';
}

// Función para mostrar paso de confirmación
function mostrarConfirmacion() {
    const estado = document.getElementById('estado').value;
    const boletos = parseInt(document.getElementById('boletos_confirmados').value) || 0;
    const comentarios = document.getElementById('comentarios').value.trim();
    
    // Validar que se ingresaron todos los nombres si acepta asistir
    if (estado === 'aceptado' && boletos > 0) {
        for (let i = 1; i <= boletos; i++) {
            const nombre = document.getElementById(`nombre_invitado_${i}`).value.trim();
            if (!nombre) {
                document.getElementById('form-alert').innerHTML = 
                    '<div class="alert alert-danger">Por favor ingresa el nombre completo de todos los invitados</div>';
                return false;
            }
        }
    }
    
    let html = `
        <div class="confirmacion-grupo">
            <h5>Grupo: ${grupoActual.nombre_grupo}</h5>
        </div>
        
        <div class="confirmacion-detalle">
            <p><strong>Estado:</strong> ${estado === 'aceptado' ? 'Sí asistiremos' : 'No podremos asistir'}</p>
    `;
    
    if (estado === 'aceptado') {
        html += `<p><strong>Número de boletos:</strong> ${boletos}</p>`;
        
        if (boletos > 0) {
            html += `<div class="invitados-confirmados">
                <p><strong>Invitados confirmados:</strong></p>
                <ul>`;
            
            for (let i = 1; i <= boletos; i++) {
                const nombre = document.getElementById(`nombre_invitado_${i}`).value.trim();
                html += `<li>${nombre}</li>`;
            }
            
            html += `</ul></div>`;
        }
    }
    
    if (comentarios) {
        html += `<p><strong>Comentarios:</strong> ${comentarios}</p>`;
    }
    
    html += `</div>`;
    
    document.getElementById('confirmacion-info').innerHTML = html;
    
    // Mostrar paso de confirmación
    document.getElementById('step-formulario').style.display = 'none';
    document.getElementById('step-confirmacion').style.display = 'block';
    
    return true;
}

// Función para volver al formulario
function volverAFormulario() {
    document.getElementById('step-confirmacion').style.display = 'none';
    document.getElementById('step-formulario').style.display = 'block';
}

// Función para enviar confirmación final
function enviarConfirmacion() {
    const formData = new FormData(document.getElementById('rsvpForm'));
    formData.append('action', 'guardar_rsvp');
    
    // Mostrar loading
    const btnEnviar = document.querySelector('#step-confirmacion .btn-primary');
    const textoOriginal = btnEnviar.textContent;
    btnEnviar.textContent = 'Enviando...';
    btnEnviar.disabled = true;
    
    fetch('./plantillas/plantilla-1/api/rsvp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarExito(data.message, data.data);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al enviar la confirmación');
    })
    .finally(() => {
        btnEnviar.textContent = textoOriginal;
        btnEnviar.disabled = false;
    });
}

// Función para mostrar pantalla de éxito
function mostrarExito(mensaje, datos) {
    document.getElementById('mensaje-exito').textContent = 
        datos.estado === 'aceptado' ? 
        '¡Muchas gracias por confirmar tu asistencia!' : 
        'Gracias por informarnos. Lamentamos que no puedan acompañarnos.';
    
    let resumenHtml = `
        <div class="resumen-grupo">
            <h5>Grupo: ${grupoActual.nombre_grupo}</h5>
        </div>
        <div class="resumen-detalle">
            <p><strong>Estado:</strong> ${datos.estado === 'aceptado' ? 'Confirmados' : 'No asistirán'}</p>
    `;
    
    if (datos.estado === 'aceptado' && datos.nombres_invitados && datos.nombres_invitados.length > 0) {
        resumenHtml += `
            <p><strong>Invitados (${datos.boletos_confirmados}):</strong></p>
            <ul>`;
        
        datos.nombres_invitados.forEach(nombre => {
            resumenHtml += `<li>${nombre}</li>`;
        });
        
        resumenHtml += `</ul>`;
    }
    
    if (datos.comentarios) {
        resumenHtml += `<p><strong>Comentarios:</strong> ${datos.comentarios}</p>`;
    }
    
    resumenHtml += `</div>`;
    
    document.getElementById('resumen-final').innerHTML = resumenHtml;
    
    // Mostrar paso de éxito
    document.getElementById('step-confirmacion').style.display = 'none';
    document.getElementById('step-exito').style.display = 'block';
}

// Función para mostrar respuesta existente
function mostrarRespuestaExistente(grupo, respuestaExistente) {
    // Cargar detalles completos de la respuesta
    fetch(`./plantillas/plantilla-1/api/rsvp.php?action=cargar_respuesta&id_grupo=${grupo.id_grupo}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const respuesta = data.respuesta;
            const nombresInvitados = data.nombres_invitados;
            
            let html = `
                <div class="respuesta-info">
                    <h5>Grupo: ${respuesta.nombre_grupo}</h5>
                    <p><strong>Estado:</strong> ${respuesta.estado === 'aceptado' ? 'Confirmados' : 'No asistirán'}</p>
            `;
            
            if (respuesta.estado === 'aceptado') {
                html += `<p><strong>Boletos confirmados:</strong> ${respuesta.boletos_confirmados}</p>`;
                
                if (nombresInvitados && nombresInvitados.length > 0) {
                    html += `<div class="invitados-lista">
                        <p><strong>Invitados:</strong></p>
                        <ul>`;
                    
                    nombresInvitados.forEach(nombre => {
                        html += `<li>${nombre}</li>`;
                    });
                    
                    html += `</ul></div>`;
                }
            }
            
            if (respuesta.comentarios) {
                html += `<p><strong>Comentarios:</strong> ${respuesta.comentarios}</p>`;
            }
            
            html += `<p><small>Respondido el: ${new Date(respuesta.fecha_respuesta).toLocaleDateString()}</small></p>
                </div>`;
            
            document.getElementById('respuesta-existente').innerHTML = html;
            
            // Mostrar paso de ver respuesta
            document.getElementById('step-codigo').style.display = 'none';
            document.getElementById('step-ver-respuesta').style.display = 'block';
        } else {
            console.error('Error cargando respuesta:', data.message);
            // Mostrar información básica si falla
            mostrarRespuestaBasica(grupo, respuestaExistente);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarRespuestaBasica(grupo, respuestaExistente);
    });
}

function mostrarRespuestaBasica(grupo, respuesta) {
    let html = `
        <div class="respuesta-info">
            <h5>Grupo: ${grupo.nombre_grupo}</h5>
            <p><strong>Estado:</strong> ${respuesta.estado === 'aceptado' ? 'Confirmados' : 'No asistirán'}</p>
    `;
    
    if (respuesta.estado === 'aceptado' && respuesta.boletos_confirmados) {
        html += `<p><strong>Boletos confirmados:</strong> ${respuesta.boletos_confirmados}</p>`;
    }
    
    if (respuesta.comentarios) {
        html += `<p><strong>Comentarios:</strong> ${respuesta.comentarios}</p>`;
    }
    
    html += `<p><small>Respondido el: ${new Date(respuesta.fecha_respuesta).toLocaleDateString()}</small></p>
        </div>`;
    
    document.getElementById('respuesta-existente').innerHTML = html;
    
    document.getElementById('step-codigo').style.display = 'none';
    document.getElementById('step-ver-respuesta').style.display = 'block';
}

// Función para editar respuesta existente
function editarRespuesta() {
    if (!grupoActual) {
        alert('Error: No se encontró la información del grupo');
        return;
    }
    
    // Cargar datos existentes en el formulario
    fetch(`./plantillas/plantilla-1/api/rsvp.php?action=cargar_respuesta&id_grupo=${grupoActual.id_grupo}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const respuesta = data.respuesta;
            const nombresInvitados = data.nombres_invitados;
            
            // Llenar formulario con datos existentes
            document.getElementById('nombre-grupo').textContent = grupoActual.nombre_grupo;
            document.getElementById('boletos-info').textContent = `Boletos asignados: ${grupoActual.boletos_asignados}`;
            document.getElementById('id_grupo').value = grupoActual.id_grupo;
            
            // Llenar select de boletos
            const select = document.getElementById('boletos_confirmados');
            select.innerHTML = '';
            for (let i = 1; i <= grupoActual.boletos_asignados; i++) {
                const selected = i === respuesta.boletos_confirmados ? 'selected' : '';
                select.innerHTML += `<option value="${i}" ${selected}>${i}</option>`;
            }
            
            // Establecer estado
            document.getElementById('estado').value = respuesta.estado;
            
            // Establecer comentarios
            document.getElementById('comentarios').value = respuesta.comentarios || '';
            
            // Mostrar campos de asistencia si es necesario
            toggleAsistenciaFields();
            
            // Si acepta asistencia, llenar nombres
            if (respuesta.estado === 'aceptado' && nombresInvitados.length > 0) {
                setTimeout(() => {
                    nombresInvitados.forEach((nombre, index) => {
                        const campo = document.getElementById(`nombre_invitado_${index + 1}`);
                        if (campo) {
                            campo.value = nombre;
                        }
                    });
                }, 100);
            }
            
            // Mostrar formulario
            document.getElementById('step-ver-respuesta').style.display = 'none';
            document.getElementById('step-formulario').style.display = 'block';
        } else {
            alert('Error al cargar los datos: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cargar los datos de la respuesta');
    });
}

// Event listener para validar código
document.getElementById('codigoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'validar_codigo');
    
    // Limpiar alertas previas
    document.getElementById('codigo-alert').innerHTML = '';
    
    // Mostrar loading
    const btnValidar = this.querySelector('.form-submit');
    const textoOriginal = btnValidar.textContent;
    btnValidar.textContent = 'Validando...';
    btnValidar.disabled = true;
    
    fetch('./plantillas/plantilla-1/api/rsvp.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            grupoActual = data.grupo;
            respuestaExistente = data.respuesta_existente;
            
            if (respuestaExistente) {
                // Mostrar respuesta existente
                mostrarRespuestaExistente(data.grupo, respuestaExistente);
            } else {
                // Mostrar formulario nuevo
                document.getElementById('nombre-grupo').textContent = data.grupo.nombre_grupo;
                document.getElementById('boletos-info').textContent = 
                    `Boletos asignados: ${data.grupo.boletos_asignados}`;
                document.getElementById('id_grupo').value = data.grupo.id_grupo;
                
                // Llenar select de boletos
                const select = document.getElementById('boletos_confirmados');
                select.innerHTML = '';
                for (let i = 1; i <= data.grupo.boletos_asignados; i++) {
                    select.innerHTML += `<option value="${i}">${i}</option>`;
                }
                
                document.getElementById('step-codigo').style.display = 'none';
                document.getElementById('step-formulario').style.display = 'block';
            }
        } else {
            document.getElementById('codigo-alert').innerHTML = 
                `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('codigo-alert').innerHTML = 
            '<div class="alert alert-danger">Error de conexión. Por favor intenta de nuevo.</div>';
    })
    .finally(() => {
        btnValidar.textContent = textoOriginal;
        btnValidar.disabled = false;
    });
});

// Event listener para el formulario principal
document.getElementById('rsvpForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validar formulario y mostrar confirmación
    if (mostrarConfirmacion()) {
        // La función mostrarConfirmacion maneja la validación y cambio de paso
    }
});

// Cerrar modal al hacer clic fuera de él
document.getElementById('rsvpModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeRSVPModal();
    }
});

// Prevenir cierre del modal al hacer clic dentro del contenido
document.querySelector('.modal-content').addEventListener('click', function(e) {
    e.stopPropagation();
});