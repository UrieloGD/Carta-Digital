<?php
/**
 * API para registrar cliente y crear payment intent en Stripe
 * Este archivo maneja el registro del cliente y la creación del pago
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/database.php';
require_once '../config/stripe_config.php';

// Solo aceptar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

function generarSlugNovios($nombre_novia, $nombre_novio) {
    $combinar = trim("{$nombre_novia}-{$nombre_novio}");
    if (empty($combinar)) {
        return 'invitacion-' . time();
    }
    
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $combinar);
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
    $slug = trim($slug, '-');

    if (empty($slug)) {
        $slug = 'invitacion-' . time();
    }

    return $slug;
}

try {
    // Obtener datos JSON del request
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        throw new Exception('Datos inválidos o formato JSON incorrecto');
    }
    
    // Validar campos requeridos
    $requiredFields = ['nombre', 'apellido', 'email', 'telefono', 'plan', 'nombre_novio', 'nombre_novia', 'fecha_evento', 'hora_evento'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }
    
    // Limpiar y validar datos
    $nombre = trim($data['nombre']);
    $apellido = trim($data['apellido']);
    $nombre_novio = trim($data['nombre_novio']);
    $nombre_novia = trim($data['nombre_novia']);
    $nombres_novios = "{$nombre_novia} & {$nombre_novio}";
    $fecha_evento = $data['fecha_evento'];
    $hora_evento = $data['hora_evento'];
    $email = trim(strtolower($data['email']));
    $telefono = trim($data['telefono']);
    $plan = trim($data['plan']);
    $plantilla_id = !empty($data['plantilla_id']) ? (int)$data['plantilla_id'] : null;
    
    // Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email inválido');
    }
    
    // Validar nombres de novios
    if (empty($nombres_novios)) {
        throw new Exception('Los nombres de los novios son requeridos');
    }
    
    if (empty($nombre_novio) || empty($nombre_novia)) {
        throw new Exception('Los nombres del novio y la novia son requeridos');
    }
    
    // Validar plan
    if (!isset($PLANES_PRECIOS[$plan])) {
        throw new Exception('Plan inválido');
    }
    
    $monto = $PLANES_PRECIOS[$plan];
    $descripcion = $PLANES_DESC[$plan];
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // ============================================
    // OBTENER PLAN_ID DE LA TABLA PLANES
    // ============================================
    $stmt = $db->prepare("SELECT id, precio FROM planes WHERE nombre = ? LIMIT 1");
    $stmt->execute([$plan]);
    $plan_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan_data) {
        throw new Exception('Plan no encontrado en la base de datos');
    }
    
    $plan_id = $plan_data['id'];
    $precio_plan = $plan_data['precio'];
    
    // ============================================
    // GENERAR SLUG ÚNICO
    // ============================================
    $slug_cliente = generarSlugNovios($nombre_novia, $nombre_novio);
    
    // Verificar que sea único
    $stmt = $db->prepare("SELECT COUNT(*) FROM clientes WHERE slug = ?");
    $stmt->execute([$slug_cliente]);
    if ($stmt->fetchColumn() > 0) {
        $slug_cliente = $slug_cliente . '-' . substr(md5(uniqid()), 0, 8);
    }
    
    // ============================================
    // GENERAR CONTRASEÑA
    // ============================================
    $raw_password = $slug_cliente . str_replace('-', '', $fecha_evento);
    $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
    
    // ============================================
    // CREAR NUEVO CLIENTE (SIEMPRE)
    // ============================================
    $stmt = $db->prepare("
        INSERT INTO clientes (slug, nombre, apellido, nombres_novios, email, telefono, password, fecha_registro) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$slug_cliente, $nombre, $apellido, $nombres_novios, $email, $telefono, $password_hash]);
    $cliente_id = $db->lastInsertId();
        
    // ============================================
    // CREAR PAYMENT INTENT EN STRIPE
    // ============================================
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $monto,
        'currency' => 'mxn',
        'description' => $descripcion . ' - ' . $nombres_novios,
        'metadata' => [
            'cliente_id' => $cliente_id,
            'plan' => $plan,
            'plan_id' => $plan_id,
            'plantilla_id' => $plantilla_id ?? '',
            'nombres_novios' => $nombres_novios,
            'nombre_completo' => $nombre . ' ' . $apellido,
            'email' => $email,
            'telefono' => $telefono,
        ],
        'receipt_email' => $email,
        'automatic_payment_methods' => [
            'enabled' => true,
            'allow_redirects' => 'never'
        ]
    ]);
    
    // ============================================
    // GENERAR SLUG ÚNICO PARA LA INVITACIÓN
    // ============================================
    $slug_invitacion = generarSlugNovios($nombre_novia, $nombre_novio);
    
    // Verificar que sea único
    $stmt = $db->prepare("SELECT COUNT(*) FROM invitaciones WHERE slug = ?");
    $stmt->execute([$slug_invitacion]);
    if ($stmt->fetchColumn() > 0) {
        $fecha_formateada = date('dmy', strtotime($fecha_evento));
        $slug_invitacion = $slug_invitacion . '-' . $fecha_formateada;
    }
    
    // ==============================
    // CREAR REGISTRO EN INVITACIONES
    // ==============================
    $stmt = $db->prepare("
        INSERT INTO invitaciones (
            plantilla_id, 
            cliente_id, 
            slug, 
            nombres_novios, 
            fecha_evento, 
            hora_evento, 
            plan_id, 
            activa, 
            fecha_creacion
        ) 
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
    
    // ============================================
    // CREAR REGISTRO DE PEDIDO
    // ============================================
    $stmt = $db->prepare("
        INSERT INTO pedidos (
            cliente_id, 
            invitacion_id, 
            plantilla_id, 
            plan, 
            monto, 
            metodo_pago,  
            payment_intent_id, 
            fecha_creacion
        ) 
        VALUES (?, ?, ?, ?, ?, 'stripe', ?, NOW())
    ");
    
    $stmt->execute([
        $cliente_id,
        $invitacion_id,
        $plantilla_id ?? 1,
        $plan,
        $precio_plan,
        $paymentIntent->id
    ]);
    
    $pedido_id = $db->lastInsertId();

    // ⚠️ SOLO PARA PRUEBAS LOCALES - COMENTA O ELIMINA EN PRODUCCIÓN
    $stmt = $db->prepare("UPDATE pedidos SET estado = 'completado', fecha_pago = NOW() WHERE id = ?");
    $stmt->execute([$pedido_id]);
    
    // Confirmar transacción
    $db->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent->client_secret,
        'pedido_id' => $pedido_id,
        'cliente_id' => $cliente_id,
        'invitacion_id' => $invitacion_id,
        'slug_cliente' => $slug_cliente,
        'slug_invitacion' => $slug_invitacion,
        'payment_intent_id' => $paymentIntent->id
    ]);
    
} catch (\Stripe\Exception\CardException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'error' => 'Error con la tarjeta: ' . $e->getError()->message
    ]);
    error_log("❌ Stripe Card Error: " . $e->getMessage());
    
} catch (\Stripe\Exception\RateLimitException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(429);
    echo json_encode([
        'error' => 'Demasiadas peticiones, intenta de nuevo en unos momentos'
    ]);
    error_log("❌ Stripe Rate Limit: " . $e->getMessage());
    
} catch (\Stripe\Exception\InvalidRequestException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'error' => 'Solicitud inválida: ' . $e->getMessage()
    ]);
    error_log("❌ Stripe Invalid Request: " . $e->getMessage());
    
} catch (\Stripe\Exception\AuthenticationException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Error de configuración del servidor'
    ]);
    error_log("❌ Stripe Authentication Error: " . $e->getMessage());
    
} catch (\Stripe\Exception\ApiConnectionException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(503);
    echo json_encode([
        'error' => 'Error de conexión, intenta de nuevo'
    ]);
    error_log("❌ Stripe Connection Error: " . $e->getMessage());
    
} catch (\Stripe\Exception\ApiErrorException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al procesar el pago'
    ]);
    error_log("❌ Stripe API Error: " . $e->getMessage());
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al guardar la información'
    ]);
    error_log("❌ Database Error: " . $e->getMessage());
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
    error_log("❌ General Error: " . $e->getMessage());
}
?>