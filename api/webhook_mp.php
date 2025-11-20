<?php
/**
 * Webhook de Mercado Pago
 * Ruta: api/webhook_mp.php
 * Se ejecuta cuando MP notifica un cambio en un pago
 */

// Cargar configuración y base de datos
require_once '../config/database.php';
// Cargar librerías de Email (Igual que en payment_success)
require_once '../config/email_config.php';
require_once '../functions/email_bienvenida.php';
require_once '../functions/email_notificacion_admin.php';

require_once '../vendor/autoload.php'; 

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');

try {
    // 1. Obtener datos
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(200);
        exit(json_encode(['status' => 'ok', 'message' => 'No data']));
    }
    
    // 2. Extraer ID de Pago
    $type = $data['type'] ?? $data['topic'] ?? '';
    $payment_id = null;

    if ($type === 'payment') {
        $payment_id = $data['data']['id'] ?? $data['resource'] ?? null;
    } 
    if (isset($data['resource']) && strpos($data['resource'], '/v1/payments/') !== false) {
        $parts = explode('/', $data['resource']);
        $payment_id = end($parts);
    }

    if ($payment_id) {
        // 3. Consultar API de Mercado Pago
        $access_token = $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/$payment_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $access_token"]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $payment_info = json_decode($response, true);
            $status = $payment_info['status'] ?? ''; 
            $external_reference = $payment_info['external_reference'] ?? null;
            
            if ($external_reference) {
                $database = new Database();
                $db = $database->getConnection();
                
                // Verificar estado actual antes de actualizar
                // Obtenemos datos completos del pedido para los correos
                $stmt = $db->prepare("
                    SELECT 
                        p.*, 
                        c.nombre, c.apellido, c.email, c.slug,
                        c.nombres_novios, c.telefono,
                        pl.nombre as plantilla_nombre,
                        i.slug as invitacion_slug, 
                        i.fecha_evento, i.hora_evento
                    FROM pedidos p
                    JOIN clientes c ON p.cliente_id = c.id
                    LEFT JOIN plantillas pl ON p.plantilla_id = pl.id
                    LEFT JOIN invitaciones i ON p.invitacion_id = i.id
                    WHERE p.id = ?
                ");
                $stmt->execute([$external_reference]);
                $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($pedido) {
                    $nuevo_estado = null;
                    
                    if ($status === 'approved' && $pedido['estado'] !== 'completado') {
                        $nuevo_estado = 'completado';
                    } else if (($status === 'rejected' || $status === 'cancelled') && $pedido['estado'] !== 'fallido') {
                        $nuevo_estado = 'fallido';
                    }
                    
                    if ($nuevo_estado) {
                        // Actualizar BD
                        $stmtUpdate = $db->prepare("
                            UPDATE pedidos 
                            SET estado = ?, payment_intent_id = ?, fecha_pago = NOW() 
                            WHERE id = ?
                        ");
                        $stmtUpdate->execute([$nuevo_estado, $payment_id, $external_reference]);
                        
                        // =====================================================
                        // ENVÍO DE CORREOS (Solo si se completó exitosamente)
                        // =====================================================
                        if ($nuevo_estado === 'completado') {
                            $raw_password = $pedido['slug'] . str_replace('-', '', $pedido['fecha_evento']);
                            
                            // Enviar emails
                            if (function_exists('enviarEmailBienvenida')) {
                                enviarEmailBienvenida($pedido, $raw_password);
                            }
                            if (function_exists('enviarNotificacionAdmin')) {
                                enviarNotificacionAdmin($pedido, $raw_password);
                            }
                            error_log("✅ Webhook: Emails enviados para pedido #$external_reference");
                        }
                        
                        error_log("✅ Webhook: Pedido #$external_reference actualizado a $nuevo_estado");
                    } else {
                        error_log("ℹ️ Webhook: Pedido #$external_reference ya estaba en estado {$pedido['estado']}");
                    }
                }
            }
        }
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'received']);
    
} catch (Exception $e) {
    error_log("❌ Webhook Error: " . $e->getMessage());
    http_response_code(200);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
