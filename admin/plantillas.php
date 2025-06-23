<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM plantillas ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor de Plantillas</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Gestor de Plantillas Base</h1>
            <a href="plantilla_nueva.php" class="btn btn-primary">+ Nueva Plantilla</a>
            <a href="index.php" class="btn btn-secondary">← Volver</a>
        </header>

        <div class="invitaciones-grid">
            <?php foreach ($plantillas as $plantilla): ?>
            <div class="invitacion-card">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($plantilla['nombre']); ?></h3>
                </div>
                <div class="card-body">
                    <p><strong>Archivo:</strong> <?php echo $plantilla['carpeta'] . '/' . $plantilla['archivo_php']; ?></p>
                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($plantilla['descripcion']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
