<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$invitacionEjemploColumnExists = false;
try {
    $check_column_query = "DESCRIBE plantillas invitacion_ejemplo_id";
    $check_column_stmt = $db->query($check_column_query);
    if ($check_column_stmt->fetch()) {
        $invitacionEjemploColumnExists = true;
    }
} catch (PDOException $e) {
    $invitacionEjemploColumnExists = false;
}

// Solo obtenemos las invitaciones si la columna existe
$invitaciones = [];
if ($invitacionEjemploColumnExists) {
    $invitaciones_query = "SELECT id, nombres_novios, slug FROM invitaciones ORDER BY nombres_novios ASC";
    $invitaciones_stmt = $db->prepare($invitaciones_query);
    $invitaciones_stmt->execute();
    $invitaciones = $invitaciones_stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $carpeta = $_POST['carpeta'];
    $archivo_principal = $_POST['archivo_principal'];
    $imagen_preview = $_POST['imagen_preview'];

    if ($invitacionEjemploColumnExists) {
        $invitacion_ejemplo_id = !empty($_POST['invitacion_ejemplo_id']) ? $_POST['invitacion_ejemplo_id'] : null;
        
        $query = "INSERT INTO plantillas (nombre, descripcion, carpeta, archivo_principal, imagen_preview, invitacion_ejemplo_id) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$nombre, $descripcion, $carpeta, $archivo_principal, $imagen_preview, $invitacion_ejemplo_id];
    } else {
        $query = "INSERT INTO plantillas (nombre, descripcion, carpeta, archivo_principal, imagen_preview) 
                  VALUES (?, ?, ?, ?, ?)";
        $params = [$nombre, $descripcion, $carpeta, $archivo_principal, $imagen_preview];
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    header("Location: plantillas.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Plantilla</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/plantilla_nueva.css?v=<?php echo filemtime('./css/plantilla_nueva.css'); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="shortcut icon" href="./../images/logo.webp"/>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-palette me-2"></i>
                Agregar Nueva Plantilla
            </a>
            <div class="navbar-nav ms-auto">
                <a href="plantillas.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-1"></i>
                    Volver
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Formulario Principal (2/3 en desktop) -->
            <div class="col-lg-8">
                <form method="POST" id="plantillaForm" class="needs-validation" novalidate>
                    <!-- Información de la Plantilla -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="bi bi-info-circle me-2"></i>
                            Información de la Plantilla
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">
                                        <i class="bi bi-tag me-1"></i>
                                        Nombre de la Plantilla *
                                    </label>
                                    <input type="text" 
                                        class="form-control" 
                                        id="nombre" 
                                        name="nombre" 
                                        placeholder="Ej: Plantilla Elegante Dorada"
                                        required>
                                    <div class="invalid-feedback">
                                        Por favor ingresa el nombre de la plantilla.
                                    </div>
                                </div>
                            </div>
                            
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
                                        placeholder="Ej: plantilla-1"
                                        required>
                                    <div class="form-text">Nombre de la carpeta donde están los archivos de la plantilla</div>
                                    <div class="invalid-feedback">
                                        Por favor ingresa el nombre de la carpeta.
                                    </div>
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
                                    rows="3"
                                    placeholder="Describe las características de esta plantilla..."></textarea>
                        </div>
                        
                        <div class="row">
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
                                        placeholder="Ej: index.php"
                                        required>
                                    <div class="form-text">Archivo PHP principal de la plantilla</div>
                                    <div class="invalid-feedback">
                                        Por favor ingresa el archivo principal.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="imagen_preview" class="form-label">
                                        <i class="bi bi-image me-1"></i>
                                        Imagen Preview *
                                    </label>
                                    <input type="text" 
                                        class="form-control" 
                                        id="imagen_preview" 
                                        name="imagen_preview" 
                                        placeholder="Ruta de la imagen se generará automáticamente"
                                        readonly>
                                    <div class="form-text">Sube una imagen usando el panel lateral</div>
                                    <div class="invalid-feedback">
                                        Por favor sube una imagen de preview.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Invitación Ejemplo -->
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="invitacion_ejemplo_id" class="form-label">
                                        <i class="bi bi-eye me-1"></i>
                                        Invitación de Ejemplo
                                    </label>
                                    <select class="form-select" 
                                            id="invitacion_ejemplo_id" 
                                            name="invitacion_ejemplo_id">
                                        <option value="">-- Seleccionar invitación ejemplo (opcional) --</option>
                                        <?php foreach ($invitaciones as $invitacion): ?>
                                            <option value="<?= $invitacion['id'] ?>">
                                                <?= htmlspecialchars($invitacion['nombres_novios']) ?> 
                                                (<?= htmlspecialchars($invitacion['slug']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Selecciona una invitación existente que sirva como ejemplo para mostrar esta plantilla.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (empty($invitaciones)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Nota:</strong> No hay invitaciones creadas aún. 
                            <a href="functions/crear.php" class="alert-link">Crea una invitación</a> 
                            primero para poder usarla como ejemplo.
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Botones de acción -->
                    <div class="floating-actions">
                        <a href="plantillas.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle me-1"></i>
                            Guardar Plantilla
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar de Preview de Imagen (1/3 en desktop) -->
            <div class="col-lg-4">
                <div class="preview-sidebar">
                    <div class="form-section sticky-preview">
                        <h3 class="section-title">
                            <i class="bi bi-image me-2"></i>
                            Imagen de Preview
                        </h3>

                        <!-- Upload de Imagen -->
                        <div class="upload-area mb-3">
                            <input type="file" 
                                   id="imagenFile" 
                                   class="d-none" 
                                   accept="image/jpeg,image/jpg,image/png,image/webp">
                            <button type="button" 
                                    id="btnSelectImage" 
                                    class="btn btn-outline-primary w-100">
                                <i class="bi bi-cloud-upload me-2"></i>
                                Seleccionar Imagen
                            </button>
                            <div class="form-text text-center mt-2">
                                Formatos: JPG, PNG, WEBP (máx. 5MB)
                            </div>
                        </div>

                        <!-- Barra de Progreso -->
                        <div id="uploadProgress" class="upload-progress d-none mb-3">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" 
                                     style="width: 0%">0%</div>
                            </div>
                            <small class="text-muted mt-1 d-block text-center">Subiendo imagen...</small>
                        </div>

                        <!-- Preview de Imagen -->
                        <div id="imagePreviewContainer" class="image-preview-container d-none">
                            <img id="imagePreview" src="" alt="Preview" class="img-fluid rounded">
                            <button type="button" 
                                    id="btnRemoveImage" 
                                    class="btn btn-danger btn-sm mt-2 w-100">
                                <i class="bi bi-trash me-1"></i>
                                Eliminar Imagen
                            </button>
                        </div>

                        <!-- Placeholder cuando no hay imagen -->
                        <div id="imagePlaceholder" class="image-placeholder">
                            <i class="bi bi-image"></i>
                            <p>No hay imagen seleccionada</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./js/plantilla_nueva.js?v=<?php echo time(); ?>"></script>
</body>
</html>
