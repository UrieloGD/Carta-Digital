// Configuración y variables globales
const CONFIG = {
    whatsappNumber: '525512345678', // Número de WhatsApp (incluir código de país)
    siteUrl: window.location.href,
    animationDelay: 100
};

// Inicialización cuando el DOM está cargado
document.addEventListener('DOMContentLoaded', function() {
    initializeAnimations();
    initializeScrollEffects();
    initializeFAQ();
    initializeForm();
    addSmoothScrolling();
});

// Inicializar animaciones de entrada
function initializeAnimations() {
    // Animar elementos del hero
    const heroElements = document.querySelectorAll('.hero-ornament, .hero-names, .hero-subtitle, .hero-details > *');
    heroElements.forEach((element, index) => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            element.style.transition = 'all 0.8s ease';
            element.style.opacity = '1';
            element.style.transform = 'translateY(0)';
        }, index * 200 + 500);
    });

    // Animar elementos del cronograma
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach((item, index) => {
        const delay = item.dataset.delay || (index * 200);
        item.style.animationDelay = `${delay}ms`;
    });

    // Animar elementos de galería
    const galeriaItems = document.querySelectorAll('.galeria-item');
    galeriaItems.forEach((item, index) => {
        const delay = item.dataset.delay || (index * 100);
        item.style.animationDelay = `${delay}ms`;
    });
}

// Efectos de scroll y animaciones en viewport
function initializeScrollEffects() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                // Animaciones específicas por sección
                if (entry.target.classList.contains('bienvenida')) {
                    animateBienvenida(entry.target);
                } else if (entry.target.classList.contains('historia')) {
                    animateHistoria(entry.target);
                } else if (entry.target.classList.contains('cronograma')) {
                    animateCronograma(entry.target);
                } else if (entry.target.classList.contains('galeria')) {
                    animateGaleria(entry.target);
                }
            }
        });
    }, observerOptions);

    // Observar secciones principales
    const sections = document.querySelectorAll('section');
    sections.forEach(section => {
        section.classList.add('fade-in-on-scroll');
        observer.observe(section);
    });
}

// Animaciones específicas por sección
function animateBienvenida(section) {
    const content = section.querySelector('.bienvenida-content');
    if (content) {
        content.style.animation = 'fadeInUp 1s ease-out';
    }
}

function animateHistoria(section) {
    const text = section.querySelector('.historia-text');
    const image = section.querySelector('.historia-image');
    
    if (text) {
        setTimeout(() => {
            text.style.animation = 'slideInLeft 0.8s ease-out';
        }, 200);
    }
    
    if (image) {
        setTimeout(() => {
            image.style.animation = 'fadeIn 0.8s ease-out';
        }, 400);
    }
}

function animateCronograma(section) {
    const items = section.querySelectorAll('.timeline-item');
    items.forEach((item, index) => {
        setTimeout(() => {
            item.classList.add('animate-slideInLeft');
        }, index * 200);
    });
}

function animateGaleria(section) {
    const items = section.querySelectorAll('.galeria-item');
    items.forEach((item, index) => {
        setTimeout(() => {
            item.classList.add('animate-scaleIn');
        }, index * 100);
    });
}

// Funcionalidad de FAQ (acordeón)
function initializeFAQ() {
    const faqQuestions = document.querySelectorAll('.faq-question');
    
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            const faqItem = this.parentElement;
            const faqAnswer = faqItem.querySelector('.faq-answer');
            const isActive = faqItem.classList.contains('active');
            
            // Cerrar todas las preguntas abiertas
            document.querySelectorAll('.faq-item.active').forEach(item => {
                item.classList.remove('active');
                item.querySelector('.faq-answer').classList.remove('active');
            });
            
            // Abrir la pregunta clickeada si no estaba activa
            if (!isActive) {
                faqItem.classList.add('active');
                faqAnswer.classList.add('active');
            }
        });
    });
}

// Función específica para toggle FAQ (llamada desde PHP)
function toggleFAQ(index) {
    const faqItem = document.querySelectorAll('.faq-item')[index];
    const faqAnswer = document.getElementById(`faq-${index}`);
    const isActive = faqItem.classList.contains('active');
    
    // Cerrar todas las preguntas abiertas
    document.querySelectorAll('.faq-item.active').forEach(item => {
        item.classList.remove('active');
        item.querySelector('.faq-answer').classList.remove('active');
    });
    
    // Abrir la pregunta clickeada si no estaba activa
    if (!isActive) {
        faqItem.classList.add('active');
        faqAnswer.classList.add('active');
    }
}

// Funcionalidad del modal RSVP
function openRSVPModal() {
    const modal = document.getElementById('rsvpModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Focus en el primer campo
    setTimeout(() => {
        const firstInput = modal.querySelector('input[type="text"]');
        if (firstInput) firstInput.focus();
    }, 300);
}

function closeRSVPModal() {
    const modal = document.getElementById('rsvpModal');
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
    
    // Limpiar formulario
    const form = document.getElementById('rsvpForm');
    if (form) form.reset();
}

// Cerrar modal al hacer clic fuera del contenido
document.addEventListener('click', function(e) {
    const modal = document.getElementById('rsvpModal');
    if (e.target === modal) {
        closeRSVPModal();
    }
});

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRSVPModal();
    }
});

// Inicializar formulario RSVP
function initializeForm() {
    const form = document.getElementById('rsvpForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar formulario
        if (!validateForm(form)) {
            return;
        }
        
        // Simular envío (aquí conectarías con tu backend)
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        // Mostrar loading en el botón
        const submitButton = form.querySelector('.form-submit');
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Enviando...';
        submitButton.disabled = true;
        
        // Simular delay de envío
        setTimeout(() => {
            console.log('Datos RSVP:', data);
            
            // Cerrar modal y mostrar mensaje de éxito
            closeRSVPModal();
            showSuccessMessage();
            
            // Restaurar botón
            submitButton.textContent = originalText;
            submitButton.disabled = false;
            
            // Aquí harías la petición real al servidor
            // fetch('/rsvp', { method: 'POST', body: formData })
        }, 1500);
    });
}

// Validar formulario RSVP
function validateForm(form) {
    const nombre = form.querySelector('#nombre').value.trim();
    const asistencia = form.querySelector('#asistencia').value;
    
    // Limpiar errores previos
    clearFormErrors(form);
    
    let isValid = true;
    
    // Validar nombre
    if (!nombre) {
        showFieldError(form.querySelector('#nombre'), 'El nombre es requerido');
        isValid = false;
    } else if (nombre.length < 2) {
        showFieldError(form.querySelector('#nombre'), 'El nombre debe tener al menos 2 caracteres');
        isValid = false;
    }
    
    // Validar asistencia
    if (!asistencia) {
        showFieldError(form.querySelector('#asistencia'), 'Por favor selecciona si asistirás');
        isValid = false;
    }
    
    return isValid;
}

// Mostrar error en campo específico
function showFieldError(field, message) {
    field.style.borderColor = '#dc3545';
    
    // Crear o actualizar mensaje de error
    let errorDiv = field.parentNode.querySelector('.field-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.color = '#dc3545';
        errorDiv.style.fontSize = '0.875rem';
        errorDiv.style.marginTop = '0.25rem';
        field.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

// Limpiar errores del formulario
function clearFormErrors(form) {
    const fields = form.querySelectorAll('input, select, textarea');
    fields.forEach(field => {
        field.style.borderColor = '#e0e0e0';
    });
    
    const errors = form.querySelectorAll('.field-error');
    errors.forEach(error => error.remove());
}

// Mostrar mensaje de éxito
function showSuccessMessage() {
    const successMessage = document.getElementById('successMessage');
    successMessage.classList.add('show');
    
    // Ocultar después de 3 segundos
    setTimeout(() => {
        successMessage.classList.remove('show');
    }, 3000);
}

// Funcionalidad de compartir en WhatsApp
function shareWhatsApp() {
    const message = `¡Estás invitado a nuestra boda! 💒❤️\n\nVisita nuestra invitación digital: ${CONFIG.siteUrl}`;
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

// Copiar enlace de la invitación
function copyLink() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(CONFIG.siteUrl).then(() => {
            showTemporaryMessage('¡Enlace copiado al portapapeles!');
        }).catch(() => {
            fallbackCopyLink();
        });
    } else {
        fallbackCopyLink();
    }
}

// Fallback para copiar enlace en navegadores antiguos
function fallbackCopyLink() {
    const textArea = document.createElement('textarea');
    textArea.value = CONFIG.siteUrl;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showTemporaryMessage('¡Enlace copiado al portapapeles!');
    } catch (err) {
        showTemporaryMessage('No se pudo copiar el enlace. Inténtalo manualmente.');
    }
    
    document.body.removeChild(textArea);
}

// Mostrar mensaje temporal
function showTemporaryMessage(message) {
    // Crear elemento de mensaje temporal
    const messageDiv = document.createElement('div');
    messageDiv.textContent = message;
    messageDiv.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #850d23;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        z-index: 1002;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInUp 0.3s ease-out;
    `;
    
    document.body.appendChild(messageDiv);
    
    // Remover después de 2 segundos
    setTimeout(() => {
        messageDiv.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(messageDiv);
        }, 300);
    }, 2000);
}

// Scroll suave a secciones
function addSmoothScrolling() {
    // Si hay enlaces de navegación, añadir comportamiento de scroll suave
    const navLinks = document.querySelectorAll('a[href^="#"]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// Función para generar código QR (opcional, requiere librería externa)
function generateQR() {
    // Esta función requeriría una librería como qrcode.js
    // Por simplicidad, mostramos un placeholder
    console.log('Función QR no implementada. Agregar librería qrcode.js si se necesita.');
}

// Funcionalidad de impresión optimizada
function printInvitation() {
    const printContent = document.body.innerHTML;
    const printWindow = window.open('', '', 'width=800,height=600');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invitación de Boda - Impresión</title>
            <link rel="stylesheet" href="invitacion.css">
            <style>
                body { background: white !important; }
                .rsvp, .footer-actions, .galeria-overlay { display: none !important; }
                .hero, section { break-inside: avoid; }
            </style>
        </head>
        <body onload="window.print(); window.close();">
            ${printContent}
        </body>
        </html>
    `);
    
    printWindow.document.close();
}

// Manejo de errores globales
window.addEventListener('error', function(e) {
    console.error('Error en la aplicación:', e.error);
    // Aquí podrías enviar errores a un servicio de logging
});

// Funciones de utilidad
const Utils = {
    // Debounce para optimizar eventos de scroll/resize
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Detectar si es dispositivo móvil
    isMobile: function() {
        return window.innerWidth <= 768;
    },
    
    // Formatear fecha
    formatDate: function(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            weekday: 'long'
        };
        return new Date(dateString).toLocaleDateString('es-ES', options);
    }
};

// Optimizaciones de rendimiento
document.addEventListener('DOMContentLoaded', function() {
    // Lazy loading para imágenes
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
});