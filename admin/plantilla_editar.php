<?php
require_once '../config/database.php';

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

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $carpeta = trim($_POST['carpeta']);
    $archivo_php = trim($_POST['archivo_php']);
    
    // Validaciones básicas
    if (empty($nombre) || empty($carpeta) || empty($archivo_php)) {
        $error = "Los campos Nombre, Carpeta y Archivo PHP son obligatorios.";
    } else {
        $update_query = "UPDATE plantillas SET nombre = ?, descripcion = ?, carpeta = ?, archivo_php = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if ($update_stmt->execute([$nombre, $descripcion, $carpeta, $archivo_php, $id])) {
            header("Location: plantillas.php");
            exit;
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
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Editar Plantilla</h1>
            <a href="plantillas.php" class="btn btn-secondary">← Volver</a>
        </header>

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
                        <small class="form-note">Ejemplo: plantillas/plantilla-1</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="archivo_php">Archivo PHP *</label>
                        <input type="text" id="archivo_php" name="archivo_php" value="<?php echo htmlspecialchars($plantilla['archivo_php']); ?>" required>
                        <small class="form-note">Ejemplo: index.php</small>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="plantillas.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>