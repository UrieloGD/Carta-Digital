// Funciones para manejo de cronograma
function agregarCronograma() {
    const container = document.getElementById('cronograma-container');
    const newItem = container.children[0].cloneNode(true);
    
    // Limpiar valores
    newItem.querySelectorAll('input, select').forEach(input => input.value = '');
    
    // Agregar botón eliminar si no existe
    if (!newItem.querySelector('.btn-danger')) {
        const formRow = newItem.querySelector('.form-row');
        const deleteGroup = document.createElement('div');
        deleteGroup.className = 'form-group';
        deleteGroup.innerHTML = '<button type="button" onclick="eliminarCronograma(this)" class="btn btn-danger btn-sm">🗑️ Eliminar</button>';
        formRow.appendChild(deleteGroup);
    }
    
    container.appendChild(newItem);
}

function eliminarCronograma(button) {
    const container = document.getElementById('cronograma-container');
    if (container.children.length > 1) {
        button.closest('.cronograma-item').remove();
    }
}

// Funciones para manejo de FAQ
function agregarFAQ() {
    const container = document.getElementById('faq-container');
    const newItem = container.children[0].cloneNode(true);
    
    // Limpiar valores
    newItem.querySelectorAll('input, textarea').forEach(input => input.value = '');
    
    // Agregar botón eliminar si no existe
    if (!newItem.querySelector('.btn-danger')) {
        const deleteGroup = document.createElement('div');
        deleteGroup.className = 'form-group';
        deleteGroup.innerHTML = '<button type="button" onclick="eliminarFAQ(this)" class="btn btn-danger btn-sm">🗑️ Eliminar</button>';
        newItem.appendChild(deleteGroup);
    }
    
    container.appendChild(newItem);
}

function eliminarFAQ(button) {
    const container = document.getElementById('faq-container');
    if (container.children.length > 1) {
        button.closest('.faq-item').remove();
    }
}

// Función para preview de imagen individual
function previewImage(input, previewId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    
    if (file && preview) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Vista previa" class="image-preview" style="max-width: 200px; height: auto; border-radius: 8px;">`;
            preview.classList.add('has-image');
        };
        reader.readAsDataURL(file);
    }
}

// Función para preview de galería múltiple
function previewGallery(input) {
    const preview = document.getElementById('gallery-preview');
    
    if (!preview) return;
    
    preview.innerHTML = '';
    
    Array.from(input.files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'gallery-preview-item';
                div.style.cssText = 'display: inline-block; margin: 10px; position: relative; border: 2px solid #ddd; border-radius: 8px; overflow: hidden;';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Imagen ${index + 1}" style="width: 150px; height: 150px; object-fit: cover; display: block;">
                    <button type="button" class="remove-preview-btn" onclick="removePreviewItem(this)" 
                            style="position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.8); color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;">×</button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
}

// Función para eliminar item de preview
function removePreviewItem(button) {
    button.parentElement.remove();
}

// Función para configurar preview individual
function setupImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (input && preview) {
        input.addEventListener('change', function(e) {
            previewImage(this, previewId);
        });
    }
}

// Función para eliminar imagen de galería existente
function eliminarImagenGaleria(imagenId) {
    if (confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
        // Obtener el ID de la invitación desde la URL o un elemento hidden
        const urlParams = new URLSearchParams(window.location.search);
        const invitacionId = urlParams.get('id');
        
        fetch('./eliminar_imagen.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                imagen_id: imagenId,
                invitacion_id: invitacionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al eliminar la imagen: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al conectar con el servidor');
        });
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Editar.js cargado correctamente');
    
    // Configurar vistas previas para imágenes individuales
    setupImagePreview('imagen_hero', 'hero-preview');
    setupImagePreview('imagen_dedicatoria', 'dedicatoria-preview');
    setupImagePreview('imagen_destacada', 'destacada-preview');
    setupImagePreview('imagen_dresscode_hombres', 'dresscode-hombres-preview');
    setupImagePreview('imagen_dresscode_mujeres', 'dresscode-mujeres-preview');
    
    // Configurar preview para galería múltiple
    const galeriaInput = document.getElementById('imagenes_galeria');
    if (galeriaInput) {
        galeriaInput.addEventListener('change', function(e) {
            previewGallery(this);
        });
    }
    
    // Validaciones adicionales del formulario
    const form = document.querySelector('.admin-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nombresNovios = document.getElementById('nombres_novios').value.trim();
            const fechaEvento = document.getElementById('fecha_evento').value;
            const horaEvento = document.getElementById('hora_evento').value;
            
            if (!nombresNovios || !fechaEvento || !horaEvento) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios (Nombres, Fecha y Hora)');
                return false;
            }
            
            // Validar que la fecha no sea en el pasado
            const fechaSeleccionada = new Date(fechaEvento);
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            
            if (fechaSeleccionada < hoy) {
                e.preventDefault();
                alert('La fecha del evento no puede ser anterior a hoy');
                return false;
            }
        });
    }
    
    // Agregar funcionalidad para mostrar/ocultar secciones opcionales
    toggleOptionalSections();
});

// Función para manejar secciones opcionales
function toggleOptionalSections() {
    // Agregar botones para mostrar/ocultar secciones opcionales
    const sectionsToToggle = [
        { id: 'cronograma-container', buttonText: 'Agregar Cronograma' },
        { id: 'faq-container', buttonText: 'Agregar FAQ' }
    ];
    
    sectionsToToggle.forEach(section => {
        const container = document.getElementById(section.id);
        if (container && container.children.length === 1) {
            // Si solo hay un elemento vacío, ocultar la sección inicialmente
            const firstItem = container.children[0];
            const hasContent = Array.from(firstItem.querySelectorAll('input, textarea, select'))
                                   .some(input => input.value.trim() !== '');
            
            if (!hasContent) {
                container.style.display = 'none';
                
                // Crear botón para mostrar la sección
                const toggleBtn = document.createElement('button');
                toggleBtn.type = 'button';
                toggleBtn.className = 'btn btn-secondary';
                toggleBtn.textContent = section.buttonText;
                toggleBtn.onclick = function() {
                    container.style.display = 'block';
                    this.style.display = 'none';
                };
                
                container.parentNode.insertBefore(toggleBtn, container);
            }
        }
    });
}

function previewAudio(input) {
    const preview = document.getElementById('music-preview');
    const audio = preview.querySelector('audio');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Verificar que sea un archivo de audio
        if (!file.type.startsWith('audio/')) {
            alert('Por favor selecciona un archivo de audio válido');
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        // Verificar tamaño (10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('El archivo es demasiado grande. Máximo 10MB.');
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        const url = URL.createObjectURL(file);
        audio.src = url;
        preview.style.display = 'block';
        
        // Limpiar URL cuando el audio se carga
        audio.onload = function() {
            URL.revokeObjectURL(url);
        };
    } else {
        preview.style.display = 'none';
    }
}

// Hacer las funciones globales para que puedan ser llamadas desde el HTML
window.agregarCronograma = agregarCronograma;
window.eliminarCronograma = eliminarCronograma;
window.agregarFAQ = agregarFAQ;
window.eliminarFAQ = eliminarFAQ;
window.previewImage = previewImage;
window.previewGallery = previewGallery;
window.eliminarImagenGaleria = eliminarImagenGaleria;
window.removePreviewItem = removePreviewItem;