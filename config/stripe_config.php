<?php
/**
 * Configuración de Stripe
 * Las claves se cargan desde variables de entorno (.env)
 * Los precios ahora se cargan desde la base de datos (tabla `planes`)
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

/**
 * FUNCIÓN: Obtener precios desde la base de datos
 * Sustituye los arrays estáticos de precios
 * 
 * @return array Array de precios formateados
 */
function obtenerPreciosPlanes() {
    static $precios_cache = null;
    
    // Si ya están en caché, devolverlos
    if ($precios_cache !== null) {
        return $precios_cache;
    }
    
    try {
        // Incluir DB solo si no está ya cargada
        if (!class_exists('Database')) {
            require_once __DIR__ . '/database.php';
        }
        
        $database = new Database();
        $db = $database->getConnection();
        
        // Obtener todos los planes activos
        $stmt = $db->query("SELECT nombre, precio FROM planes WHERE activo = 1 ORDER BY precio ASC");
        $planes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Formatear precios en centavos para Stripe
        $precios_cache = [];
        foreach ($planes as $plan) {
            // Convertir a centavos: 699.00 → 69900
            $precios_cache[$plan['nombre']] = (int)($plan['precio'] * 100);
        }
        
        return $precios_cache;
        
    } catch (Exception $e) {
        error_log("⚠️ Advertencia: No se pudieron obtener precios de la BD. Error: " . $e->getMessage());
        
        // FALLBACK: Precios por defecto (en caso de error en la BD)
        return [
            'escencial' => 69900,    // $699 MXN
            'premium' => 89900,      // $899 MXN
            'exclusivo' => 119900    // $1,199 MXN
        ];
    }
}

/**
 * FUNCIÓN: Obtener descripción de un plan
 * 
 * @param string $plan Nombre del plan
 * @return string Descripción formateada
 */
function obtenerDescripcionPlan($plan = '') {
    $plan = trim($plan) ?: 'genérico';
    return 'Invitación Digital - Plan ' . ucfirst($plan);
}

/**
 * Acceso a precios (para compatibilidad con código anterior)
 * Uso: $PLANES_PRECIOS['premium']
 */
$PLANES_PRECIOS = obtenerPreciosPlanes();

/**
 * Acceso a descripciones (para compatibilidad con código anterior)
 * Uso: $PLANES_DESC['premium']
 */
$PLANES_DESC = [];
foreach ($PLANES_PRECIOS as $nombre => $precio) {
    $PLANES_DESC[$nombre] = obtenerDescripcionPlan($nombre);
}
?>
