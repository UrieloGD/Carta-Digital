<?php
require_once './config/database.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    exit("Invitación no encontrada");
}

$database = new Database();
$db = $database->getConnection();

// Función para convertir fecha a español
function fechaEnEspanol($fecha) {
    $meses = [
        'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
        'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
        'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
        'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'
    ];
    
    $dias = [
        'Monday' => 'lunes', 'Tuesday' => 'martes', 'Wednesday' => 'miércoles',
        'Thursday' => 'jueves', 'Friday' => 'viernes', 'Saturday' => 'sábado', 'Sunday' => 'domingo'
    ];
    
    $fechaIngles = date('j \d\e F \d\e Y', strtotime($fecha));
    $fechaEspanol = str_replace(array_keys($meses), array_values($meses), $fechaIngles);
    
    return $fechaEspanol;
}

function formatearHora($hora) {
    if (empty($hora)) return '';
    
    $dateTime = DateTime::createFromFormat('H:i:s', $hora);
    
    if (!$dateTime) {
        $dateTime = DateTime::createFromFormat('H:i', $hora);
    }
    
    if (!$dateTime) {
        $dateTime = new DateTime($hora);
    }
    
    if ($dateTime) {
        $horaFormateada = $dateTime->format('g:i A');
        return $horaFormateada;
    }
    
    return $hora;
}

// Obtener datos de la invitación con información de plantilla
$query = "SELECT i.*, p.nombre as plantilla_nombre, p.carpeta as plantilla_carpeta, p.archivo_principal
          FROM invitaciones i 
          LEFT JOIN plantillas p ON i.plantilla_id = p.id 
          WHERE i.slug = ?";
$stmt = $db->prepare($query);
$stmt->execute([$slug]);
$invitacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invitacion) {
    header("HTTP/1.0 404 Not Found");
    exit("Invitación no encontrada");
}

// Obtener ubicaciones (ceremonia y evento)
$ubicaciones_query = "SELECT * FROM invitacion_ubicaciones WHERE invitacion_id = ? ORDER BY orden, tipo";
$ubicaciones_stmt = $db->prepare($ubicaciones_query);
$ubicaciones_stmt->execute([$invitacion['id']]);
$ubicaciones_result = $ubicaciones_stmt->fetchAll(PDO::FETCH_ASSOC);

// Separar ubicaciones por tipo
$ubicacion_ceremonia = null;
$ubicacion_evento = null;
foreach ($ubicaciones_result as $ubicacion_item) {
    if ($ubicacion_item['tipo'] === 'ceremonia' && !$ubicacion_ceremonia) {
        $ubicacion_ceremonia = $ubicacion_item;
    } elseif ($ubicacion_item['tipo'] === 'evento' && !$ubicacion_evento) {
        $ubicacion_evento = $ubicacion_item;
    }
}

// Obtener cronograma
$cronograma_query = "SELECT * FROM invitacion_cronograma WHERE invitacion_id = ? ORDER BY orden, hora";
$cronograma_stmt = $db->prepare($cronograma_query);
$cronograma_stmt->execute([$invitacion['id']]);
$cronograma = $cronograma_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener galería activa
$galeria_query = "SELECT * FROM invitacion_galeria WHERE invitacion_id = ? AND activa = 1 ORDER BY orden";
$galeria_stmt = $db->prepare($galeria_query);
$galeria_stmt->execute([$invitacion['id']]);
$galeria_result = $galeria_stmt->fetchAll(PDO::FETCH_ASSOC);
$galeria = array_column($galeria_result, 'ruta');

// Si no hay imágenes en la galería, usar las por defecto
if (empty($galeria)) {
    $galeria = [
        "./plantillas/natural/img/galeria/pareja1.jpg",
        "./plantillas/natural/img/galeria/pareja2.jpg", 
        "./plantillas/natural/img/galeria/pareja3.jpg",
        "./plantillas/natural/img/galeria/pareja4.jpg",
        "./plantillas/natural/img/galeria/pareja5.jpg"
    ];
}

// Obtener información completa de dresscode
$dresscode_query = "SELECT * FROM invitacion_dresscode WHERE invitacion_id = ?";
$dresscode_stmt = $db->prepare($dresscode_query);
$dresscode_stmt->execute([$invitacion['id']]);
$dresscode_info = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);

// Construir las rutas de imágenes de dresscode
if ($dresscode_info) {
    $img_dresscode_hombres = !empty($dresscode_info['hombres']) ? './' . ltrim($dresscode_info['hombres'], '/') : './plantillas/natural/img/dresscode.webp';
    $img_dresscode_mujeres = !empty($dresscode_info['mujeres']) ? './' . ltrim($dresscode_info['mujeres'], '/') : './plantillas/natural/img/dresscode2.webp';
    $descripcion_dresscode_hombres = $dresscode_info['descripcion_hombres'] ?? '';
    $descripcion_dresscode_mujeres = $dresscode_info['descripcion_mujeres'] ?? '';
} else {
    $img_dresscode_hombres = './plantillas/natural/img/dresscode.webp';
    $img_dresscode_mujeres = './plantillas/natural/img/dresscode2.webp';
    $descripcion_dresscode_hombres = '';
    $descripcion_dresscode_mujeres = '';
}

// Obtener mesa de regalos activa
$mesa_regalos_query = "SELECT * FROM invitacion_mesa_regalos WHERE invitacion_id = ? AND activa = 1 ORDER BY orden";
$mesa_regalos_stmt = $db->prepare($mesa_regalos_query);
$mesa_regalos_stmt->execute([$invitacion['id']]);
$mesa_regalos = $mesa_regalos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Asignar variables para compatibilidad con el template original
$nombres = $invitacion['nombres_novios'];
$fecha = fechaEnEspanol($invitacion['fecha_evento']);
$hora_ceremonia = formatearHora($invitacion['hora_evento']);

// Variables de ubicación (priorizar nuevas ubicaciones)
$ubicacion = $invitacion['ubicacion'] ?: ($ubicacion_ceremonia['nombre_lugar'] ?? ''); 
$direccion_completa = $invitacion['direccion_completa'] ?: ($ubicacion_ceremonia['direccion'] ?? '');

// Contenido principal con nuevos campos
$historia_texto = $invitacion['historia'] ?: "Nuestra historia comenzó de manera inesperada, pero desde el primer momento supimos que estábamos destinados a estar juntos. Cada día compartido nos ha llevado hasta este momento especial.";
$dresscode = $invitacion['dresscode'] ?: "Elegante casual. Colores naturales que armonicen con nuestro entorno verde.";
$texto_rsvp = $invitacion['texto_rsvp'] ?: 'Tu presencia es importante para nosotros. Por favor confirma tu asistencia.';
$mensaje_footer = $invitacion['mensaje_footer'] ?: '"El amor crece mejor en la libertad, como las flores silvestres en el campo."';
$firma_footer = $invitacion['firma_footer'] ?: $nombres;

// Imágenes principales
$imagen_hero = $invitacion['imagen_hero'] ?: './img/hero.jpg';
$imagen_dedicatoria = $invitacion['imagen_dedicatoria'] ?: './img/dedicatoria.jpg';
$imagen_destacada = $invitacion['imagen_destacada'] ?: './img/hero.jpg';

// Música
$musica_url = $invitacion['musica_url'] ?? '';
$musica_autoplay = (bool)($invitacion['musica_autoplay'] ?? true);

$musica_archivo = $invitacion['musica_archivo'] ?? '';
$musica_nombre = $invitacion['musica_nombre'] ?? '';

// Si no hay archivo local, usar URL (compatibilidad)
if (empty($musica_archivo) && !empty($musica_url)) {
    $musica_archivo = $musica_url;
    $musica_nombre = $musica_nombre ?: 'Canción de boda';
} elseif (empty($musica_archivo)) {
    // Usar archivo por defecto si no hay ninguno
    $musica_archivo = './music/default/wedding-song.mp3';
    $musica_nombre = 'Canción de boda';
}

// Información familiar
$padres_novia = $invitacion['padres_novia'] ?? '';
$padres_novio = $invitacion['padres_novio'] ?? '';
$padrinos_novia = $invitacion['padrinos_novia'] ?? '';
$padrinos_novio = $invitacion['padrinos_novio'] ?? '';

// Configuraciones
$mostrar_contador = (bool)($invitacion['mostrar_contador'] ?? true);

// Si no hay cronograma, usar el por defecto
if (empty($cronograma)) {
    $cronograma = [
        ["hora" => "14:00", "evento" => "Ceremonia", "icono" => "anillos", "descripcion" => "Acompáñanos en este momento sagrado."],
        ["hora" => "15:30", "evento" => "Cóctel de recepción", "icono" => "cena", "descripcion" => "Brindemos juntos al aire libre."],
        ["hora" => "17:00", "evento" => "Banquete", "icono" => "fiesta", "descripcion" => "Compartamos una deliciosa cena."],
        ["hora" => "19:30", "evento" => "Baile y celebración", "icono" => "luna", "descripcion" => "¡Que comience la fiesta!"]
    ];
}

// Registrar visita en estadísticas
try {
    $stats_query = "INSERT INTO invitacion_estadisticas (invitacion_id, tipo_evento, ip_address, user_agent) VALUES (?, 'visita', ?, ?)";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([
        $invitacion['id'], 
        $_SERVER['REMOTE_ADDR'] ?? null, 
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
} catch (Exception $e) {
    // Silenciosamente ignorar errores de estadísticas
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nombres); ?> - Invitación de Boda</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/global.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/music-player.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/hero.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/bienvenida.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/historia.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/contador.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/cronograma.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/ubicaciones.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/galeria.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/dresscode.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/mesa-regalos.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/rsvp.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/footer.css">
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/responsive.css">
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Lato:wght@300;400;500&display=swap" rel="stylesheet">
    
    <?php if ($musica_archivo): ?>
    <!-- El reproductor de música se crea dinámicamente con JavaScript -->
    <?php endif; ?>
</head>
<body>

<!-- Sección Hero -->
<section class="hero" id="hero">
    <div class="hero-overlay"></div>
    <div class="hero-background" style="background-image: url('<?php echo htmlspecialchars($imagen_hero); ?>')"></div>
    <div class="hero-content">
        <div class="hero-ornament top"></div>
        <div class="hero-text">
            <p class="hero-subtitle">Nos casamos</p>
            <h1 class="hero-names"><?php echo htmlspecialchars($nombres); ?></h1>
            <div class="hero-date"><?php echo $fecha; ?></div>
            <div class="hero-location"><?php echo htmlspecialchars($ubicacion); ?></div>
        </div>
        <div class="hero-ornament bottom"></div>
    </div>
</section>

<!-- Sección Bienvenida -->
<section class="bienvenida" id="bienvenida">
    <div class="container">
        <div class="bienvenida-content">
            <div class="bienvenida-header">
                <h2>Querida familia y amigos</h2>
                <div class="decorative-line"></div>
            </div>
            
            <div class="bienvenida-text">
                <p>Con el corazón lleno de alegría, queremos invitarlos a ser parte del momento más importante de nuestras vidas. Su amor, cariño y bendiciones han sido fundamentales en nuestro camino, y no podemos imaginar este día especial sin ustedes a nuestro lado.</p>
            </div>

            <div class="bienvenida-image">
                <div class="image-frame">
                    <img src="<?php echo htmlspecialchars($imagen_dedicatoria); ?>" alt="<?php echo htmlspecialchars($nombres); ?>" />
                    <div class="image-ornament"></div>
                </div>
            </div>

            <!-- Información familiar -->
            <?php if ($padres_novia || $padres_novio || $padrinos_novia || $padrinos_novio): ?>
            <div class="familia-info">
                <div class="familia-grid">
                    <!-- Lado izquierdo - Novia -->
                    <?php if ($padres_novia || $padrinos_novia): ?>
                    <div class="familia-lado">
                        <h4>Familia de la Novia</h4>
                        <?php if ($padres_novia): ?>
                        <div class="familia-item">
                            <strong>Padres</strong>
                            <span><?php echo htmlspecialchars($padres_novia); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($padrinos_novia): ?>
                        <div class="familia-item">
                            <strong>Padrinos</strong>
                            <span><?php echo htmlspecialchars($padrinos_novia); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Lado derecho - Novio -->
                    <?php if ($padres_novio || $padrinos_novio): ?>
                    <div class="familia-lado">
                        <h4>Familia del Novio</h4>
                        <?php if ($padres_novio): ?>
                        <div class="familia-item">
                            <strong>Padres</strong>
                            <span><?php echo htmlspecialchars($padres_novio); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($padrinos_novio): ?>
                        <div class="familia-item">
                            <strong>Padrinos</strong>
                            <span><?php echo htmlspecialchars($padrinos_novio); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="bienvenida-date-section">
                <div class="date-ornament"></div>
                <div class="bienvenida-date"><?php echo $fecha; ?></div>
                <div class="bienvenida-venue">
                    <p>La ceremonia será a las <?php echo $hora_ceremonia; ?></p>
                    <p class="venue-name"><?php echo htmlspecialchars($ubicacion); ?></p>
                    <?php if ($direccion_completa): ?>
                    <p class="venue-address"><?php echo htmlspecialchars($direccion_completa); ?></p>
                    <?php endif; ?>
                </div>
                <div class="date-ornament"></div>
            </div>
        </div>
    </div>
</section>

<!-- Sección Historia -->
<section class="historia" id="historia">
    <div class="container">
        <div class="historia-content">
            <div class="historia-header">
                <h2>Nuestra Historia</h2>
                <div class="decorative-line"></div>
            </div>
            <div class="historia-text">
                <?php
                $historia_parrafos = explode("\n", $historia_texto);
                foreach ($historia_parrafos as $parrafo) {
                    if (trim($parrafo)) {
                        echo '<p>' . htmlspecialchars(trim($parrafo)) . '</p>';
                    }
                }
                ?>
            </div>
            <div class="historia-ornament"></div>
        </div>
    </div>
</section>

<!-- Imagen de transición después de historia -->
<section class="transition-image">
    <div class="transition-overlay"></div>
    <img src="<?php echo htmlspecialchars($imagen_destacada); ?>" alt="Imagen historia" />
</section>

<?php if ($mostrar_contador): ?>
<!-- Contador regresivo -->
<section class="contador" id="contador">
    <div class="container">
        <div class="contador-content">
            <h2>Faltan...</h2>
            <div class="countdown" id="countdown">
                <div class="time-unit">
                    <span class="number" id="days">0</span>
                    <span class="label">Días</span>
                </div>
                <div class="time-unit">
                    <span class="number" id="hours">0</span>
                    <span class="label">Horas</span>
                </div>
                <div class="time-unit">
                    <span class="number" id="minutes">0</span>
                    <span class="label">Minutos</span>
                </div>
                <div class="time-unit">
                    <span class="number" id="seconds">0</span>
                    <span class="label">Segundos</span>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Cronograma -->
<section class="cronograma" id="cronograma">
    <div class="container">
        <div class="cronograma-header">
            <h2>Cronograma del día</h2>
            <div class="decorative-line"></div>
        </div>
        <div class="cronograma-timeline">
            <?php foreach($cronograma as $index => $item): ?>
            <div class="timeline-item">
                <div class="timeline-time"><?php echo formatearHora($item['hora']); ?></div>
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h3><?php echo htmlspecialchars($item['evento']); ?></h3>
                    <p><?php echo htmlspecialchars($item['descripcion'] ?? ''); ?></p>
                    <?php if (!empty($item['ubicacion'])): ?>
                    <div class="timeline-location">
                        📍 <?php echo htmlspecialchars($item['ubicacion']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Sección Ubicaciones -->
<?php if (!empty($ubicaciones_result)): ?>
<section class="ubicaciones" id="ubicaciones">
    <div class="container">
        <div class="ubicaciones-header">
            <h2>Ubicaciones</h2>
            <div class="decorative-line"></div>
        </div>
        <div class="ubicaciones-grid">
            <?php foreach($ubicaciones_result as $ubicacion_item): ?>
            <div class="ubicacion-card">
                <?php if ($ubicacion_item['imagen']): ?>
                <div class="ubicacion-image">
                    <div class="image-overlay"></div>
                    <img src="<?php echo htmlspecialchars($ubicacion_item['imagen']); ?>" alt="<?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?>" />
                </div>
                <?php endif; ?>
                <div class="ubicacion-content">
                    <div class="ubicacion-tipo"><?php echo ucfirst($ubicacion_item['tipo']); ?></div>
                    <h3><?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?></h3>
                    <p class="ubicacion-direccion"><?php echo htmlspecialchars($ubicacion_item['direccion']); ?></p>
                    
                    <?php if ($ubicacion_item['hora_inicio']): ?>
                    <p class="ubicacion-horario">
                        <?php echo formatearHora($ubicacion_item['hora_inicio']); ?>
                        <?php if ($ubicacion_item['hora_fin']): ?>
                        - <?php echo formatearHora($ubicacion_item['hora_fin']); ?>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                    <?php if ($ubicacion_item['descripcion']): ?>
                    <p class="ubicacion-descripcion"><?php echo htmlspecialchars($ubicacion_item['descripcion']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($ubicacion_item['google_maps_url']): ?>
                    <a href="<?php echo htmlspecialchars($ubicacion_item['google_maps_url']); ?>" target="_blank" class="ubicacion-maps">
                        Ver en Google Maps
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Galería -->
<section class="galeria" id="galeria">
    <div class="container">
        <div class="galeria-header">
            <h2>Momentos Especiales</h2>
            <div class="decorative-line"></div>
        </div>
        <div class="galeria-grid">
            <?php foreach(array_slice($galeria, 0, 6) as $index => $imagen): ?>
            <div class="galeria-item item-<?php echo $index + 1; ?>">
                <div class="image-overlay"></div>
                <img src="<?php echo htmlspecialchars($imagen); ?>" alt="Momento especial" />
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Sección Dress Code -->
<section class="dresscode" id="dresscode">
    <div class="container">
        <div class="dresscode-content">
            <div class="dresscode-header">
                <h2>Código de vestimenta</h2>
                <div class="decorative-line"></div>
            </div>
            <p class="dresscode-description"><?php echo htmlspecialchars($dresscode); ?></p>
            
            <div class="dresscode-examples">
                <div class="dresscode-example">
                    <div class="dresscode-image">
                        <img src="<?php echo htmlspecialchars($img_dresscode_mujeres); ?>" alt="Vestimenta femenina" />
                        <div class="image-overlay"></div>
                    </div>
                    <h3>Mujeres</h3>
                    <?php if ($descripcion_dresscode_mujeres): ?>
                    <p><?php echo htmlspecialchars($descripcion_dresscode_mujeres); ?></p>
                    <?php endif; ?>
                    <div class="color-palette">
                        <div class="color-dot olive"></div>
                        <div class="color-dot sand"></div>
                        <div class="color-dot cream"></div>
                    </div>
                </div>
                
                <div class="dresscode-example">
                    <div class="dresscode-image">
                        <img src="<?php echo htmlspecialchars($img_dresscode_hombres); ?>" alt="Vestimenta masculina" />
                        <div class="image-overlay"></div>
                    </div>
                    <h3>Hombres</h3>
                    <?php if ($descripcion_dresscode_hombres): ?>
                    <p><?php echo htmlspecialchars($descripcion_dresscode_hombres); ?></p>
                    <?php endif; ?>
                    <div class="color-palette">
                        <div class="color-dot olive"></div>
                        <div class="color-dot sand"></div>
                        <div class="color-dot cream"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección Mesa de Regalos -->
<?php if (!empty($mesa_regalos)): ?>
<section class="mesa-regalos" id="mesa-regalos">
    <div class="container">
        <div class="mesa-regalos-header">
            <h2>Mesa de Regalos</h2>
            <div class="decorative-line"></div>
            <p>Tu presencia es nuestro mejor regalo, pero si deseas obsequiarnos algo especial:</p>
        </div>
        <div class="regalos-grid">
            <?php foreach($mesa_regalos as $regalo): ?>
            <div class="regalo-card">
                <?php if ($regalo['icono']): ?>
                <div class="regalo-icon">
                    <img src="<?php echo htmlspecialchars($regalo['icono']); ?>" alt="<?php echo htmlspecialchars($regalo['nombre_tienda'] ?: $regalo['tienda']); ?>" />
                </div>
                <?php endif; ?>
                <div class="regalo-content">
                    <h3><?php echo htmlspecialchars($regalo['nombre_tienda'] ?: ucfirst(str_replace('_', ' ', $regalo['tienda']))); ?></h3>
                    <?php if ($regalo['numero_evento']): ?>
                    <p><strong>Número de evento:</strong> <?php echo htmlspecialchars($regalo['numero_evento']); ?></p>
                    <?php endif; ?>
                    <?php if ($regalo['codigo_evento']): ?>
                    <p><strong>Código:</strong> <?php echo htmlspecialchars($regalo['codigo_evento']); ?></p>
                    <?php endif; ?>
                    <?php if ($regalo['descripcion']): ?>
                    <p><?php echo htmlspecialchars($regalo['descripcion']); ?></p>
                    <?php endif; ?>
                    <?php if ($regalo['url']): ?>
                    <a href="<?php echo htmlspecialchars($regalo['url']); ?>" target="_blank" class="regalo-link">
                        Visitar tienda
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección RSVP -->
<section class="rsvp" id="rsvp">
    <div class="container">
        <div class="rsvp-content">
            <div class="rsvp-header">
                <h2>Confirma tu Asistencia</h2>
                <div class="decorative-line"></div>
            </div>
            <p><?php echo htmlspecialchars($texto_rsvp); ?></p>
            <button class="rsvp-button" onclick="openRSVPModal()">Confirmar Asistencia</button>
        </div>
    </div>
</section>

<!-- Modal RSVP -->
<div class="rsvp-modal" id="rsvpModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmar Asistencia</h3>
            <button class="modal-close" onclick="closeRSVPModal()">&times;</button>
        </div>
        <form class="rsvp-form" id="rsvpForm">
            <input type="hidden" name="invitacion_id" value="<?php echo $invitacion['id']; ?>">
            <div class="form-group">
                <label for="nombre">Nombre Completo *</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="asistencia">¿Asistirás? *</label>
                <select id="asistencia" name="asistencia" required>
                    <option value="">Selecciona una opción</option>
                    <option value="si">Sí, asistiré</option>
                    <option value="no">No podré asistir</option>
                   <option value="tal_vez">Tal vez</option>
               </select>
           </div>
           <div class="form-group">
               <label for="acompanantes">Número de acompañantes</label>
               <input type="number" id="acompanantes" name="acompanantes" min="0" max="5" value="0">
           </div>
           <div class="form-group">
               <label for="nombres_acompanantes">Nombres de acompañantes</label>
               <textarea id="nombres_acompanantes" name="nombres_acompanantes" rows="2" placeholder="Nombres de las personas que te acompañarán..."></textarea>
           </div>
           <div class="form-group">
               <label for="restricciones_alimentarias">Restricciones alimentarias</label>
               <textarea id="restricciones_alimentarias" name="restricciones_alimentarias" rows="2" placeholder="Alergias, vegetariano, vegano, etc."></textarea>
           </div>
           <div class="form-group">
               <label for="mensaje">Mensaje especial</label>
               <textarea id="mensaje" name="mensaje" rows="3" placeholder="Mensaje especial para los novios..."></textarea>
           </div>
           <button type="submit" class="form-submit">Enviar Confirmación</button>
       </form>
   </div>
</div>

<!-- Footer -->
<footer class="footer">
   <div class="container">
       <div class="footer-content">
           <div class="footer-ornament"></div>
           <p class="footer-message">
               <?php echo htmlspecialchars($mensaje_footer); ?>
           </p>
           <div class="footer-actions">
               <button class="share-button" onclick="shareWhatsApp()">
                   <span>📱</span> Compartir por WhatsApp
               </button>
               <button class="copy-button" onclick="copyLink()">
                   <span>🔗</span> Copiar enlace
               </button>
               <?php if ($musica_url): ?>
               <button class="music-toggle" onclick="toggleMusicFromFooter()" id="musicToggle">
                   <span>🎵</span> <span id="musicStatus">Controlar música</span>
               </button>
               <?php endif; ?>
           </div>
           <p class="footer-thanks">
               Gracias por ser parte de nuestro día especial
           </p>
           <p class="footer-signature">
               Con amor, <?php echo htmlspecialchars($firma_footer); ?>
           </p>
           <div class="footer-ornament"></div>
       </div>
   </div>
</footer>

<!-- Mensaje de éxito RSVP -->
<div class="success-message" id="successMessage">
   <div class="success-content">
       <span class="success-icon">✅</span>
       <h3>¡Confirmación enviada!</h3>
       <p>Gracias por confirmar tu asistencia. ¡Te esperamos!</p>
   </div>
</div>

<script>
// Variables globales para JavaScript
const invitacionData = {
    id: <?php echo $invitacion['id']; ?>,
    nombres: '<?php echo addslashes($nombres); ?>',
    fecha: '<?php echo $invitacion['fecha_evento']; ?>',
    hora: '<?php echo $invitacion['hora_evento']; ?>',
    mostrarContador: <?php echo $mostrar_contador ? 'true' : 'false'; ?>,
    musicaArchivo: '<?php echo addslashes($musica_archivo); ?>',
    musicaNombre: '<?php echo addslashes($musica_nombre); ?>',
    musicaAutoplay: <?php echo $musica_autoplay ? 'true' : 'false'; ?>
};
</script>

<script src="./plantillas/plantilla-2/js/contador.js"></script>
<script src="./plantillas/plantilla-2/js/music-player.js"></script>
<script src="./plantillas/plantilla-2/js/compartir.js"></script>
<script src="./plantillas/plantilla-2/js/rsvp.js"></script>
<script src="./plantillas/plantilla-2/js/faq.js"></script>
<script src="./plantillas/plantilla-2/js/estadisticas.js"></script>
<script src="./plantillas/plantilla-2/js/invitacion.js"></script>
</body>
</html>