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

// Obtener galería
$galeria_query = "SELECT * FROM invitacion_galeria WHERE invitacion_id = ? AND activa = 1 ORDER BY orden";
$galeria_stmt = $db->prepare($galeria_query);
$galeria_stmt->execute([$invitacion['id']]);
$galeria_result = $galeria_stmt->fetchAll(PDO::FETCH_ASSOC);
$galeria = array_column($galeria_result, 'ruta');

// Si no hay imágenes en la galería, usar las por defecto
if (empty($galeria)) {
    $galeria = [
        "./plantillas/plantilla-7/img/galeria/foto1.jpg",
        "./plantillas/plantilla-7/img/galeria/foto2.jpg", 
        "./plantillas/plantilla-7/img/galeria/foto3.jpg",
        "./plantillas/plantilla-7/img/galeria/foto4.jpg",
        "./plantillas/plantilla-7/img/galeria/foto5.jpg"
    ];
}

// Obtener dresscode
$dresscode_query = "SELECT * FROM invitacion_dresscode WHERE invitacion_id = ?";
$dresscode_stmt = $db->prepare($dresscode_query);
$dresscode_stmt->execute([$invitacion['id']]);
$dresscode_info = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);

// Obtener música
$musica_youtube_url = $invitacion['musica_youtube_url'] ?? '';
$musica_autoplay = (bool)($invitacion['musica_autoplay'] ?? false);
$musica_volumen = $invitacion['musica_volumen'] ?? 0.5;

// Dresscode
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

// Variables principales (usar nombres_novios para el nombre de la quinceañera)
$nombres = $invitacion['nombres_novios'];
$fecha = fechaEnEspanol($invitacion['fecha_evento']);
$hora_ceremonia = formatearHora($invitacion['hora_evento']);

// Variables de ubicación
$ubicacion = $invitacion['ubicacion'] ?: ($ubicacion_ceremonia['nombre_lugar'] ?? ''); 
$direccion_completa = $invitacion['direccion_completa'] ?: ($ubicacion_ceremonia['direccion'] ?? '');

// Contenido adaptado para XV años
$historia_texto = $invitacion['historia'] ?: "Han pasado 15 años llenos de aprendizaje, crecimiento y bendiciones. Cada momento ha sido especial, rodeada del amor de mi familia y amigos. Hoy celebro este paso importante en mi vida y quiero compartirlo con ustedes.";
$dresscode = $invitacion['dresscode'] ?: "Formal elegante. Hombres: Traje oscuro. Mujeres: Vestido de cocktail o largo en tonos que complementen la celebración.";
$texto_rsvp = $invitacion['texto_rsvp'] ?: 'Tu presencia es muy importante para mí. Por favor confirma tu asistencia para este día tan especial.';
$mensaje_footer = $invitacion['mensaje_footer'] ?: '"Hoy celebro 15 años de vida, amor y bendiciones. Gracias por ser parte de este momento especial."';
$firma_footer = $invitacion['firma_footer'] ?: $nombres;

// Imágenes principales
$imagen_hero = $invitacion['imagen_hero'] ?: './plantillas/plantilla-7/img/hero.jpg';
$imagen_dedicatoria = $invitacion['imagen_dedicatoria'] ?: './plantillas/plantilla-7/img/dedicatoria.jpg';
$imagen_destacada = $invitacion['imagen_destacada'] ?: './plantillas/plantilla-7/img/destacada.jpg';

// Información familiar (adaptado para quinceañera)
$padres_festejada = $invitacion['padres_novia'] ?? '';
$padrinos_honor = $invitacion['padrinos_novia'] ?? '';

// Configuraciones
$mostrar_contador = (bool)($invitacion['mostrar_contador'] ?? true);
$tipo_contador = $invitacion['tipo_contador'] ?? 'completo';
$mostrar_cronograma = (bool)($invitacion['mostrar_cronograma'] ?? true);

// RSVP
$mostrar_fecha_limite_rsvp = (bool)($invitacion['mostrar_fecha_limite_rsvp'] ?? false);
$fecha_limite_rsvp = $invitacion['fecha_limite_rsvp'] ?? null;
$mostrar_solo_adultos = (bool)($invitacion['mostrar_solo_adultos'] ?? false);
$texto_solo_adultos = $invitacion['texto_solo_adultos'] ?? 'Celebración exclusiva para adultos (No niños).';

// Verificar si el RSVP está habilitado
$rsvp_habilitado = true;
if ($fecha_limite_rsvp && $mostrar_fecha_limite_rsvp) {
    $fecha_limite = new DateTime($fecha_limite_rsvp);
    $fecha_actual = new DateTime();
    $fecha_limite->setTime(23, 59, 59);
    
    if ($fecha_actual > $fecha_limite) {
        $rsvp_habilitado = false;
    }
}

echo "<script>const RSVP_HABILITADO = " . ($rsvp_habilitado ? 'true' : 'false') . ";</script>";

// Frases para contador
$frases = [
    "Días para mi celebración",
    "Cada día más cerca de mis XV años",
    "Cuenta regresiva en días",
    "Días antes de este momento especial",
    "Solo faltan estos días…",
    "Días para celebrar juntos",
    "Días llenos de emoción por venir",
    "Faltan pocos días para el gran día"
];

$frase_aleatoria = $frases[array_rand($frases)];

// WhatsApp para RSVP
$numero_whatsapp_rsvp = !empty($invitacion['whatsapp_confirmacion']) ? $invitacion['whatsapp_confirmacion'] : '3339047672';

// Cronograma por defecto (adaptado para XV años)
if (empty($cronograma)) {
    $cronograma = [
        ["hora" => "17:00", "evento" => "Misa de acción de gracias", "descripcion" => "Ceremonia religiosa para agradecer por estos 15 años."],
        ["hora" => "18:30", "evento" => "Recepción", "descripcion" => "Inicio de la celebración en el salón."],
        ["hora" => "19:00", "evento" => "Vals y baile con papá", "descripcion" => "Momento especial con mi familia."],
        ["hora" => "20:00", "evento" => "Cena", "descripcion" => "Disfrutaremos de una deliciosa cena juntos."],
        ["hora" => "22:00", "evento" => "Fiesta", "descripcion" => "¡A bailar y disfrutar toda la noche!"]
    ];
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
    <title><?php echo htmlspecialchars($nombres); ?> - XV Años</title>
    
    <!-- Estilos Plantilla Rosa XV Años -->
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/global.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/global.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/hero.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/hero.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/bienvenida.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/bienvenida.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/historia.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/historia.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/transition-image.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/transition-image.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/contador.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/contador.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/cronograma.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/cronograma.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/ubicaciones.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/ubicaciones.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/galeria.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/galeria.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/dresscode.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/dresscode.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/rsvp.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/rsvp.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/mesa-regalos.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/mesa-regalos.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/footer.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/footer.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-7/css/music-player.css?v=<?php echo filemtime('./plantillas/plantilla-7/css/music-player.css'); ?>">
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alice&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Mea+Culpa&family=Tangerine:wght@400;700&display=swap" rel="stylesheet">

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
                <h3 class="hero-title">Mis XV</h3>
                
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
        <div class="bienvenida-bg-decoration"></div>
        
        <div class="bienvenida-content">
            <div class="bienvenida-header">
                <div class="header-ornament"></div>
                <h2>Queridos Familiares y Amigos</h2>
                <p class="header-subtitle">Los invito a celebrar conmigo este día tan especial</p>
            </div>
            
            <div class="bienvenida-main">
                <div class="bienvenida-text">
                    <div class="text-content">
                        <p class="bienvenida-intro">Con el corazón lleno de alegría y gratitud hacia Dios, les extiendo esta invitación para que me acompañen en la celebración de mis XV años.</p>
                        
                        <div class="quote-decoration">
                            <span class="quote-mark">"</span>
                            <p class="bienvenida-message">Su presencia y sus bendiciones son el mejor regalo que puedo recibir en este día que marca el inicio de una nueva etapa en mi vida.</p>
                            <span class="quote-mark quote-mark-end">"</span>
                        </div>
                    </div>
                </div>

                <?php if ($imagen_dedicatoria): ?>
                <div class="bienvenida-image">
                    <div class="image-container">
                        <div class="image-frame">
                            <img src="<?php echo htmlspecialchars($imagen_dedicatoria); ?>" 
                                 alt="<?php echo htmlspecialchars($nombres); ?>" 
                                 loading="lazy" />
                            <div class="image-overlay">
                                <div class="overlay-content">
                                    <span class="overlay-text"><?php echo htmlspecialchars($nombres); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="image-decoration"></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Información de ceremonia -->
                <div class="bienvenida-ceremony">
                    <div class="ceremony-ornament"></div>
                    
                    <div class="ceremony-main">
                        <div class="ceremony-date-container">
                            <span class="ceremony-label">Celebramos el</span>
                            <div class="ceremony-date"><?php echo $fecha; ?></div>
                        </div>
                        
                        <div class="ceremony-details">
                            <div class="ceremony-time">
                                <i class="icon-clock"></i>
                                <span><?php echo $hora_ceremonia; ?></span>
                            </div>
                            <div class="ceremony-venue">
                                <i class="icon-location"></i>
                                <span><?php echo htmlspecialchars($ubicacion); ?></span>
                            </div>
                            <?php if ($direccion_completa): ?>
                            <div class="ceremony-address">
                                <span><?php echo htmlspecialchars($direccion_completa); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Información familiar -->
                <?php if ($padres_festejada || $padrinos_honor): ?>
                <div class="familia-section">
                    <div class="familia-header">
                        <h3>Mi Familia</h3>
                        <div class="decorative-line">
                            <span class="line-accent"></span>
                        </div>
                    </div>
                    
                    <div class="familia-grid">
                        <?php if ($padres_festejada): ?>
                        <div class="familia-column">
                            <div class="familia-icon">👨‍👩‍👧</div>
                            <h4>Mis Padres</h4>
                            <div class="familia-item">
                                <span class="familia-names"><?php echo htmlspecialchars($padres_festejada); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($padrinos_honor): ?>
                        <div class="familia-column">
                            <div class="familia-icon">👑</div>
                            <h4>Mis Padrinos</h4>
                            <div class="familia-item">
                                <span class="familia-names"><?php echo htmlspecialchars($padrinos_honor); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Sección Historia -->
<section class="historia" id="historia">
    <div class="container">
        <div class="historia-content">
            <div class="historia-header">
                <div class="section-ornament top"></div>
                <h2>Mi Historia</h2>
                <p class="section-subtitle">15 años de bendiciones y aprendizaje</p>
            </div>
            
            <div class="historia-main">
                <div class="historia-timeline">
                    <div class="timeline-decoration"></div>
                    
                    <div class="historia-text">
                        <?php
                        $historia_parrafos = explode("\n", $historia_texto);
                        $contador = 0;
                        foreach ($historia_parrafos as $parrafo) {
                            if (trim($parrafo)) {
                                $contador++;
                                $delay = $contador * 0.2;
                                echo '<div class="historia-item" style="animation-delay: ' . $delay . 's;">';
                                echo '<div class="timeline-dot"></div>';
                                echo '<p class="historia-paragraph">' . htmlspecialchars(trim($parrafo)) . '</p>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
                
                <div class="historia-highlight">
                    <div class="highlight-card">
                        <div class="quote-ornament"></div>
                        
                        <blockquote class="historia-quote">
                            <div class="quote-marks-container">
                                <span class="quote-mark opening">"</span>
                                <p class="quote-text">Cada día ha sido un regalo y hoy celebro este momento especial</p>
                                <span class="quote-mark closing">"</span>
                            </div>
                        </blockquote>
                        
                        <div class="hearts-decoration">
                            <span class="heart">★</span>
                            <span class="heart">★</span>
                            <span class="heart">★</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Imagen de transición -->
<section class="transition-image">
    <div class="transition-container">
        <div class="transition-background" style="background-image: url('<?php echo htmlspecialchars($imagen_destacada); ?>')">
            <div class="transition-overlay"></div>
        </div>
        
        <div class="transition-content">
            <div class="container">
                <div class="transition-text">
                    <h3 class="transition-title">Un Momento Especial</h3>
                    <p class="transition-subtitle">Celebrando 15 años de vida y bendiciones</p>
                </div>
            </div>
        </div>
        
        <div class="transition-ornaments">
            <div class="ornament ornament-1"></div>
            <div class="ornament ornament-2"></div>
            <div class="ornament ornament-3"></div>
        </div>
    </div>
</section>

<!-- Sección Contador -->
<?php if ($mostrar_contador): ?>
<section class="contador <?php echo $tipo_contador === 'simple' ? 'contador-simple' : ''; ?>" id="contador">
    <div class="container">
        <div class="contador-content">
            <div class="contador-header">
                <h2>Cuenta Regresiva</h2>
                <div class="decorative-line"></div>
                <p class="contador-subtitle">Para mi celebración</p>
            </div>
            
            <?php if ($tipo_contador === 'simple'): ?>
            <div class="countdown countdown-simple" id="countdown">
                <div class="time-unit time-unit-large">
                    <span class="particle"></span>
                    <span class="particle"></span>
                    <span class="particle"></span>
                    <span class="label"><?= htmlspecialchars($frase_aleatoria) ?></span>
                    <span class="number" id="days">0</span>
                </div>
            </div>
            <?php else: ?>
            <div class="countdown-grid" id="countdown">
                <div class="time-unit">
                    <div class="time-card">
                        <div class="time-number" id="days">0</div>
                        <div class="time-label">Días</div>
                    </div>
                </div>
                
                <div class="time-unit">
                    <div class="time-card">
                        <div class="time-number" id="hours">0</div>
                        <div class="time-label">Horas</div>
                    </div>
                </div>
                
                <div class="time-unit">
                    <div class="time-card">
                        <div class="time-number" id="minutes">0</div>
                        <div class="time-label">Minutos</div>
                    </div>
                </div>
                
                <div class="time-unit">
                    <div class="time-card">
                        <div class="time-number" id="seconds">0</div>
                        <div class="time-label">Segundos</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="countdown-message">
                <p class="script-text">Faltan muy pocos días para celebrar juntos</p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Ubicaciones -->
<?php if (!empty($ubicaciones_result)): ?>
<section class="ubicaciones" id="ubicaciones">
    <div class="container">
        <div class="ubicaciones-content">
            <div class="ubicaciones-header">
                <h2 class="section-title">Ubicaciones</h2>
                <div class="decorative-line"></div>
                <p class="section-subtitle">Lugares donde celebraremos</p>
            </div>
            
            <div class="ubicaciones-grid">
                <?php foreach($ubicaciones_result as $index => $ubicacion_item): ?>
                <article class="ubicacion-card" data-index="<?php echo $index; ?>">
                    <div class="ubicacion-card-inner">
                        <?php if ($ubicacion_item['imagen']): ?>
                        <div class="ubicacion-image">
                            <img src="<?php echo htmlspecialchars($ubicacion_item['imagen']); ?>" 
                                 alt="<?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?>" 
                                 loading="lazy" />
                            <div class="ubicacion-overlay">
                                <div class="ubicacion-icon">
                                    <?php echo $ubicacion_item['tipo'] === 'ceremonia' ? '⛪' : ($ubicacion_item['tipo'] === 'recepcion' ? '🏛️' : '🎉'); ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="ubicacion-badge">
                            <span class="badge-icon">
                                <?php echo $ubicacion_item['tipo'] === 'ceremonia' ? '⛪' : ($ubicacion_item['tipo'] === 'recepcion' ? '🏛️' : '🎉'); ?>
                            </span>
                            <span class="badge-text"><?php echo ucfirst($ubicacion_item['tipo']); ?></span>
                        </div>
                        
                        <div class="ubicacion-content">
                            <div class="ubicacion-header-content">
                                <h3 class="ubicacion-nombre" title="<?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?>">
                                    <?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?>
                                </h3>
                            </div>
                            
                            <div class="ubicacion-details">
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                    </div>
                                    <div class="detail-content">
                                        <span class="detail-label">Dirección</span>
                                        <p class="detail-text"><?php echo htmlspecialchars($ubicacion_item['direccion']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($ubicacion_item['hora_inicio']): ?>
                                <div class="detail-item">
                                    <div class="detail-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12,6 12,12 16,14"/>
                                        </svg>
                                    </div>
                                    <div class="detail-content">
                                        <span class="detail-label">Horario</span>
                                        <p class="detail-text">
                                            <?php echo formatearHora($ubicacion_item['hora_inicio']); ?>
                                            <?php if ($ubicacion_item['hora_fin']): ?>
                                            - <?php echo formatearHora($ubicacion_item['hora_fin']); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($ubicacion_item['descripcion']): ?>
                            <div class="ubicacion-descripcion">
                                <p><?php echo htmlspecialchars($ubicacion_item['descripcion']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="ubicacion-actions">
                                <?php if ($ubicacion_item['google_maps_url']): ?>
                                <a href="<?php echo htmlspecialchars($ubicacion_item['google_maps_url']); ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="ubicacion-button btn">
                                    <svg class="button-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="button-text">Ver Ubicación</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Cronograma -->
<?php if ($mostrar_cronograma && !empty($cronograma)): ?>
<section class="cronograma" id="cronograma">
    <div class="container">
        <div class="cronograma-content">
            <div class="cronograma-header">
                <h2>Cronograma de la Fiesta</h2>
                <div class="decorative-line"></div>
                <p class="cronograma-subtitle">Momentos especiales que compartiremos</p>
            </div>
            
            <div class="timeline-container">
                <div class="timeline-line"></div>
                
                <?php foreach($cronograma as $index => $item): ?>
                <div class="timeline-item" data-index="<?php echo $index; ?>">
                    <div class="timeline-time">
                        <div class="time-card">
                            <span class="time-text"><?php echo formatearHora($item['hora']); ?></span>
                        </div>
                    </div>
                    
                    <div class="timeline-dot">
                        <div class="dot-inner"></div>
                    </div>
                    
                    <div class="timeline-content">
                        <div class="content-card">
                            <h3 class="event-title"><?php echo htmlspecialchars($item['evento']); ?></h3>
                            
                            <?php if (!empty($item['descripcion'])): ?>
                            <p class="event-description"><?php echo htmlspecialchars($item['descripcion']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($item['ubicacion'])): ?>
                            <div class="event-location">
                                <span class="location-icon">📍</span>
                                <span class="location-text"><?php echo htmlspecialchars($item['ubicacion']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Galería -->
<section class="galeria" id="galeria" aria-labelledby="galeria-title">
    <div class="container">
        <header class="galeria-header">
            <h2 id="galeria-title" class="section-title">15 Años de Recuerdos</h2>
            <div class="decorative-line" aria-hidden="true"></div>
            <p class="galeria-subtitle">Momentos inolvidables de mi vida</p>
        </header>
        
        <div class="galeria-grid" 
             id="galeria-grid" 
             role="grid" 
             aria-label="Galería de fotos">
        </div>
        
        <!-- Modal lightbox -->
        <div class="galeria-lightbox" 
             id="galeria-modal" 
             role="dialog" 
             aria-modal="true" 
             aria-labelledby="lightbox-title" 
             aria-hidden="true">
            <div class="lightbox-backdrop"></div>
            <div class="lightbox-container">
                <header class="lightbox-header">
                    <h3 id="lightbox-title" class="sr-only">Vista ampliada de imagen</h3>
                    <button class="lightbox-close" 
                            aria-label="Cerrar galería"
                            type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </header>
                
                <div class="lightbox-image-wrapper">
                    <button class="lightbox-nav lightbox-prev" 
                            aria-label="Imagen anterior"
                            type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </button>
                    
                    <img id="lightbox-image" 
                         src="" 
                         alt=""
                         style="transition: opacity 0.15s ease;" />
                    
                    <button class="lightbox-nav lightbox-next" 
                            aria-label="Siguiente imagen"
                            type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </button>
                </div>
                
                <footer class="lightbox-footer">
                    <div class="lightbox-counter" aria-live="polite">
                        <span id="lightbox-counter-text">1 de 1</span>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</section>

<!-- Sección Dress Code -->
<section class="dresscode" id="dresscode">
    <div class="container">
        <div class="dresscode-content">
            <div class="dresscode-header">
                <h2 class="section-title">Código de Vestimenta</h2>
                <div class="decorative-line"></div>
                <p class="section-subtitle">Elegancia y estilo para esta celebración</p>
            </div>
            
            <div class="dresscode-description">
                <p class="dresscode-text"><?php echo htmlspecialchars($dresscode); ?></p>
            </div>
            
            <?php if (!empty($img_dresscode_mujeres) || !empty($img_dresscode_hombres)): ?>
            <div class="dresscode-examples">
                <?php if (!empty($img_dresscode_mujeres)): ?>
                <div class="dresscode-card" data-animate="fadeInUp" data-delay="0.2s">
                    <div class="dresscode-image-container">
                        <div class="dresscode-image">
                            <img src="<?php echo htmlspecialchars($img_dresscode_mujeres); ?>" alt="Vestimenta femenina" />
                            <div class="image-overlay"></div>
                        </div>
                        <div class="dresscode-icon">
                            <span class="icon-symbol">♀</span>
                        </div>
                    </div>
                    
                    <div class="dresscode-info">
                        <h3 class="dresscode-title">Mujeres</h3>
                        <div class="dresscode-text-content">
                            <?php if ($descripcion_dresscode_mujeres): ?>
                            <p class="dresscode-description-text"><?php echo htmlspecialchars($descripcion_dresscode_mujeres); ?></p>
                            <?php else: ?>
                            <p class="dresscode-description-text">Vestidos elegantes de cocktail o largos en tonos que complementen la celebración.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($img_dresscode_hombres)): ?>
                <div class="dresscode-card" data-animate="fadeInUp" data-delay="0.4s">
                    <div class="dresscode-image-container">
                        <div class="dresscode-image">
                            <img src="<?php echo htmlspecialchars($img_dresscode_hombres); ?>" alt="Vestimenta masculina" />
                            <div class="image-overlay"></div>
                        </div>
                        <div class="dresscode-icon">
                            <span class="icon-symbol">♂</span>
                        </div>
                    </div>
                    
                    <div class="dresscode-info">
                        <h3 class="dresscode-title">Hombres</h3>
                        <div class="dresscode-text-content">
                            <?php if ($descripcion_dresscode_hombres): ?>
                            <p class="dresscode-description-text"><?php echo htmlspecialchars($descripcion_dresscode_hombres); ?></p>
                            <?php else: ?>
                            <p class="dresscode-description-text">Traje formal oscuro con corbata. Camisa clara que complemente la elegancia.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Sección Mesa de Regalos -->
<?php if (!empty($mesa_regalos)): ?>
<section class="mesa-regalos" id="mesa-regalos">
    <div class="container">
        <div class="mesa-regalos-content">
            <div class="mesa-regalos-header">
                <div class="header-ornament"></div>
                <h2 class="section-title">Mesa de Regalos</h2>
                <div class="decorative-line"></div>
                <p class="section-subtitle">Tu presencia es mi mejor regalo</p>
                <p class="section-description">Si deseas obsequiarme algo especial, estas son las opciones</p>
            </div>
            
            <div class="regalos-wrapper">
                <div class="regalos-grid">
                    <?php foreach($mesa_regalos as $index => $regalo): ?>
                    <a href="<?php echo htmlspecialchars($regalo['url']); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="regalo-card"
                       data-index="<?php echo $index; ?>">
                        <div class="regalo-card-inner">
                            <?php if ($regalo['icono']): ?>
                            <div class="regalo-icon">
                                <img src="<?php echo htmlspecialchars($regalo['icono']); ?>" 
                                     alt="<?php echo htmlspecialchars($regalo['nombre_tienda'] ?: $regalo['tienda']); ?>" />
                            </div>
                            <?php else: ?>
                            <div class="regalo-text">
                                <span><?php echo htmlspecialchars($regalo['nombre_tienda'] ?: ucfirst(str_replace('_', ' ', $regalo['tienda']))); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="card-shine"></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mesa-regalos-footer">
                <div class="footer-ornament"></div>
                <p class="footer-note">Con amor y gratitud, agradezco tu generosidad</p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección RSVP -->
<?php
$tipo_rsvp = $invitacion['tipo_rsvp'] ?? 'whatsapp';
?>

<section class="rsvp" id="rsvp">
    <div class="container">
        <div class="rsvp-content">
            <div class="rsvp-header">
                <h2 class="section-title">Confirma tu Asistencia</h2>
                <div class="decorative-line">
                    <span class="line-accent"></span>
                </div>
                <p class="section-subtitle">Tu presencia hace que este día sea perfecto</p>
            </div>
            
            <div class="rsvp-main" data-animate="fadeInUp" data-delay="0.2s">
                <div class="rsvp-message">
                    <p class="rsvp-text"><?php echo htmlspecialchars($texto_rsvp); ?></p>
                </div>
                
                <div class="rsvp-action">
                    <?php if ($tipo_rsvp === 'whatsapp'): ?>
                        <button class="rsvp-button" onclick="<?php echo $rsvp_habilitado ? 'confirmarAsistenciaWhatsApp()' : 'mostrarModalFechaLimite()'; ?>">
                            <span class="button-text">Confirmar por WhatsApp</span>
                            <div class="button-shimmer"></div>
                        </button>
                    <?php else: ?>
                        <button class="rsvp-button" onclick="<?php echo $rsvp_habilitado ? 'openRSVPModal()' : 'mostrarModalFechaLimite()'; ?>">
                            <span class="button-text">Confirmar Asistencia</span>
                            <div class="button-shimmer"></div>
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php if ($mostrar_fecha_limite_rsvp || $mostrar_solo_adultos): ?>
                <div class="rsvp-details">
                    <div class="detail-grid">
                        <?php if ($mostrar_fecha_limite_rsvp && !empty($fecha_limite_rsvp)): ?>
                        <div class="detail-item">
                            <div class="detail-icon">📅</div>
                            <div class="detail-content">
                                <span class="detail-label">Fecha límite</span>
                                <span class="detail-value"><?php echo fechaEnEspanol($fecha_limite_rsvp); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($mostrar_solo_adultos): ?>
                        <div class="detail-item">
                            <div class="detail-icon">👨‍👩‍👧‍👦</div>
                            <div class="detail-content">
                                <span class="detail-label">Solo adultos</span>
                                <span class="detail-value"><?php echo htmlspecialchars($texto_solo_adultos); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="rsvp-ornament">
                <div class="ornament-line"></div>
            </div>
        </div>
    </div>
</section>

<?php if ($tipo_rsvp === 'digital'): ?>
    <!-- Modal RSVP Digital (solo si es tipo digital) -->
    <div class="rsvp-modal" id="rsvpModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Asistencia</h3>
                <button class="modal-close" onclick="closeRSVPModal()">&times;</button>
            </div>

            <!-- Paso 1: Validar código del grupo -->
            <div class="rsvp-step" id="step-codigo">
                <form class="rsvp-form" id="codigoForm">
                    <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                    <div class="form-group">
                        <label for="codigo_grupo">Código de Invitación *</label>
                        <input type="text" id="codigo_grupo" name="codigo_grupo" required 
                               placeholder="Ingresa tu código de invitación" style="text-transform: uppercase;">
                        <small class="form-text text-muted">
                            Ingresa el código único que recibiste para tu grupo
                        </small>
                    </div>
                    <div class="alert-container" id="codigo-alert"></div>
                    <button type="submit" class="form-submit">Validar Código</button>
                </form>
            </div>

            <!-- Paso 2: Formulario completo RSVP -->
            <div class="rsvp-step" id="step-formulario" style="display: none;">
                <div class="grupo-info mb-3">
                    <div class="alert alert-info">
                        <strong id="nombre-grupo"></strong><br>
                        <span id="boletos-info"></span>
                    </div>
                </div>

                <form class="rsvp-form" id="rsvpForm">
                    <input type="hidden" name="id_grupo" id="id_grupo">
                    
                    <div class="form-group">
                        <label for="estado">¿Asistirán a la celebración? *</label>
                        <select id="estado" name="estado" required onchange="toggleAsistenciaFields()">
                            <option value="">Selecciona una opción</option>
                            <option value="aceptado">Sí, asistiremos</option>
                            <option value="rechazado">No podremos asistir</option>
                        </select>
                    </div>

                    <!-- Campos que se muestran solo si acepta asistir -->
                    <div class="campos-asistencia" id="campos-asistencia" style="display: none;">
                        <div class="form-group">
                            <label for="boletos_confirmados">¿Cuántos boletos usarán? *</label>
                            <select id="boletos_confirmados" name="boletos_confirmados" onchange="updateNombresFields()">
                                <!-- Se llena dinámicamente -->
                            </select>
                        </div>

                        <div class="nombres-container" id="nombres-container">
                            <!-- Se generan dinámicamente los campos de nombres -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="comentarios">Comentarios (opcional)</label>
                        <textarea id="comentarios" name="comentarios" rows="3" 
                                  placeholder="Mensaje especial, restricciones alimentarias, etc."></textarea>
                    </div>

                    <div class="alert-container" id="form-alert"></div>

                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary" onclick="volverACodigo()">Cambiar Código</button>
                        <button type="submit" class="form-submit">Continuar</button>
                    </div>
                </form>
            </div>

            <!-- Paso 3: Confirmación de datos -->
            <div class="rsvp-step" id="step-confirmacion" style="display: none;">
                <div class="confirmacion-header">
                    <h4>Confirma tu información</h4>
                    <p>Por favor revisa que todos los datos sean correctos:</p>
                </div>
                
                <div class="confirmacion-info" id="confirmacion-info">
                    <!-- Se llena dinámicamente -->
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" onclick="volverAFormulario()">Editar Información</button>
                    <button type="button" class="btn btn-primary" onclick="enviarConfirmacion()">Confirmar Asistencia</button>
                </div>
            </div>

            <!-- Paso 4: Ver respuesta existente -->
            <div class="rsvp-step" id="step-ver-respuesta" style="display: none;">
                <div class="alert alert-success mb-3">
                    <strong>¡Ya confirmaste tu asistencia!</strong><br>
                    Muchas gracias por responder a nuestra invitación.
                </div>
                
                <div class="respuesta-existente" id="respuesta-existente">
                    <!-- Se carga dinámicamente -->
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn btn-warning" onclick="editarRespuesta()" id="btn-editar-respuesta">
                        Modificar Respuesta
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeRSVPModal()">Cerrar</button>
                </div>
            </div>

            <!-- Paso 5: Éxito -->
            <div class="rsvp-step" id="step-exito" style="display: none;">
                <div class="exito-container">
                    <div class="exito-icon">✓</div>
                    <h3>¡Confirmación exitosa!</h3>
                    <p id="mensaje-exito"></p>
                    <div class="resumen-confirmacion" id="resumen-final">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="btn btn-primary" onclick="closeRSVPModal()">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Modal para fecha límite excedida -->
<div class="rsvp-modal" id="modalFechaLimite">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmación no disponible</h3>
            <button class="modal-close" onclick="closeModalFechaLimite()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="fechaLimite-content">
                <div class="fechaLimite-icon">⏰</div>
                <h4>Fecha límite excedida</h4>
                <p>La fecha límite para confirmar asistencia ha pasado. Ya no es posible realizar confirmaciones a través de este sistema.</p>
                <div class="fechaLimite-contacto">
                    <p>Si necesitas realizar algún cambio o tienes alguna duda, por favor contacta directamente al organizador del evento.</p>
                    <div class="contacto-info">
                        <strong>Información de contacto:</strong>
                        <p>Comunícate con <?php echo htmlspecialchars($nombres); ?> o los organizadores del evento.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" onclick="closeModalFechaLimite()">Entendido</button>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-main" data-animate="fadeInUp" data-delay="0.2">
                <div class="footer-quote">
                    <blockquote class="quote-text">
                        <?php echo htmlspecialchars($mensaje_footer); ?>
                    </blockquote>
                </div>
                
                <?php if ($invitacion['mostrar_compartir'] ?? true): ?>
                <div class="footer-actions">
                    <button class="footer-button" onclick="shareWhatsApp()" type="button">
                        <span class="button-icon" style="font-size: 1.1em;">📱</span>
                        <span class="button-text">Compartir invitación</span>
                    </button>
                    <button class="footer-button" onclick="copyLink()" type="button">
                        <span class="button-icon" style="font-size: 1.1em;">🔗</span>
                        <span class="button-text">Copiar enlace</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="footer-thanks">
                    <p class="thanks-text">Gracias por ser parte de este día especial</p>
                    <div class="thanks-ornament"></div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-signature">
                    <p class="signature-text">Hoy celebro 15 años de vida, amor y bendiciones. Gracias por ser parte de este momento especial."</p>
                    <p class="signature-names"><?php echo htmlspecialchars($nombres); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-background">
        <div class="bg-ornament bg-ornament-1"></div>
        <div class="bg-ornament bg-ornament-2"></div>
        <div class="bg-ornament bg-ornament-3"></div>
    </div>
</footer>

<?php if (!empty($musica_youtube_url)): ?>
<script src="./plantillas/plantilla-7/js/music-player.js"></script>
<script>
(function() {
    const musicConfig = {
        youtubeUrl: '<?php echo addslashes($musica_youtube_url); ?>',
        autoplay: true,
        volume: <?php echo $musica_volumen; ?>
    };
    
    if (window.initMusicPlayer) {
        initMusicPlayer(musicConfig.youtubeUrl, musicConfig.autoplay, musicConfig.volume);
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            if (window.initMusicPlayer) {
                initMusicPlayer(musicConfig.youtubeUrl, musicConfig.autoplay, musicConfig.volume);
            }
        });
    }
})();
</script>
<?php endif; ?>

<script>
const invitacionData = {
    id: <?php echo $invitacion['id']; ?>,
    nombres: '<?php echo addslashes($nombres); ?>',
    fecha: '<?php echo $invitacion['fecha_evento']; ?>',
    hora: '<?php echo $invitacion['hora_evento']; ?>',
    mostrarContador: <?php echo $mostrar_contador ? 'true' : 'false'; ?>,
    tipoContador: '<?php echo $tipo_contador; ?>',
    mostrarCronograma: <?php echo $mostrar_cronograma ? 'true' : 'false'; ?>,
};

window.numeroWhatsAppRSVP = '<?php echo $numero_whatsapp_rsvp; ?>';
const galeriaImagenes = <?php echo json_encode($galeria); ?>;
</script>

<script src="./plantillas/plantilla-7/js/contador.js?v=<?php echo filemtime('./plantillas/plantilla-7/js/contador.js'); ?>"></script>
<script src="./plantillas/plantilla-7/js/compartir.js?v=<?php echo filemtime('./plantillas/plantilla-7/js/compartir.js'); ?>"></script>
<script src="./plantillas/plantilla-7/js/rsvp.js?v=<?php echo filemtime('./plantillas/plantilla-7/js/rsvp.js'); ?>"></script>
<script src="./plantillas/plantilla-7/js/whatsapp.js?v=<?php echo filemtime('./plantillas/plantilla-7/js/whatsapp.js'); ?>"></script>
<script src="./plantillas/plantilla-7/js/estadisticas.js?v=<?php echo filemtime('./plantillas/plantilla-7/js/estadisticas.js'); ?>"></script>
<script src="./plantillas/plantilla-7/js/invitacion.js?v=<?php echo filemtime('./plantillas/plantilla-7/js/invitacion.js'); ?>"></script>
<script src="./plantillas/plantilla-7/js/galeria-rotacion.js?v=<?php echo filemtime('./plantillas/plantilla-7/js/galeria-rotacion.js'); ?>"></script>

</body>
</html>
