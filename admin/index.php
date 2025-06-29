<?php
require_once './../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar') {
    $id = $_POST['id'] ?? 0;
    
    if ($id > 0) {
        try {
            // Iniciar transacción
            $db->beginTransaction();
            
            // Obtener información de la invitación para eliminar archivos
            $query = "SELECT slug FROM invitaciones WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $invitacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invitacion) {
                // Eliminar registros relacionados
                $db->prepare("DELETE FROM invitacion_cronograma WHERE invitacion_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM invitacion_faq WHERE invitacion_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM invitacion_galeria WHERE invitacion_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM invitacion_dresscode WHERE invitacion_id = ?")->execute([$id]);
                
                // Eliminar la invitación principal
                $db->prepare("DELETE FROM invitaciones WHERE id = ?")->execute([$id]);
                
                // Eliminar carpeta de archivos subidos (opcional)
                $upload_dir = "../uploads/" . $invitacion['slug'];
                if (is_dir($upload_dir)) {
                    function eliminarDirectorio($dir) {
                        if (is_dir($dir)) {
                            $files = array_diff(scandir($dir), array('.', '..'));
                            foreach ($files as $file) {
                                $path = $dir . '/' . $file;
                                is_dir($path) ? eliminarDirectorio($path) : unlink($path);
                            }
                            rmdir($dir);
                        }
                    }
                    eliminarDirectorio($upload_dir);
                }
                
                $db->commit();
                $success_message = "Invitación eliminada correctamente.";
            } else {
                $db->rollback();
                $error_message = "Invitación no encontrada.";
            }
        } catch (Exception $e) {
            $db->rollback();
            $error_message = "Error al eliminar la invitación: " . $e->getMessage();
        }
    }
}

// Obtener todas las invitaciones con información adicional
$query = "SELECT i.*, p.nombre as plantilla_nombre, 
          (SELECT COUNT(*) FROM rsvps r WHERE r.invitacion_id = i.id) as total_rsvps,
          (SELECT COUNT(*) FROM rsvps r WHERE r.invitacion_id = i.id AND r.asistencia = 'si') as confirmados
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
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Panel de Administración</h1>
            <div class="header-actions">
                <a href="plantillas.php" class="btn btn-secondary">Gestionar Plantillas</a>
                <a href="./functions/crear.php" class="btn btn-primary">Nueva Invitación</a>
            </div>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="success-alert"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-alert"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="invitaciones-grid">
            <?php foreach($invitaciones as $invitacion): ?>
            <div class="invitacion-card">
                <!-- Imagen hero si existe -->
                <?php if (!empty($invitacion['imagen_hero'])): ?>
                <div class="card-preview">
                    <img src="<?php echo htmlspecialchars('../uploads/' . $invitacion['slug'] . '/' . $invitacion['imagen_hero']); ?>" 
                         alt="Imagen de <?php echo htmlspecialchars($invitacion['nombres_novios']); ?>"
                         class="preview-image"
                         onerror="this.style.display='none'; this.parentElement.style.display='none';">
                </div>
                <?php endif; ?>
                
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($invitacion['nombres_novios'] ?? 'Sin nombre'); ?></h3>
                </div>
                
                <div class="card-body">
                    <div class="info-row">
                        <p><strong>Fecha:</strong> 
                        <?php 
                        if ($invitacion['fecha_evento']) {
                            $fecha = new DateTime($invitacion['fecha_evento']);
                            echo $fecha->format('d/m/Y');
                            if ($invitacion['hora_evento']) {
                                echo ' - ' . date('H:i', strtotime($invitacion['hora_evento']));
                            }
                        } else {
                            echo 'No definida';
                        }
                        ?>
                        </p>
                    </div>
                    
                    <div class="info-row">
                        <p><strong>Plantilla:</strong> <?php echo htmlspecialchars($invitacion['plantilla_nombre'] ?? 'Sin plantilla'); ?></p>
                    </div>
                    
                    <div class="info-row">
                        <p><strong>Slug:</strong> <code><?php echo htmlspecialchars($invitacion['slug'] ?? 'sin-slug'); ?></code></p>
                    </div>
                    
                    <!-- Estadísticas de RSVPs -->
                    <div class="rsvp-stats">
                        <span class="stat-item">
                            <strong><?php echo $invitacion['total_rsvps'] ?? 0; ?></strong> RSVPs
                        </span>
                        <span class="stat-item confirmados">
                            <strong><?php echo $invitacion['confirmados'] ?? 0; ?></strong> Confirmados
                        </span>
                    </div>
                    
                    <!-- Estado basado en fecha -->
                    <?php 
                    $estado = 'activa';
                    $estado_texto = 'Activa';
                    if ($invitacion['fecha_evento']) {
                        $hoy = new DateTime();
                        $fecha_evento = new DateTime($invitacion['fecha_evento']);
                        if ($fecha_evento < $hoy) {
                            $estado = 'finalizada';
                            $estado_texto = 'Finalizada';
                        } elseif ($fecha_evento->diff($hoy)->days <= 7) {
                            $estado = 'proxima';
                            $estado_texto = 'Próxima';
                        }
                    }
                    ?>
                    <div class="estado-badge <?php echo $estado; ?>">
                        <?php echo $estado_texto; ?>
                    </div>
                </div>
                
                <div class="card-actions">
                    <a href="./functions/editar.php?id=<?php echo $invitacion['id']; ?>" class="btn btn-edit">Editar</a>
                    <a href="rsvps.php?id=<?php echo $invitacion['id']; ?>" class="btn btn-info">RSVPs (<?php echo $invitacion['total_rsvps'] ?? 0; ?>)</a>
                    <a href="vista_previa.php?slug=<?php echo htmlspecialchars($invitacion['slug'] ?? ''); ?>" class="btn btn-view" target="_blank">Ver</a>
                    <a href="../invitacion.php?slug=<?php echo htmlspecialchars($invitacion['slug'] ?? ''); ?>" class="btn btn-preview" target="_blank">Preview</a>
                    
                    <form method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta invitación? Esta acción eliminará también todos los RSVPs y archivos asociados.')">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id" value="<?php echo $invitacion['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-m">Eliminar</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($invitaciones)): ?>
            <div class="rsvps-table">
                <div style="text-align: center; padding: 40px;">
                    <h3>No hay invitaciones creadas</h3>
                    <p>Comienza creando tu primera invitación.</p>
                    <a href="./functions/crear.php" class="btn btn-primary">+ Crear Primera Invitación</a>
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