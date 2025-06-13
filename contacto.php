<?php $page_title = "Contacto"; include './includes/header.php'; ?>

<link rel="stylesheet" href="./css/contacto.css">

<section class="page-header">
    <div class="container">
        <h1>Contacto</h1>
        <p>¿Listo para crear tu invitación perfecta? Hablemos</p>
    </div>
</section>

<section class="contact">
    <div class="container">
        <div class="contact-content">
            <div class="contact-info">
                <h2>Conversemos sobre tu boda</h2>
                <p>Estamos aquí para ayudarte a crear la invitación digital perfecta para tu día especial. Cada detalle importa.</p>
                
                <div class="contact-details">
                    <div class="contact-item">
                        <strong>Email:</strong>
                        <span>info@cartadigital.com</span>
                    </div>
                    <div class="contact-item">
                        <strong>Teléfono:</strong>
                        <span>+1 234 567 890</span>
                    </div>
                    <div class="contact-item">
                        <strong>Horario:</strong>
                        <span>Lun - Vie: 9:00 AM - 6:00 PM</span>
                    </div>
                </div>
            </div>

            <div class="contact-form-container">
                <form class="contact-form" id="contactForm">
                    <div class="form-group">
                        <label for="name">Nombre completo</label>
                        <input type="text" id="name" name="name" required>
                        <span class="error-message" id="nameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo electrónico</label>
                        <input type="email" id="email" name="email" required>
                        <span class="error-message" id="emailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="phone">Teléfono (opcional)</label>
                        <input type="tel" id="phone" name="phone">
                    </div>

                    <div class="form-group">
                        <label for="wedding-date">Fecha de boda</label>
                        <input type="date" id="wedding-date" name="wedding-date">
                    </div>

                    <div class="form-group">
                        <label for="message">Cuéntanos sobre tu boda</label>
                        <textarea id="message" name="message" rows="5" placeholder="Describe tu visión, estilo preferido, colores, y cualquier detalle especial que te gustaría incluir en tu invitación..." required></textarea>
                        <span class="error-message" id="messageError"></span>
                    </div>

                    <button type="submit" class="btn btn-primary">Enviar Mensaje</button>
                </form>
            </div>
        </div>
    </div>
</section>

<div class="modal" id="successModal">
    <div class="modal-content">
        <h3>¡Mensaje enviado!</h3>
        <p>Gracias por contactarnos. Te responderemos pronto para crear tu invitación perfecta.</p>
        <button class="btn btn-primary" onclick="closeModal()">Cerrar</button>
    </div>
</div>

<?php include './includes/footer.php'; ?>