<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Obtener todas las invitaciones
$query = "SELECT i.*, p.nombre as plantilla_nombre 
          FROM invitaciones i 
          LEFT JOIN plantillas p ON i.plantilla_id = p.id 
          ORDER BY i.fecha_creacion DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$invitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Invitaciones</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Panel de Administración</h1>
            <a href="plantillas.php" class="btn btn-secondary">Gestionar Plantillas</a>
            <a href="crear.php" class="btn btn-primary">Nueva Invitación</a>
        </header>

        <div class="invitaciones-grid">
            <?php foreach($invitaciones as $invitacion): ?>
            <div class="invitacion-card">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($invitacion['nombres_novios'] ?? ''); ?></h3>
                    <span class="fecha"><?php echo $invitacion['fecha_evento'] ? date('d/m/Y', strtotime($invitacion['fecha_evento'])) : ''; ?></span>
                </div>
                <div class="card-body">
                    <p><strong>Ubicación:</strong> <?php echo htmlspecialchars($invitacion['ubicacion'] ?? ''); ?></p>
                    <p><strong>Plantilla:</strong> <?php echo htmlspecialchars($invitacion['plantilla_nombre'] ?? 'Sin plantilla'); ?></p>
                </div>
                <div class="card-actions">
                    <a href="editar.php?id=<?php echo $invitacion['id']; ?>" class="btn btn-edit">Editar</a>
                    <a href="rsvps.php?id=<?php echo $invitacion['id']; ?>" class="btn btn-info">RSVPs</a>
                    <a href="vista_previa.php?slug=<?php echo htmlspecialchars($invitacion['slug'] ?? ''); ?>" class="btn btn-view" target="_blank">Ver</a>
                    <a href="../invitacion.php?slug=<?php echo htmlspecialchars($invitacion['slug'] ?? ''); ?>" class="btn btn-preview" target="_blank">Preview</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>