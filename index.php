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
                <p>Incluye tus mejores fotografías para compartir.</p>
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
        <h2>Nuestras Plantillas Más Populares</h2>
        <p>Explora nuestra colección de invitaciones digitales y encuentra la que mejor se adapte a tu estilo.</p>
        
        <div class="templates-grid">
            <?php
            // Obtener las 3 plantillas más utilizadas
            try {
                require_once './config/database.php';
                
                $database = new Database();
                $db = $database->getConnection();
                
                // Query para obtener las plantillas más utilizadas con sus invitaciones de ejemplo
                $stmt = $db->prepare("
                    SELECT p.*, 
                           COUNT(DISTINCT i.id) as total_invitaciones, 
                           ie.slug as ejemplo_slug,
                           ie.nombres_novios as ejemplo_nombres
                    FROM plantillas p 
                    LEFT JOIN invitaciones i ON p.id = i.plantilla_id 
                    LEFT JOIN invitaciones ie ON p.invitacion_ejemplo_id = ie.id
                    WHERE p.activa = 1 
                    GROUP BY p.id, ie.slug, ie.nombres_novios
                    ORDER BY total_invitaciones DESC, p.fecha_creacion DESC 
                    LIMIT 3
                ");
                $stmt->execute();
                $plantillasPopulares = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($plantillasPopulares)):
                    foreach ($plantillasPopulares as $plantilla):
                        // Construir la ruta de la imagen
                        $imagenRuta = './images/default-template.png';
                        if (!empty($plantilla['imagen_preview'])) {
                            $imagenCompleta = './plantillas/' . $plantilla['carpeta'] . '/' . $plantilla['imagen_preview'];
                            if (file_exists($imagenCompleta)) {
                                $imagenRuta = $imagenCompleta;
                            }
                        }
                        
                        // Determinar la URL de destino y texto del botón
                        $tieneEjemplo = !empty($plantilla['ejemplo_slug']);
                        $urlDestino = $tieneEjemplo 
                            ? './invitacion.php?slug=' . urlencode($plantilla['ejemplo_slug'])
                            : '#';
                        $textoBoton = $tieneEjemplo ? 'Ver ejemplo' : 'Próximamente';
                        $claseBoton = $tieneEjemplo ? 'btn-template' : 'btn-template disabled';
                        
                        // Mostrar información adicional si hay ejemplo
                        $infoEjemplo = $tieneEjemplo ? ' - ' . htmlspecialchars($plantilla['ejemplo_nombres']) : '';
                ?>
                        <div class="template-card">
                            <img src="<?php echo htmlspecialchars($imagenRuta); ?>" 
                                 alt="<?php echo htmlspecialchars($plantilla['nombre']); ?>"
                                 onerror="this.src='./images/default-template.png'"
                                 loading="lazy">
                            <div class="template-info">
                                <h3><?php echo htmlspecialchars($plantilla['nombre']); ?></h3>
                                <?php if ($plantilla['descripcion']): ?>
                                    <p class="template-description"><?php echo htmlspecialchars($plantilla['descripcion']); ?></p>
                                <?php endif; ?>
                                
                                <div class="template-actions">
                                    <?php if ($tieneEjemplo): ?>
                                        <a href="<?php echo $urlDestino; ?>" 
                                           class="<?php echo $claseBoton; ?>"
                                           target="_blank"
                                           rel="noopener"><?php echo $textoBoton; ?></a>
                                        <a href="./contacto.php?plantilla=<?php echo $plantilla['id']; ?>" 
                                           class="btn-template btn-secondary">Solicitar</a>
                                    <?php else: ?>
                                        <span class="<?php echo $claseBoton; ?>"><?php echo $textoBoton; ?></span>
                                        <a href="./contacto.php?plantilla=<?php echo $plantilla['id']; ?>" 
                                           class="btn-template btn-primary">Solicitar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                <?php 
                    endforeach;
                else: 
                    // Si no hay plantillas con invitaciones, mostrar las 3 más recientes
                    $stmt = $db->prepare("
                        SELECT p.*, 
                               ie.slug as ejemplo_slug,
                               ie.nombres_novios as ejemplo_nombres
                        FROM plantillas p 
                        LEFT JOIN invitaciones ie ON p.invitacion_ejemplo_id = ie.id
                        WHERE p.activa = 1 
                        ORDER BY p.fecha_creacion DESC 
                        LIMIT 3
                    ");
                    $stmt->execute();
                    $plantillasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($plantillasRecientes as $plantilla):
                        $imagenRuta = './images/default-template.png';
                        if (!empty($plantilla['imagen_preview'])) {
                            $imagenCompleta = './plantillas/' . $plantilla['carpeta'] . '/' . $plantilla['imagen_preview'];
                            if (file_exists($imagenCompleta)) {
                                $imagenRuta = $imagenCompleta;
                            }
                        }
                        
                        $tieneEjemplo = !empty($plantilla['ejemplo_slug']);
                        $urlDestino = $tieneEjemplo 
                            ? './invitacion.php?slug=' . urlencode($plantilla['ejemplo_slug'])
                            : '#';
                        $textoBoton = $tieneEjemplo ? 'Ver ejemplo' : 'Próximamente';
                        $claseBoton = $tieneEjemplo ? 'btn-template' : 'btn-template disabled';
                ?>
                        <div class="template-card">
                            <img src="<?php echo htmlspecialchars($imagenRuta); ?>" 
                                 alt="<?php echo htmlspecialchars($plantilla['nombre']); ?>"
                                 onerror="this.src='./images/default-template.png'"
                                 loading="lazy">
                            <div class="template-info">
                                <h3><?php echo htmlspecialchars($plantilla['nombre']); ?></h3>
                                <?php if ($plantilla['descripcion']): ?>
                                    <p class="template-description"><?php echo htmlspecialchars($plantilla['descripcion']); ?></p>
                                <?php endif; ?>
                                
                                <div class="template-actions">
                                    <?php if ($tieneEjemplo): ?>
                                        <a href="<?php echo $urlDestino; ?>" 
                                           class="<?php echo $claseBoton; ?>"
                                           target="_blank"
                                           rel="noopener"><?php echo $textoBoton; ?></a>
                                        <a href="./contacto.php?plantilla=<?php echo $plantilla['id']; ?>" 
                                           class="btn-template btn-secondary">Solicitar</a>
                                    <?php else: ?>
                                        <span class="<?php echo $claseBoton; ?>"><?php echo $textoBoton; ?></span>
                                        <a href="./contacto.php?plantilla=<?php echo $plantilla['id']; ?>" 
                                           class="btn-template btn-primary">Solicitar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                <?php 
                    endforeach;
                endif;
                
            } catch(Exception $e) {
                error_log("Error al obtener plantillas populares: " . $e->getMessage());
                // Mostrar mensaje de error básico
                echo '<div class="no-templates"><p>No se pudieron cargar las plantillas en este momento.</p></div>';
            }
            ?>
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