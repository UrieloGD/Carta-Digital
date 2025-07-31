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
    
    // Crear objeto DateTime desde la hora
    $dateTime = DateTime::createFromFormat('H:i:s', $hora);
    
    // Si no funciona con segundos, intentar sin segundos
    if (!$dateTime) {
        $dateTime = DateTime::createFromFormat('H:i', $hora);
    }
    
    // Si aún no funciona, intentar con formato completo de fecha-hora
    if (!$dateTime) {
        $dateTime = new DateTime($hora);
    }
    
    if ($dateTime) {
        // Formatear a 12 horas con AM/PM en español
        $horaFormateada = $dateTime->format('g:i A');
        
        // Convertir AM/PM a español
        $horaFormateada = str_replace(['AM', 'PM'], ['AM', 'PM'], $horaFormateada);
        
        return $horaFormateada;
    }
    
    return $hora; // Devolver original si no se puede formatear
}

// Función alternativa para formato 24 horas si prefieres
function formatearHora24($hora) {
    if (empty($hora)) return '';
    
    $dateTime = DateTime::createFromFormat('H:i:s', $hora);
    
    if (!$dateTime) {
        $dateTime = DateTime::createFromFormat('H:i', $hora);
    }
    
    if (!$dateTime) {
        $dateTime = new DateTime($hora);
    }
    
    if ($dateTime) {
        return $dateTime->format('H:i');
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

// Obtener FAQs activas
$faq_query = "SELECT * FROM invitacion_faq WHERE invitacion_id = ? AND activa = 1 ORDER BY orden";
$faq_stmt = $db->prepare($faq_query);
$faq_stmt->execute([$invitacion['id']]);
$faqs = $faq_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener galería activa
$galeria_query = "SELECT * FROM invitacion_galeria WHERE invitacion_id = ? AND activa = 1 ORDER BY orden";
$galeria_stmt = $db->prepare($galeria_query);
$galeria_stmt->execute([$invitacion['id']]);
$galeria_result = $galeria_stmt->fetchAll(PDO::FETCH_ASSOC);
$galeria = array_column($galeria_result, 'ruta');

// Si no hay imágenes en la galería, usar las por defecto
if (empty($galeria)) {
    $galeria = [
        "./plantillas/plantilla-1/img/galeria/pareja1.jpg",
        "./plantillas/plantilla-1/img/galeria/pareja2.jpg", 
        "./plantillas/plantilla-1/img/galeria/pareja3.jpg",
        "./plantillas/plantilla-1/img/galeria/pareja4.jpg",
        "./plantillas/plantilla-1/img/galeria/pareja5.jpg"
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

// Construir las rutas de imágenes de dresscode
if ($dresscode_info) {
    $img_dresscode_hombres = !empty($dresscode_info['hombres']) ? './' . ltrim($dresscode_info['hombres'], '/') : './plantillas/plantilla-1/img/dresscode.webp';
    $img_dresscode_mujeres = !empty($dresscode_info['mujeres']) ? './' . ltrim($dresscode_info['mujeres'], '/') : './plantillas/plantilla-1/img/dresscode2.webp';
    $descripcion_dresscode_hombres = $dresscode_info['descripcion_hombres'] ?? '';
    $descripcion_dresscode_mujeres = $dresscode_info['descripcion_mujeres'] ?? '';
} else {
    // Si no hay registro en la tabla dresscode, usar imágenes por defecto
    $img_dresscode_hombres = './plantillas/plantilla-1/img/dresscode.webp';
    $img_dresscode_mujeres = './plantillas/plantilla-1/img/dresscode2.webp';
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
$historia_texto = $invitacion['historia'] ?: "Todo comenzó con un momento simple, que se convirtió en recuerdos, risas y amor. Cada paso de este viaje nos ha acercado más a nuestro día especial.";
// $frase_historia = $invitacion['frase_historia'] ?: 'Nuestra Historia';
$dresscode = $invitacion['dresscode'] ?: "Por favor, viste atuendo elegante para complementar la atmósfera sofisticada de nuestro día especial.";
$texto_rsvp = $invitacion['texto_rsvp'] ?: 'Por favor, confirma tu asistencia antes del 15 de julio';
$mensaje_footer = $invitacion['mensaje_footer'] ?: '"El amor es la fuerza más poderosa del mundo, y sin embargo, es la más humilde imaginable."';
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

// Si no hay cronograma, usar el por defecto
if (empty($cronograma)) {
    $cronograma = [
        ["hora" => "14:00", "evento" => "Ceremonia", "icono" => "anillos", "descripcion" => "Comparte con nosotros este momento."],
        ["hora" => "15:30", "evento" => "Cóctel de bienvenida", "icono" => "cena", "descripcion" => "Brindemos juntos por nuestro amor."],
        ["hora" => "17:00", "evento" => "Banquete", "icono" => "fiesta", "descripcion" => "Cenar juntos hace esta noche especial."],
        ["hora" => "19:30", "evento" => "Baile y celebración", "icono" => "luna", "descripcion" => "¡Hey! comienza la diversión."]
    ];
}

// Si no hay FAQs, usar las por defecto
/* if (empty($faqs)) {
    $faqs = [
        ["pregunta" => "¿Se permite la asistencia de niños?", "respuesta" => "Sí, los niños son bienvenidos. Habrá un área especial para ellos."],
        ["pregunta" => "¿Dónde puedo estacionar?", "respuesta" => "El restaurante cuenta con servicio de valet parking gratuito para todos los invitados."],
        ["pregunta" => "¿Qué regalo podemos llevar?", "respuesta" => "Su presencia es nuestro mejor regalo. Si desean obsequiarnos algo, tenemos mesa de regalos en Macy's."],
        ["pregunta" => "¿Hay hoteles cerca?", "respuesta" => "Sí, recomendamos Hotel Beverly Hills y The Standard, ambos a 5 minutos del restaurante."]
    ];
} */

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
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/global.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/global.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/hero.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/hero.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/bienvenida.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/bienvenida.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/padres-padrinos.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/padres-padrinos.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/historia.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/historia.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/contador.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/contador.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/cronograma.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/cronograma.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/ubicaciones.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/ubicaciones.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/galeria.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/galeria.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/dresscode.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/dresscode.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/faq.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/faq.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/rsvp.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/rsvp.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/footer.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/footer.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/responsive.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/responsive.css'); ?>" />
    <link rel="stylesheet" href="./plantillas/plantilla-1/css/music-player.css?v=<?php echo filemtime('./plantillas/plantilla-1/css/music-player.css'); ?>" />
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    
</head>
<body>
<!-- Sección Hero -->
<section class="hero" id="hero">
   <div class="hero-content">
       <div class="hero-header">LA BODA DE</div>
       <h1 class="hero-names"><?php echo htmlspecialchars($nombres); ?></h1>
       <div class="hero-details">
           <div class="hero-date"><?php echo strtoupper($fecha); ?></div>
           <div class="hero-location"><?php echo strtoupper($ubicacion); ?></div>
       </div>
   </div>
   <div class="hero-background"></div>
</section>

<!-- Imagen de transición full-screen -->
<section class="transition-image">
   <img src="<?php echo htmlspecialchars($imagen_hero); ?>" alt="<?php echo htmlspecialchars($nombres); ?>" />
</section>

<!-- Sección Bienvenida -->
<section class="bienvenida" id="bienvenida">
   <div class="container">
       <div class="bienvenida-content">
           <h2>Querida familia y amigos,</h2>
           <p>Estamos encantados de invitarles a celebrar el comienzo de nuestro <b>para siempre</b>. Su amor y apoyo significan el mundo para nosotros, y no podemos esperar para compartir este día tan especial a su lado.</p>
           
           <div class="bienvenida-image">
               <img src="<?php echo htmlspecialchars($imagen_dedicatoria); ?>" alt="<?php echo htmlspecialchars($nombres); ?>" />
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
               <div class="bienvenida-date"><?php echo strtoupper($fecha); ?></div>
               <div class="bienvenida-venue">
                   <p>LA CEREMONIA SERÁ A LAS <?php echo $hora_ceremonia; ?> EN <?php echo strtoupper($ubicacion); ?>.</p>
                   <?php if ($direccion_completa): ?>
                   <p class="venue-address">(<?php echo strtoupper($direccion_completa); ?>)</p>
                   <?php endif; ?>
               </div>
           </div>
       </div>
   </div>
</section>

<!-- Sección Historia -->
<section class="historia" id="historia">
   <div class="container">
       <div class="historia-content">
           <h2>NUESTRA HISTORIA</h2>
           <?php if ($frase_historia): ?>
           <div class="historia-frase">
               <p><em><?php echo strtoupper(htmlspecialchars($frase_historia)); ?></em></p>
           </div>
           <?php endif; ?>
           <div class="historia-text">
               <?php
               // Dividir la historia en párrafos si tiene saltos de línea
               $historia_parrafos = explode("\n", $historia_texto);
               foreach ($historia_parrafos as $parrafo) {
                   if (trim($parrafo)) {
                       echo '<p>' . strtoupper(htmlspecialchars(trim($parrafo))) . '</p>';
                   }
               }
               ?>
           </div>
       </div>
   </div>
</section>

<!-- Imagen de transición después de historia -->
<section class="transition-image">
   <img src="<?php echo htmlspecialchars($imagen_destacada); ?>" alt="Imagen historia" />
</section>

<?php if ($mostrar_contador): ?>
<!-- Contador regresivo -->
<section class="contador" id="contador">
   <div class="container">
       <h2>Save the Date!</h2>
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
</section>
<?php endif; ?>

<!-- Sección Cronograma -->
<section class="cronograma" id="cronograma">
   <div class="container">
       <h2>MINUTO A MINUTO</h2>
       <div class="cronograma-timeline">
           <!-- Línea vertical central -->
           <div class="timeline-line"></div>
           
           <?php foreach($cronograma as $index => $item): ?>
           <div class="timeline-item" data-delay="<?php echo $index * 200; ?>">
               <!-- Icono siempre centrado -->
               <div class="timeline-icon">
                   <img src="./plantillas/plantilla-1/img/iconos/<?php echo $item['icono']; ?>.png" alt="<?php echo htmlspecialchars($item['evento']); ?>" />
               </div>
               
               <!-- Contenido que alterna izquierda/derecha -->
               <div class="timeline-content <?php echo ($index % 2 == 0) ? 'left' : 'right'; ?>">
                   <div class="timeline-event"><?php echo strtoupper(htmlspecialchars($item['evento'])); ?></div>
                   <div class="timeline-time"><?php echo formatearHora($item['hora']); ?></div>
                   <div class="timeline-description">
                       <?php echo htmlspecialchars($item['descripcion'] ?? ''); ?>
                   </div>
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
       <h2>UBICACIONES</h2>
       <div class="ubicaciones-grid">
           <?php foreach($ubicaciones_result as $ubicacion_item): ?>
           <div class="ubicacion-card">
               <div class="ubicacion-content">
                   <div class="ubicacion-info">
                       <div class="ubicacion-tipo"><?php echo strtoupper($ubicacion_item['tipo']); ?></div>
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
                   <?php if ($ubicacion_item['imagen']): ?>
                   <div class="ubicacion-image">
                       <img src="<?php echo htmlspecialchars($ubicacion_item['imagen']); ?>" alt="<?php echo htmlspecialchars($ubicacion_item['nombre_lugar']); ?>" />
                   </div>
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
       <h2>Momentos Especiales</h2>
       <div class="galeria-carousel">
           <div class="carousel-track">
               <?php 
               // Crear un array con suficientes repeticiones para bucle infinito
               $imagenes_infinitas = [];
               for($i = 0; $i < 6; $i++) { // 6 repeticiones
                   $imagenes_infinitas = array_merge($imagenes_infinitas, $galeria);
               }
               
               foreach($imagenes_infinitas as $index => $imagen): 
               ?>
               <div class="galeria-item">
                   <img src="<?php echo htmlspecialchars($imagen); ?>" alt="Momento especial" />
               </div>
               <?php endforeach; ?>
           </div>
       </div>
   </div>
</section>

<!-- Sección Dress Code -->
<section class="dresscode" id="dresscode">
   <div class="container">
       <div class="dresscode-content">
           <h2>Código de vestimenta</h2>
           <p><?php echo strtoupper(htmlspecialchars($dresscode)); ?></p>
           
           <div class="dresscode-gender-section">
               <div class="gender-section">
                   <h3>Hombre</h3>
                   <?php if ($descripcion_dresscode_hombres): ?>
                   <p><?php echo htmlspecialchars($descripcion_dresscode_hombres); ?></p>
                   <?php endif; ?>
                   <div class="color-dots">
                       <div class="color-dot black"></div>
                       <div class="color-dot white"></div>
                   </div>
               </div>
               <div class="gender-section">
                   <h3>Mujer</h3>
                   <?php if ($descripcion_dresscode_mujeres): ?>
                   <p><?php echo htmlspecialchars($descripcion_dresscode_mujeres); ?></p>
                   <?php endif; ?>
                   <div class="color-dots">
                       <div class="color-dot burgundy"></div>
                       <div class="color-dot white"></div>
                   </div>
               </div>
           </div>
           
           <div class="dresscode-examples">
               <div class="dresscode-example-image women">
                    <img src="<?php echo htmlspecialchars($img_dresscode_mujeres); ?>" alt="Ejemplo vestimenta femenina" />
               </div>
               <div class="dresscode-example-image men">
                    <img src="<?php echo htmlspecialchars($img_dresscode_hombres); ?>" alt="Ejemplo vestimenta masculina" />
               </div>
           </div>
       </div>
   </div>
</section>

<!-- Sección Mesa de Regalos -->
<?php if (!empty($mesa_regalos)): ?>
<section class="mesa-regalos" id="mesa-regalos">
   <div class="container">
       <h2>Mesa de Regalos</h2>
       <p>Tu presencia es nuestro mejor regalo, pero si deseas obsequiarnos algo especial:</p>
       <div class="regalos-grid">
           <?php foreach($mesa_regalos as $regalo): ?>
           <div class="regalo-card">
               <?php if ($regalo['icono']): ?>
               <div class="regalo-icon">
                   <img src="<?php echo htmlspecialchars($regalo['icono']); ?>" alt="<?php echo htmlspecialchars($regalo['nombre_tienda'] ?: $regalo['tienda']); ?>" />
               </div>
               <?php endif; ?>
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
           <?php endforeach; ?>
       </div>
   </div>
</section>
<?php endif; ?>

<!-- Sección RSVP -->
<section class="rsvp" id="rsvp">
   <div class="container">
       <h2>Confirma tu Asistencia</h2>
       <p><?php echo htmlspecialchars($texto_rsvp); ?></p>
       <button class="rsvp-button" onclick="openRSVPModal()">Confirmar Asistencia</button>
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

<!-- Sección FAQ -->
<!-- <section class="faq" id="faq">
   <div class="container">
       <h2>Preguntas Frecuentes</h2>
       <div class="faq-list">
           < ?php foreach($faqs as $index => $faq): ?>
           <div class="faq-item">
               <button class="faq-question" onclick="toggleFAQ(< ?php echo $index; ?>)">
                   <span>< ?php echo htmlspecialchars($faq['pregunta']); ?></span>
                  <span class="faq-arrow">▼</span>
              </button>
              <div class="faq-answer" id="faq-< ?php echo $index; ?>">
                  <p>< ?php echo htmlspecialchars($faq['respuesta']); ?></p>
              </div>
          </div>
          < ?php endforeach; ?>
      </div>
  </div>
</section> -->

<!-- Footer -->
<footer class="footer">
  <div class="container">
      <div class="footer-content">
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
          </div>
          <p class="footer-thanks">
              Gracias por ser parte de nuestro día especial
          </p>
          <p class="footer-signature">
              Con amor, <?php echo htmlspecialchars($firma_footer); ?>
          </p>
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

<?php if (!empty($musica_youtube_url)): ?>
<link rel="stylesheet" href="./plantillas/plantilla-1/css/music-player.css">
<script src="./plantillas/plantilla-1/js/music-player.js"></script>
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
};
</script>

<script src="./plantillas/plantilla-1/js/contador.js?v=<?php echo filemtime('./plantillas/plantilla-1/js/contador.js'); ?>"></script>
<script src="./plantillas/plantilla-1/js/compartir.js?v=<?php echo filemtime('./plantillas/plantilla-1/js/compartir.js'); ?>"></script>
<script src="./plantillas/plantilla-1/js/rsvp.js?v=<?php echo filemtime('./plantillas/plantilla-1/js/rsvp.js'); ?>"></script>
<script src="./plantillas/plantilla-1/js/faq.js?v=<?php echo filemtime('./plantillas/plantilla-1/js/faq.js'); ?>"></script>
<script src="./plantillas/plantilla-1/js/estadisticas.js?v=<?php echo filemtime('./plantillas/plantilla-1/js/estadisticas.js'); ?>"></script>
<script src="./plantillas/plantilla-1/js/invitacion.js?v=<?php echo filemtime('./plantillas/plantilla-1/js/invitacion.js'); ?>"></script>
<script src="./plantillas/plantilla-1/js/music-player.js?v=<?php echo filemtime('./plantillas/plantilla-1/js/music-player.js'); ?>"></script>
</body>
</html>