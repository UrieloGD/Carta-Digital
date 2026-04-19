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

// Obtener datos de la invitación
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

// Obtener ubicaciones
$ubicaciones_query = "SELECT * FROM invitacion_ubicaciones WHERE invitacion_id = ? ORDER BY orden, tipo";
$ubicaciones_stmt = $db->prepare($ubicaciones_query);
$ubicaciones_stmt->execute([$invitacion['id']]);
$ubicaciones_result = $ubicaciones_stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Obtener galería
$galeria_query = "SELECT * FROM invitacion_galeria WHERE invitacion_id = ? AND activa = 1 ORDER BY orden";
$galeria_stmt = $db->prepare($galeria_query);
$galeria_stmt->execute([$invitacion['id']]);
$galeria_result = $galeria_stmt->fetchAll(PDO::FETCH_ASSOC);
$galeria = array_column($galeria_result, 'ruta');

// Imágenes por defecto
if (empty($galeria)) {
    $galeria = [
        "./plantillas/plantilla-8/img/galeria/foto1.jpg",
        "./plantillas/plantilla-8/img/galeria/foto2.jpg", 
        "./plantillas/plantilla-8/img/galeria/foto3.jpg",
        "./plantillas/plantilla-8/img/galeria/foto4.jpg",
        "./plantillas/plantilla-8/img/galeria/foto5.jpg"
    ];
}

// Obtener dresscode
$dresscode_query = "SELECT * FROM invitacion_dresscode WHERE invitacion_id = ?";
$dresscode_stmt = $db->prepare($dresscode_query);
$dresscode_stmt->execute([$invitacion['id']]);
$dresscode_info = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);

if ($dresscode_info) {
    $img_dresscode_hombres = !empty($dresscode_info['hombres']) ? './' . ltrim($dresscode_info['hombres'], '/') : null;
    $img_dresscode_mujeres = !empty($dresscode_info['mujeres']) ? './' . ltrim($dresscode_info['mujeres'], '/') : null;
    $descripcion_dresscode_hombres = $dresscode_info['descripcion_hombres'] ?? '';
    $descripcion_dresscode_mujeres = $dresscode_info['descripcion_mujeres'] ?? '';
} else {
    $img_dresscode_hombres = null;
    $img_dresscode_mujeres = null;
    $descripcion_dresscode_hombres = '';
    $descripcion_dresscode_mujeres = '';
}

// Obtener mesa de regalos
$mesa_regalos_query = "SELECT * FROM invitacion_mesa_regalos WHERE invitacion_id = ? AND activa = 1 ORDER BY orden";
$mesa_regalos_stmt = $db->prepare($mesa_regalos_query);
$mesa_regalos_stmt->execute([$invitacion['id']]);
$mesa_regalos = $mesa_regalos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Variables principales
$nombres = $invitacion['nombres_novios'];
$fecha = fechaEnEspanol($invitacion['fecha_evento']);
$hora_ceremonia = formatearHora($invitacion['hora_evento']);

// Variables de ubicación
$ubicacion = $invitacion['ubicacion'] ?: ($ubicacion_ceremonia['nombre_lugar'] ?? ''); 
$direccion_completa = $invitacion['direccion_completa'] ?: ($ubicacion_ceremonia['direccion'] ?? '');

// Imágenes principales
$imagen_hero = $invitacion['imagen_hero'] ?: './plantillas/plantilla-8/img/hero.jpg';
$imagen_dedicatoria = $invitacion['imagen_dedicatoria'] ?: './plantillas/plantilla-8/img/dedicatoria.jpg';
$imagen_destacada = $invitacion['imagen_destacada'] ?: './plantillas/plantilla-8/img/destacada.jpg';

// Configuraciones
$mostrar_contador = (bool)($invitacion['mostrar_contador'] ?? true);
$mostrar_cronograma = (bool)($invitacion['mostrar_cronograma'] ?? true);
$mostrar_compartir = (bool)($invitacion['mostrar_compartir'] ?? false);

// Música
$musica_youtube_url = $invitacion['musica_youtube_url'] ?? '';
$musica_autoplay = (bool)($invitacion['musica_autoplay'] ?? false);

// Textos personalizables
$historia_texto = $invitacion['historia'] ?: "Han pasado años llenos de aprendizaje, crecimiento y bendiciones. Cada momento ha sido especial, rodeada del amor de mi familia y amigos.";
$mensaje_footer = $invitacion['mensaje_footer'] ?: '"Hoy celebro este momento especial. Gracias por ser parte de esto."';
$firma_footer = $invitacion['firma_footer'] ?: $nombres;

// Tipo de RSVP
$tipo_rsvp = $invitacion['tipo_rsvp'] ?? 'whatsapp';
$numero_whatsapp_rsvp = !empty($invitacion['whatsapp_confirmacion']) ? $invitacion['whatsapp_confirmacion'] : '3339047672';

// RSVP
$rsvp_habilitado = true;
if ($invitacion['fecha_limite_rsvp'] && $invitacion['mostrar_fecha_limite_rsvp']) {
    $fecha_limite = new DateTime($invitacion['fecha_limite_rsvp']);
    $fecha_actual = new DateTime();
    $fecha_limite->setTime(23, 59, 59);
    if ($fecha_actual > $fecha_limite) {
        $rsvp_habilitado = false;
    }
}

// Registrar visita
try {
    $stats_query = "INSERT INTO invitacion_estadisticas (invitacion_id, tipo_evento, ip_address, user_agent) VALUES (?, 'visita', ?, ?)";
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->execute([
        $invitacion['id'], 
        $_SERVER['REMOTE_ADDR'] ?? null, 
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
} catch (Exception $e) {
    // Ignorar errores
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nombres); ?> - Cumpleaños</title>
    
    <!-- Estilos Plantilla K-pop Demon Hunters x Raya -->
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/global.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/hero.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/bienvenida.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/historia.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/galeria.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/contador.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/cronograma.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/ubicaciones.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/dresscode.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/rsvp.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/mesa-regalos.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/footer.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-8/css/music-player.css?v=<?php echo time(); ?>">
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;900&family=Montserrat:wght@700;800;900&family=Alice&display=swap" rel="stylesheet">
    
    <!-- Icon page -->
    <link rel="shortcut icon" href="./images/logo.webp" />
</head>
<body>

<!-- Sección Hero -->
<section class="hero" id="hero">
    <div class="hero-background">
        <div class="hero-image" style="background-image: url('<?php echo htmlspecialchars($imagen_hero); ?>')"></div>
        <div class="hero-overlay"></div>
    </div>
    
    <div class="hero-content">
        <div class="container">
            <div class="hero-text">
                <div class="hero-title">✨ Celebración Especial ✨</div>
                <h1 class="hero-names"><?php echo htmlspecialchars($nombres); ?></h1>
            </div>
        </div>
    </div>
    
    <div class="hero-scroll-indicator">
        <div class="scroll-line"></div>
    </div>
</section>

<!-- Sección Bienvenida -->
<section class="bienvenida" id="bienvenida">
    <div class="container">
        <div class="bienvenida-content">
            <div class="bienvenida-header-title">
                <h2 class="bienvenida-age-title">¡Celebramos este momento especial!</h2>
            </div>
            
            <div class="bienvenida-main">
                <?php if ($imagen_dedicatoria): ?>
                <div class="bienvenida-image">
                    <div class="image-container">
                        <div class="image-frame">
                            <img src="<?php echo htmlspecialchars($imagen_dedicatoria); ?>" 
                                 alt="<?php echo htmlspecialchars($nombres); ?>" 
                                 loading="lazy" />
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="bienvenida-header-title">
                <h2 class="bienvenida-age-title">¡El cumpleaños 6 de Valeria!</h2>
            </div>
        </div>
    </div>
</section>
    </div>
</section>

<!-- Sección Historia -->
<section class="historia" id="historia">
    <?php if ($imagen_destacada): ?>
    <div class="historia-background" style="background-image: url('<?php echo htmlspecialchars($imagen_destacada); ?>');"></div>
    <?php endif; ?>
</section>

<!-- Sección Contador -->
<?php if ($mostrar_contador): ?>
<section class="contador" id="contador">
    <div class="container">
        <div class="contador-header">
            <h2 class="contador-title">La aventura comienza en</h2>
        </div>
        <div class="contador-display" id="contador-timer">
            <div class="contador-unit">
                <div class="contador-value-wrapper">
                    <span class="contador-number" id="dias">0</span>
                </div>
                <span class="contador-label">Días</span>
            </div>
            <div class="contador-separator">:</div>
            <div class="contador-unit">
                <div class="contador-value-wrapper">
                    <span class="contador-number" id="horas">0</span>
                </div>
                <span class="contador-label">Horas</span>
            </div>
            <div class="contador-separator">:</div>
            <div class="contador-unit">
                <div class="contador-value-wrapper">
                    <span class="contador-number" id="minutos">0</span>
                </div>
                <span class="contador-label">Minutos</span>
            </div>
            <div class="contador-separator">:</div>
            <div class="contador-unit">
                <div class="contador-value-wrapper">
                    <span class="contador-number" id="segundos">0</span>
                </div>
                <span class="contador-label">Segundos</span>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Cronograma -->
<?php if ($mostrar_cronograma && !empty($cronograma)): ?>
<section class="cronograma" id="cronograma">
    <div class="container">
        <div class="cronograma-header">
            <h2>Cronograma del Día</h2>
            <div class="decorative-line"></div>
            <div class="event-date-time">
                <div class="date-info"><?php echo $fecha; ?></div>
                <div class="time-info"><?php echo $hora_ceremonia; ?></div>
            </div>
        </div>
        
        <div class="cronograma-timeline">
            <?php foreach ($cronograma as $evento): ?>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-time"><?php echo formatearHora($evento['hora']); ?></div>
                    <div class="timeline-event"><?php echo htmlspecialchars($evento['evento']); ?></div>
                    <p class="timeline-description"><?php echo htmlspecialchars($evento['descripcion']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Galería de Personajes -->
<section class="galeria" id="galeria">
    <div class="container">
        <div class="galeria-header">
            <h2>Galería Especial</h2>
            <div class="decorative-line"></div>
            <p>Momentos y personajes de este evento magico</p>
        </div>
        
        <div class="carousel-wrapper">
            <div class="carousel-container" id="carouselContainer">
                <div class="carousel-track" id="carouselTrack" style="position: relative; width: 100%;">
                    <?php foreach ($galeria as $index => $imagen): ?>
                    <div class="carousel-slide" data-index="<?php echo $index; ?>" style="display: none;">
                        <div class="slide-image-wrapper">
                            <img src="<?php echo htmlspecialchars($imagen); ?>" 
                                 alt="Imagen <?php echo $index + 1; ?>" 
                                 class="carousel-image"
                                 loading="lazy" />
                        </div>
                        <div class="slide-info">
                            <div class="slide-counter"><span id="slideNumber"><?php echo $index + 1; ?></span> / <span id="totalSlides"><?php echo count($galeria); ?></span></div>
                            <div class="slide-title">Momento <?php echo $index + 1; ?></div>
                            <p class="slide-description">Parte de la magia de este evento especial</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Controles de navegación -->
            <button class="carousel-btn carousel-prev" id="carouselPrev" aria-label="Imagen anterior">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            
            <button class="carousel-btn carousel-next" id="carouselNext" aria-label="Imagen siguiente">
                <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
            
            <!-- Indicadores de puntos -->
            <div class="carousel-dots" id="carouselDots">
                <?php foreach ($galeria as $index => $imagen): ?>
                <button class="dot" data-index="<?php echo $index; ?>" aria-label="Ir a imagen <?php echo $index + 1; ?>" <?php echo $index === 0 ? 'class="dot active"' : 'class="dot"'; ?>></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Sección Dresscode -->
<?php if ($descripcion_dresscode_hombres || $descripcion_dresscode_mujeres): ?>
<section class="dresscode" id="dresscode">
    <div class="container">
        <div class="dresscode-header">
            <h2>Dress Code</h2>
            <div class="decorative-line"></div>
        </div>
        
        <div class="dresscode-grid">
            <?php if ($descripcion_dresscode_hombres): ?>
            <div class="dresscode-item">
                <?php if ($img_dresscode_hombres): ?>
                <div class="dresscode-image">
                    <img src="<?php echo htmlspecialchars($img_dresscode_hombres); ?>" alt="Dress Code Hombres" loading="lazy" />
                </div>
                <?php endif; ?>
                <div class="dresscode-content">
                    <h3>Caballeros</h3>
                    <p><?php echo htmlspecialchars($descripcion_dresscode_hombres); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($descripcion_dresscode_mujeres): ?>
            <div class="dresscode-item">
                <?php if ($img_dresscode_mujeres): ?>
                <div class="dresscode-image">
                    <img src="<?php echo htmlspecialchars($img_dresscode_mujeres); ?>" alt="Dress Code Mujeres" loading="lazy" />
                </div>
                <?php endif; ?>
                <div class="dresscode-content">
                    <h3>Damas</h3>
                    <p><?php echo htmlspecialchars($descripcion_dresscode_mujeres); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Ubicaciones -->
<?php if ($ubicacion_evento): ?>
<section class="ubicaciones" id="ubicaciones">
    <div class="container">
        <div class="ubicaciones-header">
            <h2>Ubicación del Evento</h2>
            <div class="decorative-line"></div>
        </div>
        
        <div class="ubicacion-card-single">
            <div class="ubicacion-title"><?php echo htmlspecialchars($ubicacion_evento['nombre_lugar'] ?? 'Ubicación'); ?></div>
            
            <div class="ubicacion-address">
                <div class="ubicacion-address-text"><?php echo htmlspecialchars($ubicacion_evento['direccion'] ?? ''); ?></div>
            </div>
            
            <?php if (!empty($ubicacion_evento['google_maps_url'])): ?>
            <div class="ubicacion-button-wrapper">
                <a href="<?php echo htmlspecialchars($ubicacion_evento['google_maps_url']); ?>" target="_blank" class="ubicacion-button">
                    <span>Ver Ubicación</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Mesa de Regalos -->
<?php if (!empty($mesa_regalos)): ?>
<section class="mesa-regalos" id="mesa-regalos">
    <div class="container">
        <div class="mesa-regalos-header">
            <h2>Mesa de Regalos</h2>
            <div class="decorative-line"></div>
        </div>
        
        <div class="mesa-regalos-grid">
            <?php foreach ($mesa_regalos as $regalo): ?>
            <div class="regalo-card">
                <span class="regalo-icon"><?php echo htmlspecialchars($regalo['icono'] ?? '🎁'); ?></span>
                <div class="regalo-nombre"><?php echo htmlspecialchars($regalo['nombre']); ?></div>
                <p class="regalo-descripcion"><?php echo htmlspecialchars($regalo['descripcion']); ?></p>
                <?php if ($regalo['url_lista']): ?>
                <a href="<?php echo htmlspecialchars($regalo['url_lista']); ?>" target="_blank" class="regalo-link">Ver Lista</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección RSVP -->
<section class="rsvp" id="rsvp">
    <div class="container">
        <div class="rsvp-header">
            <h2>Confirma tu Asistencia</h2>
            <div class="decorative-line"></div>
        </div>
        
        <?php if ($rsvp_habilitado): ?>
        <div class="rsvp-main">
            <div class="rsvp-message">
                <p><?php echo htmlspecialchars($invitacion['texto_rsvp'] ?? 'Tu presencia es muy importante para nosotros. Por favor confirma tu asistencia.'); ?></p>
            </div>
            
            <?php if ($tipo_rsvp === 'whatsapp'): ?>
            <!-- RSVP por WhatsApp -->
            <div class="rsvp-action-wrapper">
                <button class="rsvp-button" onclick="confirmarPorWhatsApp()">
                    <span>Confirmar por WhatsApp</span>
                </button>
            </div>
            <?php else: ?>
            <!-- RSVP Formulario Digital -->
            <form id="rsvp-form" class="rsvp-form" method="POST" action="./api/register_and_pay.php">
                <input type="hidden" name="invitacion_slug" value="<?php echo htmlspecialchars($slug); ?>">
                
                <div class="form-group">
                    <label for="nombres">Nombre Completo *</label>
                    <input type="text" id="nombres" name="nombres" required placeholder="Tu nombre" />
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required placeholder="tu@email.com" />
                </div>
                
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" placeholder="Tu teléfono" />
                </div>
                
                <div class="form-group">
                    <label for="asistencia">¿Confirmas tu asistencia? *</label>
                    <select id="asistencia" name="asistencia" required>
                        <option value="">Selecciona una opción</option>
                        <option value="1">Sí, asistiré</option>
                        <option value="0">No podré asistir</option>
                    </select>
                </div>
                
                <button type="submit" class="rsvp-button">Confirmar Asistencia</button>
            </form>
            <?php endif; ?>
            
            <div class="rsvp-details">
                <div class="detail-item">
                    <div class="detail-label">Fecha</div>
                    <div class="detail-value"><?php echo $fecha; ?></div>
                </div>
                <div class="detail-separator">•</div>
                <div class="detail-item">
                    <div class="detail-label">Hora</div>
                    <div class="detail-value"><?php echo $hora_ceremonia; ?></div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="rsvp-closed">
            <h3>Confirmación no disponible</h3>
            <p>El período para confirmar asistencia ha finalizado. Gracias por tu interés.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-main">
                <div class="footer-quote">
                    <blockquote>
                        <?php echo htmlspecialchars($mensaje_footer); ?>
                    </blockquote>
                </div>
                
                <?php if ($mostrar_compartir): ?>
                <div class="footer-actions">
                    <button class="footer-button" onclick="shareWhatsApp()" type="button">
                        <span class="button-text">Compartir Invitación</span>
                    </button>
                    <button class="footer-button" onclick="copyLink()" type="button">
                        <span class="button-text">Copiar Enlace</span>
                    </button>
                </div>
                <?php endif; ?>
                </div>
            </div>
        
        </div>
    </div>
</footer>

<!-- Music Player -->
<?php if ($musica_youtube_url): ?>
<div class="music-player" id="musicPlayer" title="Reproducir música">
    <span class="music-player-icon">🎵</span>
    <audio id="playerAudio" src="" <?php echo $musica_autoplay ? 'autoplay' : ''; ?> loop></audio>
</div>
<?php endif; ?>

<!-- Scripts -->
<script>
    // Datos para el contador
    const FECHA_EVENTO = '<?php echo $invitacion['fecha_evento']; ?>';
    const MUSICA_URL = '<?php echo htmlspecialchars($musica_youtube_url); ?>';
    const RSVP_HABILITADO = <?php echo $rsvp_habilitado ? 'true' : 'false'; ?>;
</script>

<script src="./plantillas/plantilla-8/js/main.js?v=<?php echo time(); ?>"></script>
<script src="./plantillas/plantilla-8/js/contador.js?v=<?php echo time(); ?>"></script>
<script src="./plantillas/plantilla-8/js/galeria.js?v=<?php echo time(); ?>"></script>
<script src="./plantillas/plantilla-8/js/music-player.js?v=<?php echo time(); ?>"></script>

</body>
</html>
