// Configuración y variables globales
const CONFIG = {
    whatsappNumber: '525512345678',
    siteUrl: window.location.href,
    animationDelay: 100
};

// Inicialización cuando el DOM está cargado
document.addEventListener('DOMContentLoaded', function() {
    initializeAnimations();
    initializeScrollEffects();
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
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                entry.target.classList.add('animate-in'); // ✅ AÑADIR CLASE PARA FOOTER
                
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
                
                // Dejar de observar después de animar
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observar secciones principales
    const sections = document.querySelectorAll('section');
    sections.forEach(section => {
        section.classList.add('fade-in-on-scroll');
        observer.observe(section);
    });
    
    // ✅ OBSERVAR ELEMENTOS CON data-animate (FOOTER)
    const animateElements = document.querySelectorAll('[data-animate]');
    animateElements.forEach(element => {
        observer.observe(element);
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

// Scroll suave a secciones
function addSmoothScrolling() {
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

// Mostrar mensaje temporal
function showTemporaryMessage(message) {
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
    
    setTimeout(() => {
        messageDiv.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            document.body.removeChild(messageDiv);
        }, 300);
    }, 2000);
}

// Función para generar código QR
function generateQR() {
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
            <title>Invitación - Impresión</title>
            <link rel="stylesheet" href="./plantillas/plantilla-7/css/global.css">
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
});

// Funciones de utilidad
const Utils = {
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
    
    isMobile: function() {
        return window.innerWidth <= 768;
    },
    
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

// Optimizaciones de rendimiento - Lazy loading
document.addEventListener('DOMContentLoaded', function() {
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
