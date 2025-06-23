<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$imagen_id = $input['imagen_id'] ?? 0;
$invitacion_id = $input['invitacion_id'] ?? 0;

if (!$imagen_id || !$invitacion_id) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener la ruta de la imagen antes de eliminarla
    $query = "SELECT ruta FROM invitacion_galeria WHERE id = ? AND invitacion_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$imagen_id, $invitacion_id]);
    $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$imagen) {
        echo json_encode(['success' => false, 'message' => 'Imagen no encontrada']);
        exit;
    }
    
    // Eliminar el registro de la base de datos
    $delete_query = "DELETE FROM invitacion_galeria WHERE id = ? AND invitacion_id = ?";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->execute([$imagen_id, $invitacion_id]);
    
    // Eliminar el archivo físico
    $file_path = "../" . $imagen['ruta'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    echo json_encode(['success' => true, 'message' => 'Imagen eliminada correctamente']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la imagen: ' . $e->getMessage()]);
}
?>