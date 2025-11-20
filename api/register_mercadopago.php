<?php
/**
 * API para crear Preference en Mercado Pago
 * Lógica corregida: Pedido -> Preferencia -> Update Pedido
 */

session_start();
header('Content-Type: application/json');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // CARGAR CREDENCIALES
    $env_file = __DIR__ . '/../.env';
    
    if (!file_exists($env_file)) {
        throw new Exception('Archivo .env no encontrado');
    }
    
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
    
    $access_token = $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '';
    $webhook_url = $_ENV['MERCADOPAGO_WEBHOOK_URL'] ?? '';
    
    if (empty($access_token)) throw new Exception('Falta ACCESS_TOKEN');
    
    // CONECTAR BD
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // OBTENER DATOS
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) throw new Exception('Datos JSON inválidos');
    
    // Validaciones básicas
    $requiredFields = ['nombre', 'apellido', 'email', 'telefono', 'plan', 'nombre_novio', 'nombre_novia'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) throw new Exception("Campo requerido: {$field}");
    }
    
    // Limpieza de datos
    $nombre = trim($data['nombre']);
    $apellido = trim($data['apellido']);
    $nombre_novio = trim($data['nombre_novio']);
    $nombre_novia = trim($data['nombre_novia']);
    $nombres_novios = "{$nombre_novia} & {$nombre_novio}";
    $email = trim(strtolower($data['email']));
    $telefono = trim($data['telefono']);
    $plan = trim($data['plan']);
    $plantilla_id = !empty($data['plantilla_id']) ? (int)$data['plantilla_id'] : null;
    $fecha_evento = $data['fecha_evento'];
    $hora_evento = $data['hora_evento'];
    
    // Obtener Precio
    $stmt = $db->prepare("SELECT id, precio FROM planes WHERE nombre = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$plan]);
    $plan_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan_data) throw new Exception("Plan no encontrado");
    $plan_id = $plan_data['id'];
    $monto = $plan_data['precio'];
    
    // INICIAR TRANSACCIÓN
    $db->beginTransaction();
    
    // 1. CREAR CLIENTE (Lógica original tuya)
    $slug_cliente = strtolower("{$nombre_novia}-{$nombre_novio}");
    $slug_cliente = preg_replace('/[^a-z0-9]+/', '-', $slug_cliente);
    
    // Verificar duplicados slug
    $stmt = $db->prepare("SELECT COUNT(*) FROM clientes WHERE slug = ?");
    $stmt->execute([$slug_cliente]);
    if ($stmt->fetchColumn() > 0) $slug_cliente .= '-' . substr(md5(uniqid()), 0, 4);
    
    $raw_password = $slug_cliente . str_replace('-', '', $fecha_evento);
    $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO clientes (slug, nombre, apellido, nombres_novios, email, telefono, password, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$slug_cliente, $nombre, $apellido, $nombres_novios, $email, $telefono, $password_hash]);
    $cliente_id = $db->lastInsertId();
    
    // 2. CREAR INVITACIÓN
    $slug_invitacion = $slug_cliente; 
    $stmt = $db->prepare("INSERT INTO invitaciones (plantilla_id, cliente_id, slug, nombres_novios, fecha_evento, hora_evento, plan_id, activa, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())");
    $stmt->execute([$plantilla_id ?? 1, $cliente_id, $slug_invitacion, $nombres_novios, $fecha_evento, $hora_evento, $plan_id]);
    $invitacion_id = $db->lastInsertId();
    
    // =========================================================================
    // 3. ¡CRUCIAL! CREAR EL PEDIDO PRIMERO (Estado Pendiente)
    // =========================================================================
    $stmt = $db->prepare("
        INSERT INTO pedidos (cliente_id, invitacion_id, plantilla_id, plan, monto, metodo_pago, estado, fecha_creacion) 
        VALUES (?, ?, ?, ?, ?, 'mercado_pago', 'pendiente', NOW())
    ");
    $stmt->execute([$cliente_id, $invitacion_id, $plantilla_id ?? 1, $plan, $monto]);
    $pedido_id = $db->lastInsertId(); // <--- AQUI TENEMOS EL ID PARA LA REFERENCIA
    
    
    // =========================================================================
    // 4. CREAR PREFERENCIA MP (Usando el ID del pedido)
    // =========================================================================
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $base_url = $protocol . '://' . $_SERVER['HTTP_HOST']; // OJO: En Prod asegúrate que esto sea correcto
    
    // URLs de retorno apuntando al script payment_success_mp.php
    // Usamos external_reference para pasar el ID del pedido
    $preference_data = [
        "items" => [[
            "id" => "pedido_" . $pedido_id,
            "title" => "Plan " . ucfirst($plan) . " - Carta Digital",
            "quantity" => 1,
            "unit_price" => (float)$monto,
            "currency_id" => "MXN"
        ]],
        "payer" => [
            "name" => $nombre,
            "surname" => $apellido,
            "email" => $email,
             "phone" => [
                "area_code" => "52", 
                "number" => str_replace([' ', '-', '+', '52'], '', $telefono)
            ]
        ],
        "back_urls" => [
            "success" => $base_url . "/payment_success_mp.php", // Recibirá ?external_reference=$pedido_id
            "failure" => $base_url . "/payment_failed_mp.php",
            "pending" => $base_url . "/payment_pending_mp.php"
        ],
        "auto_return" => "approved",
        "external_reference" => (string)$pedido_id, // <--- CLAVE PARA EL WEBHOOK
        "statement_descriptor" => "CARTA DIGITAL"
    ];
    
    if (!empty($webhook_url)) {
        $preference_data["notification_url"] = $webhook_url;
    }
    
    // LLAMADA API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($preference_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ]
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $preference = json_decode($response, true);
    
    if ($http_code !== 201 || empty($preference['id'])) {
        throw new Exception('Error MP API: ' . ($preference['message'] ?? 'Desconocido'));
    }
    
    // =========================================================================
    // 5. ACTUALIZAR PEDIDO CON EL ID DE PREFERENCIA
    // =========================================================================
    // Guardamos el preference_id en payment_intent_id temporalmente para referencia
    $stmt = $db->prepare("UPDATE pedidos SET payment_intent_id = ? WHERE id = ?");
    $stmt->execute([$preference['id'], $pedido_id]);
    
    $db->commit();
    
    // DETECCIÓN DE ENTORNO PARA REDIRECT
    $environment_config = $_ENV['MERCADOPAGO_ENVIRONMENT'] ?? 'sandbox';
    $redirect_url = $preference['init_point'];
    $is_sandbox = false;

    if ($environment_config === 'sandbox') {
        if (!empty($preference['sandbox_init_point'])) {
            $redirect_url = $preference['sandbox_init_point'];
            $is_sandbox = true;
        }
    }
    
    echo json_encode([
        'success' => true,
        'preference_url' => $redirect_url,
        'pedido_id' => $pedido_id,
        'is_sandbox' => $is_sandbox
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
