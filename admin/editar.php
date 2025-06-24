<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? 0;

if (!$id) {
    header("Location: index.php");
    exit();
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
    header("Location: index.php");
    exit();
}

// Obtener cronograma
$cronograma_query = "SELECT * FROM invitacion_cronograma WHERE invitacion_id = ? ORDER BY hora";
$cronograma_stmt = $db->prepare($cronograma_query);
$cronograma_stmt->execute([$id]);
$cronograma = $cronograma_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener FAQs
$faq_query = "SELECT * FROM invitacion_faq WHERE invitacion_id = ?";
$faq_stmt = $db->prepare($faq_query);
$faq_stmt->execute([$id]);
$faqs = $faq_stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Función para guardar imágenes (igual que en crear.php)
function guardarImagen($campo, $ruta) {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        // Verificar que el archivo sea una imagen
        $imageFileType = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($imageFileType, $allowed_types)) {
            die("Solo se permiten archivos JPG, JPEG, PNG, GIF y WEBP.");
        }
        
        // Generar nombre único para evitar conflictos
        $nombre = uniqid() . '.' . $imageFileType;
        $destino = "$ruta/$nombre";
        
        // Verificar que la carpeta existe
        if (!is_dir($ruta)) {
            mkdir($ruta, 0777, true);
        }
        
        // Mover el archivo
        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
            // Devolver ruta relativa desde la raíz del proyecto
            return "uploads/" . basename(dirname($ruta)) . "/" . basename($ruta) . "/$nombre";
        } else {
            die("Error al subir la imagen: $campo");
        }
    }
    return null;
}

// Procesar formulario
if ($_POST) {
    try {
        $db->beginTransaction();
        
        // Crear carpetas de subida si no existen
        $upload_base = "../uploads/{$invitacion['slug']}";
        $secciones = ['hero', 'dedicatoria', 'destacada', 'galeria', 'dresscode'];
        foreach ($secciones as $sec) {
            $path = "$upload_base/$sec";
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }

        // Manejar imágenes principales
        $img_hero = guardarImagen('imagen_hero', "$upload_base/hero");
        $img_dedicatoria = guardarImagen('imagen_dedicatoria', "$upload_base/dedicatoria");
        $img_destacada = guardarImagen('imagen_destacada', "$upload_base/destacada");
        
        // Actualizar invitación principal
        $update_query = "UPDATE invitaciones SET 
                        plantilla_id = ?, nombres_novios = ?, fecha_evento = ?, hora_evento = ?, 
                        ubicacion = ?, direccion_completa = ?, coordenadas = ?, 
                        historia = ?, dresscode = ?, texto_rsvp = ?, 
                        mensaje_footer = ?, firma_footer = ?";
        
        $params = [
            $_POST['plantilla_id'],
            $_POST['nombres_novios'],
            $_POST['fecha_evento'],
            $_POST['hora_evento'],
            $_POST['ubicacion'],
            $_POST['direccion_completa'],
            $_POST['coordenadas'],
            $_POST['historia'],
            $_POST['dresscode'],
            $_POST['texto_rsvp'],
            $_POST['mensaje_footer'],
            $_POST['firma_footer']
        ];

        // Agregar imágenes solo si se subieron nuevas
        if ($img_hero) {
            $update_query .= ", imagen_hero = ?";
            $params[] = $img_hero;
        }
        if ($img_dedicatoria) {
            $update_query .= ", imagen_dedicatoria = ?";
            $params[] = $img_dedicatoria;
        }
        if ($img_destacada) {
            $update_query .= ", imagen_destacada = ?";
            $params[] = $img_destacada;
        }

        $update_query .= " WHERE id = ?";
        $params[] = $id;
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->execute($params);
        
        // Manejar galería de imágenes subidas
        if (!empty($_FILES['imagenes_galeria']['name'][0])) {
            $galeria_dir = "$upload_base/galeria";
            if (!is_dir($galeria_dir)) mkdir($galeria_dir, 0777, true);

            foreach ($_FILES['imagenes_galeria']['name'] as $i => $nombre) {
                if ($_FILES['imagenes_galeria']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $nombre_final = uniqid() . ".$ext";
                        $ruta_destino = "$galeria_dir/$nombre_final";
                        if (move_uploaded_file($_FILES['imagenes_galeria']['tmp_name'][$i], $ruta_destino)) {
                            // Insertar en galería
                            $galeria_insert = $db->prepare("INSERT INTO invitacion_galeria (invitacion_id, ruta) VALUES (?, ?)");
                            $galeria_insert->execute([$id, "uploads/{$invitacion['slug']}/galeria/$nombre_final"]);
                        }
                    }
                }
            }
        }

        // Manejar dresscode
        $img_dresscode_hombres = guardarImagen('imagen_dresscode_hombres', "$upload_base/dresscode");
        $img_dresscode_mujeres = guardarImagen('imagen_dresscode_mujeres', "$upload_base/dresscode");

        if ($img_dresscode_hombres || $img_dresscode_mujeres) {
            // Eliminar dresscode existente
            $delete_dresscode = "DELETE FROM invitacion_dresscode WHERE invitacion_id = ?";
            $db->prepare($delete_dresscode)->execute([$id]);
            
            // Insertar nuevo dresscode
            $dresscode_stmt = $db->prepare("INSERT INTO invitacion_dresscode (invitacion_id, hombres, mujeres) VALUES (?, ?, ?)");
            $dresscode_stmt->execute([
                $id, 
                $img_dresscode_hombres ?? ($dresscode_data['hombres'] ?? ''), 
                $img_dresscode_mujeres ?? ($dresscode_data['mujeres'] ?? '')
            ]);
        }
        
        // Eliminar cronograma existente y agregar nuevo
        $delete_cronograma = "DELETE FROM invitacion_cronograma WHERE invitacion_id = ?";
        $db->prepare($delete_cronograma)->execute([$id]);
        
        if (isset($_POST['cronograma_hora']) && !empty($_POST['cronograma_hora'][0])) {
            $cronograma_query = "INSERT INTO invitacion_cronograma (invitacion_id, hora, evento, descripcion, icono) VALUES (?, ?, ?, ?, ?)";
            $cronograma_stmt = $db->prepare($cronograma_query);
            
            foreach ($_POST['cronograma_hora'] as $index => $hora) {
                if (!empty($hora) && !empty($_POST['cronograma_evento'][$index])) {
                    $cronograma_stmt->execute([
                        $id,
                        $hora,
                        $_POST['cronograma_evento'][$index],
                        $_POST['cronograma_descripcion'][$index] ?? '',
                        $_POST['cronograma_icono'][$index] ?? 'anillos'
                    ]);
                }
            }
        }
        
        // Eliminar FAQs existentes y agregar nuevos
        $delete_faq = "DELETE FROM invitacion_faq WHERE invitacion_id = ?";
        $db->prepare($delete_faq)->execute([$id]);
        
        if (isset($_POST['faq_pregunta']) && !empty($_POST['faq_pregunta'][0])) {
            $faq_query = "INSERT INTO invitacion_faq (invitacion_id, pregunta, respuesta) VALUES (?, ?, ?)";
            $faq_stmt = $db->prepare($faq_query);
            
            foreach ($_POST['faq_pregunta'] as $index => $pregunta) {
                if (!empty($pregunta) && !empty($_POST['faq_respuesta'][$index])) {
                    $faq_stmt->execute([
                        $id,
                        $pregunta,
                        $_POST['faq_respuesta'][$index]
                    ]);
                }
            }
        }
        
        $db->commit();
        
        // Redirigir con mensaje de éxito
        header("Location: editar.php?id=" . $id . "&success=1");
        exit();
        
    } catch (Exception $e) {
        $db->rollback();
        $error = "Error al actualizar la invitación: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Invitación - <?php echo htmlspecialchars($invitacion['nombres_novios']); ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Editar Invitación: <?php echo htmlspecialchars($invitacion['nombres_novios']); ?></h1>
            <div class="header-actions">
                <a href="../invitacion.php?slug=<?php echo $invitacion['slug']; ?>" class="btn btn-preview" target="_blank">Vista Previa</a>
                <a href="index.php" class="btn btn-secondary">Volver</a>
            </div>
        </header>

        <?php if (isset($_GET['success'])): ?>
        <div class="success-alert">
            <p>✅ Invitación actualizada correctamente</p>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="error-alert">
            <p>❌ <?php echo $error; ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="form-section">
                <h3>Plantilla Base</h3>
                <div class="form-group">
                    <label for="plantilla_id">Selecciona una plantilla</label>
                    <select name="plantilla_id" id="plantilla_id" required>
                        <option value="">-- Elegir plantilla --</option>
                        <?php foreach ($plantillas as $plantilla): ?>
                            <option value="<?= $plantilla['id'] ?>" <?= $plantilla['id'] == $invitacion['plantilla_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($plantilla['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-section">
                <h3>Información Básica</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombres_novios">Nombres de los Novios</label>
                        <input type="text" id="nombres_novios" name="nombres_novios" 
                               value="<?php echo htmlspecialchars($invitacion['nombres_novios']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="slug">URL (slug)</label>
                        <input type="text" id="slug" name="slug" 
                               value="<?php echo htmlspecialchars($invitacion['slug']); ?>" readonly>
                        <small class="form-note">La URL no se puede modificar una vez creada</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_evento">Fecha del Evento</label>
                        <input type="date" id="fecha_evento" name="fecha_evento" 
                               value="<?php echo $invitacion['fecha_evento']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_evento">Hora del Evento</label>
                        <input type="time" id="hora_evento" name="hora_evento" 
                               value="<?php echo $invitacion['hora_evento']; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ubicacion">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" 
                               value="<?php echo htmlspecialchars($invitacion['ubicacion']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="direccion_completa">Dirección Completa</label>
                        <input type="text" id="direccion_completa" name="direccion_completa" 
                               value="<?php echo htmlspecialchars($invitacion['direccion_completa']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="coordenadas">Coordenadas (lat,lng)</label>
                    <input type="text" id="coordenadas" name="coordenadas" 
                           value="<?php echo htmlspecialchars($invitacion['coordenadas']); ?>" 
                           placeholder="34.0522,-118.2437">
                </div>
            </div>

            <div class="form-group">
                <label for="imagen_hero">Imagen Hero</label>
                <?php if ($invitacion['imagen_hero']): ?>
                    <div class="current-image">
                        <img src="../<?php echo $invitacion['imagen_hero']; ?>" alt="Imagen actual" style="max-width: 200px; height: auto;">
                        <p><small>Imagen actual</small></p>
                    </div>
                <?php endif; ?>
                
                <div class="file-input-wrapper">
                    <input type="file" name="imagen_hero" id="imagen_hero" accept="image/*">
                    <label for="imagen_hero" class="file-input-label">
                        <i>📁</i> Seleccionar nueva imagen
                    </label>
                </div>
                
                <div class="image-preview-container" id="preview-hero">
                    <div class="image-placeholder">
                        <i>🖼️</i>
                        <span>Vista previa aparecerá aquí</span>
                    </div>
                </div>
                
                <small class="form-note">Deja vacío para mantener la imagen actual</small>
            </div>

            <div class="form-section">
                <h3>Contenido</h3>
                <div class="form-group">
                    <label for="historia">Historia de la Pareja</label>
                    <textarea id="historia" name="historia" rows="4"><?php echo htmlspecialchars($invitacion['historia']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="dresscode">Dress Code</label>
                    <textarea id="dresscode" name="dresscode" rows="3"><?php echo htmlspecialchars($invitacion['dresscode']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="texto_rsvp">Texto RSVP</label>
                    <textarea id="texto_rsvp" name="texto_rsvp" rows="2"><?php echo htmlspecialchars($invitacion['texto_rsvp']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="mensaje_footer">Mensaje Footer</label>
                        <textarea id="mensaje_footer" name="mensaje_footer" rows="2"><?php echo htmlspecialchars($invitacion['mensaje_footer']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="firma_footer">Firma Footer</label>
                        <input type="text" id="firma_footer" name="firma_footer" 
                               value="<?php echo htmlspecialchars($invitacion['firma_footer']); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Galería de Imágenes</h3>
                
                <?php if (!empty($galeria)): ?>
                    <div class="current-gallery">
                        <h4>Imágenes actuales:</h4>
                        <div class="gallery-grid">
                            <?php foreach ($galeria as $imagen): ?>
                                <div class="gallery-item">
                                    <img src="../<?php echo $imagen['ruta']; ?>" alt="Imagen galería">
                                    <button type="button" onclick="eliminarImagenGaleria(<?php echo $imagen['id']; ?>)" class="btn btn-danger btn-sm">
                                        🗑️ Eliminar
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="imagenes_galeria">Agregar nuevas imágenes a la galería</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="imagenes_galeria[]" id="imagenes_galeria" accept="image/*" multiple>
                        <label for="imagenes_galeria" class="file-input-label">
                            <i>📁</i> Seleccionar múltiples imágenes
                        </label>
                    </div>
                    <div class="gallery-preview-container" id="gallery-previews"></div>
                    <small class="form-note">Puedes seleccionar múltiples imágenes</small>
                </div>
            </div>

            <div class="form-section">
                <h3>Imágenes para DressCode</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="imagen_dresscode_hombres">Imagen Dresscode Hombres</label>
                        <?php if ($dresscode_data && $dresscode_data['hombres']): ?>
                            <div class="current-image">
                                <img src="../<?php echo $dresscode_data['hombres']; ?>" alt="Imagen actual" style="max-width: 150px; height: auto;">
                                <p><small>Imagen actual</small></p>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagen_dresscode_hombres" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label for="imagen_dresscode_mujeres">Imagen Dresscode Mujeres</label>
                        <?php if ($dresscode_data && $dresscode_data['mujeres']): ?>
                            <div class="current-image">
                                <img src="../<?php echo $dresscode_data['mujeres']; ?>" alt="Imagen actual" style="max-width: 150px; height: auto;">
                                <p><small>Imagen actual</small></p>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagen_dresscode_mujeres" accept="image/*">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Cronograma</h3>
                <div id="cronograma-container">
                    <?php if (empty($cronograma)): ?>
                    <div class="cronograma-item">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Hora</label>
                                <input type="time" name="cronograma_hora[]">
                            </div>
                            <div class="form-group">
                                <label>Evento</label>
                                <input type="text" name="cronograma_evento[]">
                            </div>
                            <div class="form-group">
                                <label>Descripción</label>
                                <input type="text" name="cronograma_descripcion[]">
                            </div>
                            <div class="form-group">
                                <label>Icono</label>
                                <select name="cronograma_icono[]">
                                    <option value="anillos">Anillos</option>
                                    <option value="cena">Cena</option>
                                    <option value="fiesta">Fiesta</option>
                                    <option value="luna">Luna</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                        <?php foreach($cronograma as $item): ?>
                        <div class="cronograma-item">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Hora</label>
                                    <input type="time" name="cronograma_hora[]" value="<?php echo $item['hora']; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Evento</label>
                                    <input type="text" name="cronograma_evento[]" value="<?php echo htmlspecialchars($item['evento']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <input type="text" name="cronograma_descripcion[]" value="<?php echo htmlspecialchars($item['descripcion']); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Icono</label>
                                    <select name="cronograma_icono[]">
                                        <option value="anillos" <?php echo $item['icono'] == 'anillos' ? 'selected' : ''; ?>>Anillos</option>
                                        <option value="cena" <?php echo $item['icono'] == 'cena' ? 'selected' : ''; ?>>Cena</option>
                                        <option value="fiesta" <?php echo $item['icono'] == 'fiesta' ? 'selected' : ''; ?>>Fiesta</option>
                                        <option value="luna" <?php echo $item['icono'] == 'luna' ? 'selected' : ''; ?>>Luna</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button type="button" onclick="eliminarCronograma(this)" class="btn btn-danger btn-sm">
                                        🗑️ Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="agregarCronograma()" class="btn btn-add">
                    ➕ Agregar Evento
                </button>
            </div>

            <div class="form-section">
                <h3>Preguntas Frecuentes</h3>
                <div id="faq-container">
                    <?php if (empty($faqs)): ?>
                    <div class="faq-item">
                        <div class="form-group">
                            <label>Pregunta</label>
                            <input type="text" name="faq_pregunta[]">
                        </div>
                        <div class="form-group">
                            <label>Respuesta</label>
                            <textarea name="faq_respuesta[]" rows="2"></textarea>
                        </div>
                    </div>
                    <?php else: ?>
                        <?php foreach($faqs as $faq): ?>
                        <div class="faq-item">
                            <div class="form-group">
                                <label>Pregunta</label>
                                <input type="text" name="faq_pregunta[]" value="<?php echo htmlspecialchars($faq['pregunta']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Respuesta</label>
                                <textarea name="faq_respuesta[]" rows="2"><?php echo htmlspecialchars($faq['respuesta']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <button type="button" onclick="eliminarFAQ(this)" class="btn btn-danger btn-sm">Eliminar</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" onclick="agregarFAQ()" class="btn btn-add">Agregar FAQ</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
    function agregarCronograma() {
        const container = document.getElementById('cronograma-container');
        const newItem = container.children[0].cloneNode(true);
        // Limpiar valores
        newItem.querySelectorAll('input, select').forEach(input => input.value = '');
        // Remover botón eliminar si existe
        const deleteBtn = newItem.querySelector('.btn-danger');
        if (deleteBtn) deleteBtn.remove();
        container.appendChild(newItem);
    }

    function eliminarCronograma(button) {
        const container = document.getElementById('cronograma-container');
        if (container.children.length > 1) {
            button.closest('.cronograma-item').remove();
        }
    }

    function agregarFAQ() {
        const container = document.getElementById('faq-container');
        const newItem = container.children[0].cloneNode(true);
        // Limpiar valores
        newItem.querySelectorAll('input, textarea').forEach(input => input.value = '');
        // Remover botón eliminar si existe
        const deleteBtn = newItem.querySelector('.btn-danger');
        if (deleteBtn) deleteBtn.remove();
        container.appendChild(newItem);
    }

    function eliminarFAQ(button) {
        const container = document.getElementById('faq-container');
        if (container.children.length > 1) {
            button.closest('.faq-item').remove();
        }
    }

    function setupImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
        
        if (input && preview) {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Vista previa" class="image-preview">`;
                        preview.classList.add('has-image');
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    // Configurar vistas previas
    document.addEventListener('DOMContentLoaded', function() {
        setupImagePreview('imagen_hero', 'preview-hero');
        setupImagePreview('imagen_dedicatoria', 'preview-dedicatoria');
        setupImagePreview('imagen_destacada', 'preview-destacada');
        setupImagePreview('imagen_dresscode_hombres', 'preview-dresscode-hombres');
        setupImagePreview('imagen_dresscode_mujeres', 'preview-dresscode-mujeres');
        
        // Vista previa múltiple para galería
        const galeriaInput = document.getElementById('imagenes_galeria');
        const galeriaPreview = document.getElementById('gallery-previews');
        
        if (galeriaInput && galeriaPreview) {
            galeriaInput.addEventListener('change', function(e) {
                galeriaPreview.innerHTML = '';
                
                Array.from(e.target.files).forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const div = document.createElement('div');
                            div.className = 'gallery-preview-item';
                            div.innerHTML = `
                                <img src="${e.target.result}" alt="Imagen ${index + 1}">
                                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">×</button>
                            `;
                            galeriaPreview.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });
        }
    });

    function eliminarImagenGaleria(imagenId) {
        if (confirm('¿Estás seguro de que quieres eliminar esta imagen?')) {
            // Hacer una petición AJAX para eliminar la imagen
            fetch('eliminar_imagen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    imagen_id: imagenId,
                    invitacion_id: <?php echo $id; ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al eliminar la imagen');
                }
            });
        }
    }
    </script>
</body>
</html>