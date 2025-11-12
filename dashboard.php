<?php
session_start();
require_once 'config/database.php';

// Verificar si hay sesión activa
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Obtener la primera invitación y su plan
$stmt = $conn->prepare("
    SELECT 
        i.id,
        ped.plan
    FROM invitaciones i 
    INNER JOIN pedidos ped ON i.id = ped.invitacion_id
    WHERE i.cliente_id = ?
    ORDER BY i.fecha_creacion DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['cliente_id']]);
$resultado = $stmt->fetch();

if (!$resultado) {
    die("Error: No se encontraron invitaciones.");
}

$plan = $resultado['plan'];

// Redirigir según el plan
if ($plan === 'exclusivo') {
    header('Location: dashboard_rsvp.php');
} else {
    // escencial o premium
    header('Location: invitacion_cliente.php');
}
exit;
?>