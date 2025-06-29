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
    <title>Nueva Plantilla</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Agregar Nueva Plantilla</h1>
            <a href="plantillas.php" class="btn btn-secondary">← Volver</a>
        </header>

        <form method="POST" class="formulario">
            <div class="form-group">
                <label>Nombre de la Plantilla:</label>
                <input type="text" name="nombre" required>
            </div>
            <div class="form-group">
                <label>Descripción:</label>
                <textarea name="descripcion" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Carpeta (ej. plantilla-1):</label>
                <input type="text" name="carpeta" required>
            </div>
            <div class="form-group">
                <label>Archivo Principal (ej. invitacion-1.php):</label>
                <input type="text" name="archivo_principal" required>
            </div>
            <div class="form-group">
                <label>Ruta de imagen preview (opcional):</label>
                <input type="text" name="imagen_preview">
            </div>
            <button type="submit" class="btn btn-primary">Guardar Plantilla</button>
        </form>
    </div>
</body>
</html>
