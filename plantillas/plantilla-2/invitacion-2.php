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
        "./plantillas/plantilla-2/img/galeria/pareja1.jpg",
        "./plantillas/plantilla-2/img/galeria/pareja2.jpg", 
        "./plantillas/plantilla-2/img/galeria/pareja3.jpg",
        "./plantillas/plantilla-2/img/galeria/pareja4.jpg",
        "./plantillas/plantilla-2/img/galeria/pareja5.jpg"
    ];
}

// Obtener musica
$musica_youtube_url = $invitacion['musica_youtube_url'] ?? '';
$musica_autoplay = (bool)($invitacion['musica_autoplay'] ?? false);
$musica_volumen = $invitacion['musica_volumen'] ?? 0.5;

// Obtener información completa de dresscode
$dresscode_query = "SELECT * FROM invitacion_dresscode WHERE invitacion_id = ?";
$dresscode_stmt = $db->prepare($dresscode_query);
$dresscode_stmt->execute([$invitacion['id']]);
$dresscode_info = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);

// Construir las rutas de imágenes de dresscode - SOLO si existen en la base de datos
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
$historia_texto = $invitacion['historia'] ?: "Nuestra historia comenzó de manera inesperada, pero desde el primer momento supimos que estábamos destinados a estar juntos. Cada día compartido nos ha llevado hasta este momento especial.";
$dresscode = $invitacion['dresscode'] ?: "Elegante casual. Colores que armonicen con nuestro entorno verde.";
$texto_rsvp = $invitacion['texto_rsvp'] ?: 'Tu presencia es importante para nosotros. Por favor confirma tu asistencia.';
$mensaje_footer = $invitacion['mensaje_footer'] ?: '"El amor crece mejor en la libertad, como las flores silvestres en el campo."';
$firma_footer = $invitacion['firma_footer'] ?: $nombres;

// Imágenes principales
$imagen_hero = $invitacion['imagen_hero'] ?: './img/hero.jpg';
$imagen_dedicatoria = $invitacion['imagen_dedicatoria'] ?: './img/dedicatoria.jpg';
$imagen_destacada = $invitacion['imagen_destacada'] ?: './img/hero.jpg';

// Información familiar
$padres_novia = $invitacion['padres_novia'] ?? '';
$padres_novio = $invitacion['padres_novio'] ?? '';
$padrinos_novia = $invitacion['padrinos_novia'] ?? '';
$padrinos_novio = $invitacion['padrinos_novio'] ?? '';

// Configuraciones
$mostrar_contador = (bool)($invitacion['mostrar_contador'] ?? true);
$tipo_contador = $invitacion['tipo_contador'] ?? 'completo';
$mostrar_cronograma = (bool)($invitacion['mostrar_cronograma'] ?? true);

// Informacion RSVP 
$mostrar_fecha_limite_rsvp = (bool)($invitacion['mostrar_fecha_limite_rsvp'] ?? false);
$fecha_limite_rsvp = $invitacion['fecha_limite_rsvp'] ?? null;
$mostrar_solo_adultos = (bool)($invitacion['mostrar_solo_adultos'] ?? true); // true por defecto para compatibilidad
$texto_solo_adultos = $invitacion['texto_solo_adultos'] ?? 'Celebración exclusiva para adultos (No niños).';

// Verificar si el RSVP está habilitado (antes de la fecha límite)
$rsvp_habilitado = true;
$fecha_limite_rsvp = $invitacion['fecha_limite_rsvp'] ?? null;

if ($fecha_limite_rsvp && $mostrar_fecha_limite_rsvp) {
    $fecha_limite = new DateTime($fecha_limite_rsvp);
    $fecha_actual = new DateTime();
    // Agregar un día completo a la fecha límite (hasta fin del día)
    $fecha_limite->setTime(23, 59, 59);
    
    if ($fecha_actual > $fecha_limite) {
        $rsvp_habilitado = false;
    }
}

// Pasar esta variable al JavaScript
echo "<script>const RSVP_HABILITADO = " . ($rsvp_habilitado ? 'true' : 'false') . ";</script>";

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
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/global.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/global.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/hero.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/hero.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/bienvenida.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/bienvenida.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/historia.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/historia.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/contador.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/contador.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/cronograma.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/cronograma.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/ubicaciones.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/ubicaciones.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/galeria.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/galeria.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/dresscode.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/dresscode.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/rsvp.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/rsvp.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/mesa-regalos.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/mesa-regalos.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/footer.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/footer.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/responsive.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/responsive.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-2/css/music-player.css?v=<?php echo filemtime('./plantillas/plantilla-2/css/music-player.css'); ?>" />
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600;700&family=Lato:wght@300;400;500&display=swap" rel="stylesheet">
    
    <!-- Icon page -->
    <link rel="shortcut icon" href="./images/logo.webp" />

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
<section class="contador <?php echo $tipo_contador === 'simple' ? 'contador-simple' : ''; ?>" id="contador">
    <div class="container">
        <div class="contador-content">
            <h2>Faltan...</h2>
            
            <?php if ($tipo_contador === 'simple'): ?>
            <!-- Versión Simple: Solo días -->
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
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($mostrar_cronograma): ?>
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
<?php endif; ?>

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
            <div class="ubicacion-card-wrapper">
                <div class="ubicacion-card">
                    <div class="ubicacion-tipo"><?php echo ucfirst($ubicacion_item['tipo']); ?></div>
                    
                    <?php if (!empty($ubicacion_item['imagen'])): ?>
                    <div class="ubicacion-image">
                        <div class="ubicacion-overlay">
                            <div class="ubicacion-overlay-icon">📍</div>
                        </div>
                        <img src="<?php echo htmlspecialchars($ubicacion_item['imagen']); ?>" alt="<?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?>" />
                    </div>
                    <?php endif; ?>
                    
                    <div class="ubicacion-content">
                        <h3><?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?></h3>
                        
                        <div class="ubicacion-info">
                            <div class="ubicacion-info-item">
                                <div class="ubicacion-info-icon">📍</div>
                                <div class="ubicacion-info-text">
                                    <div class="ubicacion-info-label">Dirección</div>
                                    <p class="ubicacion-info-value"><?php echo htmlspecialchars($ubicacion_item['direccion']); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($ubicacion_item['hora_inicio']): ?>
                            <div class="ubicacion-info-item">
                                <div class="ubicacion-info-icon">🕐</div>
                                <div class="ubicacion-info-text">
                                    <div class="ubicacion-info-label">Horario</div>
                                    <p class="ubicacion-info-value">
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
                        <div class="ubicacion-descripcion"><?php echo htmlspecialchars($ubicacion_item['descripcion']); ?></div>
                        <?php endif; ?>
                        
                        <div class="ubicacion-actions">
                            <?php if ($ubicacion_item['google_maps_url']): ?>
                            <a href="<?php echo htmlspecialchars($ubicacion_item['google_maps_url']); ?>" target="_blank" class="ubicacion-maps">
                                Ver en Maps
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
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
        <div class="galeria-grid" id="galeria-grid">
            <!-- Las imágenes se cargarán dinámicamente con JavaScript -->
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
            
            <?php if (!empty($img_dresscode_mujeres) || !empty($img_dresscode_hombres)): ?>
            <div class="dresscode-examples">
                <?php if (!empty($img_dresscode_mujeres)): ?>
                <div class="dresscode-example">
                    <h3>Mujeres</h3>
                    <div class="dresscode-image">
                        <img src="<?php echo htmlspecialchars($img_dresscode_mujeres); ?>" alt="Vestimenta femenina" />
                        <div class="image-overlay"></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($img_dresscode_hombres)): ?>
                <div class="dresscode-example">
                    <h3>Hombres</h3>
                    <div class="dresscode-image">
                        <img src="<?php echo htmlspecialchars($img_dresscode_hombres); ?>" alt="Vestimenta masculina" />
                        <div class="image-overlay"></div>
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
        <div class="mesa-regalos-header">
            <h2>Mesa de Regalos</h2>
            <div class="decorative-line"></div>
            <p>Tu presencia es nuestro mejor regalo, pero si deseas obsequiarnos algo especial:</p>
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
            <p>Con cariño, agradecemos tu generosidad</p>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Sección RSVP -->
<?php
// Obtener el tipo de RSVP configurado (por defecto 'whatsapp' para compatibilidad)
$tipo_rsvp = $invitacion['tipo_rsvp'] ?? 'whatsapp';
?>

<!-- Sección RSVP Elegante Mejorada - Estilo Natural -->
<section class="rsvp" id="rsvp">
    <div class="container">
        <div class="rsvp-content">
            <div class="rsvp-header">
                <h2 class="section-title">Confirma tu Asistencia</h2>
                <div class="decorative-line"></div>
                <p class="section-subtitle">Tu presencia hace que este día sea perfecto</p>
            </div>
            
            <div class="rsvp-main" data-animate="fadeInUp" data-delay="0.2s">
                <div class="rsvp-message">
                    <p class="rsvp-text"><?php echo htmlspecialchars($texto_rsvp); ?></p>
                </div>
                
                <div class="rsvp-action">
                    <?php if ($tipo_rsvp === 'whatsapp'): ?>
                        <!-- Botón para confirmación por WhatsApp -->
                        <button class="rsvp-button" onclick="<?php echo $rsvp_habilitado ? 'confirmarAsistenciaWhatsApp()' : 'mostrarModalFechaLimite()'; ?>">
                            <span class="button-text">Confirmar por WhatsApp</span>
                            <div class="button-shimmer"></div>
                        </button>
                        
                    <?php else: ?>
                        <!-- Botón para sistema digital -->
                        <button class="rsvp-button" onclick="<?php echo $rsvp_habilitado ? 'openRSVPModal()' : 'mostrarModalFechaLimite()'; ?>">
                            <span class="button-text">Confirmar Asistencia</span>
                            <div class="button-shimmer"></div>
                        </button>
                    <?php endif; ?>
                </div>
                
                <?php if ($invitacion['mostrar_fecha_limite_rsvp'] || $invitacion['mostrar_solo_adultos']): ?>
                <div class="rsvp-details">
                    <div class="detail-grid">
                        <?php if ($invitacion['mostrar_fecha_limite_rsvp'] && !empty($invitacion['fecha_limite_rsvp'])): ?>
                        <div class="detail-item">
                            <div class="detail-icon">📅</div>
                            <div class="detail-content">
                                <span class="detail-label">Fecha límite</span>
                                <span class="detail-value"><?php echo fechaEnEspanol($invitacion['fecha_limite_rsvp']); ?></span>
                                <span class="detail-value">Queremos asegurarnos que tu lugar esté reservado.</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($invitacion['mostrar_solo_adultos']): ?>
                        <div class="detail-item">
                            <div class="detail-icon">👨‍👩‍👧‍👦</div>
                            <div class="detail-content">
                                <span class="detail-label">Solo adultos</span>
                                <span class="detail-value"><?php echo htmlspecialchars($invitacion['texto_solo_adultos'] ?? 'Celebración exclusiva para adultos (No niños).'); ?></span>
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
           <div class="footer-ornament"></div>
           <p class="footer-message">
               <?php echo htmlspecialchars($mensaje_footer); ?>
           </p>

            <?php if ($invitacion['mostrar_compartir'] ?? true): ?>
           <div class="footer-actions">
               <button class="share-button" onclick="shareWhatsApp()">
                   <span>📱</span> Compartir por WhatsApp
               </button>
               <button class="copy-button" onclick="copyLink()">
                   <span>🔗</span> Copiar enlace
               </button>
           </div>
            <?php endif; ?>

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

<?php if (!empty($musica_youtube_url)): ?>
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

<script src="./plantillas/plantilla-2/js/contador.js?v=<?php echo filemtime('./plantillas/plantilla-2/js/contador.js'); ?>"></script>
<script src="./plantillas/plantilla-2/js/compartir.js?v=<?php echo filemtime('./plantillas/plantilla-2/js/compartir.js'); ?>"></script>
<script src="./plantillas/plantilla-2/js/rsvp.js?v=<?php echo filemtime('./plantillas/plantilla-2/js/rsvp.js'); ?>"></script>
<script src="./plantillas/plantilla-2/js/whatsapp.js?v=<?php echo filemtime('./plantillas/plantilla-2/js/whatsapp.js'); ?>"></script>
<script src="./plantillas/plantilla-2/js/estadisticas.js?v=<?php echo filemtime('./plantillas/plantilla-2/js/estadisticas.js'); ?>"></script>
<script src="./plantillas/plantilla-2/js/invitacion.js?v=<?php echo filemtime('./plantillas/plantilla-2/js/invitacion.js'); ?>"></script>
<script src="./plantillas/plantilla-2/js/music-player.js?v=<?php echo filemtime('./plantillas/plantilla-2/js/music-player.js'); ?>"></script>
<script src="./plantillas/plantilla-2/js/galeria-rotacion.js?v=<?php echo filemtime('./plantillas/plantilla-2/js/galeria-rotacion.js'); ?>"></script>

</body>
</html>