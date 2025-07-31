<?php
// admin/functions/eliminar_galeria.php
require_once './../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit();
}

try {
    // Obtener la ruta de la imagen antes de eliminarla
    $query = "SELECT ruta FROM invitacion_galeria WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($imagen) {
        // Eliminar archivo físico
        $ruta_archivo = "../../" . $imagen['ruta'];
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
        
        // Eliminar registro de la base de datos
        $delete_query = "DELETE FROM invitacion_galeria WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>