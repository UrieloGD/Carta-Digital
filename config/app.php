<?php
// Configuración general de la aplicación
define('SITE_NAME', 'Carta Digital');
define('BASE_URL', 'http://localhost/carta-digital/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Configuración de zona horaria
date_default_timezone_set('America/Mexico_City');

// Función para obtener la URL base
function asset($path) {
    return ASSETS_URL . $path;
}

function url($path = '') {
    return BASE_URL . $path;
}
?>