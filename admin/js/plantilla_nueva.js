// admin/js/plantilla_nueva.js - JavaScript modularizado para creación de plantillas

class PlantillaNueva {
    constructor() {
        this.form = document.querySelector('form.needs-validation');
        this.nombreInput = document.getElementById('nombre');
        this.carpetaInput = document.getElementById('carpeta');
        this.invitacionEjemploSelect = document.getElementById('invitacion_ejemplo_id');
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.setupFormValidation();
        this.setupSweetAlerts();
        this.reorganizarBotones();
        
        console.log('Creación de plantillas inicializado');
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

    generateFolderName(event) {
        const nombre = event.target.value;
        const carpeta = 'plantilla-' + nombre.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
        
        this.carpetaInput.value = carpeta;
        
        // Mostrar preview de la ruta generada
        this.showFolderPreview(carpeta);
    }

    showFolderPreview(carpeta) {
        // Crear o actualizar elemento de preview
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
                
                // Mostrar información adicional con SweetAlert2 tooltip
                this.showInvitacionInfo(selectedOption.text, slug);
            }
        }
    }

    showInvitacionInfo(nombre, slug) {
        const element = this.invitacionEjemploSelect;
        
        // Tooltip de Bootstrap
        if (typeof bootstrap !== 'undefined') {
            // Destruir tooltip existente
            const existingTooltip = bootstrap.Tooltip.getInstance(element);
            if (existingTooltip) {
                existingTooltip.dispose();
            }
            
            // Crear nuevo tooltip
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
            // Enfocar el primer campo inválido
            if (invalidFields.length > 0) {
                invalidFields[0].focus();
            }
        });
    }

    confirmSave() {
        // Obtener datos del formulario para mostrar en la confirmación
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
                // Mostrar loading
                Swal.fire({
                    title: 'Creando plantilla...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    position: 'center',
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Enviar formulario después de un breve delay para mostrar el loading
                setTimeout(() => {
                    this.form.submit();
                }, 500);
            }
        });
    }

    setupCancelConfirmation() {
        // Se configurará después de reorganizar los botones
        setTimeout(() => {
            const cancelButtons = document.querySelectorAll('.floating-actions .btn-outline-secondary');
            cancelButtons.forEach(cancelButton => {
                cancelButton.addEventListener('click', (e) => {
                    // Solo preguntar si hay cambios en el formulario
                    if (this.form && this.form.classList.contains('was-validated')) {
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
        // Mostrar información de bienvenida si es la primera vez
        this.showWelcomeInfo();
    }

    showWelcomeInfo() {
        // Verificar si ya se mostró el mensaje (usando sessionStorage)
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
                
                // Marcar como mostrado
                sessionStorage.setItem('plantillaNuevaWelcomeShown', 'true');
            }, 1000);
        }
    }

    // NUEVA FUNCIÓN: Reorganizar botones (sin justify-content-end)
    reorganizarBotones() {
        const actionButtonsSection = this.form?.querySelector('.d-flex.gap-2.justify-content-end')?.closest('.form-section');
        
        if (!this.form || !actionButtonsSection) return;
        
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

    // Método para limpiar el formulario
    clearForm() {
        if (this.form) {
            this.form.reset();
            this.form.classList.remove('was-validated');
            
            // Limpiar preview de carpeta
            const previewElement = document.getElementById('folder-preview');
            if (previewElement) {
                previewElement.remove();
            }
        }
    }
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.plantillaNueva = new PlantillaNueva();
});

// Función global para limpiar formulario (puede ser llamada desde otros lugares)
window.limpiarFormularioPlantilla = function() {
    if (window.plantillaNueva) {
        window.plantillaNueva.clearForm();
    }
};