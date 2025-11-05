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

function generarSlugNovios($nombre_novio, $nombre_novia) {
    $combinar = trim("{$nombre_novio}-{$nombre_novia}");
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
    $requiredFields = ['nombre', 'apellido', 'email', 'telefono', 'plan', 'nombres_novios', 'fecha_evento', 'hora_evento'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }
    
    // Limpiar y validar datos - DEFINIR TODAS LAS VARIABLES AQUÍ
    $nombre = trim($data['nombre']);
    $apellido = trim($data['apellido']);
    $nombre_novio = trim($data['nombre_novio']);
    $nombre_novia = trim($data['nombre_novia']);
    $nombres_novios = "{$nombre_novio} & {$nombre_novia}";
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
    
    // Verificar si el email ya existe
    $stmt = $db->prepare("SELECT id FROM clientes WHERE email = ?");
    $stmt->execute([$email]);
    $clienteExistente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($clienteExistente) {
        // Si ya existe, usar ese cliente_id
        $cliente_id = $clienteExistente['id'];
        
        // Actualizar nombres_novios si cambió
        $stmt = $db->prepare("UPDATE clientes SET nombres_novios = ? WHERE id = ?");
        $stmt->execute([$nombres_novios, $cliente_id]);
    } else {
        // Generar slug único para el cliente
        $slug_cliente = generarSlugNovios($nombre_novio, $nombre_novia);
        
        // Crear nuevo cliente
        $stmt = $db->prepare("
            INSERT INTO clientes (slug, nombre, apellido, nombres_novios, email, telefono, fecha_registro) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$slug_cliente, $nombre, $apellido, $nombres_novios, $email, $telefono]);
        $cliente_id = $db->lastInsertId();
    }
    
    // Crear Payment Intent en Stripe
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $monto,
        'currency' => 'mxn',
        'description' => $descripcion . ' - ' . $nombres_novios,
        'metadata' => [
            'cliente_id' => $cliente_id,
            'plan' => $plan,
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
    
    // Generar slug único para la invitación
    $slug_invitacion = generarSlugNovios($nombre_novio, $nombre_novia);
    
    // Verificar que el slug sea único en invitaciones
    $stmt = $db->prepare("SELECT COUNT(*) FROM invitaciones WHERE slug = ?");
    $stmt->execute([$slug_invitacion]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        // Formatear fecha del evento para usarla en el slug, si existe ya el slug
        $fecha_formateada = '';
        if (!empty($fecha_evento)) {
            // Formato día, mes, año
            $fecha_formateada = date('dmy', strtotime($fecha_evento));
        } else {
            $fecha_formateada = date('dmy');
        }

        $slug_invitacion = $slug_invitacion . '-' . $fecha_formateada;
    }
    
    // Crear registro en invitaciones
    $stmt = $db->prepare("
        INSERT INTO invitaciones (plantilla_id, cliente_id, slug, nombres_novios, fecha_evento, hora_evento, activa, fecha_creacion) 
        VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->execute([
        $plantilla_id, $cliente_id, $slug_invitacion, $nombres_novios, $fecha_evento, $hora_evento
    ]);
    
    $invitacion_id = $db->lastInsertId();
    
    // Crear registro de pedido con el invitacion_id
    $stmt = $db->prepare("
        INSERT INTO pedidos (cliente_id, invitacion_id, plantilla_id, plan, monto, metodo_pago, estado, payment_intent_id, fecha_creacion) 
        VALUES (?, ?, ?, ?, ?, 'stripe', 'pendiente', ?, NOW())
    ");
    
    $stmt->execute([
        $cliente_id, $invitacion_id, $plantilla_id, $plan, $monto / 100, $paymentIntent->id
    ]);
    
    $pedido_id = $db->lastInsertId();

    // SOLO PARA PRUEBAS LOCALES --- COMENTA O ELIMINA EN PRODUCCIÓN
    $stmt = $db->prepare("UPDATE pedidos SET estado = 'completado', fecha_pago = NOW() WHERE id = ?");
    $stmt->execute([$pedido_id]);
    
    // Confirmar transacción
    $db->commit();
    
    // Log para debugging
    error_log("✅ Pedido creado exitosamente: ID={$pedido_id}, Cliente={$email}, Novios={$nombres_novios}, Plan={$plan}, Invitación={$invitacion_id}, PaymentIntent={$paymentIntent->id}");
    
    // Responder con éxito
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent->client_secret,
        'pedido_id' => $pedido_id,
        'cliente_id' => $cliente_id,
        'invitacion_id' => $invitacion_id,
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