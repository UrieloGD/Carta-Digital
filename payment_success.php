<?php 
require_once './includes/header.php';
require_once './config/database.php';
require_once './config/stripe_config.php';
require_once './config/email_config.php';
require_once './functions/email_bienvenida.php';
require_once './functions/email_notificacion_admin.php';

// Obtener parámetros
$pedido_id = isset($_GET['pedido_id']) ? (int)$_GET['pedido_id'] : null;
$payment_intent_id = isset($_GET['payment_intent']) ? $_GET['payment_intent'] : null;

$pedido = null;
$error = null;

if ($pedido_id && $payment_intent_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Obtener información del pedido (sin filtrar por estado todavía)
        $stmt = $db->prepare("
            SELECT 
                p.*, 
                c.nombre, c.apellido, c.email, c.slug,
                c.nombres_novios, c.telefono,
                pl.nombre as plantilla_nombre,
                i.slug as invitacion_slug, 
                i.fecha_evento, i.hora_evento, i.nombres_novios as invitacion_novios
            FROM pedidos p
            JOIN clientes c ON p.cliente_id = c.id
            LEFT JOIN plantillas pl ON p.plantilla_id = pl.id
            LEFT JOIN invitaciones i ON p.invitacion_id = i.id
            WHERE p.id = ?
        ");
        $stmt->execute([$pedido_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido) {
            $error = "No se encontró el pedido.";
        } 
        elseif ($pedido['payment_intent_id'] !== $payment_intent_id) {
            $error = "El pago no coincide con el pedido.";
        } 
        elseif ($pedido['estado'] !== 'completado') {
            // El pedido aún no está marcado como completado en BD
            // Verificar directamente con Stripe
            try {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
                
                if ($paymentIntent->status === 'succeeded') {
                    // Stripe confirma que está pagado
                    // Actualizar AHORA en la BD (no esperar webhook)
                    $stmt = $db->prepare("
                        UPDATE pedidos 
                        SET estado = 'completado', fecha_pago = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$pedido_id]);
                    
                    // Actualizar variable local para renderizar éxito
                    $pedido['estado'] = 'completado';
                    $pedido['fecha_pago'] = date('Y-m-d H:i:s');
                    
                    // Generar contraseña
                    $raw_password = $pedido['slug'] . str_replace('-', '', $pedido['fecha_evento']);
                    
                    // Enviar emails
                    enviarEmailBienvenida($pedido, $raw_password);
                    enviarNotificacionAdmin($pedido, $raw_password);
                    
                } else {
                    // El pago aún no está confirmado en Stripe
                    $error = "El pago aún no está confirmado. Estado: " . $paymentIntent->status;
                }
                
            } catch (\Stripe\Exception\ApiErrorException $e) {
                error_log("Error al verificar PaymentIntent: " . $e->getMessage());
                $error = "No se pudo verificar el pago con Stripe.";
            }
        } 
        else {
            // Generar contraseña para emails
            $raw_password = $pedido['slug'] . str_replace('-', '', $pedido['fecha_evento']);
            
            // Enviar emails (solo si no se han enviado - deberías verificar esto)
            enviarEmailBienvenida($pedido, $raw_password);
            enviarNotificacionAdmin($pedido, $raw_password);
        }
        
    } catch (Exception $e) {
        error_log("Error al obtener pedido: " . $e->getMessage());
        $error = "Error al procesar tu solicitud: " . $e->getMessage();
    }
} else {
    $error = "Parámetros inválidos (pedido_id o payment_intent faltante)";
}
?>

<link rel="stylesheet" href="./css/payment_success.css?v=<?php echo filemtime('./css/payment_success.css'); ?>" />

<section class="payment-success-section">
    <div class="container">
        <div class="success-container">
            
            <?php if ($pedido && $pedido['estado'] === 'completado'): ?>
                <!-- Éxito -->
                <div class="success-box">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    
                    <h1>¡Pago Completado!</h1>
                    <p class="success-message">Tu pago ha sido procesado exitosamente</p>
                    
                    <!-- Detalles del Pedido -->
                    <div class="order-details">
                        <h3>Detalles de tu Pedido</h3>
                        
                        <div class="detail-row">
                            <span class="detail-label">Número de Pedido:</span>
                            <span class="detail-value">#<?php echo str_pad($pedido['id'], 5, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Plan:</span>
                            <span class="detail-value"><?php echo ucfirst($pedido['plan']); ?></span>
                        </div>
                        
                        <?php if ($pedido['plantilla_nombre']): ?>
                            <div class="detail-row">
                                <span class="detail-label">Plantilla:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($pedido['plantilla_nombre']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <span class="detail-label">Monto Pagado:</span>
                            <span class="detail-value amount">$<?php echo number_format($pedido['monto'], 2); ?> MXN</span>
                        </div>

                        <div class="detail-row">
                            <span class="detail-label">Novios:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($pedido['nombres_novios'] ?? $pedido['invitacion_novios']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Fecha del evento:</span>
                            <span class="detail-value">
                                <?php 
                                    if (!empty($pedido['fecha_evento'])) {
                                        echo date('d/m/Y', strtotime($pedido['fecha_evento']));
                                    } else {
                                        echo "No asignada aún";
                                    }
                                ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Hora del evento:</span>
                            <span class="detail-value">
                                <?php 
                                    if (!empty($pedido['hora_evento'])) {
                                        echo date('H:i', strtotime($pedido['hora_evento']));
                                    } else {
                                        echo "No asignada aún";
                                    }
                                ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">URL de invitación:</span>
                            <span class="detail-value">
                                <?php if (!empty($pedido['invitacion_slug'])): ?>
                                    <a href="./invitacion.php?slug=<?php echo urlencode($pedido['invitacion_slug']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($pedido['invitacion_slug']); ?>
                                    </a>
                                <?php else: ?>
                                    No disponible
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Fecha:</span>
                            <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pago'])); ?></span>
                        </div>
                    </div>
                    
                    <!-- Próximos Pasos -->
                    <div class="next-steps">
                        <h3>Próximos Pasos</h3>
                        <div class="steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">
                                    <h4>Espera nuestro contacto</h4>
                                    <p>Nos pondremos en contacto contigo vía WhatsApp al número <strong><?php echo htmlspecialchars($pedido['telefono']); ?></strong> para solicitar los detalles de tu invitación.</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">
                                    <h4>Proporciona la información</h4>
                                    <p>Comparte con nosotros: nombres de los novios, fecha del evento, ubicaciones, fotos, historia de pareja, etc.</p>
                                </div>
                            </div>
                            
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">
                                    <h4>Recibe tu invitación</h4>
                                    <p>Crearemos tu invitación el mismo día y te enviaremos un link para que la compartas con tus invitados.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de Contacto -->
                    <div class="contact-info">
                        <h3>¿Necesitas ayuda?</h3>
                        <p>Si tienes cualquier duda, contáctanos:</p>
                        <ul>
                            <li>
                                <i class="fab fa-whatsapp"></i>
                                <a href="https://wa.me/523324045368" target="_blank">WhatsApp</a>
                            </li>
                            <li>
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:contacto@cartadigital.com.mx">contacto@cartadigital.com.mx</a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Confirmación por Email -->
                    <div class="email-confirmation">
                        <p>
                            <i class="fas fa-info-circle"></i>
                            Se ha enviado un email de confirmación a <strong><?php echo htmlspecialchars($pedido['email']); ?></strong>
                        </p>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="action-buttons">
                        <a href="./index.php" class="btn btn-primary">Ir a Inicio</a>
                        <a href="./plantillas.php" class="btn btn-secondary">Ver Más Plantillas</a>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Error -->
                <div class="error-box">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    
                    <h1>Algo Salió Mal</h1>
                    <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                    
                    <div class="error-actions">
                        <p>Por favor, intenta de nuevo o contáctanos si el problema persiste.</p>
                        <a href="./plantillas.php" class="btn btn-primary">Volver a Plantillas</a>
                        <a href="./contacto.php" class="btn btn-secondary">Contactarnos</a>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</section>

<?php include './includes/footer.php'; ?>
