// ========================================
// VARIABLES GLOBALES
// ========================================
let navToggle, navMenu, contactForm, successModal;

// ========================================
// INICIALIZACIÓN
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeElements();
    initializeEventListeners();
    initializeCountdown();
});

// ========================================
// INICIALIZAR ELEMENTOS
// ========================================
function initializeElements() {
    navToggle = document.getElementById('nav-toggle');
    navMenu = document.getElementById('nav-menu');
    contactForm = document.getElementById('contactForm');
    successModal = document.getElementById('successModal');
}

// ========================================
// INICIALIZAR EVENT LISTENERS
// ========================================
function initializeEventListeners() {
    // Navegación móvil
    if (navToggle && navMenu) {
        navToggle.addEventListener('click', toggleMobileMenu);
    }

    // Formulario de contacto
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactForm);
    }

    // Cerrar menú móvil al hacer clic en enlaces
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });

    // Smooth scroll para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', handleSmoothScroll);
    });

    // Cerrar modal al hacer clic fuera
    if (successModal) {
        successModal.addEventListener('click', function(e) {
            if (e.target === successModal) {
                closeModal();
            }
        });
    }

    // Efectos de scroll para animaciones
    window.addEventListener('scroll', handleScrollAnimations);
}

// ========================================
// NAVEGACIÓN MÓVIL
// ========================================
function toggleMobileMenu() {
    navMenu.classList.toggle('active');
    navToggle.classList.toggle('active');
    
    // Prevenir scroll del body cuando el menú está abierto
    if (navMenu.classList.contains('active')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
}

function closeMobileMenu() {
    navMenu.classList.remove('active');
    navToggle.classList.remove('active');
    document.body.style.overflow = '';
}

// ========================================
// FORMULARIO DE CONTACTO
// ========================================
function handleContactForm(e) {
    e.preventDefault();
    
    // Limpiar errores previos
    clearFormErrors();
    
    // Validar formulario
    if (validateForm()) {
        // Simular envío exitoso
        showSuccessModal();
        contactForm.reset();
    }
}

function validateForm() {
    let isValid = true;
    
    // Validar nombre
    const name = document.getElementById('name');
    if (name.value.trim().length < 2) {
        showError('nameError', 'El nombre debe tener al menos 2 caracteres');
        isValid = false;
    }
    
    // Validar email
    const email = document.getElementById('email');
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email.value.trim())) {
        showError('emailError', 'Por favor ingresa un email válido');
        isValid = false;
    }
    
    // Validar mensaje
    const message = document.getElementById('message');
    if (message.value.trim().length < 10) {
        showError('messageError', 'El mensaje debe tener al menos 10 caracteres');
        isValid = false;
    }
    
    return isValid;
}

function showError(errorId, message) {
    const errorElement = document.getElementById(errorId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }
}

function clearFormErrors() {
    document.querySelectorAll('.error-message').forEach(error => {
        error.textContent = '';
        error.classList.remove('show');
    });
}

// ========================================
// MODAL DE ÉXITO
// ========================================
function showSuccessModal() {
    if (successModal) {
        successModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal() {
    if (successModal) {
        successModal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// ========================================
// LIGHTBOX PARA GALERÍA
// ========================================
function openLightbox(imageSrc) {
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightbox-image');
    
    if (lightbox && lightboxImage) {
        lightboxImage.src = imageSrc;
        lightbox.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeLightbox() {
   const lightbox = document.getElementById('lightbox');
   
   if (lightbox) {
       lightbox.classList.remove('show');
       document.body.style.overflow = '';
   }
}

// Cerrar lightbox con tecla Escape
document.addEventListener('keydown', function(e) {
   if (e.key === 'Escape') {
       closeLightbox();
       closeModal();
       closeMobileMenu();
   }
});

// ========================================
// CUENTA REGRESIVA
// ========================================
function initializeCountdown() {
   const countdownElement = document.getElementById('countdown');
   if (!countdownElement) return;
   
   // Fecha de la boda - Cambiar por la fecha deseada
   const weddingDate = new Date('2025-08-15T16:00:00').getTime();
   
   updateCountdown(weddingDate);
   
   // Actualizar cada segundo
   setInterval(() => updateCountdown(weddingDate), 1000);
}

function updateCountdown(weddingDate) {
   const now = new Date().getTime();
   const distance = weddingDate - now;
   
   if (distance < 0) {
       // La boda ya pasó
       document.getElementById('days').textContent = '00';
       document.getElementById('hours').textContent = '00';
       document.getElementById('minutes').textContent = '00';
       document.getElementById('seconds').textContent = '00';
       return;
   }
   
   const days = Math.floor(distance / (1000 * 60 * 60 * 24));
   const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
   const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
   const seconds = Math.floor((distance % (1000 * 60)) / 1000);
   
   // Actualizar elementos con animación
   animateCounterChange('days', days);
   animateCounterChange('hours', hours);
   animateCounterChange('minutes', minutes);
   animateCounterChange('seconds', seconds);
}

function animateCounterChange(elementId, newValue) {
   const element = document.getElementById(elementId);
   if (!element) return;
   
   const currentValue = element.textContent;
   const formattedValue = newValue.toString().padStart(2, '0');
   
   if (currentValue !== formattedValue) {
       element.style.transform = 'scale(1.1)';
       element.style.color = '#d4af37';
       
       setTimeout(() => {
           element.textContent = formattedValue;
           element.style.transform = 'scale(1)';
           element.style.color = '';
       }, 150);
   }
}

// ========================================
// SMOOTH SCROLL
// ========================================
function handleSmoothScroll(e) {
   e.preventDefault();
   const targetId = this.getAttribute('href');
   const targetElement = document.querySelector(targetId);
   
   if (targetElement) {
       const headerOffset = 80;
       const elementPosition = targetElement.getBoundingClientRect().top;
       const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
       
       window.scrollTo({
           top: offsetPosition,
           behavior: 'smooth'
       });
   }
}

// ========================================
// ANIMACIONES DE SCROLL
// ========================================
function handleScrollAnimations() {
   const elements = document.querySelectorAll('.feature-card, .template-card, .detail-section');
   
   elements.forEach(element => {
       const elementTop = element.getBoundingClientRect().top;
       const elementVisible = 150;
       
       if (elementTop < window.innerHeight - elementVisible) {
           element.classList.add('animate-in');
       }
   });
}

// ========================================
// UTILIDADES
// ========================================

// Función para formatear fecha
function formatDate(date) {
   const options = { 
       year: 'numeric', 
       month: 'long', 
       day: 'numeric',
       weekday: 'long'
   };
   return date.toLocaleDateString('es-ES', options);
}

// Función para validar teléfono
function validatePhone(phone) {
   const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
   return phoneRegex.test(phone.replace(/\s/g, ''));
}

// Función para mostrar notificaciones toast
function showToast(message, type = 'success') {
   const toast = document.createElement('div');
   toast.className = `toast toast-${type}`;
   toast.textContent = message;
   
   // Estilos del toast
   toast.style.cssText = `
       position: fixed;
       top: 20px;
       right: 20px;
       padding: 1rem 1.5rem;
       background: ${type === 'success' ? '#4CAF50' : '#f44336'};
       color: white;
       border-radius: 5px;
       z-index: 9999;
       opacity: 0;
       transform: translateX(100%);
       transition: all 0.3s ease;
   `;
   
   document.body.appendChild(toast);
   
   // Mostrar toast
   setTimeout(() => {
       toast.style.opacity = '1';
       toast.style.transform = 'translateX(0)';
   }, 100);
   
   // Ocultar toast después de 3 segundos
   setTimeout(() => {
       toast.style.opacity = '0';
       toast.style.transform = 'translateX(100%)';
       setTimeout(() => {
           document.body.removeChild(toast);
       }, 300);
   }, 3000);
}

// ========================================
// EFECTOS ESPECIALES
// ========================================

// Efecto de partículas para el hero
function createParticles() {
   const hero = document.querySelector('.hero');
   if (!hero) return;
   
   for (let i = 0; i < 50; i++) {
       const particle = document.createElement('div');
       particle.className = 'particle';
       particle.style.cssText = `
           position: absolute;
           width: 2px;
           height: 2px;
           background: #d4af37;
           border-radius: 50%;
           opacity: 0.6;
           animation: float ${Math.random() * 10 + 5}s ease-in-out infinite;
           left: ${Math.random() * 100}%;
           top: ${Math.random() * 100}%;
           animation-delay: ${Math.random() * 5}s;
       `;
       hero.appendChild(particle);
   }
}

// Efecto parallax simple
function initParallax() {
   window.addEventListener('scroll', () => {
       const scrolled = window.pageYOffset;
       const parallaxElements = document.querySelectorAll('.parallax');
       
       parallaxElements.forEach(element => {
           const speed = element.dataset.speed || 0.5;
           element.style.transform = `translateY(${scrolled * speed}px)`;
       });
   });
}

// ========================================
// INICIALIZACIÓN DE EFECTOS ESPECIALES
// ========================================
document.addEventListener('DOMContentLoaded', function() {
   // Inicializar efectos especiales solo si no es móvil
   if (window.innerWidth > 768) {
       createParticles();
       initParallax();
   }
   
   // Lazy loading para imágenes
   initLazyLoading();
});

// ========================================
// LAZY LOADING
// ========================================
function initLazyLoading() {
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
}

// ========================================
// FUNCIONES PARA PLANTILLAS ESPECÍFICAS
// ========================================

// Función para plantilla2.php si se crea
function initTemplate2() {
   // Código específico para la segunda plantilla
   console.log('Template 2 initialized');
}

// Función para manejar múltiples plantillas
function initTemplateSpecific() {
   const templateId = document.body.dataset.template;
   
   switch(templateId) {
       case 'template1':
           initializeCountdown();
           break;
       case 'template2':
           initTemplate2();
           break;
       default:
           break;
   }
}

// ========================================
// MANEJO DE ERRORES
// ========================================
window.addEventListener('error', function(e) {
   console.error('Error detectado:', e.error);
   // Aquí puedes agregar logging o manejo de errores
});

// ========================================
// PERFORMANCE OPTIMIZATION
// ========================================

// Throttle para eventos de scroll
function throttle(func, limit) {
   let inThrottle;
   return function() {
       const args = arguments;
       const context = this;
       if (!inThrottle) {
           func.apply(context, args);
           inThrottle = true;
           setTimeout(() => inThrottle = false, limit);
       }
   };
}

// Optimizar eventos de scroll
const optimizedScrollHandler = throttle(handleScrollAnimations, 100);
window.addEventListener('scroll', optimizedScrollHandler);