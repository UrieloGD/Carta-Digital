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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="./css/index.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-envelope-heart me-2"></i>
                Panel de Administración
            </a>
            <div class="navbar-nav ms-auto">
                <a href="plantillas.php" class="btn btn-outline-light me-2">
                    <i class="bi bi-layout-text-window-reverse me-1"></i>
                    Gestionar Plantillas
                </a>
                <a href="./functions/crear.php" class="btn btn-light">
                    <i class="bi bi-plus-circle me-1"></i>
                    Nueva Invitación
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Alertas -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($invitaciones)): ?>
            <!-- Grid de invitaciones -->
            <div class="row g-4">
                <?php foreach($invitaciones as $invitacion): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card h-100">
                        <!-- Imagen hero si existe -->
                        <?php if (!empty($invitacion['imagen_hero'])): ?>
                        <img src="<?php echo htmlspecialchars('../uploads/' . $invitacion['slug'] . '/' . $invitacion['imagen_hero']); ?>" 
                             alt="Imagen de <?php echo htmlspecialchars($invitacion['nombres_novios']); ?>"
                             class="preview-image"
                             onerror="this.style.display='none';">
                        <?php else: ?>
                        <div class="preview-image bg-light d-flex align-items-center justify-content-center">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <?php echo htmlspecialchars($invitacion['nombres_novios'] ?? 'Sin nombre'); ?>
                            </h5>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
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
                                </small>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-palette me-1"></i>
                                    <?php echo htmlspecialchars($invitacion['plantilla_nombre'] ?? 'Sin plantilla'); ?>
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <code class="small bg-light px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($invitacion['slug'] ?? 'sin-slug'); ?>
                                </code>
                            </div>
                            
                            <!-- Estadísticas de RSVPs -->
                            <div class="d-flex gap-2 mb-3">
                                <span class="badge bg-secondary">
                                    <i class="bi bi-people me-1"></i>
                                    <?php echo $invitacion['total_rsvps'] ?? 0; ?> RSVPs
                                </span>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle me-1"></i>
                                    <?php echo $invitacion['confirmados'] ?? 0; ?> Confirmados
                                </span>
                            </div>
                            
                            <!-- Estado basado en fecha -->
                            <?php 
                            $estado = 'activa';
                            $estado_texto = 'Activa';
                            $estado_class = 'bg-primary';
                            if ($invitacion['fecha_evento']) {
                                $hoy = new DateTime();
                                $fecha_evento = new DateTime($invitacion['fecha_evento']);
                                if ($fecha_evento < $hoy) {
                                    $estado = 'finalizada';
                                    $estado_texto = 'Finalizada';
                                    $estado_class = 'bg-secondary';
                                } elseif ($fecha_evento->diff($hoy)->days <= 7) {
                                    $estado = 'proxima';
                                    $estado_texto = 'Próxima';
                                    $estado_class = 'bg-warning';
                                }
                            }
                            ?>
                            <div class="mb-3">
                                <span class="badge <?php echo $estado_class; ?>">
                                    <?php echo $estado_texto; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <div class="btn-group" role="group">
                                    <a href="./functions/editar.php?id=<?php echo $invitacion['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="rsvps.php?id=<?php echo $invitacion['id']; ?>" 
                                       class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-people"></i>
                                        RSVPs (<?php echo $invitacion['total_rsvps'] ?? 0; ?>)
                                    </a>
                                    <!-- <a href="vista_previa.php?slug=<?php echo htmlspecialchars($invitacion['slug'] ?? ''); ?>" 
                                       class="btn btn-outline-secondary btn-sm" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a> -->
                                    <a href="../invitacion.php?slug=<?php echo htmlspecialchars($invitacion['slug'] ?? ''); ?>" 
                                       class="btn btn-outline-success btn-sm" target="_blank">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                                
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal<?php echo $invitacion['id']; ?>">
                                    <i class="bi bi-trash me-1"></i>
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal de confirmación de eliminación -->
                    <div class="modal fade" id="deleteModal<?php echo $invitacion['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmar eliminación</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>¿Estás seguro de que quieres eliminar la invitación de <strong><?php echo htmlspecialchars($invitacion['nombres_novios']); ?></strong>?</p>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Esta acción eliminará también todos los RSVPs y archivos asociados.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $invitacion['id']; ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-trash me-1"></i>
                                            Eliminar definitivamente
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="empty-state text-center">
                <i class="bi bi-envelope-plus"></i>
                <h3 class="mt-3 mb-2">No hay invitaciones creadas</h3>
                <p class="text-muted mb-4">Comienza creando tu primera invitación digital.</p>
                <a href="./functions/crear.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>
                    Crear Primera Invitación
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>