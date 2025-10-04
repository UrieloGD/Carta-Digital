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
        this.setupAutoCloseAlerts();
        this.setupFormValidation();
        this.showSweetAlerts();
        
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
                
                // Mostrar información adicional con SweetAlert2
                this.showInvitacionInfo(selectedOption.text, slug);
            }
        }
    }

    showInvitacionInfo(nombre, slug) {
        // Podemos mostrar un tooltip o información adicional
        const element = this.invitacionEjemploSelect;
        const originalTitle = element.title;
        
        element.title = `Ejemplo: ${nombre}\nSlug: ${slug}`;
        
        // Tooltip de Bootstrap
        if (typeof bootstrap !== 'undefined') {
            const tooltip = new bootstrap.Tooltip(element, {
                title: `Ejemplo: ${nombre}\nSlug: ${slug}`,
                placement: 'top',
                trigger: 'hover focus'
            });
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
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'Entendido'
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
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Enviar formulario
                this.form.submit();
                
                // Mostrar loading
                Swal.fire({
                    title: 'Guardando...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
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

    setupAutoCloseAlerts() {
        // Auto-ocultar alertas de Bootstrap después de 5 segundos
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach((alert) => {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch (e) {
                    // Si falla, ocultar manualmente
                    alert.style.display = 'none';
                }
            });
        }, 5000);
    }

    showSweetAlerts() {
        // Mostrar alertas de éxito/error con SweetAlert2 si existen
        const successAlert = document.querySelector('.alert-success');
        const errorAlert = document.querySelector('.alert-danger');
        
        if (successAlert) {
            const message = successAlert.textContent.trim();
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: message,
                timer: 3000,
                showConfirmButton: false,
                position: 'top-end',
                toast: true
            });
            
            // Ocultar alerta de Bootstrap
            successAlert.style.display = 'none';
        }
        
        if (errorAlert) {
            const message = errorAlert.textContent.trim();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#3085d6'
            });
            
            // Ocultar alerta de Bootstrap
            errorAlert.style.display = 'none';
        }
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.plantillaEditor = new PlantillaEditor();
});