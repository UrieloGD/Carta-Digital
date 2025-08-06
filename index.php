<?php require_once './includes/header.php'; ?>

<link rel="stylesheet" href="./css/index.css?v=<?php echo filemtime('./css/index.css'); ?>" />
<!-- Hero Section -->
<section class="hero">
    <div class="hero-background">
        <img src="./images/hero.webp" alt="Pareja en la playa">
    </div>
    <div class="hero-overlay"></div>
    <div class="container">
        <div class="hero-content">
            <h1>Invitaciones digitales</h1>
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
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>100% Digital</h3>
                <p>Accesible desde cualquier dispositivo, sin necesidad de papel.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fa fa-star"></i>
                </div>
                <h3>Diseño Elegante</h3>
                <p>Diseños sofisticados que reflejan la belleza y emoción de tu día especial.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-palette"></i>
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
                    <i class="fas fa-share-alt"></i>
                </div>
                <h3>Fácil de compartir</h3>
                <p>Envía tu invitación por WhatsApp, email o redes sociales.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Entrega Rápida</h3>
                <p>Recibe tu invitación en menos de 24 horas.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fas fa-images"></i>
                </div>
                <h3>Galería de fotos</h3>
                <p>Incluye vuestras mejores fotografías para compartir.</p>
            </div>
            
            <div class="benefit-card">
                <div class="benefit-icon">
                    <i class="fas fa-map-marker-alt"></i>
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
                <img src="./images/plantilla-1.png" alt="Elegancia Dorada">
                <div class="template-info">
                    <h3>Elegancia Dorada</h3>
                    <a href="#" class="btn-template">Ver plantilla</a>
                </div>
            </div>
            
            <div class="template-card">
                <img src="./images/plantilla-1.png" alt="Elegancia Dorada">
                <div class="template-info">
                    <h3>Elegancia Dorada</h3>
                    <a href="#" class="btn-template">Ver plantilla</a>
                </div>
            </div>
            
            <div class="template-card">
                <img src="./images/plantilla-1.png" alt="Elegancia Dorada">
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
    <div class="container">
        <div class="quote-content">
            <h2>"Cada historia de amor merece una invitación inolvidable"</h2>
            <p>Haz que cada detalle cuente desde el primer momento</p>
        </div>
    </div>
</section>

<script>
// Intersection Observer para activar animaciones al hacer scroll
const observerOptions = {
    threshold: 0.3,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate');
        }
    });
}, observerOptions);

// Observar la sección quote
document.addEventListener('DOMContentLoaded', () => {
    const quoteContent = document.querySelector('.quote-content');
    if (quoteContent) {
        observer.observe(quoteContent);
    }
});
</script>
<?php 
    include './includes/footer.php';
?>