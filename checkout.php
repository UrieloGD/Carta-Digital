<?php 
require_once './includes/header.php';
require_once './config/stripe_config.php';
require_once './config/database.php';

// Obtener parámetros de URL
$plan = $_GET['plan'] ?? 'premium';
$plantilla_id = isset($_GET['plantilla']) ? (int)$_GET['plantilla'] : null;

// Validar plan
if (!isset($PLANES_PRECIOS[$plan])) {
    $plan = 'premium';
}

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

$precio = $PLANES_PRECIOS[$plan];
$precio_display = number_format($precio / 100, 2);
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
                            <?php if ($plan === 'escencial' || $plan === 'premium' || $plan === 'exclusivo'): ?>
                                <li><i class="fas fa-check"></i> Portada personalizada</li>
                                <li><i class="fas fa-check"></i> Historia de pareja</li>
                                <li><i class="fas fa-check"></i> Información de ceremonia y recepción</li>
                                <li><i class="fas fa-check"></i> Galería de fotos</li>
                                <li><i class="fas fa-check"></i> Formulario de confirmación</li>
                                <?php if ($plan === 'premium' || $plan === 'exclusivo'): ?>
                                    <li><i class="fas fa-check"></i> Cronograma del evento</li>
                                    <li><i class="fas fa-check"></i> Reproductor de música</li>
                                <?php endif; ?>
                                <?php if ($plan === 'exclusivo'): ?>
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
                                <input 
                                    type="text" 
                                    id="nombre" 
                                    name="nombre" 
                                    required
                                    placeholder="Ej: Juan"
                                    class="form-control">
                                <span class="form-error" id="error-nombre"></span>
                            </div>
                            
                            <div class="form-group">
                                <label for="apellido">Apellido</label>
                                <input 
                                    type="text" 
                                    id="apellido" 
                                    name="apellido" 
                                    required
                                    placeholder="Ej: García"
                                    class="form-control">
                                <span class="form-error" id="error-apellido"></span>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre_novio">Nombre del Novio</label>
                                <input 
                                    type="text" 
                                    id="nombre_novio" 
                                    name="nombre_novio" required 
                                    placeholder="Ejemplo: Juan" 
                                    class="form-control">
                                <span class="form-error" id="error-nombre-novio"></span>
                            </div>

                            <div class="form-group">
                                <label for="nombre_novia">Nombre de la Novia</label>
                                <input 
                                    type="text" 
                                    id="nombre_novia" 
                                    name="nombre_novia" required 
                                    placeholder="Ejemplo: Ana" 
                                    class="form-control">
                                <span class="form-error" id="error-nombre-novia"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="fecha_evento">Fecha del evento</label>
                            <input 
                                type="date"
                                id="fecha_evento" 
                                name="fecha_evento" 
                                class="form-control" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="hora_evento">Hora del evento</label>
                                <input 
                                    type="time" 
                                    id="hora_evento" 
                                    name="hora_evento" 
                                    class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input 
                                    type="tel" 
                                    id="telefono" 
                                    name="telefono" 
                                    required
                                    placeholder="+52 55 1234 5678"
                                    class="form-control">
                                <span class="form-error" id="error-telefono"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required
                                placeholder="tuemail@ejemplo.com"
                                class="form-control">
                            <span class="form-error" id="error-email"></span>
                        </div>
                    </fieldset>

                    <!-- Sección: Método de Pago (Tarjeta) -->
                    <fieldset class="form-fieldset">
                        <legend>Información de Pago</legend>
                        <p class="payment-info">Ingresa los datos de tu tarjeta de crédito o débito</p>
                        
                        <div id="card-element" class="stripe-element"></div>
                        <div id="card-errors" class="form-error"></div>
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
                        <img src="https://www.svgrepo.com/show/303139/stripe-logo.svg" alt="Stripe" style="height: 20px;">
                        <span>Powered by Stripe</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scripts -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    const STRIPE_KEY = '<?php echo STRIPE_PUBLISHABLE_KEY; ?>';
    const PLAN = '<?php echo $plan; ?>';
    const PLANTILLA_ID = <?php echo $plantilla_id ?? 'null'; ?>;
    
    // Inicializar Stripe
    const stripe = Stripe(STRIPE_KEY);
    const elements = stripe.elements();
    
    // Crear elemento de tarjeta con estilos
    const cardElement = elements.create('card', {
        hidePostalCode: true,
        style: {
            base: {
                fontSize: '16px',
                color: '#424770',
                fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                '::placeholder': {
                    color: '#aab7c4',
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        }
    });
    
    cardElement.mount('#card-element');
    
    // Manejo de errores en tiempo real
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
            displayError.style.display = 'block';
        } else {
            displayError.textContent = '';
            displayError.style.display = 'none';
        }
    });
    
    // Envío del formulario
    document.getElementById('checkout-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Deshabilitar botón
        const submitBtn = document.getElementById('submit-button');
        submitBtn.disabled = true;
        document.getElementById('submit-text').style.display = 'none';
        document.getElementById('submit-loading').style.display = 'inline';

        const nombreNovio = document.getElementById('nombre_novio').value.trim();
        const nombreNovia = document.getElementById('nombre_novia').value.trim();
        
        // Preparar datos
        const formData = {
            nombre: document.getElementById('nombre').value.trim(),
            apellido: document.getElementById('apellido').value.trim(),
            nombre_novio: nombreNovio,
            nombre_novia: nombreNovia,
            nombres_novios: `${nombreNovio} & ${nombreNovia}`,
            fecha_evento: document.getElementById('fecha_evento').value,
            hora_evento: document.getElementById('hora_evento').value,
            email: document.getElementById('email').value.trim(),
            telefono: document.getElementById('telefono').value.trim(),
            plan: PLAN,
            plantilla_id: PLANTILLA_ID
        };
        
        try {
            // Paso 1: Registrar cliente y crear payment intent
            const registerResponse = await fetch('./api/register_and_pay.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            const registerData = await registerResponse.json();
            
            if (!registerResponse.ok || registerData.error) {
                throw new Error(registerData.error || 'Error al procesar el registro');
            }
            
            const { clientSecret, pedido_id } = registerData;
            
            // Paso 2: Confirmar pago con Stripe
            const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: `${formData.nombre} ${formData.apellido}`,
                        email: formData.email
                    }
                }
            });
            
            if (error) {
                throw new Error(error.message);
            }
            
            // Pago exitoso
            if (paymentIntent.status === 'succeeded') {
                window.location.href = `./payment_success.php?pedido_id=${pedido_id}&payment_intent=${paymentIntent.id}`;
            }
            
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('card-errors').textContent = error.message;
            document.getElementById('card-errors').style.display = 'block';
            
            submitBtn.disabled = false;
            document.getElementById('submit-text').style.display = 'inline';
            document.getElementById('submit-loading').style.display = 'none';
        }
    });
</script>

