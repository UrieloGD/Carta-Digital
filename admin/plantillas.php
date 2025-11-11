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
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="./css/plantillas.css?v=<?php echo filemtime('./css/plantillas.css'); ?>">
    <!-- Icon page -->
    <link rel="shortcut icon" href="./../images/logo.webp" />
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-layout-text-window-reverse me-2"></i>
                <span class="d-none d-sm-inline">Gestor de Plantillas</span>
                <span class="d-sm-none">Plantillas</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <div class="d-lg-flex flex-lg-row flex-column gap-2 mt-2 mt-lg-0">
                        <a href="plantilla_nueva.php" class="btn btn-light btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>
                            <span class="d-none d-md-inline">Nueva </span>Plantilla
                        </a>
                        <a href="./functions/crear.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-envelope-plus me-1"></i>
                            <span class="d-none d-md-inline">Nueva </span>Invitación
                        </a>
                        <a href="./index.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>
                            <span class="d-none d-md-inline">Volver al </span>Panel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Panel de Control y Filtros -->
    <div class="container-fluid py-3 bg-white border-bottom">
        <div class="row align-items-center">
            <!-- Búsqueda -->
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" 
                        id="searchInput" placeholder="Buscar plantillas...">
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="col-lg-6 col-md-6 mb-3 mb-lg-0">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <select class="form-select form-select-sm" id="estadoFilter">
                            <option value="">Todos los estados</option>
                            <option value="activa">Activas</option>
                            <option value="inactiva">Inactivas</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <select class="form-select form-select-sm" id="ordenFilter">
                            <option value="">Orden por defecto</option>
                            <option value="nombre_asc">Nombre (A-Z)</option>
                            <option value="nombre_desc">Nombre (Z-A)</option>
                            <option value="reciente">Más recientes</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="col-lg-2 col-12">
                <div class="stats-wrapper">
                    <div class="stats-item">
                        <i class="bi bi-check-circle text-success"></i>
                        <span id="activasCount">0</span> activas
                    </div>
                    <div class="stats-item">
                        <i class="bi bi-collection text-primary"></i>
                        <span id="totalCount"><?php echo count($plantillas); ?></span> total
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    <div class="card h-100 plantilla-card">
                        <!-- Imagen de preview más compacta -->
                        <?php if (!empty($plantilla['imagen_preview'])): ?>
                            <?php 
                            $imagen_preview = ltrim($plantilla['imagen_preview'], './');
                            $ruta_preview = '../plantillas/' . $plantilla['carpeta'] . '/' . $imagen_preview;
                            ?>
                            <div class="preview-container">
                                <img src="<?php echo htmlspecialchars($ruta_preview); ?>" 
                                    alt="Preview de <?php echo htmlspecialchars($plantilla['nombre']); ?>"
                                    class="preview-image-compact"
                                    onerror="this.style.display='none'; this.parentElement.querySelector('.no-image').classList.remove('d-none');">
                                <div class="preview-placeholder d-none no-image">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="preview-placeholder">
                                <i class="bi bi-image text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body p-3">
                            <h5 class="card-title mb-2 fs-6">
                                <?php echo htmlspecialchars($plantilla['nombre']); ?>
                            </h5>
                            
                            <div class="mb-2">
                                <small class="text-muted d-block text-truncate">
                                    <i class="bi bi-folder me-1"></i>
                                    <?php echo htmlspecialchars($plantilla['carpeta']); ?>
                                </small>
                            </div>
                            
                            <?php if (!empty($plantilla['descripcion'])): ?>
                            <p class="card-text text-muted small mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($plantilla['descripcion']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="mb-2">
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
                        
                        <div class="card-footer bg-transparent p-2">
                            <div class="btn-group w-100" role="group">
                                <a href="plantilla_editar.php?id=<?php echo $plantilla['id']; ?>" 
                                class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" 
                                    class="btn btn-outline-danger btn-sm btn-delete-plantilla"
                                    data-plantilla-id="<?php echo $plantilla['id']; ?>"
                                    data-plantilla-nombre="<?php echo htmlspecialchars($plantilla['nombre']); ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
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
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script src="./js/plantillas.js?v=<?php echo filemtime('./js/plantillas.js'); ?>"></script>
</body>
</html>