// admin/js/plantilla_nueva.js - JavaScript modularizado con upload de imágenes

class PlantillaNueva {
    constructor() {
        this.form = document.querySelector('form.needs-validation');
        this.nombreInput = document.getElementById('nombre');
        this.carpetaInput = document.getElementById('carpeta');
        this.invitacionEjemploSelect = document.getElementById('invitacion_ejemplo_id');
        
        // Elementos de imagen
        this.imagenFileInput = document.getElementById('imagenFile');
        this.btnSelectImage = document.getElementById('btnSelectImage');
        this.btnRemoveImage = document.getElementById('btnRemoveImage');
        this.imagePreview = document.getElementById('imagePreview');
        this.imagePreviewContainer = document.getElementById('imagePreviewContainer');
        this.imagePlaceholder = document.getElementById('imagePlaceholder');
        this.uploadProgress = document.getElementById('uploadProgress');
        this.imagenPreviewInput = document.getElementById('imagen_preview');
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupFormValidation();
        this.setupImageUpload();
        this.setupSweetAlerts();
        
        console.log('Creación de plantillas con upload de imágenes inicializado');
    }

    bindEvents() {
        // Auto-generar nombre de carpeta basado en el nombre
        if (this.nombreInput && this.carpetaInput) {
            this.nombreInput.addEventListener('input', (e) => this.generateFolderName(e));
        }

        // Mejorar el selector de invitación con información adicional
        if (this.invitacionEjemploSelect) {
            this.invitacionEjemploSelect.addEventListener('change', (e) => this.handleInvitacionChange(e));
        }

        // Confirmación antes de cancelar
        this.setupCancelConfirmation();
    }

    setupImageUpload() {
        // Botón para abrir selector de archivos
        if (this.btnSelectImage) {
            this.btnSelectImage.addEventListener('click', () => {
                this.imagenFileInput.click();
            });
        }

        // Cuando se selecciona un archivo
        if (this.imagenFileInput) {
            this.imagenFileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    this.uploadImage(file);
                }
            });
        }

        // Botón para eliminar imagen
        if (this.btnRemoveImage) {
            this.btnRemoveImage.addEventListener('click', () => {
                this.removeImage();
            });
        }
    }

    uploadImage(file) {
        // Validar tipo de archivo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Formato no válido',
                text: 'Solo se permiten archivos JPG, PNG o WEBP',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        // Validar tamaño (5MB máximo)
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo muy grande',
                text: 'El tamaño máximo permitido es 5MB',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        // Preparar FormData
        const formData = new FormData();
        formData.append('imagen', file);

        // Mostrar barra de progreso
        this.showUploadProgress();

        // Realizar upload con AJAX
        const xhr = new XMLHttpRequest();

        // Progreso del upload
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                this.updateProgress(percentComplete);
            }
        });

        // Cuando se completa el upload
        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        this.handleUploadSuccess(response.ruta_relativa);
                    } else {
                        this.handleUploadError(response.error || 'Error desconocido');
                    }
                } catch (e) {
                    this.handleUploadError('Error al procesar la respuesta del servidor');
                }
            } else {
                this.handleUploadError('Error en la conexión con el servidor');
            }
        });

        // Error en la petición
        xhr.addEventListener('error', () => {
            this.handleUploadError('Error de red al subir la imagen');
        });

        // Enviar la petición
        xhr.open('POST', 'functions/upload_plantilla_imagen.php', true);
        xhr.send(formData);
    }

    showUploadProgress() {
        this.uploadProgress?.classList.remove('d-none');
        this.imagePlaceholder?.classList.add('d-none');
        this.btnSelectImage.disabled = true;
    }

    updateProgress(percent) {
        const progressBar = this.uploadProgress?.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent + '%';
        }
    }

    handleUploadSuccess(rutaRelativa) {
        // Ocultar barra de progreso
        this.uploadProgress?.classList.add('d-none');
        this.btnSelectImage.disabled = false;

        // Actualizar campo hidden con la ruta
        if (this.imagenPreviewInput) {
            this.imagenPreviewInput.value = rutaRelativa;
            // Remover clase de invalid si existe
            this.imagenPreviewInput.classList.remove('is-invalid');
        }

        // Mostrar preview de la imagen
        if (this.imagePreview && this.imagePreviewContainer) {
            this.imagePreview.src = '../' + rutaRelativa;
            this.imagePreviewContainer.classList.remove('d-none');
            this.imagePlaceholder?.classList.add('d-none');
        }

        // Notificación de éxito
        Swal.fire({
            icon: 'success',
            title: 'Imagen subida',
            text: 'La imagen se ha cargado correctamente',
            timer: 2000,
            showConfirmButton: false,
            position: 'top-end',
            toast: true
        });

        console.log('Imagen subida exitosamente:', rutaRelativa);
    }

    handleUploadError(errorMessage) {
        // Ocultar barra de progreso
        this.uploadProgress?.classList.add('d-none');
        this.btnSelectImage.disabled = false;

        // Mostrar placeholder
        this.imagePlaceholder?.classList.remove('d-none');

        // Mostrar error
        Swal.fire({
            icon: 'error',
            title: 'Error al subir imagen',
            text: errorMessage,
            confirmButtonColor: '#0d6efd'
        });

        console.error('Error en upload:', errorMessage);
    }

    removeImage() {
        Swal.fire({
            title: '¿Eliminar imagen?',
            text: 'Deberás subir otra imagen para continuar',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Limpiar input hidden
                if (this.imagenPreviewInput) {
                    this.imagenPreviewInput.value = '';
                }

                // Ocultar preview y mostrar placeholder
                this.imagePreviewContainer?.classList.add('d-none');
                this.imagePlaceholder?.classList.remove('d-none');

                // Limpiar input file
                if (this.imagenFileInput) {
                    this.imagenFileInput.value = '';
                }

                // Notificación
                Swal.fire({
                    icon: 'info',
                    title: 'Imagen eliminada',
                    timer: 1500,
                    showConfirmButton: false,
                    position: 'top-end',
                    toast: true
                });
            }
        });
    }

    generateFolderName(event) {
        const nombre = event.target.value;
        const carpeta = 'plantilla-' + nombre.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
        
        this.carpetaInput.value = carpeta;
        this.showFolderPreview(carpeta);
    }

    showFolderPreview(carpeta) {
        let previewElement = document.getElementById('folder-preview');
        if (!previewElement) {
            previewElement = document.createElement('div');
            previewElement.id = 'folder-preview';
            previewElement.className = 'form-text text-info mt-1';
            this.carpetaInput.parentNode.appendChild(previewElement);
        }
        
        if (carpeta) {
            previewElement.innerHTML = `<i class="bi bi-folder2-open me-1"></i>Ruta generada: <code>plantillas/${carpeta}/</code>`;
        } else {
            previewElement.innerHTML = '';
        }
    }

    handleInvitacionChange(event) {
        if (event.target.value) {
            const selectedOption = event.target.options[event.target.selectedIndex];
            const match = selectedOption.text.match(/\(([^)]+)\)$/);
            
            if (match) {
                const slug = match[1];
                console.log('Invitación seleccionada:', selectedOption.text);
                console.log('URL que se usará:', `/invitacion/${slug}`);
                
                this.showInvitacionInfo(selectedOption.text, slug);
            }
        }
    }

    showInvitacionInfo(nombre, slug) {
        const element = this.invitacionEjemploSelect;
        
        if (typeof bootstrap !== 'undefined') {
            const existingTooltip = bootstrap.Tooltip.getInstance(element);
            if (existingTooltip) {
                existingTooltip.dispose();
            }
            
            new bootstrap.Tooltip(element, {
                title: `Ejemplo: ${nombre}\nSlug: ${slug}`,
                placement: 'top',
                trigger: 'hover focus'
            });
        }
    }

    setupFormValidation() {
        if (this.form) {
            this.form.addEventListener('submit', (event) => {
                // Validar que haya imagen subida
                if (!this.imagenPreviewInput.value) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    this.imagenPreviewInput.classList.add('is-invalid');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Imagen requerida',
                        text: 'Por favor sube una imagen de preview para la plantilla',
                        confirmButtonColor: '#0d6efd'
                    });
                    
                    return;
                }
                
                if (!this.form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.showValidationErrors();
                } else {
                    event.preventDefault();
                    this.confirmSave();
                }
                
                this.form.classList.add('was-validated');
            });
        }
    }

    showValidationErrors() {
        const invalidFields = this.form.querySelectorAll(':invalid');
        const fieldNames = Array.from(invalidFields).map(field => {
            const label = field.closest('.mb-3')?.querySelector('.form-label');
            return label ? label.textContent.trim().replace('*', '') : field.name;
        });

        Swal.fire({
            icon: 'error',
            title: 'Campos requeridos',
            html: `Por favor completa los siguientes campos obligatorios:<br><strong>${fieldNames.join(', ')}</strong>`,
            confirmButtonColor: '#0d6efd',
            confirmButtonText: 'Entendido',
            position: 'center'
        }).then(() => {
            if (invalidFields.length > 0) {
                invalidFields[0].focus();
            }
        });
    }

    confirmSave() {
        const nombre = this.nombreInput.value;
        const carpeta = this.carpetaInput.value;
        const archivoPrincipal = document.getElementById('archivo_principal').value;

        Swal.fire({
            title: '¿Crear nueva plantilla?',
            html: `
                <div class="text-start">
                    <p><strong>Nombre:</strong> ${nombre}</p>
                    <p><strong>Carpeta:</strong> ${carpeta}</p>
                    <p><strong>Archivo principal:</strong> ${archivoPrincipal}</p>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-1"></i>
                    Asegúrate de que los archivos de la plantilla estén en la carpeta correcta.
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, crear plantilla',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            position: 'center'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Creando plantilla...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    position: 'center',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                setTimeout(() => {
                    this.form.submit();
                }, 500);
            }
        });
    }

    setupCancelConfirmation() {
        setTimeout(() => {
            const cancelButtons = document.querySelectorAll('.floating-actions .btn-outline-secondary, a[href="plantillas.php"]');
            cancelButtons.forEach(cancelButton => {
                cancelButton.addEventListener('click', (e) => {
                    // Solo preguntar si hay cambios en el formulario
                    const hasChanges = this.nombreInput?.value || 
                                     this.carpetaInput?.value || 
                                     this.imagenPreviewInput?.value;
                    
                    if (hasChanges) {
                        e.preventDefault();
                        
                        Swal.fire({
                            title: '¿Cancelar creación?',
                            text: 'Los datos ingresados se perderán',
                            icon: 'warning',
                            position: 'center',
                            showCancelButton: true,
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d',
                            confirmButtonText: 'Sí, cancelar',
                            cancelButtonText: 'Continuar editando',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'plantillas.php';
                            }
                        });
                    }
                });
            });
        }, 100);
    }

    setupSweetAlerts() {
        this.showWelcomeInfo();
    }

    showWelcomeInfo() {
        const alreadyShown = sessionStorage.getItem('plantillaNuevaWelcomeShown');
        
        if (!alreadyShown) {
            setTimeout(() => {
                Swal.fire({
                    title: 'Crear Nueva Plantilla',
                    html: `
                        <div class="text-start">
                            <p><i class="bi bi-lightbulb me-2 text-warning"></i> <strong>Consejos:</strong></p>
                            <ul class="small">
                                <li>El nombre de la carpeta se genera automáticamente</li>
                                <li>Sube una imagen de preview (obligatorio)</li>
                                <li>Asegúrate de que los archivos estén en la carpeta correcta</li>
                                <li>Puedes asignar una invitación existente como ejemplo</li>
                            </ul>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonColor: '#0d6efd',
                    confirmButtonText: 'Entendido',
                    position: 'center'
                });
                
                sessionStorage.setItem('plantillaNuevaWelcomeShown', 'true');
            }, 1000);
        }
    }

    clearForm() {
        if (this.form) {
            this.form.reset();
            this.form.classList.remove('was-validated');
            
            const previewElement = document.getElementById('folder-preview');
            if (previewElement) {
                previewElement.remove();
            }
            
            // Limpiar imagen
            this.removeImage();
        }
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.plantillaNueva = new PlantillaNueva();
});

window.limpiarFormularioPlantilla = function() {
    if (window.plantillaNueva) {
        window.plantillaNueva.clearForm();
    }
};
