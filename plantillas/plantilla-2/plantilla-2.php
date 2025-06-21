<?php require_once './includes/header.php'; ?>
<link rel="stylesheet" href="./css/plantilla-2/plantilla.css">

<div class="invitation-preview" data-template="template2">
    <div class="invitation-container">
        <section class="invitation-hero modern-hero">
            <div class="invitation-content">
                <div class="couple-initials">M & C</div>
                <h1 class="couple-names modern-names">María & Carlos</h1>
                <p class="invitation-subtitle">Celebremos juntos nuestro amor eterno</p>
                <div class="wedding-date modern-date">
                    <span class="date-text">15 • Agosto • 2025</span>
                </div>
            </div>
        </section>

        <section class="invitation-details modern-details">
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-time">4:00 PM</div>
                    <div class="timeline-content">
                        <h3>Ceremonia Religiosa</h3>
                        <p>Iglesia San Francisco</p>
                        <p>Calle Principal 123</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">6:00 PM</div>
                    <div class="timeline-content">
                        <h3>Recepción</h3>
                        <p>Salón de Eventos Jardín</p>
                        <p>Avenida de los Rosales 456</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-time">8:00 PM</div>
                    <div class="timeline-content">
                        <h3>Cena y Baile</h3>
                        <p>¡Que comience la fiesta!</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="love-story">
            <h2>Nuestra Historia de Amor</h2>
            <div class="story-timeline">
                <div class="story-item">
                    <div class="story-year">2020</div>
                    <div class="story-content">
                        <h4>Nos Conocimos</h4>
                        <p>Un encuentro casual que cambió nuestras vidas para siempre.</p>
                    </div>
                </div>
                <div class="story-item">
                    <div class="story-year">2022</div>
                    <div class="story-content">
                        <h4>Primer Viaje Juntos</h4>
                        <p>Descubrimos que éramos el equipo perfecto para la aventura.</p>
                    </div>
                </div>
                <div class="story-item">
                    <div class="story-year">2024</div>
                    <div class="story-content">
                        <h4>La Propuesta</h4>
                        <p>¡Él se arrodilló y ella dijo que sí!</p>
                    </div>
                </div>
                <div class="story-item">
                    <div class="story-year">2025</div>
                    <div class="story-content">
                        <h4>Nuestra Boda</h4>
                        <p>El comienzo de nuestro para siempre.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="invitation-gallery modern-gallery">
            <h2>Momentos Especiales</h2>
            <div class="gallery-slider">
                <div class="slider-container">
                    <img src="images/gallery/couple1.jpg" alt="Pareja 1" class="slider-image active">
                    <img src="images/gallery/couple2.jpg" alt="Pareja 2" class="slider-image">
                    <img src="images/gallery/couple3.jpg" alt="Pareja 3" class="slider-image">
                </div>
                <div class="slider-dots">
                    <span class="dot active" onclick="currentSlide(1)"></span>
                    <span class="dot" onclick="currentSlide(2)"></span>
                    <span class="dot" onclick="currentSlide(3)"></span>
                </div>
            </div>
        </section>

        <section class="invitation-footer modern-footer">
            <div class="footer-content">
                <p class="footer-message">¡No pueden faltar en este día tan especial!</p>
                <div class="confirmation-buttons">
                    <button class="btn btn-primary confirm-btn" onclick="confirmAttendance('si')">
                        ¡Ahí estaremos! ✨
                    </button>
                    <button class="btn btn-secondary confirm-btn" onclick="confirmAttendance('no')">
                        No podremos asistir 💔
                    </button>
                </div>
                <p class="footer-signature">Con todo nuestro amor,<br>María & Carlos</p>
            </div>
        </section>
    </div>

    <div class="preview-actions">
        <a href="plantillas.php" class="btn btn-secondary">← Volver a Plantillas</a>
        <a href="contacto.php" class="btn btn-primary">Personalizar Esta Invitación</a>
    </div>
</div>

<script>
// Slider para galería moderna
let slideIndex = 1;

function currentSlide(n) {
    showSlide(slideIndex = n);
}

function showSlide(n) {
    const slides = document.querySelectorAll('.slider-image');
    const dots = document.querySelectorAll('.dot');
    
    if (n > slides.length) slideIndex = 1;
    if (n < 1) slideIndex = slides.length;
    
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    slides[slideIndex - 1].classList.add('active');
    dots[slideIndex - 1].classList.add('active');
}

// Auto-slide cada 5 segundos
setInterval(() => {
    slideIndex++;
    showSlide(slideIndex);
}, 5000);

// Confirmación de asistencia
function confirmAttendance(response) {
    const message = response === 'si' 
        ? '¡Gracias por confirmar! Nos vemos en la boda 🎉' 
        : 'Gracias por avisarnos. ¡Te echaremos de menos! 💕';
    
    showToast(message);
}
</script>

<?php include './includes/footer.php'; ?>