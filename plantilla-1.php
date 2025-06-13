<?php include './includes/header.php'; ?>
<link rel="stylesheet" href="./css/plantilla-1/plantilla.css">

<div class="invitation-preview">
    <div class="invitation-container">
        <section class="invitation-hero">
            <div class="invitation-content">
                <h1 class="couple-names">María & Carlos</h1>
                <p class="invitation-subtitle">Te invitamos a celebrar nuestro amor</p>
                <div class="wedding-date">
                    <span class="date-text">15 de Agosto, 2025</span>
                </div>
            </div>
        </section>

        <section class="invitation-details">
            <div class="detail-section">
                <h2>Ceremonia</h2>
                <p><strong>Fecha:</strong> Sábado 15 de Agosto, 2025</p>
                <p><strong>Hora:</strong> 4:00 PM</p>
                <p><strong>Lugar:</strong> Iglesia San Francisco<br>Calle Principal 123, Ciudad</p>
            </div>

            <div class="detail-section">
                <h2>Recepción</h2>
                <p><strong>Hora:</strong> 6:00 PM</p>
                <p><strong>Lugar:</strong> Salón de Eventos Jardín<br>Avenida de los Rosales 456</p>
            </div>
        </section>

        <section class="invitation-gallery">
            <h2>Nuestra Historia</h2>
            <div class="gallery-grid">
                <img src="images/gallery/couple1.jpg" alt="Pareja 1" class="gallery-image" onclick="openLightbox('images/gallery/couple1.jpg')">
                <img src="images/gallery/couple2.jpg" alt="Pareja 2" class="gallery-image" onclick="openLightbox('images/gallery/couple2.jpg')">
                <img src="images/gallery/couple3.jpg" alt="Pareja 3" class="gallery-image" onclick="openLightbox('images/gallery/couple3.jpg')">
                <img src="images/gallery/couple4.jpg" alt="Pareja 4" class="gallery-image" onclick="openLightbox('images/gallery/couple4.jpg')">
            </div>
        </section>

        <section class="countdown-section">
            <h2>Cuenta Regresiva</h2>
            <div class="countdown" id="countdown">
                <div class="countdown-item">
                    <span class="countdown-number" id="days">00</span>
                    <span class="countdown-label">días</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="hours">00</span>
                    <span class="countdown-label">horas</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="minutes">00</span>
                    <span class="countdown-label">minutos</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="seconds">00</span>
                    <span class="countdown-label">segundos</span>
                </div>
            </div>
        </section>

        <section class="invitation-map">
            <h2>¿Cómo llegar?</h2>
            <div class="map-placeholder">
                <p>📍 Mapa interactivo aquí</p>
                <a href="https://maps.google.com" target="_blank" class="btn btn-secondary">Ver en Google Maps</a>
            </div>
        </section>

        <section class="invitation-footer">
            <p class="footer-message">Tu presencia es el mejor regalo</p>
            <p class="footer-signature">Con amor,<br>María & Carlos</p>
        </section>
    </div>

    <div class="preview-actions">
        <a href="plantillas.php" class="btn btn-secondary">← Volver a Plantillas</a>
        <a href="contacto.php" class="btn btn-primary">Personalizar Esta Invitación</a>
    </div>
</div>

<div class="lightbox" id="lightbox">
    <div class="lightbox-content">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <img id="lightbox-image" src="" alt="">
    </div>
</div>

<?php include './includes/footer.php'; ?>