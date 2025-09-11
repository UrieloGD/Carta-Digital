<?php
// includes/auth_check.php
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) {
    header('Location: ./login.php');
    exit;
}

// Verificar timeout de sesión (30 minutos)
$timeout_duration = 1800; // 30 minutos en segundos

if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout_duration) {
    // Limpiar sesión
    session_unset();
    session_destroy();
    
    header('Location: ./login.php?timeout=1');
    exit;
}

// Actualizar tiempo de actividad
$_SESSION['login_time'] = time();
?>