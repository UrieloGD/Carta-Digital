<?php
/**
 * Webhook de Mercado Pago
 * Ruta: api/webhook_mp.php
 * Se ejecuta cuando MP confirma/rechaza un pago
 */

require_once '../config/database.php';
require_once '../config/mercadopago_config.php';

header('Content-Type: application/json');

try {
    // Obtener datos del webhook
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log de debugging
    error_log("Webhook MP recibido: " . json_encode($data));
    
    if (!$data) {
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        exit;
    }
    
    // Procesar según tipo de evento
    if (isset($data['type']) && $data['type'] === 'payment') {
        $payment_data = $data['data'] ?? [];
        
        if (!empty($payment_data['id'])) {
            $payment_id = $payment_data['id'];
            
            // Conectar BD
            $database = new Database();
            $db = $database->getConnection();
            
            // Buscar pedido por payment_intent_id (que es el ID de la preferencia MP)
            $stmt = $db->prepare("
                SELECT p.id, p.invitacion_id, p.cliente_id
                FROM pedidos p
                WHERE p.payment_intent_id = ?
                LIMIT 1
            ");
            $stmt->execute([$payment_id]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($pedido) {
                // Actualizar estado a completado
                $stmt = $db->prepare("
                    UPDATE pedidos 
                    SET estado = 'completado', fecha_pago = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$pedido['id']]);
                
                error_log("✅ Pago confirmado por webhook MP: Pedido ID=" . $pedido['id']);
            }
        }
    }
    
    // Confirmar recepción a MP
    http_response_code(200);
    echo json_encode(['status' => 'received']);
    
} catch (Exception $e) {
    error_log("Error en webhook MP: " . $e->getMessage());
    http_response_code(200); // Siempre responder 200 a MP
    echo json_encode(['error' => $e->getMessage()]);
}
?>
