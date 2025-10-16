<?php
require_once './../../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID no proporcionado');
    }
    
    $id = $_GET['id'];
    
    // Obtener la ruta de la imagen
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
        
        // Eliminar de la base de datos
        $delete_query = "DELETE FROM invitacion_galeria WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([$id]);
        
        $response['success'] = true;
        $response['message'] = 'Imagen eliminada correctamente';
    } else {
        throw new Exception('Imagen no encontrada');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>