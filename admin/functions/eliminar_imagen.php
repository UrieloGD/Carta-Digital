<?php
require_once './../../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_GET['tipo']) || !isset($_GET['id'])) {
        throw new Exception('Parámetros incompletos');
    }
    
    $tipo = $_GET['tipo'];
    $id = $_GET['id'];
    
    $db->beginTransaction();
    
    switch($tipo) {
        case 'hero':
        case 'dedicatoria':
        case 'destacada':
            // Obtener la ruta actual de la imagen
            $query = "SELECT imagen_{$tipo} as imagen FROM invitaciones WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data && $data['imagen']) {
                // Eliminar el archivo físico
                $ruta_archivo = "../../" . $data['imagen'];
                if (file_exists($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
                
                // Actualizar la base de datos
                $update = "UPDATE invitaciones SET imagen_{$tipo} = NULL WHERE id = ?";
                $stmt = $db->prepare($update);
                $stmt->execute([$id]);
            }
            break;
            
        case 'dresscode_hombres':
        case 'dresscode_mujeres':
            // Obtener la ruta actual de la imagen de dresscode
            $campo = str_replace('dresscode_', '', $tipo);
            $query = "SELECT {$campo} as imagen, invitacion_id FROM invitacion_dresscode WHERE invitacion_id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data && $data['imagen']) {
                // Eliminar el archivo físico
                $ruta_archivo = "../../" . $data['imagen'];
                if (file_exists($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
                
                // Actualizar la base de datos
                $update = "UPDATE invitacion_dresscode SET {$campo} = NULL WHERE invitacion_id = ?";
                $stmt = $db->prepare($update);
                $stmt->execute([$id]);
            }
            break;
            
        case 'ceremonia':
        case 'evento':
            // Obtener la ruta actual de la imagen de ubicación
            $query = "SELECT imagen FROM invitacion_ubicaciones WHERE invitacion_id = ? AND tipo = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id, $tipo]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($data && $data['imagen']) {
                // Eliminar el archivo físico
                $ruta_archivo = "../../" . $data['imagen'];
                if (file_exists($ruta_archivo)) {
                    unlink($ruta_archivo);
                }
                
                // Actualizar la base de datos
                $update = "UPDATE invitacion_ubicaciones SET imagen = NULL WHERE invitacion_id = ? AND tipo = ?";
                $stmt = $db->prepare($update);
                $stmt->execute([$id, $tipo]);
            }
            break;
            
        default:
            throw new Exception('Tipo de imagen no válido');
    }
    
    $db->commit();
    $response['success'] = true;
    $response['message'] = 'Imagen eliminada correctamente';
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);