// admin/js/plantilla_editar.js - JavaScript modularizado para edición de plantillas

class PlantillaEditor {
    constructor() {
        this.form = document.querySelector('form.needs-validation');
        this.activaCheckbox = document.getElementById('activa');
        this.invitacionEjemploSelect = document.getElementById('invitacion_ejemplo_id');
        // AGREGAR ESTAS LÍNEAS QUE FALTABAN:
        this.imagenFileInput = document.getElementById('imagen_preview_file');
        this.btnUploadImage = document.getElementById('btn-upload-image');
        this.imagenPreviewHidden = document.getElementById('imagen_preview');
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupFormValidation();
        this.showSweetAlerts();
        this.reorganizarLayout();
    }

    bindEvents() {
        // Actualizar preview del estado en tiempo real
        if (this.activaCheckbox) {
            this.activaCheckbox.addEventListener('change', () => this.updateStatusBadge());
        }

        // Mejorar el selector de invitación con información adicional
        if (this.invitacionEjemploSelect) {
            this.invitacionEjemploSelect.addEventListener('change', (e) => this.handleInvitacionChange(e));
        }

        // Manejar carga de imagen
        if (this.btnUploadImage) {
            this.btnUploadImage.addEventListener('click', () => {
                console.log('Botón clickeado');
                this.uploadImage();
            });
        } else {
            console.error('No se encontró el botón btn-upload-image');
        }
        
        // Permitir Enter en el input de archivo
        if (this.imagenFileInput) {
            this.imagenFileInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.uploadImage();
                }
            });
        } else {
            console.error('No se encontró el input imagen_preview_file');
        }

        // Validación de campos requeridos en tiempo real
        this.setupRealTimeValidation();

        // Confirmación antes de cancelar
        this.setupCancelConfirmation();
    }

    updateStatusBadge() {
        const badge = this.activaCheckbox.closest('.form-check').querySelector('.badge');
        if (this.activaCheckbox.checked) {
            badge.className = 'badge bg-success';
            badge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Activa';
        } else {
            badge.className = 'badge bg-secondary';
            badge.innerHTML = '<i class="bi bi-pause-circle me-1"></i>Inactiva';
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
            }
        }
    }

    setupFormValidation() {
        if (this.form) {
            this.form.addEventListener('submit', (event) => {
                if (!this.form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Mostrar SweetAlert2 con errores
                    this.showValidationErrors();
                } else {
                    // Confirmación antes de guardar con SweetAlert2
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
            return label ? label.textContent.trim() : field.name;
        });

        Swal.fire({
            icon: 'error',
            title: 'Campos requeridos',
            html: `Por favor completa los siguientes campos obligatorios:<br><strong>${fieldNames.join(', ')}</strong>`,
            confirmButtonColor: '#c8a882',
            confirmButtonText: 'Entendido',
            position: 'center'
        }).then(() => {
            // Enfocar el primer campo inválido
            if (invalidFields.length > 0) {
                invalidFields[0].focus();
            }
        });
    }

    confirmSave() {
        Swal.fire({
            title: '¿Guardar cambios?',
            text: 'Se actualizará la información de la plantilla',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#c8a882',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
            position: 'center'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Guardando...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    position: 'center',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Enviar formulario
                this.form.submit();
            }
        });
    }

    setupRealTimeValidation() {
        const requiredFields = this.form?.querySelectorAll('[required]');
        if (requiredFields) {
            requiredFields.forEach(field => {
                field.addEventListener('blur', () => {
                    this.validateField(field);
                });
                
                field.addEventListener('input', () => {
                    if (field.classList.contains('is-invalid')) {
                        this.validateField(field);
                    }
                });
            });
        }
    }

    validateField(field) {
        if (field.value.trim() === '') {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
        } else {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
        }
    }

    showSweetAlerts() {
        // Mostrar alertas de éxito/error con SweetAlert2 si existen (CENTRADAS)
        const successAlert = document.querySelector('.alert-success');
        const errorAlert = document.querySelector('.alert-danger');
        
        if (successAlert) {
            const message = successAlert.textContent.trim().replace(/\s+/g, ' ');
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: message,
                timer: 3000,
                showConfirmButton: true,
                confirmButtonColor: '#c8a882',
                timerProgressBar: true,
                position: 'center'
            });
            
            // Ocultar alerta de Bootstrap
            successAlert.style.display = 'none';
        }
        
        if (errorAlert) {
            const message = errorAlert.textContent.trim().replace(/\s+/g, ' ');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#dc3545',
                position: 'center'
            });
            
            // Ocultar alerta de Bootstrap
            errorAlert.style.display = 'none';
        }
    }

    setupCancelConfirmation() {
        const cancelButtons = document.querySelectorAll('.floating-actions .btn-outline-secondary, a.btn-outline-secondary');
        cancelButtons.forEach(cancelButton => {
            cancelButton.addEventListener('click', (e) => {
                // Solo preguntar si hay cambios en el formulario
                if (this.form && this.form.classList.contains('was-validated')) {
                    e.preventDefault();
                    
                    Swal.fire({
                        title: '¿Cancelar edición?',
                        text: 'Los cambios no guardados se perderán',
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
    }

    // Reorganizar el layout para desktop (2/3 formulario, 1/3 preview)
    reorganizarLayout() {
        const previewSection = document.querySelector('.preview-card')?.closest('.form-section');
        const actionButtonsSection = this.form?.querySelector('.d-flex.gap-2.justify-content-end')?.closest('.form-section');
        
        if (!this.form) return;
        
        // Crear wrapper principal solo si hay preview
        if (previewSection) {
            const mainWrapper = document.createElement('div');
            mainWrapper.className = 'form-section';
            
            const contentWrapper = document.createElement('div');
            contentWrapper.className = 'main-content-wrapper';
            
            // Crear columna de formulario
            const formContent = document.createElement('div');
            formContent.className = 'form-content';
            
            // Mover todos los form-sections al formContent (excepto preview y botones)
            const formSections = this.form.querySelectorAll('.form-section');
            formSections.forEach(section => {
                if (section !== previewSection && section !== actionButtonsSection) {
                    const sectionContent = document.createElement('div');
                    sectionContent.className = 'form-section-content mb-4';
                    
                    while (section.firstChild) {
                        sectionContent.appendChild(section.firstChild);
                    }
                    
                    formContent.appendChild(sectionContent);
                    section.remove();
                }
            });
            
            contentWrapper.appendChild(formContent);
            
            // Crear sidebar de preview
            const previewSidebar = document.createElement('div');
            previewSidebar.className = 'preview-sidebar';
            
            const previewCard = previewSection.querySelector('.preview-card');
            if (previewCard) {
                previewSidebar.appendChild(previewCard.cloneNode(true));
            }
            
            contentWrapper.appendChild(previewSidebar);
            mainWrapper.appendChild(contentWrapper);
            
            // Insertar el nuevo layout en el formulario
            if (actionButtonsSection) {
                this.form.insertBefore(mainWrapper, actionButtonsSection);
                previewSection.remove();
            }
        }
        
        // Mover botones y CORREGIR el justify-content-end
        if (actionButtonsSection) {
            const actionButtonsDiv = actionButtonsSection.querySelector('.d-flex.gap-2');
            
            if (actionButtonsDiv) {
                // REMOVER la clase justify-content-end que causa el problema
                actionButtonsDiv.classList.remove('justify-content-end');
                
                const floatingActions = document.createElement('div');
                floatingActions.className = 'floating-actions';
                
                // Clonar solo los botones, no el div con justify-content-end
                const buttons = actionButtonsDiv.querySelectorAll('.btn');
                buttons.forEach(button => {
                    floatingActions.appendChild(button.cloneNode(true));
                });
                
                // Reemplazar el contenido de la sección
                actionButtonsSection.innerHTML = '';
                actionButtonsSection.appendChild(floatingActions);
                
                console.log('Botones reorganizados correctamente sin justify-content-end');
            }
        }
    }

    uploadImage() {
        console.log('uploadImage() llamado');
        
        if (!this.imagenFileInput) {
            console.error('Input de archivo no encontrado');
            return;
        }

        if (!this.imagenFileInput.files.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una imagen',
                text: 'Por favor selecciona una imagen antes de subir',
                confirmButtonColor: '#c8a882',
                position: 'center'
            });
            return;
        }

        const file = this.imagenFileInput.files[0];
        const maxSize = 5 * 1024 * 1024; // 5MB

        console.log('Archivo seleccionado:', file.name, 'Tamaño:', file.size);

        // Validar tamaño
        if (file.size > maxSize) {
            Swal.fire({
                icon: 'error',
                title: 'Archivo demasiado grande',
                text: 'La imagen no debe exceder 5MB',
                confirmButtonColor: '#dc3545',
                position: 'center'
            });
            return;
        }

        // Validar tipo
        if (!file.type.startsWith('image/')) {
            Swal.fire({
                icon: 'error',
                title: 'Tipo de archivo inválido',
                text: 'Solo se permiten imágenes',
                confirmButtonColor: '#dc3545',
                position: 'center'
            });
            return;
        }

        const formData = new FormData();
        formData.append('imagen', file);
        formData.append('plantilla_id', this.imagenFileInput.dataset.plantillaId);

        console.log('FormData preparado, plantilla_id:', this.imagenFileInput.dataset.plantillaId);

        // Mostrar barra de progreso
        const progressDiv = document.getElementById('upload-progress');
        const progressBar = progressDiv.querySelector('.progress-bar');
        progressDiv.style.display = 'block';
        progressBar.style.width = '0%';

        // Deshabilitar botón
        this.btnUploadImage.disabled = true;
        this.btnUploadImage.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Subiendo...';

        const xhr = new XMLHttpRequest();

        // Barra de progreso
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                console.log('Progreso:', percentComplete.toFixed(2) + '%');
            }
        });

        xhr.addEventListener('load', () => {
            console.log('Carga completada, status:', xhr.status);
            console.log('Respuesta:', xhr.responseText);

            this.btnUploadImage.disabled = false;
            this.btnUploadImage.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Subir';
            progressDiv.style.display = 'none';

            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        // Actualizar el campo oculto
                        this.imagenPreviewHidden.value = response.ruta;
                        
                        // Limpiar el input de archivo
                        this.imagenFileInput.value = '';

                        // Actualizar la vista previa si existe
                        this.updatePreviewImage(response.ruta_completa);

                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.mensaje,
                            timer: 3000,
                            showConfirmButton: true,
                            confirmButtonColor: '#c8a882',
                            timerProgressBar: true,
                            position: 'center'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.error || 'Error desconocido',
                            confirmButtonColor: '#dc3545',
                            position: 'center'
                        });
                    }
                } catch (e) {
                    console.error('Error al parsear respuesta:', e);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al procesar la respuesta del servidor',
                        confirmButtonColor: '#dc3545',
                        position: 'center'
                    });
                }
            } else {
                try {
                    const response = JSON.parse(xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.error || 'Error en la carga',
                        confirmButtonColor: '#dc3545',
                        position: 'center'
                    });
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error desconocido: ' + xhr.status,
                        confirmButtonColor: '#dc3545',
                        position: 'center'
                    });
                }
            }
        });

        xhr.addEventListener('error', () => {
            console.error('Error de red');
            this.btnUploadImage.disabled = false;
            this.btnUploadImage.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Subir';
            progressDiv.style.display = 'none';

            Swal.fire({
                icon: 'error',
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor',
                confirmButtonColor: '#dc3545',
                position: 'center'
            });
        });

        console.log('Enviando a: ./functions/upload_plantilla_imagen.php');
        xhr.open('POST', './functions/upload_plantilla_imagen.php');
        xhr.send(formData);
    }

    updatePreviewImage(rutaCompleta) {
        console.log('Actualizando preview con:', rutaCompleta);
        
        let imgElement = document.querySelector('.preview-image');
        
        if (!imgElement) {
            // Crear elemento de imagen si no existe
            const previewCard = document.querySelector('.preview-card');
            if (previewCard) {
                imgElement = document.createElement('img');
                imgElement.className = 'preview-image';
                imgElement.alt = 'Preview';
                previewCard.prepend(imgElement);
            } else {
                // Si no hay preview-card, crear la sección completa
                const previewSidebar = document.querySelector('.preview-sidebar');
                if (previewSidebar) {
                    const newPreviewCard = document.createElement('div');
                    newPreviewCard.className = 'preview-card';
                    newPreviewCard.innerHTML = `
                        <img src="" class="preview-image" alt="Preview">
                        <p class="text-muted mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Preview de la plantilla
                        </p>
                    `;
                    previewSidebar.appendChild(newPreviewCard);
                    imgElement = newPreviewCard.querySelector('.preview-image');
                }
            }
        }
        
        if (imgElement) {
            // Agregar timestamp para evitar caché
            imgElement.src = rutaCompleta + '?t=' + Date.now();
            imgElement.style.display = 'block';
            imgElement.classList.add('upload-success');
            
            // Remover clase después de la animación
            setTimeout(() => {
                imgElement.classList.remove('upload-success');
            }, 600);
        }
    }

}

// Mejorar el manejo de imágenes que no cargan
document.addEventListener('DOMContentLoaded', function() {
    const previewImages = document.querySelectorAll('.preview-image');
    previewImages.forEach(img => {
        img.addEventListener('error', function() {
            // Solo ocultar la tarjeta de preview, no el formulario
            const previewCard = this.closest('.preview-card');
            if (previewCard) {
                previewCard.style.display = 'none';
            }
        });
    });
});

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.plantillaEditor = new PlantillaEditor();
});