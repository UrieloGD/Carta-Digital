<?php
/**
 * Configuración de Mercado Pago
 * Versión simplificada (sin SDK, usa cURL)
 */
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Cargar variables de entorno con fallback a $_ENV
if (function_exists('getenv')) {
    $access_token = getenv('MERCADOPAGO_ACCESS_TOKEN') ?: ($_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '');
    $public_key = getenv('MERCADOPAGO_PUBLIC_KEY') ?: ($_ENV['MERCADOPAGO_PUBLIC_KEY'] ?? '');
    $environment = getenv('MERCADOPAGO_ENVIRONMENT') ?: ($_ENV['MERCADOPAGO_ENVIRONMENT'] ?? 'sandbox');
    $webhook_url = getenv('MERCADOPAGO_WEBHOOK_URL') ?: ($_ENV['MERCADOPAGO_WEBHOOK_URL'] ?? '');
} else {
    $access_token = $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '';
    $public_key = $_ENV['MERCADOPAGO_PUBLIC_KEY'] ?? '';
    $environment = $_ENV['MERCADOPAGO_ENVIRONMENT'] ?? 'sandbox';
    $webhook_url = $_ENV['MERCADOPAGO_WEBHOOK_URL'] ?? '';
}

// ============================================
// CONFIGURACIÓN MERCADO PAGO
// ============================================
define('MERCADOPAGO_ACCESS_TOKEN', $_ENV['MERCADOPAGO_ACCESS_TOKEN'] ?? '');
define('MERCADOPAGO_PUBLIC_KEY', $_ENV['MERCADOPAGO_PUBLIC_KEY'] ?? '');
define('MERCADOPAGO_ENVIRONMENT', $_ENV['MERCADOPAGO_ENVIRONMENT'] ?? 'sandbox');
define('MERCADOPAGO_WEBHOOK_URL', $_ENV['MERCADOPAGO_WEBHOOK_URL'] ?? '');

// Validar configuración
if (empty(MERCADOPAGO_ACCESS_TOKEN) || empty(MERCADOPAGO_PUBLIC_KEY)) {
    error_log('❌ ERROR: Las credenciales de Mercado Pago no están configuradas');
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        die('Error de configuración de Mercado Pago. Contacta al administrador.');
    }
}

// Define la variable para saber que está cargado
define('MERCADOPAGO_CONFIGURED', true);
?>
