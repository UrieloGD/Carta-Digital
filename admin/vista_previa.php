<?php
// Este archivo simplemente redirige a la invitación real
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("Location: index.php");
    exit();
}

header("Location: ../invitacion.php?slug=" . urlencode($slug));
exit();
?>