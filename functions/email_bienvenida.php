<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/load_env.php';

/**
 * Función para enviar email de bienvenida con credenciales
 * @param array $pedido - Datos del pedido y cliente
 * @return bool - True si se envió correctamente, false si falló
 */
function enviarEmailBienvenida($pedido, $raw_password) {
    $email_destino = $pedido['email'];
    $nombre_cliente = $pedido['nombre'];
    $slug_cliente = $pedido['slug'];
    
    $subject = "🎊 ¡Confirmación de tu pedido - Carta Digital!";
    
    // HTML del email
    $html_body = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Confirmación de Pedido</title>
        <style>
            body {
                margin: 0;
                padding: 0;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f4f4f4;
            }
            .email-container {
                max-width: 600px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            .email-header {
                background: #c8a882;
                color: white;
                padding: 30px;
                text-align: center;
            }
            .email-header h1 {
                margin: 0;
                font-size: 28px;
            }
            .email-body {
                padding: 30px;
                color: #333;
            }
            .greeting {
                font-size: 18px;
                margin-bottom: 20px;
            }
            .info-box {
                background-color: #f8f9fa;
                border-left: 4px solid #c8a882;
                padding: 15px;
                margin: 20px 0;
                border-radius: 15px;
            }
            .info-box h3 {
                margin-top: 0;
                color: #c8a882;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #e0e0e0;
            }
            .info-row:last-child {
                border-bottom: none;
            }
            .info-label {
                font-weight: bold;
                color: #555;
            }
            .info-value {
                color: #333;
            }
            .credentials-box {
                background: #c8a882;
                color: white;
                padding: 20px;
                border-radius: 15px;
                margin: 25px 0;
                text-align: center;
            }
            .credentials-box h3 {
                margin-top: 0;
                font-size: 20px;
            }
            .credential-item {
                background-color: rgba(255,255,255,0.2);
                padding: 12px;
                border-radius: 8px;
                margin: 10px 0;
                font-size: 16px;
            }
            .credential-item strong {
                display: block;
                margin-bottom: 5px;
                font-size: 14px;
                opacity: 0.9;
            }
            .credential-value {
                font-size: 18px;
                font-weight: bold;
                letter-spacing: 1px;
            }
            .btn-primary {
                display: inline-block;
                background: #c8a882;
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 25px;
                font-weight: 600;
                margin: 20px 0;
            }
            .btn-primary:hover {
                background: #b8956b;
            }
            .steps {
                margin: 25px 0;
            }
            .step {
                display: flex;
                align-items: flex-start;
                margin: 15px 0;
            }
            .step-number {
                background: #c8a882;
                color: white;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                margin-right: 15px;
                flex-shrink: 0;
            }
            .step-content h4 {
                margin: 0 0 5px 0;
                color: #c8a882;
            }
            .step-content p {
                margin: 0;
                color: #666;
                font-size: 14px;
            }
            .footer {
                background-color: #f8f9fa;
                padding: 20px;
                text-align: center;
                color: #666;
                font-size: 12px;
            }
            .footer a {
                color: #c8a882;
                text-decoration: none;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='email-header'>
                <h1>🎊 ¡Gracias por tu compra!</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>Tu pedido ha sido confirmado</p>
            </div>
            
            <div class='email-body'>
                <div class='greeting'>
                    Hola <strong>" . htmlspecialchars($nombre_cliente) . "</strong>,
                </div>
                
                <p>¡Muchas gracias por confiar en Carta Digital para tu evento especial! Tu pago ha sido procesado exitosamente.</p>
                
                <div class='info-box'>
                    <h3>📋 Detalles de tu Pedido</h3>
                    <div class='info-row'>
                        <span class='info-label'>Número de pedido:</span>
                        <span class='info-value'>#" . str_pad($pedido['id'], 5, '0', STR_PAD_LEFT) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Plan:</span>
                        <span class='info-value'>" . ucfirst($pedido['plan']) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Monto pagado:</span>
                        <span class='info-value'>$" . number_format($pedido['monto'], 2) . " MXN</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Novios:</span>
                        <span class='info-value'>" . htmlspecialchars($pedido['nombres_novios']) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Fecha del evento:</span>
                        <span class='info-value'>" . date('d/m/Y', strtotime($pedido['fecha_evento'])) . "</span>
                    </div>
                </div>
                
                <div class='credentials-box'>
                    <h3>🔑 Tus Credenciales de Acceso</h3>
                    <p style='margin-bottom: 15px; opacity: 0.9;'>Usa estos datos para acceder a tu dashboard:</p>
                    
                    <div class='credential-item'>
                        <strong>📝 Código de acceso:</strong>
                        <div class='credential-value'>" . htmlspecialchars($slug_cliente) . "</div>
                    </div>
                    
                    <div class='credential-item'>
                        <strong>🔒 Contraseña:</strong>
                        <div class='credential-value'>" . htmlspecialchars($raw_password) . "</div>
                    </div>
                </div>
                
                <div style='text-align: center;'>
                    <a href='https://cartadigital.com.mx/dashboard.php' class='btn-primary'>
                        Acceder a mi Dashboard
                    </a>
                </div>
                
                <h3 style='color: #c8a882; margin-top: 30px;'>📝 Próximos Pasos</h3>
                <div class='steps'>
                    <div class='step'>
                        <div class='step-number'>1</div>
                        <div class='step-content'>
                            <h4>Accede a tu dashboard</h4>
                            <p>Ingresa con tu código de acceso y contraseña para entrar a tu panel.</p>
                        </div>
                    </div>
                    
                    <div class='step'>
                        <div class='step-number'>2</div>
                        <div class='step-content'>
                            <h4>Espera nuestro contacto</h4>
                            <p>Nos comunicaremos contigo para obtener los detalles de tu invitación.</p>
                        </div>
                    </div>
                    
                    <div class='step'>
                        <div class='step-number'>3</div>
                        <div class='step-content'>
                            <h4>Comparte con tus invitados</h4>
                            <p>Cuando esté lista, comparte tu invitación digital por WhatsApp, email o redes sociales.</p>
                        </div>
                    </div>
                </div>
                
                <p style='margin-top: 25px;'>Si tienes alguna duda, no dudes en contactarnos. ¡Estamos aquí para ayudarte!</p>
                
                <p style='margin-top: 20px;'>
                    <strong>Saludos cordiales,</strong><br>
                    El equipo de Carta Digital 💜
                </p>
            </div>
            
            <div class='footer'>
                <p>
                    📱 WhatsApp: <a href='https://wa.me/523339047672'>+52 33 3904 7672</a><br>
                    📧 Email: <a href='mailto:contacto@cartadigital.com.mx'>contacto@cartadigital.com.mx</a>
                </p>
                <p style='margin-top: 10px;'>
                    © 2025 Carta Digital. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    try {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email_destino, $nombre_cliente);
        $mail->addReplyTo(SMTP_FROM_EMAIL, 'Soporte Carta Digital');
        
        // Contenido
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        
        // Enviar
        $mail->send();
        error_log("✅ Email enviado exitosamente a: " . $email_destino);
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Error al enviar email: {$mail->ErrorInfo}");
        return false;
    }
}
?>