<?php
require_once './../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$id = $_GET['id'] ?? 0;

if (!$id) {
    header("Location: ./../index.php");
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
    header("Location: ./../index.php");
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

// FUNCIÓN CORREGIDA para guardar imágenes usando el slug
function guardarImagen($campo, $plantilla_id, $slug, $seccion) {
    if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
        // Verificar que el archivo sea una imagen
        $imageFileType = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($imageFileType, $allowed_types)) {
            die("Solo se permiten archivos JPG, JPEG, PNG, GIF y WEBP.");
        }
        
        // RUTA CORREGIDA: Desde la raíz del proyecto usando el slug
        $ruta_fisica = __DIR__ . "/../../plantillas/plantilla-$plantilla_id/uploads/$slug/$seccion";
        
        // Verificar que la carpeta existe
        if (!is_dir($ruta_fisica)) {
            mkdir($ruta_fisica, 0777, true);
        }
        
        // Generar nombre único para evitar conflictos
        $nombre = uniqid() . '.' . $imageFileType;
        $destino = "$ruta_fisica/$nombre";
        
        // Mover el archivo
        if (move_uploaded_file($_FILES[$campo]['tmp_name'], $destino)) {
            // RETORNAR RUTA RELATIVA para guardar en BD
            return "./plantillas/plantilla-$plantilla_id/uploads/$slug/$seccion/$nombre";
        } else {
            die("Error al subir la imagen: $campo");
        }
    }
    return null;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $plantilla_id = $_POST['plantilla_id'];
        $slug = $invitacion['slug']; // Mantener el slug original
        
        // Obtener valores del formulario con valores por defecto
        $frase_historia = $_POST['frase_historia'] ?? $invitacion['frase_historia'];
        $padres_novia = $_POST['padres_novia'] ?? $invitacion['padres_novia'];
        $padres_novio = $_POST['padres_novio'] ?? $invitacion['padres_novio'];
        $padrinos_novia = $_POST['padrinos_novia'] ?? $invitacion['padrinos_novia'];
        $padrinos_novio = $_POST['padrinos_novio'] ?? $invitacion['padrinos_novio'];
        $musica_url = $_POST['musica_url'] ?? $invitacion['musica_url'];
        $musica_autoplay = $_POST['musica_autoplay'] ?? $invitacion['musica_autoplay'];
        $mostrar_contador = $_POST['mostrar_contador'] ?? $invitacion['mostrar_contador'];

        // Manejar imágenes principales con estructura corregida
        $img_hero = guardarImagen('imagen_hero', $plantilla_id, $slug, 'hero');
        $img_dedicatoria = guardarImagen('imagen_dedicatoria', $plantilla_id, $slug, 'dedicatoria');
        $img_destacada = guardarImagen('imagen_destacada', $plantilla_id, $slug, 'destacada');
        
        // Actualizar invitación principal con todos los campos
        $update_query = "UPDATE invitaciones SET 
                        plantilla_id = ?, nombres_novios = ?, fecha_evento = ?, hora_evento = ?, 
                        ubicacion = ?, direccion_completa = ?, historia = ?, frase_historia = ?,
                        dresscode = ?, texto_rsvp = ?, mensaje_footer = ?, firma_footer = ?,
                        padres_novia = ?, padres_novio = ?, padrinos_novia = ?, padrinos_novio = ?,
                        musica_url = ?, musica_autoplay = ?, mostrar_contador = ?";
        
        $params = [
            $plantilla_id,
            $_POST['nombres_novios'],
            $_POST['fecha_evento'],
            $_POST['hora_evento'],
            $_POST['ubicacion'] ?? '',
            $_POST['direccion_completa'] ?? '',
            $_POST['historia'] ?? '',
            $frase_historia,
            $_POST['dresscode'] ?? '',
            $_POST['texto_rsvp'] ?? '',
            $_POST['mensaje_footer'] ?? '',
            $_POST['firma_footer'] ?? '',
            $padres_novia,
            $padres_novio,
            $padrinos_novia,
            $padrinos_novio,
            $musica_url,
            $musica_autoplay,
            $mostrar_contador
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

        // Eliminar y recrear ubicaciones
        $db->prepare("DELETE FROM invitacion_ubicaciones WHERE invitacion_id = ?")->execute([$id]);
        
        // Procesar ubicaciones
        if (!empty($_POST['ceremonia_lugar'])) {
            $ubicacion_stmt = $db->prepare("INSERT INTO invitacion_ubicaciones (invitacion_id, tipo, nombre_lugar, direccion, hora_inicio, google_maps_url) VALUES (?, ?, ?, ?, ?, ?)");
            $ubicacion_stmt->execute([
                $id,
                'ceremonia',
                $_POST['ceremonia_lugar'],
                $_POST['ceremonia_direccion'] ?? '',
                $_POST['ceremonia_hora'] ?? null,
                $_POST['ceremonia_maps'] ?? ''
            ]);
        }

        if (!empty($_POST['evento_lugar'])) {
            $ubicacion_stmt = $db->prepare("INSERT INTO invitacion_ubicaciones (invitacion_id, tipo, nombre_lugar, direccion, hora_inicio, google_maps_url) VALUES (?, ?, ?, ?, ?, ?)");
            $ubicacion_stmt->execute([
                $id,
                'evento',
                $_POST['evento_lugar'],
                $_POST['evento_direccion'] ?? '',
                $_POST['evento_hora'] ?? null,
                $_POST['evento_maps'] ?? ''
            ]);
        }
        
        // Manejar galería de imágenes nuevas con estructura corregida
        if (!empty($_FILES['imagenes_galeria']['name'][0])) {
            $galeria_dir = __DIR__ . "/../../plantillas/plantilla-$plantilla_id/uploads/$slug/galeria";
            if (!is_dir($galeria_dir)) mkdir($galeria_dir, 0777, true);

            foreach ($_FILES['imagenes_galeria']['name'] as $i => $nombre) {
                if ($_FILES['imagenes_galeria']['error'][$i] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $nombre_final = uniqid() . ".$ext";
                        $ruta_destino = "$galeria_dir/$nombre_final";
                        if (move_uploaded_file($_FILES['imagenes_galeria']['tmp_name'][$i], $ruta_destino)) {
                            // RUTA RELATIVA para BD
                            $galeria_insert = $db->prepare("INSERT INTO invitacion_galeria (invitacion_id, ruta) VALUES (?, ?)");
                            $galeria_insert->execute([$id, "./plantillas/plantilla-$plantilla_id/uploads/$slug/galeria/$nombre_final"]);
                        }
                    }
                }
            }
        }

        // Manejar dresscode con estructura corregida
        $img_dresscode_hombres = guardarImagen('imagen_dresscode_hombres', $plantilla_id, $slug, 'dresscode');
        $img_dresscode_mujeres = guardarImagen('imagen_dresscode_mujeres', $plantilla_id, $slug, 'dresscode');

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
        
        // Cronograma (solo si hay datos)
        if (isset($_POST['cronograma_hora']) && !empty(array_filter($_POST['cronograma_hora']))) {
            $cronograma_stmt = $db->prepare("INSERT INTO invitacion_cronograma (invitacion_id, hora, evento, descripcion, icono) VALUES (?, ?, ?, ?, ?)");
            foreach ($_POST['cronograma_hora'] as $i => $hora) {
                if (!empty($hora) && !empty($_POST['cronograma_evento'][$i])) {
                    $cronograma_stmt->execute([
                        $id,
                        $hora,
                        $_POST['cronograma_evento'][$i],
                        $_POST['cronograma_descripcion'][$i] ?? '',
                        $_POST['cronograma_icono'][$i] ?? 'anillos'
                    ]);
                }
            }
        }
        
        // Eliminar FAQs existentes y agregar nuevos
        $delete_faq = "DELETE FROM invitacion_faq WHERE invitacion_id = ?";
        $db->prepare($delete_faq)->execute([$id]);
        
        // FAQs (solo si hay datos)
        if (isset($_POST['faq_pregunta']) && !empty(array_filter($_POST['faq_pregunta']))) {
            $faq_stmt = $db->prepare("INSERT INTO invitacion_faq (invitacion_id, pregunta, respuesta) VALUES (?, ?, ?)");
            foreach ($_POST['faq_pregunta'] as $i => $pregunta) {
                if (!empty($pregunta) && !empty($_POST['faq_respuesta'][$i])) {
                    $faq_stmt->execute([
                        $id,
                        $pregunta,
                        $_POST['faq_respuesta'][$i]
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
    <link rel="stylesheet" href="./../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Editar Invitación: <?php echo htmlspecialchars($invitacion['nombres_novios']); ?></h1>
            <div class="header-actions">
                <a href="./../../invitacion.php?slug=<?php echo $invitacion['slug']; ?>" class="btn btn-preview" target="_blank">Vista Previa</a>
                <a href="./../index.php" class="btn btn-secondary">Volver</a>
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
            </div>

            <div class="form-section">
                <h3>Ubicaciones del Evento</h3>
                
                <div class="ubicacion-section">
                    <h4>Ceremonia</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="ceremonia_lugar">Lugar de la Ceremonia</label>
                            <input type="text" id="ceremonia_lugar" name="ceremonia_lugar" 
                                value="<?php echo $ceremonia ? htmlspecialchars($ceremonia['nombre_lugar']) : ''; ?>" 
                                placeholder="Iglesia San José">
                        </div>
                        <div class="form-group">
                            <label for="ceremonia_hora">Hora de la Ceremonia</label>
                            <input type="time" id="ceremonia_hora" name="ceremonia_hora" 
                                value="<?php echo $ceremonia ? $ceremonia['hora_inicio'] : ''; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ceremonia_direccion">Dirección de la Ceremonia</label>
                        <input type="text" id="ceremonia_direccion" name="ceremonia_direccion" 
                            value="<?php echo $ceremonia ? htmlspecialchars($ceremonia['direccion']) : ''; ?>" 
                            placeholder="Calle Principal 123">
                    </div>
                    <div class="form-group">
                        <label for="ceremonia_maps">URL de Google Maps (Ceremonia)</label>
                        <input type="url" id="ceremonia_maps" name="ceremonia_maps" 
                            value="<?php echo $ceremonia ? htmlspecialchars($ceremonia['google_maps_url']) : ''; ?>" 
                            placeholder="https://maps.google.com/?q=...">
                    </div>
                </div>
                
                <div class="ubicacion-section">
                    <h4>Evento/Recepción</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="evento_lugar">Lugar del Evento</label>
                            <input type="text" id="evento_lugar" name="evento_lugar" 
                                value="<?php echo $evento ? htmlspecialchars($evento['nombre_lugar']) : ''; ?>" 
                                placeholder="Salón de Eventos Villa Jardín">
                        </div>
                        <div class="form-group">
                            <label for="evento_hora">Hora del Evento</label>
                            <input type="time" id="evento_hora" name="evento_hora" 
                                value="<?php echo $evento ? $evento['hora_inicio'] : ''; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="evento_direccion">Dirección del Evento</label>
                        <input type="text" id="evento_direccion" name="evento_direccion" 
                            value="<?php echo $evento ? htmlspecialchars($evento['direccion']) : ''; ?>" 
                            placeholder="Avenida Central 456">
                    </div>
                    <div class="form-group">
                        <label for="evento_maps">URL de Google Maps (Evento)</label>
                        <input type="url" id="evento_maps" name="evento_maps" 
                            value="<?php echo $evento ? htmlspecialchars($evento['google_maps_url']) : ''; ?>" 
                            placeholder="https://maps.google.com/?q=...">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Contenido Personalizado</h3>
                
                <div class="form-group">
                    <label for="historia">Historia de Amor</label>
                    <textarea id="historia" name="historia" rows="4" placeholder="Cuenta vuestra historia de amor..."><?php echo htmlspecialchars($invitacion['historia']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="frase_historia">Frase para la Historia</label>
                    <input type="text" id="frase_historia" name="frase_historia" 
                        value="<?php echo htmlspecialchars($invitacion['frase_historia']); ?>" 
                        placeholder="Ej: Nuestra historia de amor">
                </div>
                
                <div class="form-group">
                    <label for="dresscode">Descripción del Código de Vestimenta</label>
                    <textarea id="dresscode" name="dresscode" rows="2" placeholder="Por favor, viste atuendo elegante..."><?php echo htmlspecialchars($invitacion['dresscode']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="texto_rsvp">Texto para RSVP</label>
                    <input type="text" id="texto_rsvp" name="texto_rsvp" 
                        value="<?php echo htmlspecialchars($invitacion['texto_rsvp']); ?>" 
                        placeholder="Confirma tu asistencia antes del...">
                </div>
            </div>

            <div class="form-section">
                <h3>Información Familiar</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="padres_novia">Padres de la Novia</label>
                        <input type="text" id="padres_novia" name="padres_novia" 
                            value="<?php echo htmlspecialchars($invitacion['padres_novia']); ?>" 
                            placeholder="Nombres de los padres de la novia">
                    </div>
                    <div class="form-group">
                        <label for="padres_novio">Padres del Novio</label>
                        <input type="text" id="padres_novio" name="padres_novio" 
                            value="<?php echo htmlspecialchars($invitacion['padres_novio']); ?>" 
                            placeholder="Nombres de los padres del novio">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="padrinos_novia">Padrinos de la Novia</label>
                        <input type="text" id="padrinos_novia" name="padrinos_novia" 
                            value="<?php echo htmlspecialchars($invitacion['padrinos_novia']); ?>" 
                            placeholder="Nombres de los padrinos de la novia">
                    </div>
                    <div class="form-group">
                        <label for="padrinos_novio">Padrinos del Novio</label>
                        <input type="text" id="padrinos_novio" name="padrinos_novio" 
                            value="<?php echo htmlspecialchars($invitacion['padrinos_novio']); ?>" 
                            placeholder="Nombres de los padrinos del novio">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Configuraciones Adicionales</h3>
                
                <div class="form-group">
                    <label for="musica_url">URL de Música de Fondo</label>
                    <input type="url" id="musica_url" name="musica_url" 
                        value="<?php echo htmlspecialchars($invitacion['musica_url']); ?>" 
                        placeholder="https://ejemplo.com/musica.mp3">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="musica_autoplay">Reproducir música automáticamente</label>
                        <select id="musica_autoplay" name="musica_autoplay">
                            <option value="1" <?php echo $invitacion['musica_autoplay'] == 1 ? 'selected' : ''; ?>>Sí</option>
                            <option value="0" <?php echo $invitacion['musica_autoplay'] == 0 ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mostrar_contador">Mostrar contador regresivo</label>
                        <select id="mostrar_contador" name="mostrar_contador">
                            <option value="1" <?php echo $invitacion['mostrar_contador'] == 1 ? 'selected' : ''; ?>>Sí</option>
                            <option value="0" <?php echo $invitacion['mostrar_contador'] == 0 ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Mensajes Personalizados</h3>
                
                <div class="form-group">
                    <label for="mensaje_footer">Mensaje del Footer</label>
                    <textarea id="mensaje_footer" name="mensaje_footer" rows="2" placeholder="El amor es la fuerza más poderosa del mundo..."><?php echo htmlspecialchars($invitacion['mensaje_footer']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="firma_footer">Firma del Footer</label>
                    <input type="text" id="firma_footer" name="firma_footer" 
                        value="<?php echo htmlspecialchars($invitacion['firma_footer']); ?>" 
                        placeholder="Con amor, Victoria & Matthew">
                </div>
            </div>

            <div class="form-section">
                <h3>Imágenes</h3>
                
                <div class="form-group">
                    <label for="imagen_hero">Imagen Hero</label>
                    <?php if ($invitacion['imagen_hero']): ?>
                        <div class="current-image">
                            <img src="../../<?php echo $invitacion['imagen_hero']; ?>" alt="Imagen actual" style="max-width: 200px; height: auto;">
                            <p><small>Imagen actual</small></p>
                        </div>
                    <?php endif; ?>
                    <div class="image-upload-container">
                        <div class="file-input-wrapper">
                            <input type="file" name="imagen_hero" id="imagen_hero" accept="image/*" onchange="previewImage(this, 'hero-preview')">
                            <label for="imagen_hero" class="file-input-label">
                                <i>📷</i> Seleccionar nueva imagen Hero
                            </label>
                        </div>
                        <div id="hero-preview" class="image-preview-container">
                            <div class="image-placeholder">
                                <i>🖼️</i>
                                <span>La nueva imagen aparecerá aquí</span>
                            </div>
                        </div>
                    </div>
                    <small class="form-note">Deja vacío para mantener la imagen actual</small>
                </div>

                <div class="form-group">
                    <label for="imagen_dedicatoria">Imagen Dedicatoria</label>
                    <?php if ($invitacion['imagen_dedicatoria']): ?>
                        <div class="current-image">
                            <img src="../../<?php echo $invitacion['imagen_dedicatoria']; ?>" alt="Imagen actual" style="max-width: 200px; height: auto;">
                            <p><small>Imagen actual</small></p>
                        </div>
                    <?php endif; ?>
                    <div class="image-upload-container">
                        <div class="file-input-wrapper">
                            <input type="file" name="imagen_dedicatoria" id="imagen_dedicatoria" accept="image/*" onchange="previewImage(this, 'dedicatoria-preview')">
                            <label for="imagen_dedicatoria" class="file-input-label">
                                <i>📷</i> Seleccionar nueva imagen Dedicatoria
                            </label>
                        </div>
                        <div id="dedicatoria-preview" class="image-preview-container">
                            <div class="image-placeholder">
                                <i>💕</i>
                                <span>La nueva imagen aparecerá aquí</span>
                            </div>
                        </div>
                    </div>
                    <small class="form-note">Deja vacío para mantener la imagen actual</small>
                </div>

                <div class="form-group">
                    <label for="imagen_destacada">Imagen Destacada</label>
                    <?php if ($invitacion['imagen_destacada']): ?>
                        <div class="current-image">
                            <img src="../../<?php echo $invitacion['imagen_destacada']; ?>" alt="Imagen actual" style="max-width: 200px; height: auto;">
                            <p><small>Imagen actual</small></p>
                        </div>
                    <?php endif; ?>
                    <div class="image-upload-container">
                        <div class="file-input-wrapper">
                            <input type="file" name="imagen_destacada" id="imagen_destacada" accept="image/*" onchange="previewImage(this, 'destacada-preview')">
                            <label for="imagen_destacada" class="file-input-label">
                                <i>📷</i> Seleccionar nueva imagen Destacada
                            </label>
                        </div>
                        <div id="destacada-preview" class="image-preview-container">
                            <div class="image-placeholder">
                                <i>⭐</i>
                                <span>La nueva imagen aparecerá aquí</span>
                            </div>
                        </div>
                    </div>
                    <small class="form-note">Deja vacío para mantener la imagen actual</small>
                </div>
            </div>

            <!-- Galería -->
            <div class="form-section">
                <h3>Galería de imágenes</h3>
                
                <?php if (!empty($galeria)): ?>
                    <div class="current-gallery">
                        <h4>Imágenes actuales:</h4>
                        <div class="gallery-grid">
                            <?php foreach ($galeria as $imagen): ?>
                                <div class="gallery-item">
                                    <img src="../../<?php echo $imagen['ruta']; ?>" alt="Imagen galería">
                                    <button type="button" onclick="eliminarImagenGaleria(<?php echo $imagen['id']; ?>)" class="btn btn-danger btn-sm">
                                        🗑️ Eliminar
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="imagenes_galeria">Agregar nuevas imágenes a la galería (puedes seleccionar varias)</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="imagenes_galeria[]" id="imagenes_galeria" accept="image/*" multiple onchange="previewGallery(this)">
                        <label for="imagenes_galeria" class="file-input-label">
                            <i>🖼️</i> Seleccionar imágenes para galería
                        </label>
                    </div>
                    <div id="gallery-preview" class="gallery-preview-container"></div>
                    <small class="form-note">Puedes seleccionar múltiples imágenes</small>
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
                <button type="button" onclick="agregarCronograma()" class="btn btn-add">Agregar Evento</button>
            </div>

            <!-- Dresscode -->
            <div class="form-section">
                <h3>Imágenes para DressCode</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="imagen_dresscode_hombres">Imagen Dresscode Hombres</label>
                        <?php if ($dresscode_data && $dresscode_data['hombres']): ?>
                            <div class="current-image">
                                <img src="../../<?php echo $dresscode_data['hombres']; ?>" alt="Imagen actual" style="max-width: 150px; height: auto;">
                                <p><small>Imagen actual</small></p>
                            </div>
                        <?php endif; ?>
                        <div class="image-upload-container">
                            <div class="file-input-wrapper">
                                <input type="file" name="imagen_dresscode_hombres" id="imagen_dresscode_hombres" accept="image/*" onchange="previewImage(this, 'dresscode-hombres-preview')">
                                <label for="imagen_dresscode_hombres" class="file-input-label">
                                    <i>👔</i> Seleccionar imagen Hombres
                                </label>
                            </div>
                            <div id="dresscode-hombres-preview" class="image-preview-container">
                                <div class="image-placeholder">
                                    <i>👨</i>
                                    <span>Nueva imagen para hombres</span>
                                </div>
                            </div>
                        </div>
                        <small class="form-note">Deja vacío para mantener la imagen actual</small>
                    </div>
                    <div class="form-group">
                        <label for="imagen_dresscode_mujeres">Imagen Dresscode Mujeres</label>
                        <?php if ($dresscode_data && $dresscode_data['mujeres']): ?>
                            <div class="current-image">
                                <img src="../../<?php echo $dresscode_data['mujeres']; ?>" alt="Imagen actual" style="max-width: 150px; height: auto;">
                                <p><small>Imagen actual</small></p>
                            </div>
                        <?php endif; ?>
                        <div class="image-upload-container">
                            <div class="file-input-wrapper">
                                <input type="file" name="imagen_dresscode_mujeres" id="imagen_dresscode_mujeres" accept="image/*" onchange="previewImage(this, 'dresscode-mujeres-preview')">
                                <label for="imagen_dresscode_mujeres" class="file-input-label">
                                    <i>👗</i> Seleccionar imagen Mujeres
                                </label>
                            </div>
                            <div id="dresscode-mujeres-preview" class="image-preview-container">
                                <div class="image-placeholder">
                                    <i>👩</i>
                                    <span>Nueva imagen para mujeres</span>
                                </div>
                            </div>
                        </div>
                        <small class="form-note">Deja vacío para mantener la imagen actual</small>
                    </div>
                </div>
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
                <a href="./../index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
    <script src="./../js/editar.js"></script>
</body>
</html>