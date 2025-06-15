<?php require_once './includes/header.php'; ?>

<link rel="stylesheet" href="./css/index.css">

<!-- Hero Section -->
<section class="hero">
    <div class="hero-background">
        <img src="./images/hero.webp" alt="Pareja en la playa">
    </div>
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <h1>Invitaciones digitales de</h1>
            <h2>con estilo y elegancia</h2>
            <p>Crea momentos inolvidables con nuestras exclusivas invitaciones digitales.</p>
            <div class="hero-buttons">
                <a href="./plantillas.php" class="btn btn-primary">Explorar plantillas</a>
                <a href="./contacto.php" class="btn btn-secondary">Contáctanos</a>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="about">
    <div class="container">
        <h2>¿Qué es <span class="highlight">Carta Digital?</span></h2>
        <p>Carta Digital transforma tus invitaciones de boda en experiencias digitales interactivas, elegantes y personalizables que reflejan la esencia única de vuestra historia de amor.</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L2 7v10c0 5.55 3.84 9.74 9 11 5.16-1.26 9-5.45 9-11V7l-10-5z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <h3>100% Digital</h3>
                <p>Accesible desde cualquier dispositivo, sin necesidad de papel.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <h3>Diseño Elegante</h3>
                <p>Diseños sofisticados que reflejan la belleza y emoción de tu día especial.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <h3>Personalizado</h3>
                <p>Cada detalle adaptado a tu estilo y necesidades, creando una experiencia única.</p>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="benefits">
    <div class="container">
        <h2>Beneficios</h2>
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                        <path d="M8 5v14l11-7z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <h3>Fácil de compartir</h3>
                <p>Envía tu invitación por WhatsApp, email o redes sociales.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <h3>Entrega Rápida</h3>
                <p>Recibe tu invitación en menos de 24 horas.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <h3>Galería de fotos</h3>
                <p>Incluye vuestras mejores fotografías para compartir.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <h3>Mapas y detalles</h3>
                <p>Incluye ubicaciones y toda la información necesaria.</p>
            </div>
        </div>
    </div>
</section>

<!-- Templates Preview -->
<section class="templates-preview">
    <div class="container">
        <h2>Nuestras Plantillas</h2>
        <p>Explora nuestra colección de invitaciones digitales y encuentra la que mejor se adapte a tu estilo.</p>
        
        <div class="templates-grid">
            <div class="template-card">
                <img src="./images/template1.jpg" alt="Elegancia Dorada">
                <div class="template-info">
                    <h3>Elegancia Dorada</h3>
                    <a href="#" class="btn-template">Ver plantilla</a>
                </div>
            </div>
            
            <div class="template-card">
                <img src="./images/template2.jpg" alt="Elegancia Dorada">
                <div class="template-info">
                    <h3>Elegancia Dorada</h3>
                    <a href="#" class="btn-template">Ver plantilla</a>
                </div>
            </div>
            
            <div class="template-card">
                <img src="./images/template3.jpg" alt="Elegancia Dorada">
                <div class="template-info">
                    <h3>Elegancia Dorada</h3>
                    <a href="#" class="btn-template">Ver plantilla</a>
                </div>
            </div>
            
            <div class="template-card">
                <img src="./images/template4.jpg" alt="Elegancia Dorada">
                <div class="template-info">
                    <h3>Elegancia Dorada</h3>
                    <a href="#" class="btn-template">Ver plantilla</a>
                </div>
            </div>
        </div>
        
        <div class="templates-cta">
            <a href="./plantillas.php" class="btn btn-primary">Ver todas las plantillas</a>
        </div>
    </div>
</section>

<!-- Quote Section -->
<section class="quote">
    <div class="quote-content">
        <h2>"Cada historia de amor merece una invitación inolvidable"</h2>
        <p>Haz que cada detalle cuente desde el primer momento</p>
    </div>
</section>

<?php include './includes/footer.php'; ?>