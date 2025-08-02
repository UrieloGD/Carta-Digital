<?php
require_once './../config/database.php';

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? 0;

// Obtener datos de la plantilla
$query = "SELECT * FROM plantillas WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id]);
$plantilla = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plantilla) {
    header("Location: plantillas.php");
    exit("Plantilla no encontrada");
}

// Función para limpiar rutas de imagen
function limpiarRutaImagen($ruta) {
    if (empty($ruta)) return '';
    return ltrim($ruta, './');
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $carpeta = trim($_POST['carpeta']);
    $archivo_principal = trim($_POST['archivo_principal']);
    $imagen_preview = trim($_POST['imagen_preview']);
    $activa = isset($_POST['activa']) ? 1 : 0;
    
    $imagen_preview = limpiarRutaImagen($imagen_preview);
    
    if (empty($nombre) || empty($carpeta) || empty($archivo_principal)) {
        $error = "Los campos Nombre, Carpeta y Archivo PHP son obligatorios.";
    } else {
        $update_query = "UPDATE plantillas SET nombre = ?, descripcion = ?, carpeta = ?, archivo_principal = ?, imagen_preview = ?, activa = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$nombre, $descripcion, $carpeta, $archivo_principal, $imagen_preview, $activa, $id])) {
            $success = "Plantilla actualizada correctamente.";
            $stmt->execute([$id]);
            $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Error al actualizar la plantilla.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Plantilla - <?php echo htmlspecialchars($plantilla['nombre']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/plantilla_editar.css">
    <!-- Icon page -->
    <link rel="shortcut icon" href="./images/logo.webp" />
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-pencil-square me-2"></i>
                Editar Plantilla
            </a>
            <div class="navbar-nav ms-auto">
                <a href="plantillas.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i>
                    Volver
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Mensajes de estado -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <!-- Información de la Plantilla -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-info-circle me-2"></i>
                    Información de la Plantilla
                </h3>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">
                                <i class="bi bi-tag me-1"></i>
                                Nombre de la Plantilla *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="<?php echo htmlspecialchars($plantilla['nombre']); ?>" 
                                   required>
                            <div class="invalid-feedback">
                                Por favor ingresa el nombre de la plantilla.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-activity me-1"></i>
                                Estado
                            </label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="activa" 
                                       name="activa" 
                                       value="1" 
                                       <?php echo $plantilla['activa'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="activa">
                                    <span class="status-badge">
                                        <span class="badge <?php echo $plantilla['activa'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <i class="bi bi-<?php echo $plantilla['activa'] ? 'check-circle' : 'pause-circle'; ?> me-1"></i>
                                            <?php echo $plantilla['activa'] ? 'Activa' : 'Inactiva'; ?>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <div class="form-text">Las plantillas inactivas no se mostrarán en la selección</div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">
                        <i class="bi bi-card-text me-1"></i>
                        Descripción
                    </label>
                    <textarea class="form-control" 
                              id="descripcion" 
                              name="descripcion" 
                              rows="3"><?php echo htmlspecialchars($plantilla['descripcion']); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="carpeta" class="form-label">
                                <i class="bi bi-folder me-1"></i>
                                Carpeta *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="carpeta" 
                                   name="carpeta" 
                                   value="<?php echo htmlspecialchars($plantilla['carpeta']); ?>" 
                                   required>
                            <div class="form-text">Ejemplo: plantilla-1</div>
                            <div class="invalid-feedback">
                                Por favor ingresa el nombre de la carpeta.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="archivo_principal" class="form-label">
                                <i class="bi bi-file-earmark-code me-1"></i>
                                Archivo Principal *
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="archivo_principal" 
                                   name="archivo_principal" 
                                   value="<?php echo htmlspecialchars($plantilla['archivo_principal']); ?>" 
                                   required>
                            <div class="form-text">Ejemplo: index.php</div>
                            <div class="invalid-feedback">
                                Por favor ingresa el archivo principal.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="imagen_preview" class="form-label">
                        <i class="bi bi-image me-1"></i>
                        Imagen Preview
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="imagen_preview" 
                           name="imagen_preview" 
                           value="<?php echo htmlspecialchars($plantilla['imagen_preview']); ?>">
                    <div class="form-text">Ejemplo: img/preview.png (relativo a la carpeta de la plantilla)</div>
                </div>
            </div>

            <!-- Preview de la imagen actual -->
            <?php if (!empty($plantilla['imagen_preview'])): ?>
                <?php 
                $imagen_preview = ltrim($plantilla['imagen_preview'], './');
                $ruta_preview = '../plantillas/' . $plantilla['carpeta'] . '/' . $imagen_preview;
                ?>
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-eye me-2"></i>
                        Vista Previa Actual
                    </h3>
                    <div class="preview-card">
                        <img src="<?php echo htmlspecialchars($ruta_preview); ?>" 
                             alt="Preview de <?php echo htmlspecialchars($plantilla['nombre']); ?>"
                             class="preview-image"
                             onerror="this.style.display='none'; this.closest('.form-section').style.display='none';">
                        <p class="text-muted mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Preview actual de la plantilla
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Botones de acción -->
            <div class="form-section">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="plantillas.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-1"></i>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validación de formulario Bootstrap
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Auto-ocultar mensajes de éxito después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Actualizar preview del estado en tiempo real
        document.getElementById('activa').addEventListener('change', function() {
            const badge = this.closest('.form-check').querySelector('.badge');
            const icon = badge.querySelector('i');
            const text = badge.querySelector('i').nextSibling;
            
            if (this.checked) {
                badge.className = 'badge bg-success';
                icon.className = 'bi bi-check-circle me-1';
                badge.innerHTML = '<i class="bi bi-check-circle me-1"></i>Activa';
            } else {
                badge.className = 'badge bg-secondary';
                icon.className = 'bi bi-pause-circle me-1';
                badge.innerHTML = '<i class="bi bi-pause-circle me-1"></i>Inactiva';
            }
        });
    </script>
</body>
</html>