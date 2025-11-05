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
                        <li><i class="fas fa-check"></i>Ubicación (info + botón)</li>
                        <li><i class="fas fa-check"></i>Galería 6 fotos</li>
                        <li><i class="fas fa-check"></i>Dresscode solo texto</li>
                        <li><i class="fas fa-check"></i>Reservación por WhatsApp</li>
                        <li><i class="fas fa-check"></i>Soporte 7 días</li>
                    </ul>
                </div>
                <a href="./seleccionar_plantilla.php?plan=escencial" class="btn btn-outline btn-plan">
                    <i class="fas fa-shopping-cart"></i> Elegir Plan
                </a>
            </div>

            <!-- Plan Premium -->
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
                        <li><i class="fas fa-check"></i>Galería 10 fotos</li>
                        <li><i class="fas fa-check"></i>Dresscode con imágenes</li>
                        <li><i class="fas fa-check"></i>Reservación con boletaje digital</li>
                        <li><i class="fas fa-check"></i>Reproductor musical</li>
                        <li><i class="fas fa-check"></i>Soporte 30 días</li>
                    </ul>
                </div>
                <a href="./seleccionar_plantilla.php?plan=premium" class="btn btn-primary btn-plan">
                    <i class="fas fa-shopping-cart"></i> Elegir Plan
                </a>
            </div>

            <!-- Plan Exclusivo -->
            <div class="plan-card exclusivo-plan">
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
                        <li><i class="fas fa-check"></i>Todo lo del plan Premium</li>
                        <li><i class="fas fa-check"></i>Galería 15 fotos</li>
                        <li><i class="fas fa-check"></i>Sección para tu mesa de regalos</li>
                        <li><i class="fas fa-check"></i>Reservación con boletaje digital</li>
                        <li><i class="fas fa-check"></i>Límite de tiempo para confirmación</li>
                        <li><i class="fas fa-check"></i>Sección para eventos adultos</li>
                        <li><i class="fas fa-check"></i>Soporte hasta el evento</li>
                        <li><i class="fas fa-check"></i>Cambios de colores y tipografía</li>
                    </ul>
                </div>
                <a href="./seleccionar_plantilla.php?plan=exclusivo" class="btn btn-outline btn-plan">
                    <i class="fas fa-shopping-cart"></i> Elegir Plan
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Comparison Table -->
<section class="comparison-section">
    <div class="container">
        <h2>Comparación de Planes</h2>
        <div class="table-responsive">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Características</th>
                        <th>Escencial</th>
                        <th>Premium</th>
                        <th>Exclusivo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Precio</strong></td>
                        <td>$699</td>
                        <td>$899</td>
                        <td>$1,199</td>
                    </tr>
                    <tr>
                        <td>Portada Personalizada</td>
                        <td><i class="fas fa-check"></i></td>
                        <td><i class="fas fa-check"></i></td>
                        <td><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Galería de Fotos</td>
                        <td>6 fotos</td>
                        <td>10 fotos</td>
                        <td>15 fotos</td>
                    </tr>
                    <tr>
                        <td>Contador de Eventos</td>
                        <td>Simple</td>
                        <td>Con Regresiva</td>
                        <td>Con Regresiva</td>
                    </tr>
                    <tr>
                        <td>Cronograma del Evento</td>
                        <td><i class="fas fa-times"></i></td>
                        <td><i class="fas fa-check"></i></td>
                        <td><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Reproductor de Música</td>
                        <td><i class="fas fa-times"></i></td>
                        <td><i class="fas fa-check"></i></td>
                        <td><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Mesa de Regalos</td>
                        <td><i class="fas fa-times"></i></td>
                        <td><i class="fas fa-times"></i></td>
                        <td><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Sección Adultos</td>
                        <td><i class="fas fa-times"></i></td>
                        <td><i class="fas fa-times"></i></td>
                        <td><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Personalización de Colores</td>
                        <td><i class="fas fa-times"></i></td>
                        <td><i class="fas fa-times"></i></td>
                        <td><i class="fas fa-check"></i></td>
                    </tr>
                    <tr>
                        <td>Soporte Técnico</td>
                        <td>7 días</td>
                        <td>30 días</td>
                        <td>Hasta el evento</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section">
    <div class="container">
        <h2>Preguntas Frecuentes</h2>
        <div class="faq-grid">
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
            <div class="faq-item">
                <h4>¿Qué métodos de pago aceptan?</h4>
                <p>Aceptamos tarjetas de crédito y débito a través de Stripe. Próximamente Mercado Pago y transferencias bancarias.</p>
            </div>
            <div class="faq-item">
                <h4>¿Mi invitación será responsive?</h4>
                <p>Sí, todas nuestras invitaciones se ven perfectas en móviles, tablets y computadoras.</p>
            </div>
            <div class="faq-item">
                <h4>¿Puedo compartir mi invitación?</h4>
                <p>Claro, recibirás un link único que puedes compartir por WhatsApp, email, redes sociales o cualquier medio.</p>
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
