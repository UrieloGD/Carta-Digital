// Funciones para agregar/eliminar elementos dinámicos

function agregarCronograma() {
    const container = document.getElementById('cronograma-container');
    const newItem = document.createElement('div');
    newItem.className = 'cronograma-item';
    newItem.innerHTML = `
        <div class="form-row">
            <div class="form-group">
                <label>Hora</label>
                <input type="time" name="cronograma_hora[]">
            </div>
            <div class="form-group">
                <label>Evento</label>
                <input type="text" name="cronograma_evento[]">
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <input type="text" name="cronograma_descripcion[]">
            </div>
            <div class="form-group">
                <label>Icono</label>
                <select name="cronograma_icono[]">
                    <option value="anillos">Anillos</option>
                    <option value="cena">Cena</option>
                    <option value="fiesta">Fiesta</option>
                    <option value="luna">Luna</option>
                </select>
            </div>
            <div class="form-group">
                <button type="button" onclick="eliminarCronograma(this)" class="btn btn-danger btn-sm">Eliminar</button>
            </div>
        </div>
    `;
    container.appendChild(newItem);
}

function eliminarCronograma(button) {
    const container = document.getElementById('cronograma-container');
    if (container.children.length > 1) {
        button.closest('.cronograma-item').remove();
    } else {
        alert('Debe mantener al menos un evento en el cronograma');
    }
}

function agregarFAQ() {
    const container = document.getElementById('faq-container');
    const newItem = document.createElement('div');
    newItem.className = 'faq-item';
    newItem.innerHTML = `
        <div class="form-group">
            <label>Pregunta</label>
            <input type="text" name="faq_pregunta[]">
        </div>
        <div class="form-group">
            <label>Respuesta</label>
            <textarea name="faq_respuesta[]" rows="2"></textarea>
        </div>
        <div class="form-group">
            <button type="button" onclick="eliminarFAQ(this)" class="btn btn-danger btn-sm">Eliminar</button>
        </div>
    `;
    container.appendChild(newItem);
}

function eliminarFAQ(button) {
    button.closest('.faq-item').remove();
}

function agregarGaleria() {
    const container = document.getElementById('galeria-container');
    const newItem = document.createElement('div');
    newItem.className = 'galeria-item';
    newItem.innerHTML = `
        <div class="form-group">
            <label>URL de la imagen</label>
            <input type="url" name="galeria_urls[]" placeholder="https://ejemplo.com/imagen.jpg">
        </div>
        <div class="form-group">
            <button type="button" onclick="eliminarGaleria(this)" class="btn btn-danger btn-sm">Eliminar</button>
        </div>
    `;
    container.appendChild(newItem);
}

function eliminarGaleria(button) {
    button.closest('.galeria-item').remove();
}

// Generar slug automáticamente basado en los nombres
document.addEventListener('DOMContentLoaded', function() {
    const nombresInput = document.getElementById('nombres_novios');
    const slugInput = document.getElementById('slug');
    
    if (nombresInput && slugInput && !slugInput.value) {
        nombresInput.addEventListener('input', function() {
            const nombres = this.value;
            const slug = nombres
                .toLowerCase()
                .replace(/[^a-z0-9\s&]/g, '')
                .replace(/\s+/g, '-')
                .replace(/&/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            
            if (slug) {
                slugInput.value = slug + '-2025';
            }
        });
    }
});

// Confirmar eliminación
function confirmarEliminacion() {
    return confirm('¿Estás seguro de que deseas eliminar esta invitación? Esta acción no se puede deshacer.');
}

// Previsualizar imagen
function previsualizarImagen(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Crear preview de imagen
            let preview = input.parentNode.querySelector('.image-preview');
            if (!preview) {
                preview = document.createElement('img');
                preview.className = 'image-preview';
                preview.style.maxWidth = '200px';
                preview.style.marginTop = '10px';
                input.parentNode.appendChild(preview);
            }
            preview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}