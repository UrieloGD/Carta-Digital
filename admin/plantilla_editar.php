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
    // Quitar "./" del inicio si existe
    return ltrim($ruta, './');
}

// Función para construir ruta completa de preview
function construirRutaPreview($carpeta, $imagen_preview) {
    if (empty($imagen_preview)) return '';
    $imagen_limpia = limpiarRutaImagen($imagen_preview);
    return '../plantillas/' . $carpeta . '/' . $imagen_limpia;
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $carpeta = trim($_POST['carpeta']);
    $archivo_principal = trim($_POST['archivo_principal']);
    $imagen_preview = trim($_POST['imagen_preview']);
    $activa = isset($_POST['activa']) ? 1 : 0;
    
    // Limpiar la ruta de imagen preview
    $imagen_preview = limpiarRutaImagen($imagen_preview);
    
    // Validaciones básicas
    if (empty($nombre) || empty($carpeta) || empty($archivo_principal)) {
        $error = "Los campos Nombre, Carpeta y Archivo PHP son obligatorios.";
    } else {
        $update_query = "UPDATE plantillas SET nombre = ?, descripcion = ?, carpeta = ?, archivo_principal = ?, imagen_preview = ?, activa = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$nombre, $descripcion, $carpeta, $archivo_principal, $imagen_preview, $activa, $id])) {
            $success = "Plantilla actualizada correctamente.";
            // Refrescar los datos de la plantilla
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
    <title>Editar Plantilla</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/plantilla_editar.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Editar Plantilla</h1>
            <a href="plantillas.php" class="btn btn-secondary">← Volver</a>
        </header>

        <?php if (isset($success)): ?>
            <div class="success-alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="admin-form">
            <div class="form-section">
                <h3>Información de la Plantilla</h3>
                
                <div class="form-group">
                    <label for="nombre">Nombre de la Plantilla *</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($plantilla['nombre']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($plantilla['descripcion']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="carpeta">Carpeta *</label>
                        <input type="text" id="carpeta" name="carpeta" value="<?php echo htmlspecialchars($plantilla['carpeta']); ?>" required>
                        <small class="form-note">Ejemplo: plantilla-1</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="archivo_principal">Archivo Principal *</label>
                        <input type="text" id="archivo_principal" name="archivo_principal" value="<?php echo htmlspecialchars($plantilla['archivo_principal']); ?>" required>
                        <small class="form-note">Ejemplo: index.php</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="imagen_preview">Imagen Preview</label>
                    <input type="text" id="imagen_preview" name="imagen_preview" value="<?php echo htmlspecialchars($plantilla['imagen_preview']); ?>">
                    <small class="form-note">Ejemplo: img/preview.png (relativo a la carpeta de la plantilla)</small>
                </div>

                <!-- Preview de la imagen actual -->
                <?php if (!empty($plantilla['imagen_preview'])): ?>
                    <?php 
                    // Construir la ruta completa: ../plantillas/carpeta/imagen_preview (desde admin hacia raíz)
                    $imagen_preview = ltrim($plantilla['imagen_preview'], './');
                    $ruta_preview = '../plantillas/' . $plantilla['carpeta'] . '/' . $imagen_preview;
                    ?>
                <div class="card-preview">
                    <img src="<?php echo htmlspecialchars($ruta_preview); ?>" 
                        alt="Preview de <?php echo htmlspecialchars($plantilla['nombre']); ?>"
                        class="preview-image"
                        onerror="this.style.display='none'; this.parentElement.style.display='none';">
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="activa" value="1" <?php echo $plantilla['activa'] ? 'checked' : ''; ?>>
                        Plantilla activa
                    </label>
                    <small class="form-note">Las plantillas inactivas no se mostrarán en la selección</small>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="plantillas.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
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

        // Actualizar preview en tiempo real
        document.getElementById('imagen_preview').addEventListener('input', function() {
            const carpeta = document.getElementById('carpeta').value;
            const imagen = this.value;
            
            if (imagen && carpeta) {
                const rutaCompleta = './plantillas/' + carpeta + '/' + imagen.replace(/^\.\//, '');
                // Aquí podrías agregar lógica para mostrar un preview en tiempo real
                console.log('Nueva ruta preview:', rutaCompleta);
            }
        });
    </script>
</body>
</html>