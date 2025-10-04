// crear.js - JavaScript para el formulario de creación de invitaciones (actualizado con SweetAlert2)

// Generar slug automáticamente desde los nombres de los novios
function initSlugGeneration() {
    const nombresInput = document.getElementById('nombres_novios');
    const slugInput = document.getElementById('slug');
    
    if (nombresInput && slugInput) {
        nombresInput.addEventListener('input', function() {
            const nombres = this.value;
            const slug = nombres
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '') // Remover caracteres especiales
                .replace(/\s+/g, '-') // Reemplazar espacios con guiones
                .replace(/-+/g, '-') // Reemplazar múltiples guiones con uno solo
                .trim('-'); // Remover guiones al inicio y final
            
            slugInput.value = slug;
        });
    }
}

// Validación del número de WhatsApp
function initWhatsAppValidation() {
    const whatsappInput = document.getElementById('whatsapp_confirmacion');
    if (whatsappInput) {
        whatsappInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Solo números
            if (value.length > 15) {
                value = value.substring(0, 15); // Máximo 15 dígitos
            }
            e.target.value = value;
           
            // Cambiar el color del borde según la validez
            if (value.length >= 10 && value.length <= 15) {
                e.target.style.borderColor = '#28a745'; // Verde si es válido
            } else if (value.length > 0) {
                e.target.style.borderColor = '#dc3545'; // Rojo si es inválido
            } else {
                e.target.style.borderColor = ''; // Default si está vacío
            }
        });
    }
}

// Función para previsualizar imágenes individuales
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId + '-img');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Función para previsualizar galería de imágenes
function previewGallery(input) {
    const preview = document.getElementById('gallery-preview');
    preview.innerHTML = '';
   
    if (input.files) {
        Array.from(input.files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-3 mb-3';
                    col.innerHTML = `
                        <div class="card">
                            <img src="${e.target.result}" class="card-img-top" style="height: 120px; object-fit: cover;">
                            <div class="card-body p-2">
                                <small class="text-muted">${file.name}</small>
                            </div>
                        </div>
                    `;
                    preview.appendChild(col);
                }
                reader.readAsDataURL(file);
            }
        });
    }
}

// Validaciones de fechas
function initDateValidation() {
    const fechaLimiteRsvp = document.getElementById('fecha_limite_rsvp');
    const fechaEvento = document.getElementById('fecha_evento');

    if (fechaLimiteRsvp && fechaEvento) {
        // Validar que la fecha límite RSVP no sea posterior a la fecha del evento
        fechaLimiteRsvp.addEventListener('change', function() {
            const fechaEventoValue = fechaEvento.value;
            const fechaLimiteValue = this.value;
           
            if (fechaEventoValue && fechaLimiteValue && fechaLimiteValue > fechaEventoValue) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha inválida',
                    text: 'La fecha límite para RSVP no puede ser posterior a la fecha del evento',
                    confirmButtonColor: '#3085d6'
                });
                this.value = '';
            }
        });

        fechaEvento.addEventListener('change', function() {
            const fechaLimiteValue = fechaLimiteRsvp.value;
            const fechaEventoValue = this.value;
           
            if (fechaEventoValue && fechaLimiteValue && fechaLimiteValue > fechaEventoValue) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha inválida',
                    text: 'La fecha límite para RSVP no puede ser posterior a la fecha del evento',
                    confirmButtonColor: '#3085d6'
                });
                fechaLimiteRsvp.value = '';
            }
        });
    }
}

// Función para agregar elementos al cronograma
function agregarCronograma() {
    const container = document.getElementById('cronograma-container');
    const mostrarCronograma = document.getElementById('mostrar_cronograma');
    const cronogramaContent = document.getElementById('cronograma-content');
    
    // Solo agregar si el cronograma está visible y activado
    if (mostrarCronograma && mostrarCronograma.checked && cronogramaContent.style.display !== 'none') {
        const newItem = document.createElement('div');
        newItem.className = 'cronograma-item';
        newItem.innerHTML = `
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Hora</label>
                    <input type="time" name="cronograma_hora[]" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Evento</label>
                    <input type="text" name="cronograma_evento[]" class="form-control" placeholder="Evento">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="cronograma_descripcion[]" class="form-control" placeholder="Descripción">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Icono</label>
                    <select name="cronograma_icono[]" class="form-select">
                        <option value="anillos">Anillos</option>
                        <option value="cena">Cena</option>
                        <option value="fiesta">Fiesta</option>
                        <option value="luna">Luna</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" onclick="eliminarCronograma(this)" class="btn btn-outline-danger btn-sm mt-2">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(newItem);
    } else {
        Swal.fire({
            icon: 'info',
            title: 'Cronograma desactivado',
            text: 'Activa la sección de cronograma primero para agregar eventos.',
            confirmButtonColor: '#3085d6'
        });
    }
}

// Función para eliminar elementos del cronograma
function eliminarCronograma(button) {
    Swal.fire({
        title: '¿Eliminar evento?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            button.closest('.cronograma-item').remove();
            Swal.fire({
                title: 'Eliminado',
                text: 'Evento eliminado del cronograma',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

// Función para mostrar/ocultar campos según el tipo de RSVP
function toggleRSVPFields() {
    const tipoRsvp = document.getElementById('tipo_rsvp');
    const campoWhatsapp = document.getElementById('campo-whatsapp');
    const inputWhatsapp = document.getElementById('whatsapp_confirmacion');
    
    if (tipoRsvp && campoWhatsapp && inputWhatsapp) {
        if (tipoRsvp.value === 'whatsapp') {
            campoWhatsapp.style.display = 'block';
            inputWhatsapp.required = true;
            // Remover atributos que podrían causar problemas
            inputWhatsapp.removeAttribute('disabled');
            inputWhatsapp.removeAttribute('readonly');
        } else {
            campoWhatsapp.style.display = 'none';
            inputWhatsapp.required = false;
            // Limpiar el valor cuando no es WhatsApp
            inputWhatsapp.value = '';
        }
    }
}

// Función para mostrar/ocultar campos del contador
function toggleContadorFields() {
    const mostrarContador = document.getElementById('mostrar_contador');
    const tipoContador = document.getElementById('tipo_contador');
    
    if (mostrarContador && tipoContador) {
        tipoContador.disabled = !mostrarContador.checked;
        
        // Cambiar estilo visual cuando está deshabilitado
        if (tipoContador.disabled) {
            tipoContador.classList.add('text-muted', 'bg-light');
        } else {
            tipoContador.classList.remove('text-muted', 'bg-light');
        }
    }
}

function toggleCronogramaFields() {
    const mostrarCronograma = document.getElementById('mostrar_cronograma');
    const cronogramaContent = document.getElementById('cronograma-content');
    
    if (mostrarCronograma && cronogramaContent) {
        if (mostrarCronograma.checked) {
            cronogramaContent.style.display = 'block';
            // Habilitar todos los campos dentro del cronograma
            const inputs = cronogramaContent.querySelectorAll('input, select, button');
            inputs.forEach(input => {
                input.disabled = false;
                input.classList.remove('text-muted', 'bg-light');
            });
        } else {
            cronogramaContent.style.display = 'none';
            // Deshabilitar todos los campos dentro del cronograma
            const inputs = cronogramaContent.querySelectorAll('input, select, button');
            inputs.forEach(input => {
                input.disabled = true;
                input.classList.add('text-muted', 'bg-light');
            });
        }
    }
}

// Validación del formulario
function initFormValidation() {
    const form = document.querySelector('form');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const slug = document.getElementById('slug').value;
            const regex = /^[a-z0-9-]+$/;
            
            if (!regex.test(slug)) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'URL inválida',
                    text: 'La URL (slug) solo puede contener letras minúsculas, números y guiones',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    document.getElementById('slug').focus();
                });
                return false;
            }
        });
    }
}

// Mostrar alerta de éxito si hay parámetro en la URL
function showSuccessAlert() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        Swal.fire({
            title: '¡Éxito!',
            text: 'Invitación creada correctamente',
            icon: 'success',
            timer: 3000,
            showConfirmButton: false,
            position: 'top-end',
            toast: true
        });
    }
}

// Mostrar alerta de error si hay error del servidor
function showErrorAlert() {
    const errorElement = document.querySelector('.error-alert');
    if (errorElement) {
        const errorText = errorElement.querySelector('p').textContent;
        Swal.fire({
            title: 'Error',
            text: errorText,
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {    
    // Inicializar todas las funciones
    initSlugGeneration();
    initWhatsAppValidation();
    initDateValidation();
    initFormValidation();
    
    // Inicializar toggles
    toggleRSVPFields();
    toggleContadorFields();
    toggleCronogramaFields();
    
    // Mostrar alertas
    showSuccessAlert();
    showErrorAlert();
    
    // Agregar eventos
    const tipoRsvp = document.getElementById('tipo_rsvp');
    const mostrarContador = document.getElementById('mostrar_contador');
    const mostrarCronograma = document.getElementById('mostrar_cronograma');
    
    if (tipoRsvp) tipoRsvp.addEventListener('change', toggleRSVPFields);
    if (mostrarContador) mostrarContador.addEventListener('change', toggleContadorFields);
    if (mostrarCronograma) mostrarCronograma.addEventListener('change', toggleCronogramaFields);
});