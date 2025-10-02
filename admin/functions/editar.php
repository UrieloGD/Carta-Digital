<?php
require_once './../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? 0;

if (!$id) {
    header("Location: ./../index.php");
    exit();
}

$error = null;
$success = false;

// Procesar formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Función para subir imágenes con la nueva estructura de carpetas
        function subirImagen($archivo, $plantilla_id, $slug, $seccion) {
            if (!isset($_FILES[$archivo]) || $_FILES[$archivo]['error'] !== UPLOAD_ERR_OK) {
                return null;
            }
            
            $extension = strtolower(pathinfo($_FILES[$archivo]['name'], PATHINFO_EXTENSION));
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($extension, $extensiones_permitidas)) {
                throw new Exception("Formato de imagen no permitido: {$extension}");
            }
            
            $nombre_archivo = uniqid() . '.' . $extension;
            // Salir de admin/functions/ para llegar a la raíz del proyecto
            $carpeta = "../../plantillas/plantilla-{$plantilla_id}/uploads/{$slug}/{$seccion}/";
            
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }
            
            $ruta_completa = $carpeta . $nombre_archivo;
            
            if (move_uploaded_file($_FILES[$archivo]['tmp_name'], $ruta_completa)) {
                // Retornar la ruta relativa desde la raíz del proyecto
                return "plantillas/plantilla-{$plantilla_id}/uploads/{$slug}/{$seccion}/" . $nombre_archivo;
            }
            
            throw new Exception("Error al subir la imagen: {$archivo}");
        }

        function subirImagenUbicacion($archivo, $plantilla_id, $slug, $tipo_ubicacion) {
            if (!isset($_FILES[$archivo]) || $_FILES[$archivo]['error'] !== UPLOAD_ERR_OK) {
                return null;
            }
            
            $extension = strtolower(pathinfo($_FILES[$archivo]['name'], PATHINFO_EXTENSION));
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($extension, $extensiones_permitidas)) {
                throw new Exception("Formato de imagen no permitido: {$extension}");
            }
            
            $nombre_archivo = uniqid() . '.' . $extension;
            $carpeta = "../../plantillas/plantilla-{$plantilla_id}/uploads/{$slug}/ubicaciones/";
            
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }
            
            $ruta_completa = $carpeta . $nombre_archivo;
            
            if (move_uploaded_file($_FILES[$archivo]['tmp_name'], $ruta_completa)) {
                return "plantillas/plantilla-{$plantilla_id}/uploads/{$slug}/ubicaciones/" . $nombre_archivo;
            }
            
            throw new Exception("Error al subir la imagen de ubicación: {$archivo}");
        }
        
        // Validar campos requeridos
        if (empty($_POST['plantilla_id']) || empty($_POST['nombres_novios']) || 
            empty($_POST['fecha_evento']) || empty($_POST['hora_evento'])) {
            throw new Exception("Por favor, completa todos los campos obligatorios");
        }
        
        // Obtener datos actuales de la invitación
        $current_query = "SELECT * FROM invitaciones WHERE id = ?";
        $current_stmt = $db->prepare($current_query);
        $current_stmt->execute([$id]);
        $current_data = $current_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$current_data) {
            throw new Exception("Invitación no encontrada");
        }
        
        // Preparar datos para actualizar
        $plantilla_id = $_POST['plantilla_id'];
        $slug = $current_data['slug']; // El slug no se puede cambiar
        $musica_autoplay = isset($_POST['musica_autoplay']) ? 1 : 0;
        $musica_volumen = $_POST['musica_volumen'] ?? 0.5;
        
        // Subir nuevas imágenes principales si se proporcionaron
        $imagen_hero = subirImagen('imagen_hero', $plantilla_id, $slug, 'hero') ?: $current_data['imagen_hero'];
        $imagen_dedicatoria = subirImagen('imagen_dedicatoria', $plantilla_id, $slug, 'dedicatoria') ?: $current_data['imagen_dedicatoria'];
        $imagen_destacada = subirImagen('imagen_destacada', $plantilla_id, $slug, 'destacada') ?: $current_data['imagen_destacada'];
        
        // Actualizar invitación principal
        $update_query = "UPDATE invitaciones SET 
            plantilla_id = ?, nombres_novios = ?, fecha_evento = ?, hora_evento = ?,
            historia = ?, dresscode = ?, texto_rsvp = ?, mensaje_footer = ?, firma_footer = ?,
            padres_novia = ?, padres_novio = ?, padrinos_novia = ?, padrinos_novio = ?,
            musica_youtube_url = ?, musica_autoplay = ?, musica_volumen = ?,
            imagen_hero = ?, imagen_dedicatoria = ?, imagen_destacada = ?, whatsapp_confirmacion = ?, 
            tipo_rsvp = ?, fecha_limite_rsvp = ?, mostrar_contador = ?, tipo_contador = ?, mostrar_cronograma = ?
            WHERE id = ?";

        $mostrar_contador = isset($_POST['mostrar_contador']) ? 1 : 0;
        $tipo_contador = $_POST['tipo_contador'] ?? 'completo';
        $mostrar_cronograma = isset($_POST['mostrar_cronograma']) ? 1 : 0;

        $stmt = $db->prepare($update_query);
        $stmt->execute([
            $plantilla_id,
            $_POST['nombres_novios'],
            $_POST['fecha_evento'],
            $_POST['hora_evento'],
            $_POST['historia'] ?? '',
            $_POST['dresscode'] ?? '',
            $_POST['texto_rsvp'] ?? '',
            $_POST['mensaje_footer'] ?? '',
            $_POST['firma_footer'] ?? '',
            $_POST['padres_novia'] ?? '',
            $_POST['padres_novio'] ?? '',
            $_POST['padrinos_novia'] ?? '',
            $_POST['padrinos_novio'] ?? '',
            $_POST['musica_youtube_url'] ?? '',
            $musica_autoplay,
            $musica_volumen,
            $imagen_hero,
            $imagen_dedicatoria,
            $imagen_destacada,
            ($_POST['tipo_rsvp'] ?? 'digital') == 'whatsapp' ? ($_POST['whatsapp_confirmacion'] ?? '') : NULL,
            $_POST['tipo_rsvp'] ?? 'digital',
            !empty($_POST['fecha_limite_rsvp']) ? $_POST['fecha_limite_rsvp'] : NULL,
            $mostrar_contador,
            $tipo_contador,
            $mostrar_cronograma,
            $id
        ]);
        
        // Actualizar ubicaciones (eliminar y volver a insertar)
        $db->prepare("DELETE FROM invitacion_ubicaciones WHERE invitacion_id = ?")->execute([$id]);
        
        // Insertar ceremonia si se proporcionó
        if (!empty($_POST['ceremonia_lugar'])) {
            $imagen_ceremonia = subirImagenUbicacion('ceremonia_imagen', $plantilla_id, $slug, 'ceremonia');
            // Si no se subió nueva imagen, mantener la actual
            if (!$imagen_ceremonia && $ceremonia) {
                $imagen_ceremonia = $ceremonia['imagen'];
            }
            
            $stmt = $db->prepare("INSERT INTO invitacion_ubicaciones (invitacion_id, tipo, nombre_lugar, direccion, hora_inicio, google_maps_url, imagen) VALUES (?, 'ceremonia', ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $_POST['ceremonia_lugar'],
                $_POST['ceremonia_direccion'] ?? '',
                $_POST['ceremonia_hora'] ?? null,
                $_POST['ceremonia_maps'] ?? '',
                $imagen_ceremonia
            ]);
        }
        
        // Insertar evento si se proporcionó
        if (!empty($_POST['evento_lugar'])) {
            $imagen_evento = subirImagenUbicacion('evento_imagen', $plantilla_id, $slug, 'evento');
            // Si no se subió nueva imagen, mantener la actual
            if (!$imagen_evento && $evento) {
                $imagen_evento = $evento['imagen'];
            }
            
            $stmt = $db->prepare("INSERT INTO invitacion_ubicaciones (invitacion_id, tipo, nombre_lugar, direccion, hora_inicio, google_maps_url, imagen) VALUES (?, 'evento', ?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $_POST['evento_lugar'],
                $_POST['evento_direccion'] ?? '',
                $_POST['evento_hora'] ?? null,
                $_POST['evento_maps'] ?? '',
                $imagen_evento
            ]);
        }
        
        // Actualizar cronograma manteniendo el orden original
        if (!empty($_POST['cronograma_hora']) && is_array($_POST['cronograma_hora'])) {
            // Obtener IDs existentes del cronograma
            $existing_query = "SELECT id FROM invitacion_cronograma WHERE invitacion_id = ? ORDER BY orden ASC, id ASC";
            $existing_stmt = $db->prepare($existing_query);
            $existing_stmt->execute([$id]);
            $existing_ids = $existing_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Preparar statements
            $update_stmt = $db->prepare("UPDATE invitacion_cronograma SET hora = ?, evento = ?, descripcion = ?, icono = ?, orden = ? WHERE id = ?");
            $insert_stmt = $db->prepare("INSERT INTO invitacion_cronograma (invitacion_id, hora, evento, descripcion, icono, orden) VALUES (?, ?, ?, ?, ?, ?)");
            
            $processed_ids = [];
            $orden = 1;
            
            foreach ($_POST['cronograma_hora'] as $index => $hora) {
                if (!empty($hora) && !empty($_POST['cronograma_evento'][$index])) {
                    $evento = $_POST['cronograma_evento'][$index];
                    $descripcion = $_POST['cronograma_descripcion'][$index] ?? '';
                    $icono = $_POST['cronograma_icono'][$index] ?? 'anillos';
                    
                    if (isset($existing_ids[$index])) {
                        // Actualizar registro existente
                        $cronograma_id = $existing_ids[$index];
                        $update_stmt->execute([$hora, $evento, $descripcion, $icono, $orden, $cronograma_id]);
                        $processed_ids[] = $cronograma_id;
                    } else {
                        // Insertar nuevo registro
                        $insert_stmt->execute([$id, $hora, $evento, $descripcion, $icono, $orden]);
                    }
                    $orden++;
                }
            }
            
            // Eliminar registros que ya no existen
            if (!empty($existing_ids)) {
                $ids_to_delete = array_diff($existing_ids, $processed_ids);
                if (!empty($ids_to_delete)) {
                    $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
                    $delete_stmt = $db->prepare("DELETE FROM invitacion_cronograma WHERE id IN ($placeholders)");
                    $delete_stmt->execute($ids_to_delete);
                }
            }
        } else {
            // Si no hay cronograma, eliminar todos los existentes
            $db->prepare("DELETE FROM invitacion_cronograma WHERE invitacion_id = ?")->execute([$id]);
        }
        
        // Agregar nuevas imágenes a la galería (sin eliminar las existentes)
        if (isset($_FILES['imagenes_galeria']) && !empty($_FILES['imagenes_galeria']['name'][0])) {
            $stmt = $db->prepare("INSERT INTO invitacion_galeria (invitacion_id, ruta) VALUES (?, ?)");
            $carpeta_galeria = "../../plantillas/plantilla-{$plantilla_id}/uploads/{$slug}/galeria/";
            
            if (!is_dir($carpeta_galeria)) {
                mkdir($carpeta_galeria, 0755, true);
            }
            
            foreach ($_FILES['imagenes_galeria']['name'] as $index => $nombre) {
                if ($_FILES['imagenes_galeria']['error'][$index] === UPLOAD_ERR_OK) {
                    $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($extension, $extensiones_permitidas)) {
                        $nombre_archivo = uniqid() . '.' . $extension;
                        $ruta_completa = $carpeta_galeria . $nombre_archivo;
                        
                        if (move_uploaded_file($_FILES['imagenes_galeria']['tmp_name'][$index], $ruta_completa)) {
                            $ruta_relativa = "plantillas/plantilla-{$plantilla_id}/uploads/{$slug}/galeria/" . $nombre_archivo;
                            $stmt->execute([$id, $ruta_relativa]);
                        }
                    }
                }
            }
        }
        
        // Actualizar imágenes de dresscode
        $dresscode_query = "SELECT * FROM invitacion_dresscode WHERE invitacion_id = ?";
        $dresscode_stmt = $db->prepare($dresscode_query);
        $dresscode_stmt->execute([$id]);
        $current_dresscode = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);
        
        $imagen_dresscode_hombres = subirImagen('imagen_dresscode_hombres', $plantilla_id, $slug, 'dresscode');
        $imagen_dresscode_mujeres = subirImagen('imagen_dresscode_mujeres', $plantilla_id, $slug, 'dresscode');
        
        // Si no se subieron nuevas imágenes, mantener las actuales
        if (!$imagen_dresscode_hombres && $current_dresscode) {
            $imagen_dresscode_hombres = $current_dresscode['hombres'];
        }
        if (!$imagen_dresscode_mujeres && $current_dresscode) {
            $imagen_dresscode_mujeres = $current_dresscode['mujeres'];
        }
        
        // Actualizar o insertar dresscode
        if ($imagen_dresscode_hombres || $imagen_dresscode_mujeres) {
            if ($current_dresscode) {
                $stmt = $db->prepare("UPDATE invitacion_dresscode SET hombres = ?, mujeres = ? WHERE invitacion_id = ?");
                $stmt->execute([
                    $imagen_dresscode_hombres,
                    $imagen_dresscode_mujeres,
                    $id
                ]);
            } else {
                $stmt = $db->prepare("INSERT INTO invitacion_dresscode (invitacion_id, hombres, mujeres) VALUES (?, ?, ?)");
                $stmt->execute([
                    $id,
                    $imagen_dresscode_hombres,
                    $imagen_dresscode_mujeres
                ]);
            }
        }
        
        $db->commit();
        $success = true;
        
        // Redireccionar con mensaje de éxito
        header("Location: editar.php?id=" . $id . "&success=1");
        exit();
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = $e->getMessage();
    }
}

// Obtener plantillas disponibles
$plantilla_query = "SELECT id, nombre FROM plantillas WHERE activa = 1";
$plantilla_stmt = $db->prepare($plantilla_query);
$plantilla_stmt->execute();
$plantillas = $plantilla_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos de la invitación
$query = "SELECT * FROM invitaciones WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$id]);
$invitacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invitacion) {
    header("Location: ./../index.php");
    exit();
}

// Obtener cronograma
$cronograma_query = "SELECT id, invitacion_id, TIME_FORMAT(hora, '%H:%i') as hora, evento, descripcion, icono, orden FROM invitacion_cronograma WHERE invitacion_id = ? ORDER BY orden ASC, id ASC";
$cronograma_stmt = $db->prepare($cronograma_query);
$cronograma_stmt->execute([$id]);
$cronograma = $cronograma_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener galería
$galeria_query = "SELECT * FROM invitacion_galeria WHERE invitacion_id = ?";
$galeria_stmt = $db->prepare($galeria_query);
$galeria_stmt->execute([$id]);
$galeria = $galeria_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos de dresscode
$dresscode_query = "SELECT * FROM invitacion_dresscode WHERE invitacion_id = ?";
$dresscode_stmt = $db->prepare($dresscode_query);
$dresscode_stmt->execute([$id]);
$dresscode_data = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);

// Obtener ubicaciones
$ubicaciones_query = "SELECT * FROM invitacion_ubicaciones WHERE invitacion_id = ?";
$ubicaciones_stmt = $db->prepare($ubicaciones_query);
$ubicaciones_stmt->execute([$id]);
$ubicaciones = $ubicaciones_stmt->fetchAll(PDO::FETCH_ASSOC);

// Separar ubicaciones por tipo
$ceremonia = null;
$evento = null;
foreach($ubicaciones as $ub) {
    if($ub['tipo'] == 'ceremonia') $ceremonia = $ub;
    if($ub['tipo'] == 'evento') $evento = $ub;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Invitación - <?php echo htmlspecialchars($invitacion['nombres_novios']); ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./../css/editar.css?v=<?php echo filemtime('./../css/editar.css'); ?>" />
    <!-- Icon page -->
    <link rel="shortcut icon" href="./../../images/logo.webp" />
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="./../index.php">
                <i class="bi bi-pencil-square me-2"></i>
                <span class="d-none d-lg-inline">Editar Invitación: <?php echo htmlspecialchars($invitacion['nombres_novios']); ?></span>
                <span class="d-lg-none d-none d-md-inline">Editar: <?php echo htmlspecialchars(substr($invitacion['nombres_novios'], 0, 15) . (strlen($invitacion['nombres_novios']) > 15 ? '...' : '')); ?></span>
                <span class="d-md-none">Editar</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <div class="d-lg-flex flex-lg-row flex-column gap-2 mt-2 mt-lg-0">
                        <a href="./../../invitacion.php?slug=<?php echo htmlspecialchars($invitacion['slug']); ?>" 
                        class="btn btn-outline-light btn-sm" target="_blank">
                            <i class="bi bi-eye me-1"></i>
                            <span class="d-none d-md-inline">Vista </span>Previa
                        </a>
                        <a href="./crear.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>
                            <span class="d-none d-md-inline">Nueva </span>Invitación
                        </a>
                        <a href="./../index.php" class="btn btn-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>
                            <span class="d-none d-md-inline">Volver al </span>Panel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <?php if (isset($_GET['success'])): ?>
        <div class="success-alert">
            <p class="mb-0"><i class="bi bi-check-circle-fill me-2"></i> Invitación actualizada correctamente</p>
        </div>
        <?php endif; ?>

        <?php if (isset($error) && !empty($error)): ?>
        <div class="error-alert">
            <p class="mb-0"><i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="two-columns">
                <!-- Plantilla Base -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-layout-text-window-reverse me-2"></i>
                        Plantilla Base
                    </h3>
                    <div class="form-group">
                        <label for="plantilla_id" class="form-label">Selecciona una plantilla</label>
                        <select name="plantilla_id" id="plantilla_id" class="form-select" required>
                            <option value="">-- Elegir plantilla --</option>
                            <?php foreach ($plantillas as $plantilla): ?>
                                <option value="<?= $plantilla['id'] ?>" <?= $plantilla['id'] == $invitacion['plantilla_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plantilla['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Música de Fondo -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="bi bi-music-note me-2"></i>
                        Música de Fondo
                    </h3>
                    <div class="form-group">
                        <label for="musica_youtube_url" class="form-label">URL de YouTube</label>
                        <input type="url" id="musica_youtube_url" name="musica_youtube_url" 
                            class="form-control" placeholder="https://www.youtube.com/watch?v=dQw4w9WgXcQ"
                            value="<?php echo htmlspecialchars($invitacion['musica_youtube_url'] ?? ''); ?>">
                        <div class="form-text">Pega el enlace completo del video de YouTube</div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="musica_autoplay" name="musica_autoplay" value="1"
                                <?php echo isset($invitacion['musica_autoplay']) && $invitacion['musica_autoplay'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="musica_autoplay">
                                Reproducir automáticamente
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="musica_volumen" class="form-label">Volumen inicial (0-1)</label>
                        <input type="range" class="form-range" id="musica_volumen" name="musica_volumen" 
                            min="0" max="1" step="0.1" value="<?php echo $invitacion['musica_volumen'] ?? 0.5; ?>">
                        <div class="form-text">0 = silencio, 1 = volumen máximo</div>
                    </div>
                </div>
            </div>
            
            <!-- Configuraciones del Contador -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-clock me-2"></i>
                    Configuración del Contador
                </h3>
                
                <div class="two-columns">
                    <!-- Mostrar Contador -->
                    <div class="form-group">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="mostrar_contador" name="mostrar_contador" value="1"
                                <?php echo isset($invitacion['mostrar_contador']) && $invitacion['mostrar_contador'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="mostrar_contador">
                                Mostrar contador regresivo
                            </label>
                        </div>
                        <div class="form-text">Activa o desactiva el contador de días faltantes</div>
                    </div>
                    
                    <!-- Tipo de Contador -->
                    <div class="form-group">
                        <label for="tipo_contador" class="form-label">Tipo de Contador</label>
                        <select name="tipo_contador" id="tipo_contador" class="form-select">
                            <option value="completo" <?php echo ($invitacion['tipo_contador'] ?? 'completo') == 'completo' ? 'selected' : ''; ?>>
                                Completo (Días, Horas, Minutos, Segundos)
                            </option>
                            <option value="simple" <?php echo ($invitacion['tipo_contador'] ?? 'completo') == 'simple' ? 'selected' : ''; ?>>
                                Simple (Solo días)
                            </option>
                        </select>
                        <div class="form-text">Elige el estilo del contador regresivo</div>
                    </div>
                </div>
            </div>

            <!-- Información Básica -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-info-circle me-2"></i>
                    Información Básica
                </h3>
                <div class="two-columns">
                    <div class="form-group">
                        <label for="nombres_novios" class="form-label">Nombres de los Novios *</label>
                        <input type="text" id="nombres_novios" name="nombres_novios" class="form-control" required
                            value="<?php echo htmlspecialchars($invitacion['nombres_novios']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="slug" class="form-label">URL (slug)</label>
                        <input type="text" id="slug" name="slug" class="form-control" required 
                            placeholder="ej: victoria-matthew-2025" 
                            value="<?php echo htmlspecialchars($invitacion['slug']); ?>" readonly>
                        <div class="form-text">La URL no se puede modificar una vez creada</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_evento" class="form-label">Fecha del Evento *</label>
                        <input type="date" id="fecha_evento" name="fecha_evento" class="form-control" required
                            value="<?php echo $invitacion['fecha_evento']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="hora_evento" class="form-label">Hora del Evento *</label>
                        <input type="time" id="hora_evento" name="hora_evento" class="form-control" required
                            value="<?php echo $invitacion['hora_evento']; ?>">
                    </div>
                </div>
            </div>

            <!-- Ubicaciones del Evento -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-geo-alt me-2"></i>
                    Ubicaciones del Evento
                </h3>
                
                <div class="two-columns">
                    <!-- Columna izquierda - Ceremonia -->
                    <div>
                        <h5 class="text-primary mb-3">Ceremonia</h5>
                        <div class="form-group">
                            <label for="ceremonia_lugar" class="form-label">Lugar de la Ceremonia</label>
                            <input type="text" id="ceremonia_lugar" name="ceremonia_lugar" 
                                class="form-control" placeholder="Iglesia San José"
                                value="<?php echo $ceremonia ? htmlspecialchars($ceremonia['nombre_lugar']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="ceremonia_hora" class="form-label">Hora de la Ceremonia</label>
                            <input type="time" id="ceremonia_hora" name="ceremonia_hora" class="form-control"
                                value="<?php echo $ceremonia ? $ceremonia['hora_inicio'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="ceremonia_direccion" class="form-label">Dirección de la Ceremonia</label>
                            <input type="text" id="ceremonia_direccion" name="ceremonia_direccion" 
                                class="form-control" placeholder="Calle Principal 123"
                                value="<?php echo $ceremonia ? htmlspecialchars($ceremonia['direccion']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="ceremonia_maps" class="form-label">URL de Google Maps (Ceremonia)</label>
                            <input type="url" id="ceremonia_maps" name="ceremonia_maps" 
                                class="form-control" placeholder="https://maps.google.com/?q=..."
                                value="<?php echo $ceremonia ? htmlspecialchars($ceremonia['google_maps_url']) : ''; ?>">
                        </div>
                        
                        <!-- Imagen Ceremonia - DENTRO de la columna de ceremonia -->
                        <div class="form-group">
                            <label for="ceremonia_imagen" class="form-label">Imagen de la Ceremonia (opcional)</label>
                            
                            <?php if ($ceremonia && $ceremonia['imagen']): ?>
                                <div class="current-image mb-2">
                                    <img src="../../<?php echo $ceremonia['imagen']; ?>" alt="Imagen actual" class="img-thumbnail" style="max-height: 150px;">
                                    <div class="form-text mt-1">Imagen actual</div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="input-group">
                                <input type="file" name="ceremonia_imagen" id="ceremonia_imagen" accept="image/*" 
                                    class="form-control" onchange="previewImage(this, 'ceremonia-imagen-preview')">
                                <label class="input-group-text" for="ceremonia_imagen">
                                    <i class="bi bi-image"></i>
                                </label>
                            </div>
                            
                            <div id="ceremonia-imagen-preview" class="mt-2">
                                <img id="ceremonia-imagen-preview-img" src="#" alt="Preview" class="img-thumbnail d-none img-preview" style="max-height: 150px;">
                            </div>
                            <div class="form-text">Agrega una imagen de referencia del lugar (opcional)</div>
                        </div>
                    </div>
                    
                    <!-- Columna derecha - Evento/Recepción -->
                    <div>
                        <h5 class="text-primary mb-3">Evento/Recepción</h5>
                        <div class="form-group">
                            <label for="evento_lugar" class="form-label">Lugar del Evento</label>
                            <input type="text" id="evento_lugar" name="evento_lugar" 
                                class="form-control" placeholder="Salón de Eventos Villa Jardín"
                                value="<?php echo $evento ? htmlspecialchars($evento['nombre_lugar']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="evento_hora" class="form-label">Hora del Evento</label>
                            <input type="time" id="evento_hora" name="evento_hora" class="form-control"
                                value="<?php echo $evento ? $evento['hora_inicio'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="evento_direccion" class="form-label">Dirección del Evento</label>
                            <input type="text" id="evento_direccion" name="evento_direccion" 
                                class="form-control" placeholder="Avenida Central 456"
                                value="<?php echo $evento ? htmlspecialchars($evento['direccion']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="evento_maps" class="form-label">URL de Google Maps (Evento)</label>
                            <input type="url" id="evento_maps" name="evento_maps" 
                                class="form-control" placeholder="https://maps.google.com/?q=..."
                                value="<?php echo $evento ? htmlspecialchars($evento['google_maps_url']) : ''; ?>">
                        </div>
                        
                        <!-- Imagen Evento - DENTRO de la columna de evento -->
                        <div class="form-group">
                            <label for="evento_imagen" class="form-label">Imagen del Evento (opcional)</label>
                            
                            <?php if ($evento && $evento['imagen']): ?>
                                <div class="current-image mb-2">
                                    <img src="../../<?php echo $evento['imagen']; ?>" alt="Imagen actual" class="img-thumbnail" style="max-height: 150px;">
                                    <div class="form-text mt-1">Imagen actual</div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="input-group">
                                <input type="file" name="evento_imagen" id="evento_imagen" accept="image/*" 
                                    class="form-control" onchange="previewImage(this, 'evento-imagen-preview')">
                                <label class="input-group-text" for="evento_imagen">
                                    <i class="bi bi-image"></i>
                                </label>
                            </div>
                            
                            <div id="evento-imagen-preview" class="mt-2">
                                <img id="evento-imagen-preview-img" src="#" alt="Preview" class="img-thumbnail d-none img-preview" style="max-height: 150px;">
                            </div>
                            <div class="form-text">Agrega una imagen de referencia del lugar (opcional)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido Personalizado -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-card-text me-2"></i>
                    Contenido Personalizado
                </h3>
                
                <div class="form-group">
                    <label for="historia" class="form-label">Historia de Amor</label>
                    <textarea id="historia" name="historia" rows="4" class="form-control" 
                        placeholder="Cuenta vuestra historia de amor..."><?php echo htmlspecialchars($invitacion['historia']); ?></textarea>
                </div>
            </div>

            <!-- Imágenes -->
            <div class="form-section images-section">
                <h3 class="section-title">
                    <i class="bi bi-images me-2"></i>
                    Imágenes
                </h3>
                
                <div class="two-columns">
                    <!-- Imagen Hero -->
                    <div class="form-group">
                        <label for="imagen_hero" class="form-label">Imagen Hero</label>
                        
                        <?php if ($invitacion['imagen_hero']): ?>
                            <div class="current-image mb-2">
                                <img src="../../<?php echo $invitacion['imagen_hero']; ?>" alt="Imagen actual" class="img-thumbnail">
                                <div class="form-text mt-1">Imagen actual</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <input type="file" name="imagen_hero" id="imagen_hero" accept="image/*" 
                                class="form-control" onchange="previewImage(this, 'hero-preview')">
                            <label class="input-group-text" for="imagen_hero">
                                <i class="bi bi-upload"></i>
                            </label>
                        </div>
                        
                        <div id="hero-preview" class="mt-2">
                            <img id="hero-preview-img" src="#" alt="Preview" class="img-thumbnail d-none img-preview">
                        </div>
                        <div class="form-text">Deja vacío para mantener la imagen actual</div>
                    </div>
                    
                    <!-- Imagen Dedicatoria -->
                    <div class="form-group">
                        <label for="imagen_dedicatoria" class="form-label">Imagen Dedicatoria</label>
                        
                        <?php if ($invitacion['imagen_dedicatoria']): ?>
                            <div class="current-image mb-2">
                                <img src="../../<?php echo $invitacion['imagen_dedicatoria']; ?>" alt="Imagen actual" class="img-thumbnail">
                                <div class="form-text mt-1">Imagen actual</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <input type="file" name="imagen_dedicatoria" id="imagen_dedicatoria" accept="image/*" 
                                class="form-control" onchange="previewImage(this, 'dedicatoria-preview')">
                            <label class="input-group-text" for="imagen_dedicatoria">
                                <i class="bi bi-upload"></i>
                            </label>
                        </div>
                        
                        <div id="dedicatoria-preview" class="mt-2">
                            <img id="dedicatoria-preview-img" src="#" alt="Preview" class="img-thumbnail d-none img-preview">
                        </div>
                        <div class="form-text">Deja vacío para mantener la imagen actual</div>
                    </div>
                    
                    <!-- Imagen Destacada -->
                    <div class="form-group">
                        <label for="imagen_destacada" class="form-label">Imagen Destacada</label>
                        
                        <?php if ($invitacion['imagen_destacada']): ?>
                            <div class="current-image mb-2">
                                <img src="../../<?php echo $invitacion['imagen_destacada']; ?>" alt="Imagen actual" class="img-thumbnail">
                                <div class="form-text mt-1">Imagen actual</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <input type="file" name="imagen_destacada" id="imagen_destacada" accept="image/*" 
                                class="form-control" onchange="previewImage(this, 'destacada-preview')">
                            <label class="input-group-text" for="imagen_destacada">
                                <i class="bi bi-upload"></i>
                            </label>
                        </div>
                        
                        <div id="destacada-preview" class="mt-2">
                            <img id="destacada-preview-img" src="#" alt="Preview" class="img-thumbnail d-none img-preview">
                        </div>
                        <div class="form-text">Deja vacío para mantener la imagen actual</div>
                    </div>
                </div>
            </div>

            <!-- Información Familiar -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-people me-2"></i>
                    Información Familiar
                </h3>
                
                <div class="two-columns">
                    <div class="form-group">
                        <label for="padres_novia" class="form-label">Padres de la Novia</label>
                        <input type="text" id="padres_novia" name="padres_novia" class="form-control" 
                            placeholder="Nombres de los padres de la novia"
                            value="<?php echo htmlspecialchars($invitacion['padres_novia']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="padres_novio" class="form-label">Padres del Novio</label>
                        <input type="text" id="padres_novio" name="padres_novio" class="form-control" 
                            placeholder="Nombres de los padres del novio"
                            value="<?php echo htmlspecialchars($invitacion['padres_novio']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="padrinos_novia" class="form-label">Padrinos de la Novia</label>
                        <input type="text" id="padrinos_novia" name="padrinos_novia" class="form-control" 
                            placeholder="Nombres de los padrinos de la novia"
                            value="<?php echo htmlspecialchars($invitacion['padrinos_novia']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="padrinos_novio" class="form-label">Padrinos del Novio</label>
                        <input type="text" id="padrinos_novio" name="padrinos_novio" class="form-control" 
                            placeholder="Nombres de los padrinos del novio"
                            value="<?php echo htmlspecialchars($invitacion['padrinos_novio']); ?>">
                    </div>
                </div>
            </div>

            <!-- Galería - Full Width -->
            <div class="form-section full-width">
                <h3 class="section-title">
                    <i class="bi bi-collection me-2"></i>
                    Galería de Imágenes
                </h3>
                
                <?php if (!empty($galeria)): ?>
                    <div class="current-gallery mb-4">
                        <h5>Imágenes actuales:</h5>
                        <div class="gallery-grid">
                            <?php foreach ($galeria as $imagen): ?>
                                <div class="gallery-item">
                                    <img src="../../<?php echo $imagen['ruta']; ?>" alt="Imagen galería">
                                    <button type="button" onclick="eliminarImagenGaleria(<?php echo $imagen['id']; ?>)" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="imagenes_galeria" class="form-label">Agregar nuevas imágenes a la galería (puedes seleccionar varias)</label>
                    <div class="input-group">
                        <input type="file" name="imagenes_galeria[]" id="imagenes_galeria" accept="image/*" 
                            multiple class="form-control" onchange="previewGallery(this)">
                        <label class="input-group-text" for="imagenes_galeria">
                            <i class="bi bi-images"></i> Seleccionar
                        </label>
                    </div>
                    <div class="form-text">Puedes seleccionar múltiples imágenes manteniendo presionado Ctrl (Windows) o Cmd (Mac)</div>
                </div>
                <div id="gallery-preview" class="row mt-3"></div>
            </div>

            <!-- Cronograma - Full Width -->
            <div class="form-section full-width">
                <h3 class="section-title">
                    <i class="bi bi-clock me-2"></i>
                    Cronograma del Evento
                </h3>
                
                <!-- Control para mostrar/ocultar cronograma -->
                <div class="form-group mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="mostrar_cronograma" name="mostrar_cronograma" value="1"
                            <?php echo (isset($invitacion['mostrar_cronograma']) ? $invitacion['mostrar_cronograma'] : 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="mostrar_cronograma">
                            Mostrar sección de cronograma
                        </label>
                    </div>
                    <div class="form-text">Activa o desactiva la sección completa del cronograma en la invitación</div>
                </div>
                
                <div id="cronograma-content" style="<?php echo (isset($invitacion['mostrar_cronograma']) && !$invitacion['mostrar_cronograma']) ? 'display: none;' : ''; ?>">                    
                    <div id="cronograma-container">
                        <?php if (empty($cronograma)): ?>
                        <div class="cronograma-item">
                            <input type="hidden" name="cronograma_orden[]" value="1">
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <label class="form-label">Hora</label>
                                    <input type="time" name="cronograma_hora[]" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Evento</label>
                                    <input type="text" name="cronograma_evento[]" class="form-control" 
                                        placeholder="Ceremonia">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Descripción</label>
                                    <input type="text" name="cronograma_descripcion[]" class="form-control" 
                                        placeholder="Descripción del evento">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Icono</label>
                                    <select name="cronograma_icono[]" class="form-select">
                                        <option value="anillos">Anillos</option>
                                        <option value="cena">Cena</option>
                                        <option value="fiesta">Fiesta</option>
                                        <option value="luna">Luna</option>
                                    </select>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" onclick="eliminarCronograma(this)" class="btn btn-outline-danger btn-sm mt-2">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                            <?php foreach($cronograma as $orden_item => $item): ?>
                            <div class="cronograma-item">
                                <!-- Campo oculto para mantener el orden -->
                                <input type="hidden" name="cronograma_orden[]" value="<?php echo $orden_item + 1; ?>">
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <label class="form-label">Hora</label>
                                        <input type="time" name="cronograma_hora[]" class="form-control" 
                                            value="<?php echo $item['hora']; ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Evento</label>
                                        <input type="text" name="cronograma_evento[]" class="form-control" 
                                            placeholder="Ceremonia"
                                            value="<?php echo htmlspecialchars($item['evento']); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Descripción</label>
                                        <input type="text" name="cronograma_descripcion[]" class="form-control" 
                                            placeholder="Descripción del evento"
                                            value="<?php echo htmlspecialchars($item['descripcion']); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Icono</label>
                                        <select name="cronograma_icono[]" class="form-select">
                                            <option value="anillos" <?php echo $item['icono'] == 'anillos' ? 'selected' : ''; ?>>Anillos</option>
                                            <option value="cena" <?php echo $item['icono'] == 'cena' ? 'selected' : ''; ?>>Cena</option>
                                            <option value="fiesta" <?php echo $item['icono'] == 'fiesta' ? 'selected' : ''; ?>>Fiesta</option>
                                            <option value="luna" <?php echo $item['icono'] == 'luna' ? 'selected' : ''; ?>>Luna</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" onclick="eliminarCronograma(this)" class="btn btn-outline-danger btn-sm mt-2">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="agregarCronograma()" class="btn btn-outline-primary mt-2">
                        <i class="bi bi-plus-circle me-1"></i>
                        Agregar Evento
                    </button>
                </div>
            </div>

            <!-- Dresscode -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-person-check me-2"></i>
                    Código de Vestimenta
                </h3>
                
                <div class="two-columns">
                    <div class="form-group">
                        <label for="imagen_dresscode_hombres" class="form-label">Imagen Dresscode Hombres</label>
                        
                        <?php if ($dresscode_data && $dresscode_data['hombres']): ?>
                            <div class="current-image mb-2">
                                <img src="../../<?php echo $dresscode_data['hombres']; ?>" alt="Imagen actual" class="img-thumbnail">
                                <div class="form-text mt-1">Imagen actual</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <input type="file" name="imagen_dresscode_hombres" id="imagen_dresscode_hombres" 
                                accept="image/*" class="form-control" onchange="previewImage(this, 'dresscode-hombres-preview')">
                            <label class="input-group-text" for="imagen_dresscode_hombres">
                                <i class="bi bi-person-fill"></i>
                            </label>
                        </div>
                        
                        <div id="dresscode-hombres-preview" class="mt-2">
                            <img id="dresscode-hombres-preview-img" src="#" alt="Preview" class="img-thumbnail d-none img-preview">
                        </div>
                        <div class="form-text">Deja vacío para mantener la imagen actual</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagen_dresscode_mujeres" class="form-label">Imagen Dresscode Mujeres</label>
                        
                        <?php if ($dresscode_data && $dresscode_data['mujeres']): ?>
                            <div class="current-image mb-2">
                                <img src="../../<?php echo $dresscode_data['mujeres']; ?>" alt="Imagen actual" class="img-thumbnail">
                                <div class="form-text mt-1">Imagen actual</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="input-group">
                            <input type="file" name="imagen_dresscode_mujeres" id="imagen_dresscode_mujeres" 
                                accept="image/*" class="form-control" onchange="previewImage(this, 'dresscode-mujeres-preview')">
                            <label class="input-group-text" for="imagen_dresscode_mujeres">
                                <i class="bi bi-person-dress"></i>
                            </label>
                        </div>
                        
                        <div id="dresscode-mujeres-preview" class="mt-2">
                            <img id="dresscode-mujeres-preview-img" src="#" alt="Preview" class="img-thumbnail d-none img-preview">
                        </div>
                        <div class="form-text">Deja vacío para mantener la imagen actual</div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="dresscode" class="form-label">Descripción del Código de Vestimenta</label>
                    <textarea id="dresscode" name="dresscode" rows="2" class="form-control" 
                        placeholder="Por favor, viste atuendo elegante..."><?php echo htmlspecialchars($invitacion['dresscode']); ?></textarea>
                </div>
            </div>

            <!-- RSVP -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-calendar-check me-2"></i>
                    Confirmación RSVP
                </h3>
                
                <div class="two-columns">
                    <!-- Texto RSVP -->
                    <div class="form-group">
                        <label for="texto_rsvp" class="form-label">Texto para RSVP</label>
                        <input type="text" id="texto_rsvp" name="texto_rsvp" class="form-control" 
                            placeholder="Confirma tu asistencia antes del..."
                            value="<?php echo htmlspecialchars($invitacion['texto_rsvp']); ?>">
                    </div>
                    
                    <!-- Fecha límite RSVP -->
                    <div class="form-group">
                        <label for="fecha_limite_rsvp" class="form-label">Fecha Límite para Confirmar</label>
                        <input type="date" id="fecha_limite_rsvp" name="fecha_limite_rsvp" class="form-control"
                            value="<?php echo $invitacion['fecha_limite_rsvp'] ?? ''; ?>">
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>
                            Después de esta fecha, los invitados no podrán confirmar su asistencia
                        </div>
                    </div>
                </div>
                
                <!-- Tipo de RSVP -->
                <div class="form-group">
                    <label for="tipo_rsvp" class="form-label">Tipo de Confirmación RSVP</label>
                    <select id="tipo_rsvp" name="tipo_rsvp" class="form-select" onchange="toggleRSVPFields()">
                        <option value="digital" <?php echo ($invitacion['tipo_rsvp'] ?? 'digital') == 'digital' ? 'selected' : ''; ?>>
                            Sistema de Boletaje Digital
                        </option>
                        <option value="whatsapp" <?php echo ($invitacion['tipo_rsvp'] ?? 'digital') == 'whatsapp' ? 'selected' : ''; ?>>
                            Confirmación por WhatsApp
                        </option>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Elige cómo prefieres que tus invitados confirmen su asistencia
                    </div>
                </div>

                <!-- Campo WhatsApp -->
                <div class="form-group" id="campo-whatsapp" style="<?php echo ($invitacion['tipo_rsvp'] ?? 'digital') == 'whatsapp' ? '' : 'display: none;'; ?>">
                    <label for="whatsapp_confirmacion" class="form-label">Número de WhatsApp para Confirmaciones *</label>
                    <input type="tel" id="whatsapp_confirmacion" name="whatsapp_confirmacion" class="form-control" 
                        placeholder="3339047672" pattern="[0-9]{10,15}"
                        value="<?php echo htmlspecialchars($invitacion['whatsapp_confirmacion'] ?? ''); ?>"
                        <?php echo ($invitacion['tipo_rsvp'] ?? 'digital') == 'whatsapp' ? 'required' : ''; ?>>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Número de WhatsApp donde recibirás las confirmaciones de asistencia (solo números, sin espacios ni guiones)
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="form-section">
                <h3 class="section-title">
                    <i class="bi bi-chat-heart me-2"></i>
                    Mensaje del Footer
                </h3>
                
                <div class="form-group">
                    <label for="mensaje_footer" class="form-label">Mensaje del Footer</label>
                    <textarea id="mensaje_footer" name="mensaje_footer" rows="2" class="form-control" 
                        placeholder="El amor es la fuerza más poderosa del mundo..."><?php echo htmlspecialchars($invitacion['mensaje_footer']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="firma_footer" class="form-label">Firma del Footer</label>
                    <input type="text" id="firma_footer" name="firma_footer" class="form-control" 
                        placeholder="Con amor, Victoria & Matthew"
                        value="<?php echo htmlspecialchars($invitacion['firma_footer']); ?>">
                </div>
            </div>

            <!-- Botones de acción flotantes -->
            <div class="floating-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>
                    Guardar Cambios
                </button>
                <a href="./../index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i>
                    Cancelar
                </a>
            </div>
        </form>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./../js/editar.js?v=<?php echo filemtime('./../js/editar.js'); ?>"></script>
</body>
</html>