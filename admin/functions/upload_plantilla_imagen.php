<?php
require_once './../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Validar que venga al menos la imagen
if (!isset($_FILES['imagen'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se proporcionó ninguna imagen']);
    exit;
}

// El plantillaid es OPCIONAL (para nuevas plantillas)
$plantillaId = isset($_POST['plantillaid']) ? intval($_POST['plantillaid']) : null;
$archivo = $_FILES['imagen'];

// Validaciones
$extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$tipoMimePermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
$tipoMime = mime_content_type($archivo['tmp_name']);

if (!in_array($extension, $extensionesPermitidas)) {
    http_response_code(400);
    echo json_encode(['error' => 'Extensión de archivo no permitida. Solo: ' . implode(', ', $extensionesPermitidas)]);
    exit;
}

if (!in_array($tipoMime, $tipoMimePermitidos)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de archivo no válido']);
    exit;
}

if ($archivo['size'] > 5 * 1024 * 1024) { // 5MB máximo
    http_response_code(400);
    echo json_encode(['error' => 'El archivo es demasiado grande (máximo 5MB)']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Si hay plantillaId, obtener la carpeta existente
    if ($plantillaId) {
        $query = "SELECT carpeta FROM plantillas WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$plantillaId]);
        $plantilla = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$plantilla) {
            http_response_code(404);
            echo json_encode(['error' => 'Plantilla no encontrada']);
            exit;
        }
        
        $carpetaPlantilla = __DIR__ . '/../../plantillas/' . $plantilla['carpeta'];
        $nombreArchivo = 'plantilla-' . $plantillaId . '.' . $extension;
    } else {
        // Nueva plantilla: guardar en carpeta temporal
        $carpetaPlantilla = __DIR__ . '/../../plantillas/temp-uploads';
        $nombreArchivo = 'temp-' . uniqid() . '.' . $extension;
    }
    
    // Crear carpeta img si no existe
    $carpetaImg = $carpetaPlantilla . '/img';
    
    if (!is_dir($carpetaPlantilla)) {
        if (!mkdir($carpetaPlantilla, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo crear la carpeta de plantilla']);
            exit;
        }
    }
    
    if (!is_dir($carpetaImg)) {
        if (!mkdir($carpetaImg, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'No se pudo crear la carpeta img']);
            exit;
        }
    }
    
    // Eliminar imagen anterior si existe y hay ID
    if ($plantillaId) {
        $archivosExistentes = glob($carpetaImg . '/plantilla-' . $plantillaId . '.*');
        foreach ($archivosExistentes as $archivoViejo) {
            if (file_exists($archivoViejo)) {
                unlink($archivoViejo);
            }
        }
    }
    
    // Mover archivo subido
    $rutaArchivo = $carpetaImg . '/' . $nombreArchivo;
    
    if (!move_uploaded_file($archivo['tmp_name'], $rutaArchivo)) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar el archivo']);
        exit;
    }
    
    // Ruta relativa
    $rutaRelativa = 'img/' . $nombreArchivo;
    
    // Si hay ID, actualizar la base de datos
    if ($plantillaId) {
        $updateQuery = "UPDATE plantillas SET imagen_preview = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateQuery);
        
        if ($updateStmt->execute([$rutaRelativa, $plantillaId])) {
            echo json_encode([
                'success' => true,
                'mensaje' => 'Imagen subida correctamente',
                'ruta_relativa' => $rutaRelativa,
                'ruta_completa' => '../plantillas/' . $plantilla['carpeta'] . '/' . $rutaRelativa
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar la base de datos']);
        }
    } else {
        // Nueva plantilla: solo devolver la ruta
        echo json_encode([
            'success' => true,
            'mensaje' => 'Imagen subida correctamente',
            'ruta_relativa' => 'plantillas/temp-uploads/' . $rutaRelativa,
            'archivo_temp' => $nombreArchivo
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
