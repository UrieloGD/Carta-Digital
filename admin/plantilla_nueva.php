<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $carpeta = $_POST['carpeta'];
    $archivo_principal = $_POST['archivo_principal'];
    $imagen_preview = $_POST['imagen_preview'];

    $query = "INSERT INTO plantillas (nombre, descripcion, carpeta, archivo_principal, imagen_preview) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$nombre, $descripcion, $carpeta, $archivo_principal, $imagen_preview]);

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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/plantilla_nueva.css">
    <!-- Icon page -->
    <link rel="shortcut icon" href="./images/logo.webp" />
</head>
<body>
    <!-- Navbar -->
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

    <div class="container py-4">
        <form method="POST" class="needs-validation" novalidate>
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
                                Imagen Preview
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="imagen_preview" 
                                   name="imagen_preview" 
                                   placeholder="Ej: img/preview.png">
                            <div class="form-text">Ruta relativa a la imagen de vista previa (opcional)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="form-section">
                <div class="d-flex gap-2 justify-content-end">
                    <a href="plantillas.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-x-circle me-1"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-1"></i>
                        Guardar Plantilla
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

        // Auto-generar nombre de carpeta basado en el nombre
        document.getElementById('nombre').addEventListener('input', function(e) {
            const nombre = e.target.value;
            const carpeta = 'plantilla-' + nombre.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
            document.getElementById('carpeta').value = carpeta;
        });
    </script>
</body>
</html>