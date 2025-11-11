<?php
/**
 * Configuración de Autenticación
 */

session_start();

// Tiempo de sesión (30 minutos)
define('SESSION_TIMEOUT', 30 * 60);

/**
 * Obtener la URL del login de forma consistente
 */
function getLoginUrl() {
    // Obtener la ruta base del proyecto
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Obtener el directorio base del proyecto
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $baseDir = dirname(dirname($scriptName)); // Sube dos niveles desde el archivo actual
    
    // Asegurar que termine en /
    if ($baseDir === '/' || $baseDir === '\\') {
        $baseDir = '';
    }
    
    return $protocol . '://' . $host . $baseDir . '/admin/login.php';
}

/**
 * Verificar si usuario está autenticado
 */
function isLoggedIn() {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    // Verificar timeout
    if (isset($_SESSION['ultimo_acceso'])) {
        if (time() - $_SESSION['ultimo_acceso'] > SESSION_TIMEOUT) {
            session_destroy();
            return false;
        }
    }
    
    $_SESSION['ultimo_acceso'] = time();
    return true;
}

/**
 * Verificar rol de usuario
 */
function checkRole($roles_permitidos = []) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $rol_usuario = $_SESSION['admin_rol'] ?? '';
    
    if (empty($roles_permitidos)) {
        return true; // Si no se especifica rol, solo verifica login
    }
    
    return in_array($rol_usuario, (array)$roles_permitidos);
}

/**
 * Redirigir si no está autenticado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getLoginUrl());
        exit;
    }
}

/**
 * Redirigir si no tiene rol
 */
function requireRole($roles) {
    requireLogin();
    if (!checkRole($roles)) {
        http_response_code(403);
        die('Acceso denegado. No tienes permisos para ver esta página.');
    }
}

/**
 * Hacer logout
 */
function logout() {
    session_destroy();
    header('Location: ' . getLoginUrl());
    exit;
}

/**
 * Obtener información del usuario actual
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'nombre' => $_SESSION['admin_nombre'] ?? null,
        'email' => $_SESSION['admin_email'] ?? null,
        'rol' => $_SESSION['admin_rol'] ?? null
    ];
}
?>