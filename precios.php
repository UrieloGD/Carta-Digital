<?php 
$page_title = "Precios"; 
include './includes/header.php';

try {
    require_once './config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener plantillas con sus precios
    $stmt = $db->prepare("
        SELECT p.*, 
               ie.slug as ejemplo_slug,
               ie.nombres_novios as ejemplo_nombres
        FROM plantillas p 
        LEFT JOIN invitaciones ie ON p.invitacion_ejemplo_id = ie.id
        WHERE p.activa = 1 
        ORDER BY p.precio ASC, p.fecha_creacion DESC
    ");
    $stmt->execute();
    $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $plantillas = [];
    error_log("Error al obtener plantillas con precios: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="./css/precios.css?v=<?php echo filemtime('./css/precios.css'); ?>" />

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <div class="header-content">
            <h1>Precios de nuestras <span class="highlight">invitaciones</span></h1>
            <p class="header-subtitle">Encuentra la invitación perfecta que se adapte a tu presupuesto y estilo.</p>
        </div>
    </div>
</section>

<!-- Pricing Plans Section -->
<section class="pricing-plans">
    <div class="container">
        <div class="plans-grid">
            <!-- Plan Escencial -->
            <div class="plan-card basic-plan">
                <div class="plan-header">
                    <h3>Escencial</h3>
                    <div class="price">
                        <span class="currency">$</span>
                        <span class="amount">699</span>
                        <span class="period">MXN</span>
                    </div>
                </div>
                <div class="plan-features">
                    <ul>
                        <li><i class="fas fa-check"></i>Portada, Bienvenida, Historia</li>
                        <li><i class="fas fa-check"></i>Contador simple</li>
                        <!-- <li><i class="fas fa-times"></i>Cronograma</li> -->
                        <li><i class="fas fa-check"></i>Ubicación (info + botón)</li>
                        <li><i class="fas fa-check"></i>Galería 6 fotos (no interactiva)</li>
                        <li><i class="fas fa-check"></i>Dresscode solo texto</li>
                        <li><i class="fas fa-check"></i>Reservación por WhatsApp</li>
                        <!-- <li><i class="fas fa-times"></i>Reproductor musical</li> -->
                        <li><i class="fas fa-check"></i>Soporte 7 días</li>
                        <!-- <li><i class="fas fa-times"></i>Cambios de colores/tipografía</li> -->
                    </ul>
                </div>
                <a href="./contacto.php?plan=escencial" class="btn btn-outline">Elegir Plan</a>
            </div>

            <!-- Plan premium -->
            <div class="plan-card premium-plan featured">
                <div class="featured-badge">Más Popular</div>
                <div class="plan-header">
                    <h3>Premium</h3>
                    <div class="price">
                        <span class="currency">$</span>
                        <span class="amount">899</span>
                        <span class="period">MXN</span>
                    </div>
                </div>
                <div class="plan-features">
                    <ul>
                        <li><i class="fas fa-check"></i>Portada, Bienvenida, Historia</li>
                        <li><i class="fas fa-check"></i>Contador con cuenta regresiva</li>
                        <li><i class="fas fa-check"></i>Cronograma del evento</li>
                        <li><i class="fas fa-check"></i>Ubicaciones con imágenes</li>
                        <li><i class="fas fa-check"></i>Galería 10 fotos (interactiva)</li>
                        <li><i class="fas fa-check"></i>Dresscode con imágenes</li>
                        <li><i class="fas fa-check"></i>Reservación con boletaje digital</li>
                        <li><i class="fas fa-check"></i>Reproductor musical</li>
                        <li><i class="fas fa-check"></i>Soporte 30 días</li>
                        <!-- <li><i class="fas fa-times"></i>Cambios de colores/tipografía</li> -->
                    </ul>
                </div>
                <a href="./contacto.php?plan=premium" class="btn btn-primary">Elegir Plan</a>
            </div>

            <!-- Plan Exclusivo -->
            <div class="plan-card Exclusivo-plan">
                <div class="plan-header">
                    <h3>Exclusivo</h3>
                    <div class="price">
                        <span class="currency">$</span>
                        <span class="amount">1199</span>
                        <span class="period">MXN</span>
                    </div>
                </div>
                <div class="plan-features">
                    <ul>
                        <li><i class="fas fa-check"></i>Todo lo del plan Intermedio</li>
                        <!-- <li><i class="fas fa-check"></i>Ubicación con mapa interactivo</li> -->
                        <li><i class="fas fa-check"></i>Galería 15 fotos (interactiva)</li>
                        <li><i class="fas fa-check"></i>Reservación con boletaje digital</li>
                        <li><i class="fas fa-check"></i>Soporte hasta el evento</li>
                        <li><i class="fas fa-check"></i>Cambios de colores, tipografía y ajustes de la plantilla</li>
                    </ul>
                </div>
                <a href="./contacto.php?plan=exclusivo" class="btn btn-outline">Elegir Plan</a>
            </div>
        </div>
    </div>
</section>

<!-- Templates with Prices 
<section class="templates-pricing">
    <div class="container">
        <h2>Precios por Plantilla</h2>
        <p class="section-subtitle">Cada plantilla tiene características únicas que definen su precio</p>
        
        <div class="templates-grid">
            <?php 
            if (!empty($plantillas)): 
                foreach ($plantillas as $plantilla): 
                    // Construir la ruta de la imagen
                    $imagenRuta = './images/default-template.png';
                    if (!empty($plantilla['imagen_preview'])) {
                        $imagenCompleta = './plantillas/' . $plantilla['carpeta'] . '/' . $plantilla['imagen_preview'];
                        if (file_exists($imagenCompleta)) {
                            $imagenRuta = $imagenCompleta;
                        }
                    }
                    
                    // Determinar si tiene ejemplo y configurar URLs
                    $tieneEjemplo = !empty($plantilla['ejemplo_slug']);
                    $urlDestino = $tieneEjemplo 
                        ? './invitacion.php?slug=' . urlencode($plantilla['ejemplo_slug'])
                        : '#';
                    $textoBotonVer = $tieneEjemplo ? 'Ver plantilla' : 'Próximamente';
                    $claseBotonVer = $tieneEjemplo ? 'btn btn-secondary' : 'btn btn-secondary disabled';
            ?>
                    <div class="template-price-card">
                        <div class="template-image">
                            <img src="<?php echo htmlspecialchars($imagenRuta); ?>" 
                                 alt="<?php echo htmlspecialchars($plantilla['nombre']); ?>"
                                 onerror="this.src='./images/default-template.png'"
                                 loading="lazy">
                        </div>
                        <div class="template-info">
                            <h3><?php echo htmlspecialchars($plantilla['nombre']); ?></h3>
                            <?php if (!empty($plantilla['descripcion'])): ?>
                                <p class="template-description"><?php echo htmlspecialchars($plantilla['descripcion']); ?></p>
                            <?php endif; ?>
                            
                            <div class="template-price">
                                <?php if (!empty($plantilla['precio']) && $plantilla['precio'] > 0): ?>
                                    <span class="price-amount">$<?php echo number_format($plantilla['precio'], 0, '.', ','); ?></span>
                                    <span class="price-currency">MXN</span>
                                <?php else: ?>
                                    <span class="price-amount">Consultar</span>
                                    <span class="price-currency">precio</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="template-actions">
                                <?php if ($tieneEjemplo): ?>
                                    <a href="<?php echo $urlDestino; ?>" 
                                       class="<?php echo $claseBotonVer; ?>"
                                       target="_blank"
                                       rel="noopener"><?php echo $textoBotonVer; ?></a>
                                    <a href="./contacto.php?plantilla=<?php echo $plantilla['id']; ?>" 
                                       class="btn btn-primary">Solicitar</a>
                                <?php else: ?>
                                    <span class="<?php echo $claseBotonVer; ?>"><?php echo $textoBotonVer; ?></span>
                                    <a href="./contacto.php?plantilla=<?php echo $plantilla['id']; ?>" 
                                       class="btn btn-primary">Solicitar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
            <?php 
                endforeach; 
            else: 
            ?>
                <div class="no-templates">
                    <p>No hay plantillas disponibles en este momento.</p>
                    <a href="./contacto.php" class="btn btn-primary">Contáctanos</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section> -->

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <h2>Preguntas Frecuentes</h2>
        <div class="faq-grid">
            <!--<div class="faq-item">
                <h4>¿Qué incluye el precio?</h4>
                <p>Cada plan incluye el diseño personalizado, hosting por 6 meses y soporte técnico durante todo el proceso.</p>
            </div>-->
            <div class="faq-item">
                <h4>¿Puedo cambiar de plan después?</h4>
                <p>Sí, puedes actualizar tu plan en cualquier momento pagando únicamente la diferencia.</p>
            </div>
            <div class="faq-item">
                <h4>¿Hay costos adicionales?</h4>
                <p>No, el precio mostrado es final. Solo se cobrarían costos adicionales si solicitas funcionalidades extra no incluidas en tu plan.</p>
            </div>
            <div class="faq-item">
                <h4>¿Cuánto tiempo toma la entrega?</h4>
                <p>Entregamos tu invitación digital en menos de 24 horas después de recibir toda la información necesaria.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>¿Necesitas algo personalizado?</h2>
            <p>Si ningún plan se ajusta a tus necesidades, contáctanos para crear una solución a medida.</p>
            <a href="./contacto.php" class="btn btn-primary">Contactar ahora</a>
        </div>
    </div>
</section>

<?php include './includes/footer.php'; ?>