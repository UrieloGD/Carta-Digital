<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/load_env.php';

/**
 * Enviar notificación al admin cuando hay un nuevo pedido
 */
function enviarNotificacionAdmin($pedido, $raw_password) {
    $admin_email = 'contacto@cartadigital.com.mx';
    $subject = "🎊 Nuevo Pedido - Carta Digital #" . str_pad($pedido['id'], 5, '0', STR_PAD_LEFT);
    
    $html_body = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Nuevo Pedido</title>
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
            .section-title {
                font-size: 20px;
                color: #c8a882;
                margin-top: 25px;
                margin-bottom: 15px;
                font-weight: 600;
            }
            .info-box {
                background-color: #f8f9fa;
                border-left: 4px solid #c8a882;
                padding: 15px;
                margin: 15px 0;
                border-radius: 8px;
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
                background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
                border: 2px solid #ffc107;
                color: #856404;
                padding: 20px;
                border-radius: 15px;
                margin: 25px 0;
                text-align: center;
            }
            .credentials-box h3 {
                margin-top: 0;
                font-size: 20px;
                color: #856404;
            }
            .credential-item {
                background-color: rgba(255,255,255,0.7);
                padding: 12px;
                border-radius: 8px;
                margin: 10px 0;
                font-size: 16px;
            }
            .credential-item strong {
                display: block;
                margin-bottom: 5px;
                font-size: 14px;
            }
            .credential-value {
                font-size: 18px;
                font-weight: bold;
                letter-spacing: 0.5px;
                color: #856404;
            }
            .action-box {
                background: #e8f5e9;
                border-left: 4px solid #4caf50;
                padding: 15px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .action-box strong {
                color: #2e7d32;
            }
            .whatsapp-link {
                display: inline-block;
                background: #25d366;
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 25px;
                font-weight: 600;
                margin: 15px 0;
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
                <h1>🎊 Nuevo Pedido Recibido</h1>
                <p style='margin: 10px 0 0 0; opacity: 0.9;'>Pedido #" . str_pad($pedido['id'], 5, '0', STR_PAD_LEFT) . "</p>
            </div>
            
            <div class='email-body'>
                <h3 class='section-title'>📋 Información del Cliente</h3>
                <div class='info-box'>
                    <div class='info-row'>
                        <span class='info-label'>Nombre completo:</span>
                        <span class='info-value'>" . htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellido']) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Email:</span>
                        <span class='info-value'>" . htmlspecialchars($pedido['email']) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Teléfono:</span>
                        <span class='info-value'>" . htmlspecialchars($pedido['telefono']) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Novios:</span>
                        <span class='info-value'>" . htmlspecialchars($pedido['nombres_novios']) . "</span>
                    </div>
                </div>
                
                <h3 class='section-title'>💳 Detalles del Pedido</h3>
                <div class='info-box'>
                    <div class='info-row'>
                        <span class='info-label'>Plan:</span>
                        <span class='info-value'>" . ucfirst($pedido['plan']) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Monto pagado:</span>
                        <span class='info-value'>$" . number_format($pedido['monto'], 2) . " MXN</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Fecha del evento:</span>
                        <span class='info-value'>" . date('d/m/Y', strtotime($pedido['fecha_evento'])) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Hora del evento:</span>
                        <span class='info-value'>" . date('H:i', strtotime($pedido['hora_evento'])) . "</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Slug de invitación:</span>
                        <span class='info-value'>" . htmlspecialchars($pedido['invitacion_slug']) . "</span>
                    </div>
                </div>
                
                <h3 class='section-title'>🔑 Credenciales del Cliente</h3>
                <div class='credentials-box'>
                    <h3>Acceso al Dashboard</h3>
                    <p style='margin-bottom: 15px; opacity: 0.9;'>El cliente puede acceder con estas credenciales:</p>
                    
                    <div class='credential-item'>
                        <strong>📧 Email de acceso:</strong>
                        <div class='credential-value'>" . htmlspecialchars($pedido['email']) . "</div>
                    </div>
                    
                    <div class='credential-item'>
                        <strong>🔒 Contraseña:</strong>
                        <div class='credential-value'>" . htmlspecialchars($raw_password) . "</div>
                    </div>
                </div>
                
                <div class='action-box'>
                    <p style='margin: 0;'>
                        <strong>⚠️ Acción Requerida:</strong><br>
                        Contacta al cliente para obtener los detalles de la invitación (fotos, historia, ubicaciones, etc.)
                    </p>
                </div>
                
                <div style='text-align: center; margin: 25px 0;'>
                    <a href='https://wa.me/52" . preg_replace('/[^0-9]/', '', $pedido['telefono']) . "?text=Hola%20" . urlencode($pedido['nombre']) . "%2C%20confirmamos%20tu%20pedido%20de%20invitación%20digital.%20¿Cuándo%20podemos%20coordinar%20para%20obtener%20los%20detalles%3F' class='whatsapp-link' target='_blank'>
                        <span style='font-size: 20px;'>📱</span> Contactar por WhatsApp
                    </a>
                </div>
                
                <p style='margin-top: 25px; color: #666; font-size: 14px; text-align: center;'>
                    Este es un email automático generado por el sistema de Carta Digital.
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
        
        $mail->setFrom(SMTP_FROM_EMAIL, 'Carta Digital - Sistema');
        $mail->addAddress($admin_email, 'Admin Carta Digital');
        
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        
        $mail->send();
        error_log("✅ Notificación enviada al admin: " . $admin_email);
        return true;
        
    } catch (Exception $e) {
        error_log("❌ Error al enviar notificación admin: {$mail->ErrorInfo}");
        return false;
    }
}
?>