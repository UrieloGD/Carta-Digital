<?php
/**
 * API para Galería
 * Gestión de imágenes subidas a la plantilla
 */

require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $invitacion_id = $_POST['invitacion_id'] ?? 0;
            
            if (!isset($_FILES['imagen'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No se envió imagen']);
                exit;
            }

            $file = $_FILES['imagen'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nombre_archivo = uniqid() . '.' . $ext;
            $ruta_destino = '../plantillas/plantilla-8.1/img/galeria/' . $nombre_archivo;

            if (move_uploaded_file($file['tmp_name'], $ruta_destino)) {
                // Registrar en BD
                $query = "INSERT INTO invitacion_galeria (invitacion_id, ruta, orden, activa) 
                         VALUES (?, ?, 
                                (SELECT COALESCE(MAX(orden), 0) + 1 FROM invitacion_galeria WHERE invitacion_id = ?),
                                1)";
                $stmt = $db->prepare($query);
                $stmt->execute([$invitacion_id, $ruta_destino, $invitacion_id]);

                echo json_encode([
                    'success' => true,
                    'ruta' => $ruta_destino,
                    'id' => $db->lastInsertId()
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al subir imagen']);
            }
        }
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $imagen_id = $_POST['imagen_id'] ?? 0;
            
            // Obtener ruta
            $query = "SELECT ruta FROM invitacion_galeria WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$imagen_id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                // Eliminar archivo
                if (file_exists($resultado['ruta'])) {
                    unlink($resultado['ruta']);
                }

                // Eliminar de BD
                $query = "DELETE FROM invitacion_galeria WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute([$imagen_id]);

                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Imagen no encontrada']);
            }
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Acción no válida']);
        break;
}
?>
