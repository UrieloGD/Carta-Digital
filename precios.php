<?php 
$page_title = "Precios"; 
include './includes/header.php';
require_once './config/stripe_config.php';
require_once './config/database.php';

try {
    // Obtener planes desde la BD
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->query("SELECT id, nombre, precio, descripcion FROM planes WHERE activo = 1 ORDER BY precio ASC");
    $planes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $planes = [];
    error_log("Error al obtener planes: " . $e->getMessage());
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
            <?php 
            // Características por plan (puedes también ponerlas en la BD si lo deseas)
            $caracteristicas = [
                'escencial' => [
                    'Portada, Bienvenida, Historia',
                    'Contador simple',
                    'Ubicación (info + botón)',
                    'Galería 6 fotos',
                    'Dresscode solo texto',
                    'Reservación por WhatsApp',
                    'Soporte 7 días'
                ],
                'premium' => [
                    'Portada, Bienvenida, Historia',
                    'Contador con cuenta regresiva',
                    'Cronograma del evento',
                    'Ubicaciones con imágenes',
                    'Galería 10 fotos',
                    'Dresscode con imágenes',
                    'Reservación con boletaje digital',
                    'Reproductor musical',
                    'Soporte 30 días'
                ],
                'exclusivo' => [
                    'Todo lo del plan Premium',
                    'Galería 15 fotos',
                    'Sección para tu mesa de regalos',
                    'Reservación con boletaje digital',
                    'Límite de tiempo para confirmación',
                    'Sección para eventos adultos',
                    'Soporte hasta el evento',
                    'Cambios de colores y tipografía'
                ]
            ];
            
            foreach ($planes as $index => $plan):
                $es_popular = strtolower($plan['nombre']) === 'premium';
                $clase_plan = 'plan-card ' . strtolower($plan['nombre']) . '-plan';
                if ($es_popular) {
                    $clase_plan .= ' featured';
                }
                $caracteristicas_plan = $caracteristicas[strtolower($plan['nombre'])] ?? [];
            ?>
            <div class="<?php echo $clase_plan; ?>">
                <?php if ($es_popular): ?>
                    <div class="featured-badge">Más Popular</div>
                <?php endif; ?>
                
                <div class="plan-header">
                    <h3><?php echo htmlspecialchars($plan['nombre']); ?></h3>
                    <div class="price">
                        <span class="currency">$</span>
                        <span class="amount"><?php echo number_format($plan['precio'], 0); ?></span>
                        <span class="period">MXN</span>
                    </div>
                </div>
                
                <div class="plan-features">
                    <ul>
                        <?php foreach ($caracteristicas_plan as $caracteristica): ?>
                            <li><i class="fas fa-check"></i><?php echo htmlspecialchars($caracteristica); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <a href="./plantillas.php?plan=<?php echo urlencode(strtolower($plan['nombre'])); ?>" 
                   class="btn <?php echo $es_popular ? 'btn-primary' : 'btn-outline'; ?> btn-plan">
                    <i class="fas fa-shopping-cart"></i> Elegir Plan
                </a>
            </div>
            <?php endforeach; ?>
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
                        <?php foreach ($planes as $plan): ?>
                            <th><?php echo htmlspecialchars($plan['nombre']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Precio</strong></td>
                        <?php foreach ($planes as $plan): ?>
                            <td>$<?php echo number_format($plan['precio'], 0); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <td>Portada Personalizada</td>
                        <?php foreach ($planes as $plan): ?>
                            <td><i class="fas fa-check"></i></td>
                        <?php endforeach; ?>
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