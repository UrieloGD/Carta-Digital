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

// Obtener invitación con toda la información
$stmt = $conn->prepare("
    SELECT 
        i.id,
        i.tipo_rsvp,
        i.plan_id,
        ped.plan as pedido_plan
    FROM invitaciones i 
    LEFT JOIN pedidos ped ON i.id = ped.invitacion_id
    WHERE i.cliente_id = ?
    ORDER BY i.fecha_creacion DESC
    LIMIT 1
");
$stmt->execute([$_SESSION['cliente_id']]);
$resultado = $stmt->fetch();

if (!$resultado) {
    die("Error: No se encontraron invitaciones.");
}

$tipo_rsvp = $resultado['tipo_rsvp'];
$plan_id = $resultado['plan_id'];
$pedido_plan = $resultado['pedido_plan'];

// ✅ Redirigir a dashboard RSVP si:
// 1. tipo_rsvp es 'digital' O
// 2. plan_id es 3 (Exclusivo) O
// 3. pedido_plan es 'Exclusivo'
if ($tipo_rsvp === 'digital' || $plan_id == 3 || $pedido_plan === 'Exclusivo') {
    header('Location: dashboard_rsvp.php');
} else {
    header('Location: invitacion_cliente.php');
}
exit;
?>
