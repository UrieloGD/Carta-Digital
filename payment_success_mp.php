<?php 
require_once './includes/header.php';
require_once './config/database.php';
require_once './config/email_config.php';
require_once './functions/email_bienvenida.php';
require_once './functions/email_notificacion_admin.php';
require_once './vendor/autoload.php'; 

// Cargar variables de entorno para API MP
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Obtener parámetros de Mercado Pago
// MP suele mandar: collection_id, collection_status, payment_id, status, external_reference, etc.
$collection_id = $_GET['collection_id'] ?? $_GET['payment_id'] ?? null;
$status = $_GET['status'] ?? $_GET['collection_status'] ?? null;

$pedido = null;
$error = null;

if ($collection_id) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // 1. Buscar el pedido (Igual que en Stripe, pero buscando por payment_intent_id O por el ID de preferencia)
        // Nota: A veces MP devuelve el ID de pago, a veces la preferencia. Buscamos flexiblemente.
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
            WHERE p.payment_intent_id = ? 
            OR p.id = ? -- Por si mandamos el ID en external_reference y lo recibimos por GET
            LIMIT 1
        ");
        
        // Intentamos buscar por el ID que nos manda MP
        // Si usaste external_reference=pedido_id, MP lo devuelve como external_reference en la URL
        $external_ref = $_GET['external_reference'] ?? null;
        
        $search_id = $collection_id;
        if ($external_ref && is_numeric($external_ref)) {
             // Si viene referencia externa, es más seguro buscar por ahí
             // Pero la consulta de arriba busca por payment_intent_id, así que adaptamos:
             // Hacemos una búsqueda directa por ID si tenemos external reference
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
            $search_id = $external_ref;
        }
        
        $stmt->execute([$search_id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedido) {
            $error = "No se encontró el pedido asociado a este pago.";
        } 
        elseif ($status === 'approved') {
            // 2. Verificar si ya estaba completado
            if ($pedido['estado'] !== 'completado') {
                
                // Actualizar BD
                $stmt = $db->prepare("
                    UPDATE pedidos 
                    SET estado = 'completado', 
                        fecha_pago = NOW(),
                        payment_intent_id = ? -- Actualizamos con el ID real del pago si cambió
                    WHERE id = ?
                ");
                // Si tenemos el payment_id real de MP (collection_id), lo guardamos
                $stmt->execute([$collection_id, $pedido['id']]);
                
                // Actualizar variable local
                $pedido['estado'] = 'completado';
                $pedido['fecha_pago'] = date('Y-m-d H:i:s');
                
                // 3. ENVIAR CORREOS (La parte clave que faltaba)
                // Generar contraseña igual que en Stripe
                $raw_password = $pedido['slug'] . str_replace('-', '', $pedido['fecha_evento']);
                
                // Enviar emails
                if (function_exists('enviarEmailBienvenida')) {
                    enviarEmailBienvenida($pedido, $raw_password);
                }
                if (function_exists('enviarNotificacionAdmin')) {
                    enviarNotificacionAdmin($pedido, $raw_password);
                }
            }
        } 
        elseif ($status === 'pending' || $status === 'in_process') {
            $error = "Tu pago está en proceso de validación. Te notificaremos por correo cuando se complete.";
             // Podrías redirigir a payment_pending_mp.php aquí si prefieres
        } 
        else {
            $error = "El pago no fue aprobado. Estado: " . htmlspecialchars($status);
            // Podrías redirigir a payment_failed_mp.php aquí
        }
        
    } catch (Exception $e) {
        error_log("Error al procesar éxito MP: " . $e->getMessage());
        $error = "Error interno al procesar la solicitud.";
    }
} else {
    $error = "Parámetros de pago inválidos.";
}
?>

<!-- Usamos el MISMO CSS que Stripe -->
<link rel="stylesheet" href="./css/payment_success.css?v=<?php echo filemtime('./css/payment_success.css'); ?>" />

<section class="payment-success-section">
    <div class="container">
        <div class="success-container">
            
            <?php if ($pedido && ($pedido['estado'] === 'completado' || $status === 'approved')): ?>
                <!-- Éxito (Copia exacta de la estructura de Stripe) -->
                <div class="success-box">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    
                    <h1>¡Pago Completado!</h1>
                    <p class="success-message">Tu pago vía Mercado Pago ha sido procesado exitosamente</p>
                    
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
                        
                        <?php if (!empty($pedido['plantilla_nombre'])): ?>
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
                            <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pago'] ?? 'now')); ?></span>
                        </div>
                    </div>
                    
                    <!-- Próximos Pasos (Idéntico a Stripe) -->
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
                                <a href="https://wa.me/523339047672" target="_blank">WhatsApp</a>
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
                <!-- Error (Copia exacta de la estructura de Stripe) -->
                <div class="error-box">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    
                    <h1>Algo Salió Mal</h1>
                    <p class="error-message"><?php echo htmlspecialchars($error ?? "Error desconocido"); ?></p>
                    
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
