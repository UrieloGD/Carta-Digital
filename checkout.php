<?php 
require_once './includes/header.php';
require_once './config/stripe_config.php';
require_once './config/database.php';

// Obtener parámetros de URL
$plan = $_GET['plan'] ?? 'Escencial';
$plantilla_id = isset($_GET['plantilla']) ? (int)$_GET['plantilla'] : null;

// Validar plan
if (!isset($PLANES_PRECIOS[$plan])) {
    $plan = 'Escencial';
}

$precio = $PLANES_PRECIOS[$plan];
$precio_display = number_format($precio / 100, 2);

// Obtener info de plantilla
$plantilla = null;
if ($plantilla_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("SELECT id, nombre FROM plantillas WHERE id = ? AND activa = 1");
        $stmt->execute([$plantilla_id]);
        $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error al obtener plantilla: " . $e->getMessage());
    }
}
?>

<link rel="stylesheet" href="./css/checkout.css?v=<?php echo filemtime('./css/checkout.css'); ?>" />

<section class="checkout-section">
    <div class="container">
        <div class="checkout-wrapper">
            
            <!-- Columna Izquierda: Resumen del Pedido -->
            <div class="checkout-summary">
                <div class="summary-box">
                    <h3>Resumen de tu Pedido</h3>
                    
                    <div class="summary-item">
                        <label>Plan Seleccionado</label>
                        <p class="summary-value"><?php echo ucfirst($plan); ?></p>
                    </div>
                    
                    <?php if ($plantilla): ?>
                        <div class="summary-item">
                            <label>Plantilla</label>
                            <p class="summary-value"><?php echo htmlspecialchars($plantilla['nombre']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-item price-item">
                        <label>Total a Pagar</label>
                        <p class="summary-price">$<?php echo $precio_display; ?> <span>MXN</span></p>
                    </div>
                    
                    <div class="summary-features">
                        <h4>¿Qué Incluye?</h4>
                        <ul>
                            <?php if ($plan === 'Escencial' || $plan === 'Premium' || $plan === 'Exclusivo'): ?>
                                <li><i class="fas fa-check"></i> Portada personalizada</li>
                                <li><i class="fas fa-check"></i> Historia de pareja</li>
                                <li><i class="fas fa-check"></i> Información de ceremonia y recepción</li>
                                <li><i class="fas fa-check"></i> Galería de fotos</li>
                                <li><i class="fas fa-check"></i> Formulario de confirmación</li>
                                <?php if ($plan === 'Premium' || $plan === 'Exclusivo'): ?>
                                    <li><i class="fas fa-check"></i> Cronograma del evento</li>
                                    <li><i class="fas fa-check"></i> Reproductor de música</li>
                                <?php endif; ?>
                                <?php if ($plan === 'Exclusivo'): ?>
                                    <li><i class="fas fa-check"></i> Mesa de regalos</li>
                                    <li><i class="fas fa-check"></i> Sección adultos</li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Formulario de Registro + Pago -->
            <div class="checkout-form-section">
                <h2>Completa tu Compra</h2>
                <p class="form-subtitle">Nos pondremos en contacto contigo para crear tu invitación</p>
                
                <form id="checkout-form" class="checkout-form">
                    
                    <!-- Sección: Información de Contacto -->
                    <fieldset class="form-fieldset">
                        <legend>Tu Información</legend>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" id="nombre" name="nombre" required placeholder="Ej: Juan" class="form-control">
                                <span class="form-error" id="error-nombre"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="apellido">Apellido</label>
                                <input type="text" id="apellido" name="apellido" required placeholder="Ej: García" class="form-control">
                                <span class="form-error" id="error-apellido"></span>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre_novia">Nombre de la Novia</label>
                                <input type="text" id="nombre_novia" name="nombre_novia" required placeholder="Ejemplo: Ana" class="form-control">
                                <span class="form-error" id="error-nombre-novia"></span>
                            </div>

                            <div class="form-group">
                                <label for="nombre_novio">Nombre del Novio</label>
                                <input type="text" id="nombre_novio" name="nombre_novio" required placeholder="Ejemplo: Juan" class="form-control">
                                <span class="form-error" id="error-nombre-novio"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="fecha_evento">Fecha del evento</label>
                            <input type="date" id="fecha_evento" name="fecha_evento" class="form-control" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="hora_evento">Hora del evento</label>
                                <input type="time" id="hora_evento" name="hora_evento" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" id="telefono" name="telefono" required placeholder="+52 55 1234 5678" class="form-control">
                                <span class="form-error" id="error-telefono"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required placeholder="tuemail@ejemplo.com" class="form-control">
                            <span class="form-error" id="error-email"></span>
                        </div>
                    </fieldset>

                    <!-- Selector de Método de Pago -->
                    <div class="payment-method-selector">
                        <label class="method-option">
                            <input type="radio" name="payment_method" value="stripe" checked>
                            <span class="method-label">
                                <i class="fas fa-credit-card"></i>
                                <strong>Tarjeta de Crédito/Débito</strong>
                                <!-- <small>Stripe Seguro</small> -->
                            </span>
                        </label>
                        <label class="method-option">
                            <input type="radio" name="payment_method" value="mercadopago">
                            <span class="method-label">
                                <i class="fas fa-wallet"></i>
                                <strong>Pagar con Mercado Pago</strong>
                                <!-- <small>Múltiples opciones</small> -->
                            </span>
                        </label>
                    </div>

                    <!-- Sección: Stripe -->
                    <fieldset class="form-fieldset stripe-section" id="stripe-section">
                        <div class="payment-section-title">
                            <i class="fas fa-credit-card"></i>
                            Información de Pago - Tarjeta
                        </div>
                        <p class="payment-info">Ingresa los datos de tu tarjeta de crédito o débito</p>
                        
                        <div id="card-element" class="stripe-element"></div>
                        <div id="card-errors" class="form-error"></div>
                    </fieldset>

                    <!-- Sección: Mercado Pago -->
                    <fieldset class="form-fieldset mercadopago-section" id="mercadopago-section" style="display: none;">
                        <div class="payment-section-title">
                            <i class="fas fa-wallet"></i>
                            Información de Pago - Mercado Pago
                        </div>
                        <p class="payment-info">Serás redirigido a Mercado Pago de forma segura</p>
                        <p class="payment-details">
                            <i class="fas fa-info-circle"></i>
                            Puedes pagar con tarjeta de crédito, tarjeta de débito, efectivo en sucursales y más.
                        </p>
                    </fieldset>

                    <!-- Botón de Pago -->
                    <button type="submit" id="submit-button" class="btn btn-primary btn-large">
                        <span id="submit-text">Pagar $<?php echo $precio_display; ?> MXN</span>
                        <span id="submit-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Procesando...
                        </span>
                    </button>

                    <input type="hidden" name="plan" value="<?php echo htmlspecialchars($plan); ?>">
                    <input type="hidden" name="plantilla_id" value="<?php echo $plantilla_id ?? ''; ?>">
                </form>

                <!-- Indicadores de Seguridad -->
                <div class="security-badges">
                    <div class="badge">
                        <i class="fas fa-lock"></i>
                        <span>Conexión Segura SSL</span>
                    </div>
                    <div class="badge">
                        <span>Powered by Stripe</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scripts -->
<script>
    // Variables globales para el módulo
    const CHECKOUT_CONFIG = {
        stripeKey: '<?php echo STRIPE_PUBLISHABLE_KEY; ?>',
        plan: '<?php echo $plan; ?>',
        plantillaId: <?php echo $plantilla_id ?? 'null'; ?>,
        precio: '<?php echo $precio_display; ?>'
    };
</script>
<script src="https://js.stripe.com/v3/"></script>
<script src="./js/checkout.js?v=<?php echo filemtime('./js/checkout.js'); ?>"></script>

<?php include './includes/footer.php'; ?>