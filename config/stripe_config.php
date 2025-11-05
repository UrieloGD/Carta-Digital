<?php
/**
 * Configuración de Stripe
 * Las claves se cargan desde variables de entorno (.env)
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Modo de prueba/producción
define('STRIPE_TEST_MODE', filter_var($_ENV['STRIPE_TEST_MODE'] ?? 'true', FILTER_VALIDATE_BOOLEAN));

if (STRIPE_TEST_MODE) {
    // CLAVES DE PRUEBA (desde .env)
    define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_TEST_PUBLISHABLE_KEY'] ?? '');
    define('STRIPE_SECRET_KEY', $_ENV['STRIPE_TEST_SECRET_KEY'] ?? '');
    define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_TEST_WEBHOOK_SECRET'] ?? '');
} else {
    // CLAVES DE PRODUCCIÓN (desde .env)
    define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_LIVE_PUBLISHABLE_KEY'] ?? '');
    define('STRIPE_SECRET_KEY', $_ENV['STRIPE_LIVE_SECRET_KEY'] ?? '');
    define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_LIVE_WEBHOOK_SECRET'] ?? '');
}

// Validar que las claves estén configuradas
if (empty(STRIPE_SECRET_KEY) || empty(STRIPE_PUBLISHABLE_KEY)) {
    error_log('❌ ERROR: Las claves de Stripe no están configuradas correctamente');
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        die('Error de configuración del servidor. Por favor contacta al administrador.');
    }
}

// Inicializar Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Precios de planes (en centavos MXN)
$PLANES_PRECIOS = [
    'escencial' => 69900,    // $699 MXN
    'premium' => 89900,      // $899 MXN
    'exclusivo' => 119900    // $1,199 MXN
];

// Descripciones de planes
$PLANES_DESC = [
    'escencial' => 'Invitación Digital - Plan Escencial',
    'premium' => 'Invitación Digital - Plan Premium',
    'exclusivo' => 'Invitación Digital - Plan Exclusivo'
];
?>