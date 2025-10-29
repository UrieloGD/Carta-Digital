// admin/js/plantilla_editar.js - JavaScript modularizado para edición de plantillas

class PlantillaEditor {
    constructor() {
        this.form = document.querySelector('form.needs-validation');
        this.activaCheckbox = document.getElementById('activa');
        this.invitacionEjemploSelect = document.getElementById('invitacion_ejemplo_id');
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupFormValidation();
        this.showSweetAlerts();
        this.reorganizarLayout();
        
        console.log('Editor de plantillas inicializado');
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
            confirmButtonColor: '#0d6efd',
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
            confirmButtonColor: '#0d6efd',
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
                confirmButtonColor: '#0d6efd',
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
        const cancelButtons = document.querySelectorAll('.floating-actions .btn-outline-secondary');
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
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.plantillaEditor = new PlantillaEditor();
});