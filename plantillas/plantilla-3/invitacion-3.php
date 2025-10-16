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
        "./plantillas/plantilla-3/img/galeria/pareja1.jpg",
        "./plantillas/plantilla-3/img/galeria/pareja2.jpg", 
        "./plantillas/plantilla-3/img/galeria/pareja3.jpg",
        "./plantillas/plantilla-3/img/galeria/pareja4.jpg",
        "./plantillas/plantilla-3/img/galeria/pareja5.jpg"
    ];
}

// Obtener información completa de dresscode
$dresscode_query = "SELECT * FROM invitacion_dresscode WHERE invitacion_id = ?";
$dresscode_stmt = $db->prepare($dresscode_query);
$dresscode_stmt->execute([$invitacion['id']]);
$dresscode_info = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);

// Obtener musica
$musica_youtube_url = $invitacion['musica_youtube_url'] ?? '';
$musica_autoplay = (bool)($invitacion['musica_autoplay'] ?? false);
$musica_volumen = $invitacion['musica_volumen'] ?? 0.5;

// Construir las rutas de imágenes de dresscode - LÓGICA MEJORADA DE PLANTILLA 2
if ($dresscode_info) {
    $img_dresscode_hombres = !empty($dresscode_info['hombres']) ? './' . ltrim($dresscode_info['hombres'], '/') : null;
    $img_dresscode_mujeres = !empty($dresscode_info['mujeres']) ? './' . ltrim($dresscode_info['mujeres'], '/') : null;
    $descripcion_dresscode_hombres = $dresscode_info['descripcion_hombres'] ?? '';
    $descripcion_dresscode_mujeres = $dresscode_info['descripcion_mujeres'] ?? '';
} else {
    // Si no hay registro en la tabla dresscode, no mostrar imágenes
    $img_dresscode_hombres = null;
    $img_dresscode_mujeres = null;
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
$historia_texto = $invitacion['historia'] ?: "Nuestra historia comenzó de manera inesperada, pero desde el primer momento supimos que estábamos destinados a estar juntos. Cada día compartido nos ha llevado hasta este momento especial que queremos celebrar junto a ustedes.";
$dresscode = $invitacion['dresscode'] ?: "Elegante formal. Tonos oscuros y colores profundos que armonicen con la solemnidad de la ocasión.";
$texto_rsvp = $invitacion['texto_rsvp'] ?: 'Tu presencia es importante para nosotros. Por favor confirma tu asistencia para este día tan especial.';
$mensaje_footer = $invitacion['mensaje_footer'] ?: '"El amor es la fuerza más imponente del mundo y, sin embargo, la más humilde que se pueda imaginar."';
$firma_footer = $invitacion['firma_footer'] ?: $nombres;

// Imágenes principales
$imagen_hero = $invitacion['imagen_hero'] ?: './plantillas/plantilla-3/img/hero.jpg';
$imagen_dedicatoria = $invitacion['imagen_dedicatoria'] ?: './plantillas/plantilla-3/img/dedicatoria.jpg';
$imagen_destacada = $invitacion['imagen_destacada'] ?: './plantillas/plantilla-3/img/destacada.jpg';

// Información familiar
$padres_novia = $invitacion['padres_novia'] ?? '';
$padres_novio = $invitacion['padres_novio'] ?? '';
$padrinos_novia = $invitacion['padrinos_novia'] ?? '';
$padrinos_novio = $invitacion['padrinos_novio'] ?? '';

// Configuraciones MEJORADAS con lógica de plantilla 2
$mostrar_contador = (bool)($invitacion['mostrar_contador'] ?? true);
$tipo_contador = $invitacion['tipo_contador'] ?? 'completo';
$mostrar_cronograma = (bool)($invitacion['mostrar_cronograma'] ?? true);

// Frases aleatorias para contador simple (de plantilla 2)
$frases = [
    "Días que nos separan del gran día",
    "Cada día más cerca de nuestro gran día",
    "Cuenta regresiva en días",
    "Días antes de vivir algo único",
    "Solo faltan estos días…",
    "Días para celebrar juntos",
    "Días llenos de emoción por venir",
    "Faltan pocos días para el gran momento"
];

// Elegir una frase al azar
$frase_aleatoria = $frases[array_rand($frases)];

// Número de WhatsApp para RSVP desde la base de datos
$numero_whatsapp_rsvp = !empty($invitacion['whatsapp_confirmacion']) ? $invitacion['whatsapp_confirmacion'] : '3339047672';

// Si no hay cronograma, usar el por defecto
if (empty($cronograma)) {
    $cronograma = [
        ["hora" => "14:00", "evento" => "Ceremonia Religiosa", "icono" => "church", "descripcion" => "Nos uniremos en matrimonio ante Dios y nuestros seres queridos."],
        ["hora" => "15:30", "evento" => "Sesión de fotos", "icono" => "camera", "descripcion" => "Capturaremos los momentos más especiales del día."],
        ["hora" => "17:00", "evento" => "Cóctel de recepción", "icono" => "glass", "descripcion" => "Brindemos juntos por nuestro nuevo comienzo."],
        ["hora" => "19:30", "evento" => "Cena de gala", "icono" => "dinner", "descripcion" => "Compartamos una elegante cena en familia."],
        ["hora" => "22:00", "evento" => "Baile y celebración", "icono" => "dance", "descripcion" => "Celebremos hasta altas horas de la madrugada."]
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
    
    <!-- Estilos Plantilla Gótica -->
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/global.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/global.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/hero.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/hero.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/bienvenida.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/bienvenida.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/historia.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/historia.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/transition-image.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/transition-image.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/contador.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/contador.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/cronograma.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/cronograma.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/ubicaciones.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/ubicaciones.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/galeria.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/galeria.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/dresscode.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/dresscode.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/rsvp.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/rsvp.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/mesa-regalos.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/mesa-regalos.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/footer.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/footer.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-3/css/music-player.css?v=<?php echo filemtime('./plantillas/plantilla-3/css/music-player.css'); ?>">
    
    <!-- Fuentes cursivas mejoradas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Satisfy&display=swap" rel="stylesheet">
    
    <!-- Icon page -->
    <link rel="shortcut icon" href="./images/logo.webp" />
</head>
<body>

<!-- Sección Hero Elegante Minimalista -->
<section class="hero" id="hero">
    <div class="hero-background">
        <div class="hero-image" style="background-image: url('<?php echo htmlspecialchars($imagen_hero); ?>')"></div>
        <div class="hero-overlay"></div>
    </div>
    
    <div class="hero-content">
        <div class="container">
            <div class="hero-text">
                <p class="hero-invitation">Nos unimos en matrimonio</p>
                <h1 class="hero-names"><?php echo htmlspecialchars($nombres); ?></h1>
                
                <div class="hero-details">
                    <div class="hero-date"><?php echo $fecha; ?></div>
                    <?php if ($ubicacion): ?>
                    <div class="hero-location"><?php echo htmlspecialchars($ubicacion); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="hero-scroll-indicator">
        <div class="scroll-line"></div>
    </div>
</section>

<!-- Contador regresivo elegante minimalista -->
<?php if ($mostrar_contador): ?>
<section class="contador <?php echo $tipo_contador === 'simple' ? 'contador-simple' : ''; ?>" id="contador">
    <div class="container">
        <div class="contador-content">
            <div class="contador-header">
                <h2>Cuenta Regresiva</h2>
                <div class="decorative-line"></div>
                <p class="contador-subtitle">Hasta nuestro día especial</p>
            </div>
            
            <?php if ($tipo_contador === 'simple'): ?>
            <!-- Versión Simple: Solo días (lógica de plantilla 2) -->
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
            <!-- Versión Completa: Días, Horas, Minutos, Segundos -->
            <div class="countdown-wrapper">
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
                
                <div class="countdown-message">
                    <p class="script-text">Faltan muy pocos días para celebrar juntos</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección Bienvenida Elegante Minimalista Mejorada -->
<section class="bienvenida" id="bienvenida">
    <div class="container">
        <!-- Elemento decorativo de fondo -->
        <div class="bienvenida-bg-decoration"></div>
        
        <div class="bienvenida-content">
            <div class="bienvenida-header">
                <div class="header-ornament"></div>
                <h2>Querida Familia y Amigos</h2>
                <p class="header-subtitle">Les invitamos a ser testigos de nuestro amor.</p>
            </div>
            
            <div class="bienvenida-main">
                <div class="bienvenida-text">
                    <div class="text-content">
                        <p class="bienvenida-intro">Porque juntos somos mejores, hemos decidido caminar de la mano para siempre</p>
                        
                        <div class="quote-decoration">
                            <span class="quote-mark">"</span>
                            <p class="bienvenida-message">Queremos que seas parte de este día único en nuestras vidas.</p>
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

                <!-- Información de ceremonia destacada mejorada -->
                <div class="bienvenida-ceremony">
                    <div class="ceremony-ornament">
                        <img src="./plantillas/plantilla-3/img/iconos/anillos-imagen.png" alt="Anillos de boda" class="ceremony-icon-img">
                    </div>
                    
                    <div class="ceremony-main">
                        <div class="ceremony-date-container">
                            <span class="ceremony-label">Nos casamos el</span>
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

                <!-- Información familiar mejorada -->
                <?php if ($padres_novia || $padres_novio || $padrinos_novia || $padrinos_novio): ?>
                <div class="familia-section">
                    <div class="familia-header">
                        <h3>Nuestras Familias</h3>
                        <div class="decorative-line">
                            <span class="line-accent"></span>
                        </div>
                    </div>
                    
                    <div class="familia-grid">
                        <?php if ($padres_novia || $padrinos_novia): ?>
                        <div class="familia-column" data-side="novia">
                            <div class="familia-icon">👰</div>
                            <h4>Familia de la Novia</h4>
                            
                            <?php if ($padres_novia): ?>
                            <div class="familia-item">
                                <span class="familia-label">Padres</span>
                                <span class="familia-names"><?php echo htmlspecialchars($padres_novia); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($padrinos_novia): ?>
                            <div class="familia-item">
                                <span class="familia-label">Padrinos</span>
                                <span class="familia-names"><?php echo htmlspecialchars($padrinos_novia); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($padres_novio || $padrinos_novio): ?>
                        <div class="familia-column" data-side="novio">
                            <div class="familia-icon">🤵</div>
                            <h4>Familia del Novio</h4>
                            
                            <?php if ($padres_novio): ?>
                            <div class="familia-item">
                                <span class="familia-label">Padres</span>
                                <span class="familia-names"><?php echo htmlspecialchars($padres_novio); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($padrinos_novio): ?>
                            <div class="familia-item">
                                <span class="familia-label">Padrinos</span>
                                <span class="familia-names"><?php echo htmlspecialchars($padrinos_novio); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Sección Historia Elegante Minimalista -->
<section class="historia" id="historia">
    <div class="container">
        <div class="historia-content">
            <div class="historia-header">
                <div class="section-ornament top"></div>
                <h2>Nuestra Historia</h2>
                <p class="section-subtitle">El camino que nos trajo hasta aquí</p>
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
                                <p class="quote-text">Cada día compartido nos ha llevado hasta este momento especial</p>
                                <span class="quote-mark closing">"</span>
                            </div>
                        </blockquote>
                        
                        <div class="hearts-decoration">
                            <span class="heart"></span>
                            <span class="heart"></span>
                            <span class="heart"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Imagen de transición elegante minimalista -->
<section class="transition-image">
    <div class="transition-container">
        <div class="transition-background" style="background-image: url('<?php echo htmlspecialchars($imagen_destacada); ?>')">
            <div class="transition-overlay"></div>
        </div>
        
        <div class="transition-content">
            <div class="container">
                <div class="transition-text">
                    <h3 class="transition-title">Más valen dos que uno, porque obtienen más fruto de su esfuerzo. Si caen, el uno levanta al otro...</h3>
                    <p class="transition-subtitle">Eclesiastés 4:9-12</p>
                </div>
            </div>
        </div>
        
        <!-- Elementos decorativos minimalistas -->
        <div class="transition-ornaments">
            <div class="ornament ornament-1"></div>
            <div class="ornament ornament-2"></div>
            <div class="ornament ornament-3"></div>
        </div>
    </div>
</section>

<!-- Sección Ubicaciones Elegante Minimalista Mejorada -->
<?php if (!empty($ubicaciones_result)): ?>
<section class="ubicaciones" id="ubicaciones">
    <div class="container">
        <div class="ubicaciones-content">
            <div class="ubicaciones-header">
                <h2 class="section-title">Ubicacion</h2>
                <div class="decorative-line">
                    <span class="line-accent"></span>
                </div>
                <p class="section-subtitle">Lugar donde celebraremos nuestro amor</p>
            </div>
            
            <div class="ubicaciones-grid">
                <?php foreach($ubicaciones_result as $index => $ubicacion_item): ?>
                <article class="ubicacion-card" data-index="<?php echo $index; ?>">
                    <div class="ubicacion-card-inner">
                        <!-- Imagen de ubicación -->
                        <?php if ($ubicacion_item['imagen']): ?>
                        <div class="ubicacion-image">
                            <img src="<?php echo htmlspecialchars($ubicacion_item['imagen']); ?>" 
                                 alt="<?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?>" 
                                 loading="lazy" />
                            <div class="ubicacion-overlay">
                                <div class="ubicacion-icon">
                                    <?php echo $ubicacion_item['tipo'] === 'ceremonia' ? '⛪' : ($ubicacion_item['tipo'] === 'recepcion' ? '🏛️' : ''); ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Badge de tipo mejorado -->
                        <div class="ubicacion-badge">
                            <span class="badge-icon">
                                <?php echo $ubicacion_item['tipo'] === 'ceremonia' ? '⛪' : ($ubicacion_item['tipo'] === 'recepcion' ? '🏛️' : ''); ?>
                            </span>
                            <span class="badge-text"><?php echo ucfirst($ubicacion_item['tipo']); ?></span>
                        </div>
                        
                        <!-- Contenido de ubicación -->
                        <div class="ubicacion-content">
                            <div class="ubicacion-header-content">
                                <h3 class="ubicacion-nombre" title="<?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?>">
                                    <?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?>
                                </h3>
                            </div>
                            
                            <!-- Información principal -->
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
                            
                            <!-- Descripción -->
                            <?php if ($ubicacion_item['descripcion']): ?>
                            <div class="ubicacion-descripcion">
                                <p><?php echo htmlspecialchars($ubicacion_item['descripcion']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Acciones -->
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

<!-- Sección Cronograma Elegante Minimalista -->
 <?php if ($mostrar_cronograma && !empty($cronograma)): ?>
<section class="cronograma" id="cronograma">
    <div class="container">
        <div class="cronograma-content">
            <div class="cronograma-header">
                <h2>Cronograma de la Celebración</h2>
                <div class="decorative-line">
                    <span class="line-accent"></span>
                </div>
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
            <h2 id="galeria-title" class="section-title">Momentos Eternos</h2>
            <div class="decorative-line" aria-hidden="true"></div>
            <p class="galeria-subtitle">Recuerdos que atesoramos en nuestro corazón</p>
        </header>
        
        <!-- Grid de galería -->
        <div class="galeria-grid" 
             id="galeria-grid" 
             role="grid" 
             aria-label="Galería de fotos de la pareja">
            <!-- Las imágenes se cargarán dinámicamente -->
        </div>
        
        <!-- Modal lightbox -->
        <div class="galeria-modal" 
             id="galeria-modal" 
             role="dialog" 
             aria-modal="true" 
             aria-labelledby="modal-title" 
             aria-hidden="true">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <header class="modal-header">
                    <h3 id="modal-title" class="sr-only">Vista ampliada de imagen</h3>
                    <button class="modal-close" 
                            aria-label="Cerrar galería"
                            type="button">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                        </svg>
                    </button>
                </header>
                
                <div class="modal-image-container">
                    <img id="modal-image" 
                         src="" 
                         alt=""
                         loading="lazy" />
                    
                    <!-- Navegación -->
                    <button class="modal-nav modal-prev" 
                            aria-label="Imagen anterior"
                            type="button">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M15.41 16.09L10.83 11.5l4.58-4.59L14 5.5l-6 6 6 6z"/>
                        </svg>
                    </button>
                    
                    <button class="modal-nav modal-next" 
                            aria-label="Siguiente imagen"
                            type="button">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8.59 16.34L13.17 11.75l-4.58-4.59L10 5.75l6 6-6 6z"/>
                        </svg>
                    </button>
                </div>
                
                <footer class="modal-footer">
                    <div class="modal-counter" aria-live="polite">
                        <span id="modal-counter-text">1 de 5</span>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</section>

<!-- Sección Dress Code Elegante Minimalista -->
<section class="dresscode" id="dresscode">
    <div class="container">
        <div class="dresscode-content">
            <div class="dresscode-header">
                <h2 class="section-title">Código de Vestimenta</h2>
                <div class="decorative-line">
                    <span class="line-accent"></span>
                </div>
                <p class="section-subtitle">Se solicita vestimenta rigurosamente formal para el evento.</p>
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
                            <p class="dresscode-description-text">Rigurosa Formal.</p>
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
                            <p class="dresscode-description-text">Rigurosa Formal.</p>
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
                <h2 class="section-title">Mesa de Regalos</h2>
                <div class="decorative-line">
                    <span class="line-accent"></span>
                </div>
                <p class="section-subtitle">Tu presencia es nuestro mejor regalo</p>
                <p class="section-description">Si deseas obsequiarnos algo especial, hemos preparado estas opciones con mucho cariño</p>
            </div>
            
            <div class="regalos-wrapper">
                <div class="regalos-grid">
                    <?php foreach($mesa_regalos as $regalo): ?>
                    <a href="<?php echo htmlspecialchars($regalo['url']); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="regalo-card">
                        <div class="regalo-content">
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
                        </div>
                        <div class="card-shine"></div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mesa-regalos-footer">
                <p class="footer-note">Con amor y gratitud por acompañarnos en este momento tan especial</p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección RSVP -->
<?php
// Obtener el tipo de RSVP configurado (por defecto 'whatsapp' para compatibilidad)
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
                        <!-- Botón para confirmación por WhatsApp -->
                        <button class="rsvp-button whatsapp-button" onclick="confirmarAsistenciaWhatsApp()">
                            <span class="button-text">Confirmar por WhatsApp</span>
                            <div class="button-shimmer"></div>
                        </button>
                        
                    <?php else: ?>
                        <!-- Botón para sistema digital (original) -->
                        <button class="rsvp-button" onclick="openRSVPModal()">
                            <span class="button-text">Confirmar Asistencia</span>
                            <div class="button-shimmer"></div>
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="rsvp-details">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-icon">📅</div>
                            <div class="detail-content">
                                <span class="detail-label">Fecha límite</span>
                                <span class="detail-value"><?php echo fechaEnEspanol(date('Y-m-d', strtotime($invitacion['fecha_evento'] . ' -15 days'))); ?></span>
                                <span class="detail-value">Queremos asegurarnos que tu lugar esté reservado.</span>
                            </div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-icon">👨‍👩‍👧‍👦</div>
                            <div class="detail-content">
                                <span class="detail-label">Solo adultos</span>
                                <span class="detail-value">Celebración exclusiva para adultos (No niños).</span>
                            </div>
                        </div>
                    </div>
                </div>
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
                    <button type="button" class="btn btn-primary" onclick="editarRespuesta()" id="btn-editar-respuesta">
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

<!-- Footer Elegante Minimalista -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-main" data-animate="fadeInUp" data-delay="0.2">
                <div class="footer-quote">
                    <blockquote class="quote-text">
                        <?php echo htmlspecialchars($mensaje_footer); ?>
                    </blockquote>
                    <cite class="quote-author">— <?php echo htmlspecialchars($firma_footer); ?></cite>
                </div>

                <!-- Imagen después de la firma -->
                <div class="footer-ornament-container">
                    <div class="footer-ceremony-ornament">
                        <img src="./plantillas/plantilla-3/img/iconos/sobre-dinero.webp" alt="Anillos de boda" class="footer-ceremony-icon-img">
                    </div>
                </div>
                
                <!-- <div class="footer-actions">
                    <button class="footer-button" onclick="shareWhatsApp()" type="button">
                        <span class="button-icon" style="font-size: 1.1em;">📱</span>
                        <span class="button-text">Compartir invitación</span>
                    </button>
                    <button class="footer-button" onclick="copyLink()" type="button">
                        <span class="button-icon" style="font-size: 1.1em;">🔗</span>
                        <span class="button-text">Copiar enlace</span>
                    </button>
                </div> -->
                
                <div class="footer-thanks">
                    <p class="thanks-text">Gracias por ser parte de nuestro día especial</p>
                    <div class="thanks-ornament"></div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-signature">
                    <p class="signature-text">Con todo nuestro amor</p>
                    <p class="signature-names"><?php echo htmlspecialchars($nombres); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Elementos decorativos de fondo -->
    <div class="footer-background">
        <div class="bg-ornament bg-ornament-1"></div>
        <div class="bg-ornament bg-ornament-2"></div>
        <div class="bg-ornament bg-ornament-3"></div>
    </div>
</footer>

<?php if (!empty($musica_youtube_url)): ?>
<link rel="stylesheet" href="./plantillas/plantilla-3/css/music-player.css">
<script src="./plantillas/plantilla-3/js/music-player.js"></script>
<script>
(function() {
    const musicConfig = {
        youtubeUrl: '<?php echo addslashes($musica_youtube_url); ?>',
        autoplay: true, // Siempre true para auto-reproducir
        volume: <?php echo $musica_volumen; ?>
    };
    
    console.log('Configuración de música:', musicConfig);
    
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

// Agrega este JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                const delay = element.dataset.delay || '0s';
                
                setTimeout(() => {
                    element.classList.add('animate-in');
                }, parseFloat(delay) * 1000);
                
                observer.unobserve(element);
            }
        });
    }, observerOptions);

    document.querySelectorAll('[data-animate]').forEach(el => {
        observer.observe(el);
    });
});
</script>
<?php endif; ?>

<script>
// Variables globales para JavaScript
const invitacionData = {
    id: <?php echo $invitacion['id']; ?>,
    nombres: '<?php echo addslashes($nombres); ?>',
    fecha: '<?php echo $invitacion['fecha_evento']; ?>',
    hora: '<?php echo $invitacion['hora_evento']; ?>',
    mostrarContador: <?php echo $mostrar_contador ? 'true' : 'false'; ?>,
    tipoContador: '<?php echo $tipo_contador; ?>',
    mostrarCronograma: <?php echo $mostrar_cronograma ? 'true' : 'false'; ?>,
};

// Configurar número de WhatsApp para RSVP
window.numeroWhatsAppRSVP = '<?php echo $numero_whatsapp_rsvp; ?>';
</script>

<script>
// Pasar las imágenes de PHP a JavaScript
const galeriaImagenes = <?php echo json_encode($galeria); ?>;
</script>

<script src="./plantillas/plantilla-3/js/contador.js?v=<?php echo filemtime('./plantillas/plantilla-3/js/contador.js'); ?>"></script>
<script src="./plantillas/plantilla-3/js/compartir.js?v=<?php echo filemtime('./plantillas/plantilla-3/js/compartir.js'); ?>"></script>
<script src="./plantillas/plantilla-3/js/rsvp.js?v=<?php echo filemtime('./plantillas/plantilla-3/js/rsvp.js'); ?>"></script>
<script src="./plantillas/plantilla-3/js/mesa-regalos.js?v=<?php echo filemtime('./plantillas/plantilla-3/js/mesa-regalos.js'); ?>"></script>
<script src="./plantillas/plantilla-3/js/whatsapp.js?v=<?php echo filemtime('./plantillas/plantilla-3/js/whatsapp.js'); ?>"></script>
<script src="./plantillas/plantilla-3/js/estadisticas.js?v=<?php echo filemtime('./plantillas/plantilla-3/js/estadisticas.js'); ?>"></script>
<script src="./plantillas/plantilla-3/js/invitacion.js?v=<?php echo filemtime('./plantillas/plantilla-3/js/invitacion.js'); ?>"></script>
<script src="./plantillas/plantilla-3/js/music-player.js?v=<?php echo filemtime('./plantillas/plantilla-3/js/music-player.js'); ?>"></script>
<script src="./plantillas/plantilla-3/js/galeria-rotacion.js?v=<?php echo filemtime('./plantillas/plantilla-3/js/galeria-rotacion.js'); ?>"></script>

</body>
</html>