<?php
/**
 * Configuración de Email SMTP
 * Las credenciales se cargan desde variables de entorno (.env)
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configuración de email desde .env
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.hostinger.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 465);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'Carta Digital');

// Validar que las credenciales estén configuradas
if (empty(SMTP_USER) || empty(SMTP_PASS)) {
    error_log('❌ ERROR: Las credenciales de email no están configuradas correctamente en .env');
}
?>
