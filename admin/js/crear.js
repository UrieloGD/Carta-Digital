// Función para previsualizar imágenes individuales
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const file = input.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 300px; max-height: 200px;">`;
                preview.classList.add('has-image');
                
                // Actualizar el label del botón
                const label = input.nextElementSibling;
                label.innerHTML = `<i>✅</i> Cambiar imagen`;
                label.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
            };
            reader.readAsDataURL(file);
        }
    }

    // Función para previsualizar galería múltiple
    function previewGallery(input) {
        const preview = document.getElementById('gallery-preview');
        const files = Array.from(input.files);
        
        if (files.length > 0) {
            preview.innerHTML = '';
            
            files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'gallery-preview-item';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Galería ${index + 1}">
                            <button type="button" class="remove-btn" onclick="removeGalleryItem(this, ${index})" title="Eliminar">×</button>
                        `;
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Actualizar label
            const label = input.nextElementSibling;
            label.innerHTML = `<i>✅</i> ${files.length} imagen${files.length > 1 ? 'es' : ''} seleccionada${files.length > 1 ? 's' : ''}`;
            label.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
        }
    }

    // Función para eliminar item de galería (visual)
    function removeGalleryItem(button, index) {
        const item = button.parentElement;
        item.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            item.remove();
            updateGalleryCount();
        }, 300);
    }

    // Actualizar contador de galería
    function updateGalleryCount() {
        const preview = document.getElementById('gallery-preview');
        const input = document.getElementById('imagenes_galeria');
        const label = input.nextElementSibling;
        const count = preview.children.length;
        
        if (count === 0) {
            label.innerHTML = `<i>🖼️</i> Seleccionar imágenes para galería`;
            label.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        } else {
            label.innerHTML = `<i>✅</i> ${count} imagen${count > 1 ? 'es' : ''} seleccionada${count > 1 ? 's' : ''}`;
        }
    }

    // Funciones existentes mejoradas
    function agregarCronograma() {
        const container = document.getElementById('cronograma-container');
        const newItem = container.children[0].cloneNode(true);
        
        // Limpiar valores
        newItem.querySelectorAll('input, select').forEach(input => input.value = '');
        
        // Añadir animación
        newItem.style.opacity = '0';
        newItem.style.transform = 'translateY(-20px)';
        container.appendChild(newItem);
        
        setTimeout(() => {
            newItem.style.transition = 'all 0.3s ease';
            newItem.style.opacity = '1';
            newItem.style.transform = 'translateY(0)';
        }, 10);
    }

    function agregarFAQ() {
        const container = document.getElementById('faq-container');
        const newItem = container.children[0].cloneNode(true);
        
        // Limpiar valores
        newItem.querySelectorAll('input, textarea').forEach(input => input.value = '');
        
        // Añadir animación
        newItem.style.opacity = '0';
        newItem.style.transform = 'translateY(-20px)';
        container.appendChild(newItem);
        
        setTimeout(() => {
            newItem.style.transition = 'all 0.3s ease';
            newItem.style.opacity = '1';
            newItem.style.transform = 'translateY(0)';
        }, 10);
    }

    // Validación mejorada del formulario
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.admin-form');
        
        form.addEventListener('submit', function(e) {
            // Mostrar indicador de carga
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i>⏳</i> Creando invitación...';
            submitBtn.disabled = true;
            
            // Si hay algún error, restaurar el botón
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 10000);
        });
        
        // Validación en tiempo real del slug
        const slugInput = document.getElementById('slug');
        slugInput.addEventListener('input', function() {
            let value = this.value.toLowerCase();
            value = value.replace(/[^a-z0-9\-]/g, '');
            value = value.replace(/--+/g, '-');
            this.value = value;
        });
    });

    // CSS para animación fadeOut
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
        }
    `;
    document.head.appendChild(style);