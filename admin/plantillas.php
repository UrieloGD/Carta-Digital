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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Plantillas</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .preview-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 15px 15px 0 0;
        }
        
        .empty-state {
            padding: 4rem 2rem;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-layout-text-window-reverse me-2"></i>
                Gestor de Plantillas
            </a>
            <div class="navbar-nav ms-auto">
                <a href="plantilla_nueva.php" class="btn btn-light me-2">
                    <i class="bi bi-plus-circle me-1"></i>
                    Nueva Plantilla
                </a>
                <a href="index.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i>
                    Volver al Panel
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

        <?php if (!empty($plantillas)): ?>
            <!-- Grid de plantillas -->
            <div class="row g-4">
                <?php foreach ($plantillas as $plantilla): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card h-100">
                        <!-- Mostrar imagen de preview -->
                        <?php if (!empty($plantilla['imagen_preview'])): ?>
                            <?php 
                            $imagen_preview = ltrim($plantilla['imagen_preview'], './');
                            $ruta_preview = '../plantillas/' . $plantilla['carpeta'] . '/' . $imagen_preview;
                            ?>
                            <img src="<?php echo htmlspecialchars($ruta_preview); ?>" 
                                 alt="Preview de <?php echo htmlspecialchars($plantilla['nombre']); ?>"
                                 class="preview-image"
                                 onerror="this.style.display='none'; this.parentElement.querySelector('.no-image').classList.remove('d-none');">
                            <div class="preview-image bg-light d-flex align-items-center justify-content-center d-none no-image">
                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            </div>
                        <?php else: ?>
                            <div class="preview-image bg-light d-flex align-items-center justify-content-center">
                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <?php echo htmlspecialchars($plantilla['nombre']); ?>
                            </h5>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-folder me-1"></i>
                                    <?php echo htmlspecialchars($plantilla['carpeta'] . '/' . $plantilla['archivo_principal']); ?>
                                </small>
                            </div>
                            
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars($plantilla['descripcion']); ?>
                            </p>
                            
                            <div class="mb-3">
                                <?php if ($plantilla['activa']): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Activa
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-pause-circle me-1"></i>
                                        Inactiva
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <div class="btn-group" role="group">
                                    <a href="plantilla_editar.php?id=<?php echo $plantilla['id']; ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="bi bi-pencil me-1"></i>
                                        Editar
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal<?php echo $plantilla['id']; ?>">
                                        <i class="bi bi-trash me-1"></i>
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal de confirmación de eliminación -->
                    <div class="modal fade" id="deleteModal<?php echo $plantilla['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmar eliminación</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>¿Estás seguro de que quieres eliminar la plantilla <strong><?php echo htmlspecialchars($plantilla['nombre']); ?></strong>?</p>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Esta acción no se puede deshacer.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $plantilla['id']; ?>">
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
                <i class="bi bi-layout-text-window-reverse"></i>
                <h3 class="mt-3 mb-2">No hay plantillas registradas</h3>
                <p class="text-muted mb-4">Comienza creando tu primera plantilla base.</p>
                <a href="plantilla_nueva.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>
                    Crear Primera Plantilla
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