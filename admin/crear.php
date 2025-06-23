<?php
require_once '../config/database.php';

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

    // Verificar si ya existe una invitación con el mismo slug
    $check_slug = $db->prepare("SELECT COUNT(*) FROM invitaciones WHERE slug = ?");
    $check_slug->execute([$slug]);
    if ($check_slug->fetchColumn() > 0) {
        die("Ya existe una invitación con ese slug.");
    }

    // Crear carpetas de subida
    $upload_base = "../uploads/$slug";
    $secciones = ['hero', 'dedicatoria', 'destacada', 'galeria', 'dresscode'];
    foreach ($secciones as $sec) {
        $path = "$upload_base/$sec";
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    // FUNCIÓN CORREGIDA para guardar imágenes
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

    // Guardar imágenes con debugging
    $img_hero = guardarImagen('imagen_hero', "$upload_base/hero");
    $img_dedicatoria = guardarImagen('imagen_dedicatoria', "$upload_base/dedicatoria");
    $img_destacada = guardarImagen('imagen_destacada', "$upload_base/destacada");

    // Debug: mostrar qué imágenes se guardaron
    // echo "Hero: " . ($img_hero ?? 'No subida') . "<br>";
    // echo "Dedicatoria: " . ($img_dedicatoria ?? 'No subida') . "<br>";
    // echo "Destacada: " . ($img_destacada ?? 'No subida') . "<br>";
    // exit; // Descomenta estas líneas para hacer debug

    // Insertar en tabla principal
    $query = "INSERT INTO invitaciones (plantilla_id, slug, nombres_novios, fecha_evento, hora_evento, 
              ubicacion, direccion_completa, coordenadas, historia, dresscode, texto_rsvp, 
              mensaje_footer, firma_footer, imagen_hero, imagen_dedicatoria, imagen_destacada) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        $_POST['plantilla_id'],
        $slug,
        $_POST['nombres_novios'],
        $_POST['fecha_evento'],
        $_POST['hora_evento'],
        $_POST['ubicacion'] ?? '',
        $_POST['direccion_completa'] ?? '',
        $_POST['coordenadas'] ?? '',
        $_POST['historia'] ?? '',
        $_POST['dresscode'] ?? '',
        $_POST['texto_rsvp'] ?? '',
        $_POST['mensaje_footer'] ?? '',
        $_POST['firma_footer'] ?? '',
        $img_hero,
        $img_dedicatoria,
        $img_destacada
    ]);

    $invitacion_id = $db->lastInsertId();

     // Galería
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
                        $galeria_paths[] = "uploads/$slug/galeria/$nombre_final";
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

    // Dresscode
    $img_dresscode_hombres = guardarImagen('imagen_dresscode_hombres', "$upload_base/dresscode");
    $img_dresscode_mujeres = guardarImagen('imagen_dresscode_mujeres', "$upload_base/dresscode");

    if ($img_dresscode_hombres || $img_dresscode_mujeres) {
        $dresscode_stmt = $db->prepare("INSERT INTO invitacion_dresscode (invitacion_id, hombres, mujeres) VALUES (?, ?, ?)");
        $dresscode_stmt->execute([$invitacion_id, $img_dresscode_hombres ?? '', $img_dresscode_mujeres ?? '']);
    }
    
    // Cronograma
    if (isset($_POST['cronograma_hora'])) {
        $cronograma_stmt = $db->prepare("INSERT INTO invitacion_cronograma (invitacion_id, hora, evento, descripcion, icono) VALUES (?, ?, ?, ?, ?)");
        foreach ($_POST['cronograma_hora'] as $i => $hora) {
            $cronograma_stmt->execute([
                $invitacion_id,
                $hora,
                $_POST['cronograma_evento'][$i],
                $_POST['cronograma_descripcion'][$i],
                $_POST['cronograma_icono'][$i]
            ]);
        }
    }

    // FAQs
    if (isset($_POST['faq_pregunta'])) {
        $faq_stmt = $db->prepare("INSERT INTO invitacion_faq (invitacion_id, pregunta, respuesta) VALUES (?, ?, ?)");
        foreach ($_POST['faq_pregunta'] as $i => $pregunta) {
            $faq_stmt->execute([
                $invitacion_id,
                $pregunta,
                $_POST['faq_respuesta'][$i]
            ]);
        }
    }

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Invitación</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>Crear Nueva Invitación</h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="ubicacion">Ubicación</label>
                        <input type="text" id="ubicacion" name="ubicacion" required>
                    </div>
                    <div class="form-group">
                        <label for="direccion_completa">Dirección Completa</label>
                        <input type="text" id="direccion_completa" name="direccion_completa">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="coordenadas">Coordenadas (lat,lng)</label>
                    <input type="text" id="coordenadas" name="coordenadas" placeholder="34.0522,-118.2437">
                </div>
            </div>

            <div class="form-section">
                <h3>Imágenes</h3>
                <div class="form-group">
                    <label for="imagen_hero">Imagen Hero</label>
                    <input type="file" name="imagen_hero" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="imagen_dedicatoria">Imagen Dedicatoria</label>
                    <input type="file" name="imagen_dedicatoria" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="imagen_destacada">Imagen Destacada</label>
                    <input type="file" name="imagen_destacada" accept="image/*">
                </div>
            </div>

            <div class="form-section">
                <h3>Contenido</h3>
                <div class="form-group">
                    <label for="historia">Historia de la Pareja</label>
                    <textarea id="historia" name="historia" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="dresscode">Dress Code</label>
                    <textarea id="dresscode" name="dresscode" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="texto_rsvp">Texto RSVP</label>
                    <textarea id="texto_rsvp" name="texto_rsvp" rows="2"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="mensaje_footer">Mensaje Footer</label>
                        <textarea id="mensaje_footer" name="mensaje_footer" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="firma_footer">Firma Footer</label>
                        <input type="text" id="firma_footer" name="firma_footer">
                    </div>
                </div>
            </div>

            <!-- Galería -->
            <h3>Galería de imágenes</h3>
            <div class="form-group">
                <label for="imagenes_galeria[]">Imágenes de Galería (puedes seleccionar varias)</label>
                <input type="file" name="imagenes_galeria[]" accept="image/*" multiple>
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
            <h3>Imágenes para DressCode</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="imagen_dresscode_hombres">Imagen Dresscode Hombres</label>
                    <input type="file" name="imagen_dresscode_hombres" accept="image/*">
                </div>
                <div class="form-group">
                    <label for="imagen_dresscode_mujeres">Imagen Dresscode Mujeres</label>
                    <input type="file" name="imagen_dresscode_mujeres" accept="image/*">
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
        container.appendChild(newItem);
    }

    function agregarFAQ() {
        const container = document.getElementById('faq-container');
        const newItem = container.children[0].cloneNode(true);
        // Limpiar valores
        newItem.querySelectorAll('input, textarea').forEach(input => input.value = '');
        container.appendChild(newItem);
    }
    </script>
</body>
</html>