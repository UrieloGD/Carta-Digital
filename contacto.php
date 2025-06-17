<?php $page_title = "Contacto"; include './includes/header.php'; ?>

<link rel="stylesheet" href="./css/contacto.css">

<section class="contact">
    <div class="container">
        <!-- Título principal -->
        <div class="invitation-request">
            <h1>¿Quieres tu invitación personalizada?</h1>
            <p>Escríbenos o contáctanos directamente</p>
        </div>

        <div class="contact-content">
            <!-- Formulario de contacto -->
            <div class="contact-form-container">
                <form class="contact-form" id="contactForm">
                    <div class="form-group">
                        <label for="name">Nombre</label>
                        <input type="text" id="name" name="name" placeholder="Tu nombre" required>
                        <span class="error-message" id="nameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" placeholder="tu@correo.com" required>
                        <span class="error-message" id="emailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="message">Mensaje</label>
                        <textarea id="message" name="message" rows="5" placeholder="¿Qué tipo de invitación necesitas? ¿Para qué evento?" required></textarea>
                        <span class="error-message" id="messageError"></span>
                    </div>

                    <button type="submit" class="btn-send">Enviar mensaje</button>
                </form>
            </div>

            <!-- Información de contacto y redes sociales -->
            <div>
                <!-- Información de contacto -->
                <div class="contact-info-card">
                    <h2>Información de contacto</h2>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <i class="fa fa-map-marker contact-icon"></i>
                            <span>Av. Insurgentes Sur 1234, Col. Del Valle, Ciudad de México</span>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fa fa-phone contact-icon"></i>
                            <span>+52 55 1234 5678</span>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fa fa-envelope contact-icon"></i>
                            <span>contacto@cartadigital.com</span>
                        </div>
                    </div>
                </div>

                <!-- Redes sociales con recuadro -->
                <div class="social-media-card">
                    <h3>Síguenos en redes sociales</h3>
                    <div class="contact-social-buttons">
                        <a href="#" class="contact-social-btn instagram">
                            <img src="./images/logos/instagram.webp" alt="Instagram" class="contact-social-icon">
                            <span>Instagram</span>
                        </a>
                        <a href="#" class="contact-social-btn facebook">
                            <img src="./images/logos/facebook.webp" alt="Facebook" class="contact-social-icon">
                            <span>Facebook</span>
                        </a>
                        <a href="#" class="contact-social-btn whatsapp">
                            <img src="./images/logos/whatsapp.webp" alt="WhatsApp" class="contact-social-icon">
                            <span>WhatsApp</span>
                        </a>
                        <a href="#" class="contact-social-btn messenger">
                            <img src="./images/logos/Messenger.webp" alt="Messenger" class="contact-social-icon">
                            <span>Messenger</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include './includes/footer.php'; ?>