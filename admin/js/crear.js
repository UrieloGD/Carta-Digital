// crear.js - JavaScript para el formulario de creación de invitaciones

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
function initDateValidations() {
    const fechaEventoInput = document.getElementById('fecha_evento');
    const fechaLimiteInput = document.getElementById('fecha_limite_rsvp');
    
    function validateDates() {
        const fechaEvento = fechaEventoInput.value;
        const fechaLimite = fechaLimiteInput.value;
        
        if (fechaEvento && fechaLimite && fechaLimite > fechaEvento) {
            alert('La fecha límite para RSVP no puede ser posterior a la fecha del evento');
            fechaLimiteInput.value = '';
        }
    }
    
    if (fechaLimiteInput) {
        fechaLimiteInput.addEventListener('change', validateDates);
    }
    
    if (fechaEventoInput) {
        fechaEventoInput.addEventListener('change', validateDates);
    }
}

// Función para agregar elementos al cronograma
function agregarCronograma() {
    const container = document.getElementById('cronograma-container');
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
}

// Función para eliminar elementos del cronograma
function eliminarCronograma(button) {
    button.closest('.cronograma-item').remove();
}

// Función para mostrar/ocultar campos según el tipo de RSVP
function toggleRSVPFields() {
    const tipoRsvp = document.getElementById('tipo_rsvp').value;
    const campoWhatsapp = document.getElementById('campo-whatsapp');
    const inputWhatsapp = document.getElementById('whatsapp_confirmacion');
    
    if (tipoRsvp === 'whatsapp') {
        campoWhatsapp.style.display = 'block';
        inputWhatsapp.required = true;
    } else {
        campoWhatsapp.style.display = 'none';
        inputWhatsapp.required = false;
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
                alert('La URL (slug) solo puede contener letras minúsculas, números y guiones');
                document.getElementById('slug').focus();
                return false;
            }
        });
    }
}

// Función principal de inicialización
function initApp() {
    initSlugGeneration();
    initWhatsAppValidation();
    initDateValidations();
    initFormValidation();
    toggleRSVPFields(); // Llamar una vez al inicio para establecer el estado inicial
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', initApp);