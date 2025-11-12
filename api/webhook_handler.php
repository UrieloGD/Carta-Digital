<?php
/**
 * Webhook handler para Stripe
 * Este archivo recibe notificaciones de Stripe cuando un pago es exitoso o falla
 * CRÍTICO: Debes configurar este webhook en tu dashboard de Stripe
 */

require_once '../config/database.php';
require_once '../config/stripe_config.php';

// Obtener el payload del webhook
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    // Verificar la firma del webhook (SEGURIDAD CRÍTICA)
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        STRIPE_WEBHOOK_SECRET
    );
    
    // Log del evento recibido
    error_log("Webhook recibido: " . $event->type);
    
    // Conectar a base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Manejar diferentes tipos de eventos
    switch ($event->type) {
        case 'payment_intent.succeeded':
            // El pago fue exitoso
            $paymentIntent = $event->data->object;
            
            error_log("Pago exitoso: " . $paymentIntent->id);
            
            // Actualizar pedido en base de datos
            $stmt = $db->prepare("
                UPDATE pedidos 
                SET estado = 'completado', 
                    fecha_pago = NOW()
                WHERE payment_intent_id = ? 
                AND estado = 'pendiente'
            ");
            
            $stmt->execute([$paymentIntent->id]);
            
            if ($stmt->rowCount() > 0) {
                // Obtener datos del pedido con plan desde tabla planes
                $stmt = $db->prepare("
                    SELECT 
                        p.*, 
                        c.nombre, 
                        c.apellido, 
                        c.email, 
                        c.telefono,
                        pt.nombre as plantilla_nombre,
                        pl.nombre as plan_nombre,
                        pl.precio as precio_plan
                    FROM pedidos p
                    JOIN clientes c ON p.cliente_id = c.id
                    LEFT JOIN plantillas pt ON p.plantilla_id = pt.id
                    LEFT JOIN invitaciones i ON p.invitacion_id = i.id
                    LEFT JOIN planes pl ON i.plan_id = pl.id
                    WHERE p.payment_intent_id = ?
                ");
                $stmt->execute([$paymentIntent->id]);
                $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($pedido) {
                    // Enviar email de confirmación al cliente
                    enviarEmailConfirmacion($pedido);
                    
                    // Enviar notificación al admin (tú)
                    enviarNotificacionAdmin($pedido);
                    
                    error_log("Pedido actualizado y emails enviados: Pedido ID={$pedido['id']}");
                }
            }
            
            break;
            
        case 'payment_intent.payment_failed':
            // El pago falló
            $paymentIntent = $event->data->object;
            
            error_log("Pago fallido: " . $paymentIntent->id);
            
            // Actualizar pedido como cancelado
            $stmt = $db->prepare("
                UPDATE pedidos 
                SET estado = 'cancelado'
                WHERE payment_intent_id = ?
            ");
            
            $stmt->execute([$paymentIntent->id]);
            
            // Opcional: enviar email al cliente notificando el fallo
            
            break;
            
        case 'payment_intent.canceled':
            // El pago fue cancelado
            $paymentIntent = $event->data->object;
            
            error_log("Pago cancelado: " . $paymentIntent->id);
            
            $stmt = $db->prepare("
                UPDATE pedidos 
                SET estado = 'cancelado'
                WHERE payment_intent_id = ?
            ");
            
            $stmt->execute([$paymentIntent->id]);
            
            break;
            
        default:
            // Evento no manejado
            error_log("Evento no manejado: " . $event->type);
    }
    
    // Responder con 200 para confirmar recepción
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch(\UnexpectedValueException $e) {
    // Payload inválido
    error_log("Webhook error - Invalid payload: " . $e->getMessage());
    http_response_code(400);
    exit();
    
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Firma inválida
    error_log("Webhook error - Invalid signature: " . $e->getMessage());
    http_response_code(400);
    exit();
    
} catch(Exception $e) {
    // Error general
    error_log("Webhook error: " . $e->getMessage());
    http_response_code(500);
    exit();
}

/**
 * Enviar email de confirmación al cliente
 */
function enviarEmailConfirmacion($pedido) {
    $to = $pedido['email'];
    $subject = "¡Pago Confirmado! - Carta Digital";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #8B7355; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            .btn { display: inline-block; padding: 12px 24px; background: #8B7355; color: white; text-decoration: none; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>¡Gracias por tu compra!</h1>
            </div>
            <div class='content'>
                <p>Hola {$pedido['nombre']},</p>
                <p>Tu pago ha sido procesado exitosamente.</p>
                
                <h3>Detalles de tu pedido:</h3>
                <ul>
                    <li><strong>Plan:</strong> " . ucfirst($pedido['plan_nombre'] ?? $pedido['plan']) . "</li>
                    <li><strong>Plantilla:</strong> " . ($pedido['plantilla_nombre'] ?? 'Por definir') . "</li>
                    <li><strong>Monto:</strong> $" . number_format($pedido['precio_plan'] ?? $pedido['monto'], 2) . " MXN</li>
                </ul>
                
                <p>Nos pondremos en contacto contigo vía WhatsApp al número <strong>{$pedido['telefono']}</strong> para solicitar los detalles de tu invitación.</p>
                
                <p>Tu invitación estará lista el mismo día.</p>
                
                <p>Cualquier duda, no dudes en contactarnos.</p>
            </div>
            <div class='footer'>
                <p>Carta Digital &copy; " . date('Y') . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: Carta Digital <noreply@cartadigital.com>\r\n";
    
    // Enviar email
    $enviado = mail($to, $subject, $message, $headers);
    
    if ($enviado) {
        error_log("Email de confirmación enviado a: {$to}");
    } else {
        error_log("Error al enviar email de confirmación a: {$to}");
    }
    
    return $enviado;
}

/**
 * Enviar notificación al administrador
 */
function enviarNotificacionAdmin($pedido) {
    // Cambia este email por el tuyo
    $admin_email = "admin@cartadigital.com";
    
    $subject = "Nuevo Pedido Completado - Carta Digital";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>🎉 Nuevo Pedido Completado</h2>
        
        <h3>Información del Cliente:</h3>
        <ul>
            <li><strong>Nombre:</strong> {$pedido['nombre']} {$pedido['apellido']}</li>
            <li><strong>Email:</strong> {$pedido['email']}</li>
            <li><strong>Teléfono:</strong> {$pedido['telefono']}</li>
        </ul>
        
        <h3>Detalles del Pedido:</h3>
        <ul>
            <li><strong>Plan:</strong> " . ucfirst($pedido['plan_nombre'] ?? $pedido['plan']) . "</li>
            <li><strong>Plantilla:</strong> " . ($pedido['plantilla_nombre'] ?? 'No seleccionada') . "</li>
            <li><strong>Monto:</strong> $" . number_format($pedido['precio_plan'] ?? $pedido['monto'], 2) . " MXN</li>
            <li><strong>Payment Intent ID:</strong> {$pedido['payment_intent_id']}</li>
        </ul>
        
        <p><strong>Siguiente paso:</strong> Contactar al cliente por WhatsApp para solicitar los detalles de la invitación.</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: Sistema Carta Digital <sistema@cartadigital.com>\r\n";
    
    mail($admin_email, $subject, $message, $headers);
}
?>
