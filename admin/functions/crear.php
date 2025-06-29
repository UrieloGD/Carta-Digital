<?php
require_once './../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Obtener plantillas disponibles
$plantilla_query = "SELECT id, nombre FROM plantillas WHERE activa = 1";
$plantilla_stmt = $db->prepare($plantilla_query);
$plantilla_stmt->execute();
$plantillas = $plantilla_stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim($_POST['slug']);
    $plantilla_id = $_POST['plantilla_id'];

    // Verificar si ya existe una invitación con el mismo slug
    $check_slug = $db->prepare("SELECT COUNT(*) FROM invitaciones WHERE slug = ?");
    $check_slug->execute([$slug]);
    if ($check_slug->fetchColumn() > 0) {
        die("Ya existe una invitación con ese slug.");
    }

    // NUEVA ESTRUCTURA: Crear carpetas dentro de la plantilla
    $upload_base = "./../../plantillas/plantilla-$plantilla_id/uploads/$slug";
    $secciones = ['hero', 'dedicatoria', 'destacada', 'galeria', 'dresscode'];
    foreach ($secciones as $sec) {
        $path = "$upload_base/$sec";
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    // CORREGIR: Usar valores de $_POST en lugar de $invitacion que no existe
    $frase_historia = $_POST['frase_historia'] ?? "Nuestra historia de amor";
    $padres_novia = $_POST['padres_novia'] ?? '';
    $padres_novio = $_POST['padres_novio'] ?? '';
    $padrinos_novia = $_POST['padrinos_novia'] ?? '';
    $padrinos_novio = $_POST['padrinos_novio'] ?? '';
    $musica_url = $_POST['musica_url'] ?? '';
    $musica_autoplay = $_POST['musica_autoplay'] ?? 1;
    $mostrar_contador = $_POST['mostrar_contador'] ?? 1;

    // FUNCIÓN CORREGIDA para guardar imágenes con nueva estructura
    function guardarImagen($campo, $ruta, $plantilla_id, $slug) {
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
                // NUEVA RUTA: Devolver ruta relativa accesible desde navegador
                $seccion = basename($ruta);
                return "plantillas/plantilla-$plantilla_id/uploads/$slug/$seccion/$nombre";
            } else {
                die("Error al subir la imagen: $campo");
            }
        }
        return null;
    }

    // Guardar imágenes con nueva estructura
    $img_hero = guardarImagen('imagen_hero', "$upload_base/hero", $plantilla_id, $slug);
    $img_dedicatoria = guardarImagen('imagen_dedicatoria', "$upload_base/dedicatoria", $plantilla_id, $slug);
    $img_destacada = guardarImagen('imagen_destacada', "$upload_base/destacada", $plantilla_id, $slug);

    // Insertar en tabla principal - CORREGIDO: usar las variables correctas
    $query = "INSERT INTO invitaciones (plantilla_id, slug, nombres_novios, fecha_evento, hora_evento, 
            ubicacion, direccion_completa, historia, frase_historia, dresscode, texto_rsvp, 
            mensaje_footer, firma_footer, imagen_hero, imagen_dedicatoria, imagen_destacada,
            padres_novia, padres_novio, padrinos_novia, padrinos_novio, musica_url, musica_autoplay, mostrar_contador) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($query);
    $stmt->execute([
        $plantilla_id,
        $slug,
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
        $img_hero,
        $img_dedicatoria,
        $img_destacada,
        $padres_novia,
        $padres_novio,
        $padrinos_novia,
        $padrinos_novio,
        $musica_url,
        $musica_autoplay,
        $mostrar_contador
    ]);

    $invitacion_id = $db->lastInsertId();

    // Procesar ubicaciones
    if (!empty($_POST['ceremonia_lugar'])) {
        $ubicacion_stmt = $db->prepare("INSERT INTO invitacion_ubicaciones (invitacion_id, tipo, nombre_lugar, direccion, hora_inicio, google_maps_url) VALUES (?, ?, ?, ?, ?, ?)");
        $ubicacion_stmt->execute([
            $invitacion_id,
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
            $invitacion_id,
            'evento',
            $_POST['evento_lugar'],
            $_POST['evento_direccion'] ?? '',
            $_POST['evento_hora'] ?? null,
            $_POST['evento_maps'] ?? ''
        ]);
    }

    // Galería con nueva estructura
    $galeria_paths = [];
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
                        // NUEVA RUTA para galería
                        $galeria_paths[] = "plantillas/plantilla-$plantilla_id/uploads/$slug/galeria/$nombre_final";
                    }
                }
            }
        }

        // Guardar en tabla (una fila por imagen)
        if (!empty($galeria_paths)) {
            $galeria_stmt = $db->prepare("INSERT INTO invitacion_galeria (invitacion_id, ruta) VALUES (?, ?)");
            foreach ($galeria_paths as $ruta_img) {
                $galeria_stmt->execute([$invitacion_id, $ruta_img]);
            }
        }
    }

    // Dresscode con nueva estructura
    $img_dresscode_hombres = guardarImagen('imagen_dresscode_hombres', "$upload_base/dresscode", $plantilla_id, $slug);
    $img_dresscode_mujeres = guardarImagen('imagen_dresscode_mujeres', "$upload_base/dresscode", $plantilla_id, $slug);

    if ($img_dresscode_hombres || $img_dresscode_mujeres) {
        $dresscode_stmt = $db->prepare("INSERT INTO invitacion_dresscode (invitacion_id, hombres, mujeres) VALUES (?, ?, ?)");
        $dresscode_stmt->execute([$invitacion_id, $img_dresscode_hombres ?? '', $img_dresscode_mujeres ?? '']);
    }
    
    // Cronograma (solo si hay datos)
    if (isset($_POST['cronograma_hora']) && !empty(array_filter($_POST['cronograma_hora']))) {
        $cronograma_stmt = $db->prepare("INSERT INTO invitacion_cronograma (invitacion_id, hora, evento, descripcion, icono) VALUES (?, ?, ?, ?, ?)");
        foreach ($_POST['cronograma_hora'] as $i => $hora) {
            if (!empty($hora) && !empty($_POST['cronograma_evento'][$i])) {
                $cronograma_stmt->execute([
                    $invitacion_id,
                    $hora,
                    $_POST['cronograma_evento'][$i],
                    $_POST['cronograma_descripcion'][$i] ?? '',
                    $_POST['cronograma_icono'][$i] ?? 'anillos'
                ]);
            }
        }
    }

    // FAQs (solo si hay datos)
    if (isset($_POST['faq_pregunta']) && !empty(array_filter($_POST['faq_pregunta']))) {
        $faq_stmt = $db->prepare("INSERT INTO invitacion_faq (invitacion_id, pregunta, respuesta) VALUES (?, ?, ?)");
        foreach ($_POST['faq_pregunta'] as $i => $pregunta) {
            if (!empty($pregunta) && !empty($_POST['faq_respuesta'][$i])) {
                $faq_stmt->execute([
                    $invitacion_id,
                    $pregunta,
                    $_POST['faq_respuesta'][$i]
                ]);
            }
        }
    }

    header("Location: ./../index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Invitación</title>
    <link rel="stylesheet" href="./../css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Crear Nueva Invitación</h1>
            <a href="./../index.php" class="btn btn-secondary">Volver</a>
        </header>

        <!-- CORRECCIÓN: Una sola etiqueta form con enctype correcto -->
        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <div class="form-section">
                <h3>Plantilla Base</h3>
                <div class="form-group">
                    <label for="plantilla_id">Selecciona una plantilla</label>
                    <select name="plantilla_id" id="plantilla_id" required>
                        <option value="">-- Elegir plantilla --</option>
                        <?php foreach ($plantillas as $plantilla): ?>
                            <option value="<?= $plantilla['id'] ?>">
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
                        <input type="text" id="nombres_novios" name="nombres_novios" required>
                    </div>
                    <div class="form-group">
                        <label for="slug">URL (slug)</label>
                        <input type="text" id="slug" name="slug" required placeholder="ej: victoria-matthew-2025">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_evento">Fecha del Evento</label>
                        <input type="date" id="fecha_evento" name="fecha_evento" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_evento">Hora del Evento</label>
                        <input type="time" id="hora_evento" name="hora_evento" required>
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
                            <input type="text" id="ceremonia_lugar" name="ceremonia_lugar" placeholder="Iglesia San José">
                        </div>
                        <div class="form-group">
                            <label for="ceremonia_hora">Hora de la Ceremonia</label>
                            <input type="time" id="ceremonia_hora" name="ceremonia_hora">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ceremonia_direccion">Dirección de la Ceremonia</label>
                        <input type="text" id="ceremonia_direccion" name="ceremonia_direccion" placeholder="Calle Principal 123">
                    </div>
                    <div class="form-group">
                        <label for="ceremonia_maps">URL de Google Maps (Ceremonia)</label>
                        <input type="url" id="ceremonia_maps" name="ceremonia_maps" placeholder="https://maps.google.com/?q=...">
                    </div>
                </div>
                
                <div class="ubicacion-section">
                    <h4>Evento/Recepción</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="evento_lugar">Lugar del Evento</label>
                            <input type="text" id="evento_lugar" name="evento_lugar" placeholder="Salón de Eventos Villa Jardín">
                        </div>
                        <div class="form-group">
                            <label for="evento_hora">Hora del Evento</label>
                            <input type="time" id="evento_hora" name="evento_hora">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="evento_direccion">Dirección del Evento</label>
                        <input type="text" id="evento_direccion" name="evento_direccion" placeholder="Avenida Central 456">
                    </div>
                    <div class="form-group">
                        <label for="evento_maps">URL de Google Maps (Evento)</label>
                        <input type="url" id="evento_maps" name="evento_maps" placeholder="https://maps.google.com/?q=...">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Contenido Personalizado</h3>
                
                <div class="form-group">
                    <label for="historia">Historia de Amor</label>
                    <textarea id="historia" name="historia" rows="4" placeholder="Cuenta vuestra historia de amor..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="frase_historia">Frase para la Historia</label>
                    <input type="text" id="frase_historia" name="frase_historia" placeholder="Ej: Nuestra historia de amor">
                </div>
                
                <div class="form-group">
                    <label for="dresscode">Descripción del Código de Vestimenta</label>
                    <textarea id="dresscode" name="dresscode" rows="2" placeholder="Por favor, viste atuendo elegante..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="texto_rsvp">Texto para RSVP</label>
                    <input type="text" id="texto_rsvp" name="texto_rsvp" placeholder="Confirma tu asistencia antes del...">
                </div>
            </div>

            <div class="form-section">
                <h3>Información Familiar</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="padres_novia">Padres de la Novia</label>
                        <input type="text" id="padres_novia" name="padres_novia" placeholder="Nombres de los padres de la novia">
                    </div>
                    <div class="form-group">
                        <label for="padres_novio">Padres del Novio</label>
                        <input type="text" id="padres_novio" name="padres_novio" placeholder="Nombres de los padres del novio">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="padrinos_novia">Padrinos de la Novia</label>
                        <input type="text" id="padrinos_novia" name="padrinos_novia" placeholder="Nombres de los padrinos de la novia">
                    </div>
                    <div class="form-group">
                        <label for="padrinos_novio">Padrinos del Novio</label>
                        <input type="text" id="padrinos_novio" name="padrinos_novio" placeholder="Nombres de los padrinos del novio">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Configuraciones Adicionales</h3>
                
                <div class="form-group">
                    <label for="musica_url">URL de Música de Fondo</label>
                    <input type="url" id="musica_url" name="musica_url" placeholder="https://ejemplo.com/musica.mp3">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="musica_autoplay">Reproducir música automáticamente</label>
                        <select id="musica_autoplay" name="musica_autoplay">
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mostrar_contador">Mostrar contador regresivo</label>
                        <select id="mostrar_contador" name="mostrar_contador">
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Mensajes Personalizados</h3>
                
                <div class="form-group">
                    <label for="mensaje_footer">Mensaje del Footer</label>
                    <textarea id="mensaje_footer" name="mensaje_footer" rows="2" placeholder="El amor es la fuerza más poderosa del mundo..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="firma_footer">Firma del Footer</label>
                    <input type="text" id="firma_footer" name="firma_footer" placeholder="Con amor, Victoria & Matthew">
                </div>
            </div>

            <div class="form-section">
                <h3>Imágenes</h3>
                
                <div class="form-group">
                    <label for="imagen_hero">Imagen Hero</label>
                    <div class="image-upload-container">
                        <div class="file-input-wrapper">
                            <input type="file" name="imagen_hero" id="imagen_hero" accept="image/*" onchange="previewImage(this, 'hero-preview')">
                            <label for="imagen_hero" class="file-input-label">
                                <i>📷</i> Seleccionar imagen Hero
                            </label>
                        </div>
                        <div id="hero-preview" class="image-preview-container">
                            <div class="image-placeholder">
                                <i>🖼️</i>
                                <span>La imagen aparecerá aquí</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="imagen_dedicatoria">Imagen Dedicatoria</label>
                    <div class="image-upload-container">
                        <div class="file-input-wrapper">
                            <input type="file" name="imagen_dedicatoria" id="imagen_dedicatoria" accept="image/*" onchange="previewImage(this, 'dedicatoria-preview')">
                            <label for="imagen_dedicatoria" class="file-input-label">
                                <i>📷</i> Seleccionar imagen Dedicatoria
                            </label>
                        </div>
                        <div id="dedicatoria-preview" class="image-preview-container">
                            <div class="image-placeholder">
                                <i>💕</i>
                                <span>La imagen aparecerá aquí</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="imagen_destacada">Imagen Destacada</label>
                    <div class="image-upload-container">
                        <div class="file-input-wrapper">
                            <input type="file" name="imagen_destacada" id="imagen_destacada" accept="image/*" onchange="previewImage(this, 'destacada-preview')">
                            <label for="imagen_destacada" class="file-input-label">
                                <i>📷</i> Seleccionar imagen Destacada
                            </label>
                        </div>
                        <div id="destacada-preview" class="image-preview-container">
                            <div class="image-placeholder">
                                <i>⭐</i>
                                <span>La imagen aparecerá aquí</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Galería -->
            <div class="form-section">
                <h3>Galería de imágenes</h3>
                <div class="form-group">
                    <label for="imagenes_galeria">Imágenes de Galería (puedes seleccionar varias)</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="imagenes_galeria[]" id="imagenes_galeria" accept="image/*" multiple onchange="previewGallery(this)">
                        <label for="imagenes_galeria" class="file-input-label">
                            <i>🖼️</i> Seleccionar imágenes para galería
                        </label>
                    </div>
                    <div id="gallery-preview" class="gallery-preview-container"></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Cronograma</h3>
                <div id="cronograma-container">
                    <div class="cronograma-item">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Hora</label>
                                <input type="time" name="cronograma_hora[]" required>
                            </div>
                            <div class="form-group">
                                <label>Evento</label>
                                <input type="text" name="cronograma_evento[]" required>
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
                </div>
                <button type="button" onclick="agregarCronograma()" class="btn btn-add">Agregar Evento</button>
            </div>

            <!-- Dresscode -->
            <div class="form-section">
                <h3>Imágenes para DressCode</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="imagen_dresscode_hombres">Imagen Dresscode Hombres</label>
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
                                    <span>Imagen para hombres</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="imagen_dresscode_mujeres">Imagen Dresscode Mujeres</label>
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
                                    <span>Imagen para mujeres</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Preguntas Frecuentes</h3>
                <div id="faq-container">
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
                </div>
                <button type="button" onclick="agregarFAQ()" class="btn btn-add">Agregar FAQ</button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Crear Invitación</button>
                <a href="./../index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <script src="./../js/crear.js"></script>
</body>
</html>