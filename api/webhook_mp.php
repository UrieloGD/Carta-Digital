<?php
/**
 * Webhook de Mercado Pago
 * Ruta: api/webhook_mp.php
 * Se ejecuta cuando MP notifica un cambio en un pago
 */

// Cargar configuración y base de datos
require_once '../config/database.php';
require_once '../vendor/autoload.php'; // Asegúrate de cargar el autoload para Dotenv

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configurar headers
header('Content-Type: application/json');

try {
    // 1. Obtener datos del webhook
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log para debugging (Opcional: guardar en un archivo de texto para revisar)
    // file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - " . $input . "\n", FILE_APPEND);
    
    if (!$data) {
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'No data received']);
        exit;
    }
    
    // 2. Verificar si es una notificación de pago
    // Mercado Pago puede enviar notificaciones con estructura 'type' o 'topic'
    $type = $data['type'] ?? $data['topic'] ?? '';
    $payment_id = null;

    if ($type === 'payment') {
        $payment_id = $data['data']['id'] ?? $data['resource'] ?? null;
    } 
    // A veces la estructura cambia ligeramente dependiendo de la versión de la API
    // Si recibes una URL en 'resource', extrae el ID
    if (isset($data['resource']) && strpos($data['resource'], '/v1/payments/') !== false) {
        $parts = explode('/', $data['resource']);
        $payment_id = end($parts);
    }

    if ($payment_id) {
        // 3. Consultar API de Mercado Pago para validar el estado real
        // Nunca confíes solo en los datos del webhook, consulta la fuente oficial
        
        $access_token = $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/$payment_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token",
            "Content-Type: application/json"
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            $payment_info = json_decode($response, true);
            
            // 4. Extraer datos clave
            $status = $payment_info['status'] ?? ''; // approved, pending, rejected
            $external_reference = $payment_info['external_reference'] ?? null; // AQUÍ DEBE VENIR TU PEDIDO ID
            
            // Solo procesamos si tenemos la referencia externa (ID del pedido)
            if ($external_reference) {
                
                // Conectar BD
                $database = new Database();
                $db = $database->getConnection();
                
                // Determinar el nuevo estado para tu BD
                $nuevo_estado = null;
                if ($status === 'approved') {
                    $nuevo_estado = 'completado';
                } else if ($status === 'rejected' || $status === 'cancelled') {
                    $nuevo_estado = 'fallido';
                }
                
                if ($nuevo_estado) {
                    // 5. Actualizar el pedido en tu BD
                    // Usamos external_reference para encontrar el pedido correcto
                    $stmt = $db->prepare("
                        UPDATE pedidos 
                        SET estado = ?, 
                            payment_intent_id = ?, -- Guardamos el ID real del pago de MP (ej. 1234567890)
                            fecha_pago = NOW() 
                        WHERE id = ?
                    ");
                    
                    // Ejecutamos update: estado, payment_id de MP, y el ID de tu pedido (external_reference)
                    $stmt->execute([$nuevo_estado, $payment_id, $external_reference]);
                    
                    error_log("✅ Webhook: Pedido #$external_reference actualizado a estado: $nuevo_estado (Payment ID: $payment_id)");
                }
            } else {
                error_log("⚠️ Webhook: Pago recibido ($payment_id) sin external_reference");
            }
        } else {
            error_log("❌ Webhook: Error al consultar API Mercado Pago. HTTP Code: $http_code");
        }
    }
    
    // Siempre responder 200 OK a Mercado Pago para que deje de reintentar
    http_response_code(200);
    echo json_encode(['status' => 'received']);
    
} catch (Exception $e) {
    error_log("❌ Webhook Error Fatal: " . $e->getMessage());
    http_response_code(200); // Responder 200 incluso en error interno para evitar bucle de MP
    echo json_encode(['error' => $e->getMessage()]);
}
?>
