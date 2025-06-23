<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Procesar acciones (eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if ($action === 'eliminar') {
        $delete_query = "DELETE FROM plantillas WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        if ($delete_stmt->execute([$id])) {
            $success_message = "Plantilla eliminada correctamente.";
        } else {
            $error_message = "Error al eliminar la plantilla.";
        }
    }
}

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
            <div class="header-actions">
                <a href="plantilla_nueva.php" class="btn btn-primary">+ Nueva Plantilla</a>
                <a href="index.php" class="btn btn-secondary">← Volver</a>
            </div>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="success-alert"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-alert"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="invitaciones-grid">
            <?php foreach ($plantillas as $plantilla): ?>
            <div class="invitacion-card">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($plantilla['nombre']); ?></h3>
                </div>
                <div class="card-body">
                    <p><strong>Archivo:</strong> <?php echo htmlspecialchars($plantilla['carpeta'] . '/' . $plantilla['archivo_php']); ?></p>
                    <p><strong>Descripción:</strong> <?php echo htmlspecialchars($plantilla['descripcion']); ?></p>
                    
                    <div class="card-actions">
                        <a href="plantilla_editar.php?id=<?php echo $plantilla['id']; ?>" class="btn btn-edit btn-m">Editar</a>
                        
                        <form method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta plantilla?')">
                            <input type="hidden" name="action" value="eliminar">
                            <input type="hidden" name="id" value="<?php echo $plantilla['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-m">Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($plantillas)): ?>
            <div class="rsvps-table">
                <div style="text-align: center; padding: 40px;">
                    <h3>No hay plantillas registradas</h3>
                    <p>Comienza creando tu primera plantilla base.</p>
                    <a href="plantilla_nueva.php" class="btn btn-primary">+ Crear Primera Plantilla</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-ocultar mensajes de éxito después de 3 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.success-alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 3000);
    </script>
</body>
</html>