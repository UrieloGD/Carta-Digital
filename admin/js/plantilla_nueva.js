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
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, crear plantilla',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'Creando plantilla...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
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
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido'
                });
                
                // Marcar como mostrado
                sessionStorage.setItem('plantillaNuevaWelcomeShown', 'true');
            }, 1000);
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