<?php
/**
 * API para crear Preference en Mercado Pago (sin SDK)
 */

session_start();
header('Content-Type: application/json');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // CARGAR CREDENCIALES PRIMERO
    $env_file = __DIR__ . '/../.env';
    
    if (!file_exists($env_file)) {
        throw new Exception('Archivo .env no encontrado en: ' . $env_file);
    }
    
    // Parsear .env
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
        }
    }
    
    $access_token = $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '';
    $webhook_url = $_ENV['MERCADOPAGO_WEBHOOK_URL'] ?? '';
    
    if (empty($access_token)) {
        throw new Exception('MERCADOPAGO_ACCESS_TOKEN vacío en .env');
    }
    
    // CONECTAR BD
    require_once '../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // OBTENER DATOS
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar campos
    $requiredFields = ['nombre', 'apellido', 'email', 'telefono', 'plan', 'nombre_novio', 'nombre_novia', 'fecha_evento', 'hora_evento'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Campo requerido vacío: {$field}");
        }
    }
    
    // Limpiar datos
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
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido: ' . $email);
    }
    
    // Obtener plan y precio
    $stmt = $db->prepare("SELECT id, precio FROM planes WHERE nombre = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$plan]);
    $plan_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan_data) {
        throw new Exception("Plan no encontrado: {$plan}");
    }
    
    $plan_id = $plan_data['id'];
    $monto = $plan_data['precio'];
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Generar slug cliente
    $slug_cliente = strtolower("{$nombre_novia}-{$nombre_novio}");
    $slug_cliente = preg_replace('/[^a-z0-9]+/', '-', $slug_cliente);
    $slug_cliente = trim($slug_cliente, '-');
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM clientes WHERE slug = ?");
    $stmt->execute([$slug_cliente]);
    if ($stmt->fetchColumn() > 0) {
        $slug_cliente = $slug_cliente . '-' . substr(md5(uniqid()), 0, 8);
    }
    
    // Generar contraseña
    $raw_password = $slug_cliente . str_replace('-', '', $fecha_evento);
    $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
    
    // Crear cliente
    $stmt = $db->prepare("
        INSERT INTO clientes (slug, nombre, apellido, nombres_novios, email, telefono, password, fecha_registro) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$slug_cliente, $nombre, $apellido, $nombres_novios, $email, $telefono, $password_hash]);
    $cliente_id = $db->lastInsertId();
    
    error_log("✅ Cliente creado: ID={$cliente_id}");
    
    // Crear invitación
    $slug_invitacion = strtolower("{$nombre_novia}-{$nombre_novio}");
    $slug_invitacion = preg_replace('/[^a-z0-9]+/', '-', $slug_invitacion);
    $slug_invitacion = trim($slug_invitacion, '-');
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM invitaciones WHERE slug = ?");
    $stmt->execute([$slug_invitacion]);
    if ($stmt->fetchColumn() > 0) {
        $slug_invitacion = $slug_invitacion . '-' . date('dmy', strtotime($fecha_evento));
    }
    
    // Crear invitación
    $stmt = $db->prepare("
        INSERT INTO invitaciones (plantilla_id, cliente_id, slug, nombres_novios, fecha_evento, hora_evento, plan_id, activa, fecha_creacion) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->execute([
        $plantilla_id ?? 1,
        $cliente_id,
        $slug_invitacion,
        $nombres_novios,
        $fecha_evento,
        $hora_evento,
        $plan_id
    ]);

    $invitacion_id = $db->lastInsertId();

    $preference_data = [
        "items" => [[
            "id" => "invitacion_" . $invitacion_id, 
            "title" => "Plan " . ucfirst($plan) . " - Carta Digital",
            "quantity" => 1,
            "unit_price" => (float)$monto,
            "currency_id" => "MXN"
        ]],
        "payer" => [
            "name" => $nombre,
            "surname" => $apellido,
            "email" => $email,
            "phone" => ["area_code" => "52", "number" => str_replace([' ', '-', '+', '52'], '', $telefono)]
        ],
        "notification_url" => $webhook_url,
        "back_urls" => [
            "success" => "http://" . $_SERVER['HTTP_HOST'] . "/payment_success_mp.php?collection_id={collection_id}",
            "failure" => "http://" . $_SERVER['HTTP_HOST'] . "/payment_failed_mp.php",
            "pending" => "http://" . $_SERVER['HTTP_HOST'] . "/payment_pending_mp.php"
        ],
        "external_reference" => "invitacion_" . $invitacion_id
    ];
    
    // Llamar API MP con cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($preference_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if (!empty($curl_error)) {
        throw new Exception('cURL error: ' . $curl_error);
    }
    
    $preference = json_decode($response, true);
    
    if ($http_code !== 201 || empty($preference['id'])) {
        throw new Exception('Error MP API (HTTP ' . $http_code . '): ' . ($preference['message'] ?? json_encode($preference)));
    }
    
    // Crear pedido
    $stmt = $db->prepare("
        INSERT INTO pedidos (cliente_id, invitacion_id, plantilla_id, plan, monto, metodo_pago, payment_intent_id) 
        VALUES (?, ?, ?, ?, ?, 'mercado_pago', ?)
    ");
    $stmt->execute([
        $cliente_id, 
        $invitacion_id, 
        $plantilla_id ?? 1, 
        $plan, 
        $monto,
        $preference['id']
    ]);
    $pedido_id = $db->lastInsertId();
    
    $db->commit();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'preference_url' => $preference['init_point'],
        'preference_id' => $preference['id'],
        'pedido_id' => $pedido_id
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("❌ Error registro_mercadopago: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
