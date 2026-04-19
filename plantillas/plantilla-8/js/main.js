/* Main.js - Funcionalidades generales */

document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll para links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Animación de entrada en scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('[data-animate]').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease-out';
        observer.observe(el);
    });

    // Efectos en hover para cards
    const cards = document.querySelectorAll('.character-card, .regalo-card, .ubicacion-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.animation = 'characterGlow 0.3s ease-out';
        });
    });

    // Eventos scroll para animaciones
    window.addEventListener('scroll', function() {
        const elements = document.querySelectorAll('[data-scroll-animate]');
        elements.forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight * 0.8) {
                el.classList.add('animated');
            }
        });
    });
});

// Función para compartir por WhatsApp
function shareWhatsApp() {
    const url = window.location.href;
    const title = document.querySelector('title')?.textContent || 'Mi invitación';
    const text = encodeURIComponent(`¡Hola! Te invito a ver mi invitación especial:\n\n${url}`);
    const whatsappUrl = `https://wa.me/?text=${text}`;
    window.open(whatsappUrl, '_blank');
}

// Función para confirmar por WhatsApp (RSVP)
function confirmarPorWhatsApp() {
    const numero = '<?php echo $numero_whatsapp_rsvp; ?>';
    const nombres = '<?php echo htmlspecialchars($nombres); ?>';
    const fecha = '<?php echo $fecha; ?>';
    const mensaje = encodeURIComponent(`¡Hola! Quiero confirmar mi asistencia a la celebración de ${nombres} el ${fecha}. ✨`);
    const whatsappUrl = `https://wa.me/${numero}?text=${mensaje}`;
    window.open(whatsappUrl, '_blank');
}

// Función para copiar enlace
function copyLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        const btn = event.target.closest('.footer-button');
        const originalText = btn.querySelector('.button-text').textContent;
        btn.querySelector('.button-text').textContent = '¡Copiado!';
        setTimeout(() => {
            btn.querySelector('.button-text').textContent = originalText;
        }, 2000);
    }).catch(() => {
        alert('Error al copiar el enlace');
    });
}

// Función para copiar al portapapeles
function copiarAlPortapapeles(texto) {
    navigator.clipboard.writeText(texto).then(() => {
        alert('Copiado al portapapeles');
    });
}

// Función para compartir
function compartirInvitacion(titulo) {
    if (navigator.share) {
        navigator.share({
            title: titulo,
            url: window.location.href
        });
    } else {
        // Fallback
        const url = window.location.href;
        const texto = `Mira mi invitación: ${url}`;
        copiarAlPortapapeles(url);
    }
}

// Lazy loading mejorado para imágenes
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.add('loaded');
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }
});
