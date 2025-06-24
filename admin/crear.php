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
    $plantilla_id = $_POST['plantilla_id'];

    // Verificar si ya existe una invitación con el mismo slug
    $check_slug = $db->prepare("SELECT COUNT(*) FROM invitaciones WHERE slug = ?");
    $check_slug->execute([$slug]);
    if ($check_slug->fetchColumn() > 0) {
        die("Ya existe una invitación con ese slug.");
    }

    // NUEVA ESTRUCTURA: Crear carpetas dentro de la plantilla
    $upload_base = "../plantillas/plantilla-$plantilla_id/uploads/$slug";
    $secciones = ['hero', 'dedicatoria', 'destacada', 'galeria', 'dresscode'];
    foreach ($secciones as $sec) {
        $path = "$upload_base/$sec";
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

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
        $plantilla_id,
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
    
    // Cronograma (sin cambios)
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

    // FAQs (sin cambios)
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
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <script>
    // Función para previsualizar imágenes individuales
    function previewImage(input, previewId) {
        const preview = document.getElementById(previewId);
        const file = input.files[0];
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width: 300px; max-height: 200px;">`;
                preview.classList.add('has-image');
                
                // Actualizar el label del botón
                const label = input.nextElementSibling;
                label.innerHTML = `<i>✅</i> Cambiar imagen`;
                label.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
            };
            reader.readAsDataURL(file);
        }
    }

    // Función para previsualizar galería múltiple
    function previewGallery(input) {
        const preview = document.getElementById('gallery-preview');
        const files = Array.from(input.files);
        
        if (files.length > 0) {
            preview.innerHTML = '';
            
            files.forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'gallery-preview-item';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Galería ${index + 1}">
                            <button type="button" class="remove-btn" onclick="removeGalleryItem(this, ${index})" title="Eliminar">×</button>
                        `;
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Actualizar label
            const label = input.nextElementSibling;
            label.innerHTML = `<i>✅</i> ${files.length} imagen${files.length > 1 ? 'es' : ''} seleccionada${files.length > 1 ? 's' : ''}`;
            label.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
        }
    }

    // Función para eliminar item de galería (visual)
    function removeGalleryItem(button, index) {
        const item = button.parentElement;
        item.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            item.remove();
            updateGalleryCount();
        }, 300);
    }

    // Actualizar contador de galería
    function updateGalleryCount() {
        const preview = document.getElementById('gallery-preview');
        const input = document.getElementById('imagenes_galeria');
        const label = input.nextElementSibling;
        const count = preview.children.length;
        
        if (count === 0) {
            label.innerHTML = `<i>🖼️</i> Seleccionar imágenes para galería`;
            label.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        } else {
            label.innerHTML = `<i>✅</i> ${count} imagen${count > 1 ? 'es' : ''} seleccionada${count > 1 ? 's' : ''}`;
        }
    }

    // Funciones existentes mejoradas
    function agregarCronograma() {
        const container = document.getElementById('cronograma-container');
        const newItem = container.children[0].cloneNode(true);
        
        // Limpiar valores
        newItem.querySelectorAll('input, select').forEach(input => input.value = '');
        
        // Añadir animación
        newItem.style.opacity = '0';
        newItem.style.transform = 'translateY(-20px)';
        container.appendChild(newItem);
        
        setTimeout(() => {
            newItem.style.transition = 'all 0.3s ease';
            newItem.style.opacity = '1';
            newItem.style.transform = 'translateY(0)';
        }, 10);
    }

    function agregarFAQ() {
        const container = document.getElementById('faq-container');
        const newItem = container.children[0].cloneNode(true);
        
        // Limpiar valores
        newItem.querySelectorAll('input, textarea').forEach(input => input.value = '');
        
        // Añadir animación
        newItem.style.opacity = '0';
        newItem.style.transform = 'translateY(-20px)';
        container.appendChild(newItem);
        
        setTimeout(() => {
            newItem.style.transition = 'all 0.3s ease';
            newItem.style.opacity = '1';
            newItem.style.transform = 'translateY(0)';
        }, 10);
    }

    // Validación mejorada del formulario
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.admin-form');
        
        form.addEventListener('submit', function(e) {
            // Mostrar indicador de carga
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i>⏳</i> Creando invitación...';
            submitBtn.disabled = true;
            
            // Si hay algún error, restaurar el botón
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 10000);
        });
        
        // Validación en tiempo real del slug
        const slugInput = document.getElementById('slug');
        slugInput.addEventListener('input', function() {
            let value = this.value.toLowerCase();
            value = value.replace(/[^a-z0-9\-]/g, '');
            value = value.replace(/--+/g, '-');
            this.value = value;
        });
    });

    // CSS para animación fadeOut
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; transform: scale(1); }
            to { opacity: 0; transform: scale(0.8); }
        }
    `;
    document.head.appendChild(style);
    </script>

</body>
</html>