<?php
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    exit("Invitación no encontrada");
}

$database = new Database();
$db = $database->getConnection();

// Obtener datos de la invitación
$query = "SELECT * FROM invitaciones WHERE slug = ?";
$stmt = $db->prepare($query);
$stmt->execute([$slug]);
$invitacion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invitacion) {
    header("HTTP/1.0 404 Not Found");
    exit("Invitación no encontrada");
}

// Obtener cronograma
$cronograma_query = "SELECT * FROM invitacion_cronograma WHERE invitacion_id = ? ORDER BY hora";
$cronograma_stmt = $db->prepare($cronograma_query);
$cronograma_stmt->execute([$invitacion['id']]);
$cronograma = $cronograma_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener FAQs
$faq_query = "SELECT * FROM invitacion_faq WHERE invitacion_id = ?";
$faq_stmt = $db->prepare($faq_query);
$faq_stmt->execute([$invitacion['id']]);
$faqs = $faq_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener galería
$galeria_query = "SELECT * FROM invitacion_galeria WHERE invitacion_id = ?";
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

// Obtener imágenes de dresscode
$dresscode_query = "SELECT hombres, mujeres FROM invitacion_dresscode WHERE invitacion_id = ?";
$dresscode_stmt = $db->prepare($dresscode_query);
$dresscode_stmt->execute([$invitacion['id']]);
$dresscode_img = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);

// CORRECCIÓN: Construir las rutas correctamente
if ($dresscode_img) {
    $img_dresscode_hombres = !empty($dresscode_img['hombres']) ? './' . ltrim($dresscode_img['hombres'], '/') : './plantillas/plantilla-1/img/dresscode.webp';
    $img_dresscode_mujeres = !empty($dresscode_img['mujeres']) ? './' . ltrim($dresscode_img['mujeres'], '/') : './plantillas/plantilla-1/img/dresscode2.webp';
} else {
    // Si no hay registro en la tabla dresscode, usar imágenes por defecto
    $img_dresscode_hombres = './plantillas/plantilla-1/img/dresscode.webp';
    $img_dresscode_mujeres = './plantillas/plantilla-1/img/dresscode2.webp';
}

// Asignar variables para compatibilidad con el template original
$nombres = $invitacion['nombres_novios'];
$fecha = date('j \d\e F \d\e Y', strtotime($invitacion['fecha_evento']));
$hora_ceremonia = date('H:i', strtotime($invitacion['hora_evento']));
$ubicacion = $invitacion['ubicacion'];  
$direccion_completa = $invitacion['direccion_completa'];
$coordenadas = $invitacion['coordenadas'];
$historia_texto = $invitacion['historia'] ?: "Todo comenzó con un momento simple, que se convirtió en recuerdos, risas y amor. Cada paso de este viaje nos ha acercado más a nuestro día especial.";
$dresscode = $invitacion['dresscode'] ?: "Por favor, viste atuendo elegante para complementar la atmósfera sofisticada de nuestro día especial.";

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
if (empty($faqs)) {
    $faqs = [
        ["pregunta" => "¿Se permite la asistencia de niños?", "respuesta" => "Sí, los niños son bienvenidos. Habrá un área especial para ellos."],
        ["pregunta" => "¿Dónde puedo estacionar?", "respuesta" => "El restaurante cuenta con servicio de valet parking gratuito para todos los invitados."],
       ["pregunta" => "¿Qué regalo podemos llevar?", "respuesta" => "Su presencia es nuestro mejor regalo. Si desean obsequiarnos algo, tenemos mesa de regalos en Macy's."],
       ["pregunta" => "¿Hay hoteles cerca?", "respuesta" => "Sí, recomendamos Hotel Beverly Hills y The Standard, ambos a 5 minutos del restaurante."]
   ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title><?php echo htmlspecialchars($nombres); ?> - Invitación de Boda</title>
   <!-- Estilos -->
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/global.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/hero.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/bienvenida.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/historia.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/cronograma.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/galeria.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/dresscode.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/faq.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/rsvp.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/footer.css">
   <link rel="stylesheet" href="./plantillas/plantilla-1/css/responsive.css">
   
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
   <img src="<?php echo $invitacion['imagen_hero'] ?: './img/hero.jpg'; ?>" alt="<?php echo htmlspecialchars($nombres); ?>" />
</section>

<!-- Sección Bienvenida -->
<section class="bienvenida" id="bienvenida">
   <div class="container">
       <div class="bienvenida-content">
           <h2>Querida familia y amigos,</h2>
           <p>Estamos encantados de invitarles a celebrar el comienzo de nuestro <b>para siempre</b>. Su amor y apoyo significan el mundo para nosotros, y no podemos esperar para compartir este día tan especial a su lado.</p>
           
           <div class="bienvenida-image">
               <img src="<?php echo $invitacion['imagen_dedicatoria'] ?: './img/dedicatoria.jpg'; ?>" alt="<?php echo htmlspecialchars($nombres); ?>" />
           </div>
           
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
   <img src="<?php echo $invitacion['imagen_destacada'] ?: './img/hero.jpg'; ?>" alt="Imagen historia" />
</section>

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
                   <div class="timeline-time"><?php echo $item['hora']; ?></div>
                   <div class="timeline-description">
                       <?php echo htmlspecialchars($item['descripcion'] ?? ''); ?>
                   </div>
               </div>
           </div>
           <?php endforeach; ?>
       </div>
   </div>
</section>

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
                   <div class="color-dots">
                       <div class="color-dot black"></div>
                       <div class="color-dot white"></div>
                   </div>
               </div>
               <div class="gender-section">
                   <h3>Mujer</h3>
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

<!-- Sección RSVP -->
<section class="rsvp" id="rsvp">
   <div class="container">
       <h2>Confirma tu Asistencia</h2>
       <p><?php echo htmlspecialchars($invitacion['texto_rsvp'] ?: 'Por favor, confirma tu asistencia antes del 15 de julio'); ?></p>
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
               <label for="asistencia">¿Asistirás? *</label>
               <select id="asistencia" name="asistencia" required>
                   <option value="">Selecciona una opción</option>
                   <option value="si">Sí, asistiré</option>
                   <option value="no">No podré asistir</option>
               </select>
           </div>
           <div class="form-group">
               <label for="acompanantes">Número de acompañantes</label>
               <input type="number" id="acompanantes" name="acompanantes" min="0" max="5" value="0">
           </div>
           <div class="form-group">
               <label for="comentario">Comentario opcional</label>
               <textarea id="comentario" name="comentario" rows="3" placeholder="Mensaje especial para los novios..."></textarea>
           </div>
           <button type="submit" class="form-submit">Enviar Confirmación</button>
       </form>
   </div>
</div>

<!-- Sección FAQ -->
<section class="faq" id="faq">
   <div class="container">
       <h2>Preguntas Frecuentes</h2>
       <div class="faq-list">
           <?php foreach($faqs as $index => $faq): ?>
           <div class="faq-item">
               <button class="faq-question" onclick="toggleFAQ(<?php echo $index; ?>)">
                   <span><?php echo htmlspecialchars($faq['pregunta']); ?></span>
                   <span class="faq-arrow">▼</span>
               </button>
               <div class="faq-answer" id="faq-<?php echo $index; ?>">
                   <p><?php echo htmlspecialchars($faq['respuesta']); ?></p>
               </div>
           </div>
           <?php endforeach; ?>
       </div>
   </div>
</section>

<!-- Footer -->
<footer class="footer">
   <div class="container">
       <div class="footer-content">
           <p class="footer-message">
               <?php echo htmlspecialchars($invitacion['mensaje_footer'] ?: '"El amor es la fuerza más poderosa del mundo, y sin embargo, es la más humilde imaginable."'); ?>
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
               Con amor, <?php echo htmlspecialchars($invitacion['firma_footer'] ?: $nombres); ?>
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

<script src="./plantillas/plantilla-1/js/invitacion.js"></script>
</body>
</html>