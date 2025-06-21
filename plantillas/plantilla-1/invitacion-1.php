<?php
// Simulación de datos del backend
$nombres = "Victoria & Matthew";
$fecha = "30 de julio de 2025";
$hora_ceremonia = "14:00";
$ubicacion = "Restaurant Belaire, Los Angeles";
$direccion_completa = "1234 Sunset Blvd, Los Angeles";
$coordenadas = "34.0522,-118.2437"; // Coordenadas para Google Maps
$historia_texto = "Todo comenzó con un momento simple, que se convirtió en recuerdos, risas y amor. Cada paso de este viaje nos ha acercado más a nuestro día especial.";
$dresscode = "Por favor, viste atuendo elegante para complementar la atmósfera sofisticada de nuestro día especial.";

// Cronograma del evento
$cronograma = [
    ["hora" => "14:00", "evento" => "Ceremonia", "icono"],
    ["hora" => "15:30", "evento" => "Cóctel de bienvenida", "icono"],
    ["hora" => "17:00", "evento" => "Banquete", "icono"],
    ["hora" => "19:30", "evento" => "Baile y celebración", "icono"]
];

// Preguntas frecuentes
$faqs = [
    ["pregunta" => "¿Se permite la asistencia de niños?", "respuesta" => "Sí, los niños son bienvenidos. Habrá un área especial para ellos."],
    ["pregunta" => "¿Dónde puedo estacionar?", "respuesta" => "El restaurante cuenta con servicio de valet parking gratuito para todos los invitados."],
    ["pregunta" => "¿Qué regalo podemos llevar?", "respuesta" => "Su presencia es nuestro mejor regalo. Si desean obsequiarnos algo, tenemos mesa de regalos en Macy's."],
    ["pregunta" => "¿Hay hoteles cerca?", "respuesta" => "Sí, recomendamos Hotel Beverly Hills y The Standard, ambos a 5 minutos del restaurante."]
];

// Galería de imágenes (rutas de ejemplo)
$galeria = [
    "img/galeria/pareja1.jpg",
    "img/galeria/pareja2.jpg", 
    "img/galeria/pareja3.jpg",
    "img/galeria/pareja4.jpg",
    "img/galeria/pareja5.jpg"
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $nombres; ?> - Invitación de Boda</title>
    <!-- Estilos -->

    <!-- <link rel="stylesheet" href="./css/invitacion.css"> Hoja con todos los estilos-->
    <link rel="stylesheet" href="./css/global.css">
    <link rel="stylesheet" href="./css/hero.css">
    <link rel="stylesheet" href="./css/bienvenida.css">
    <link rel="stylesheet" href="./css/historia.css">
    <link rel="stylesheet" href="./css/cronograma.css">
    <link rel="stylesheet" href="./css/galeria.css">
    <link rel="stylesheet" href="./css/dresscode.css">
    <link rel="stylesheet" href="./css/faq.css">
    <link rel="stylesheet" href="./css/rsvp.css">
    <link rel="stylesheet" href="./css/footer.css">
    <link rel="stylesheet" href="./css/responsive.css">
    
    <!-- Fuentes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
<!-- Sección Hero -->
<section class="hero" id="hero">
    <div class="hero-content">
        <div class="hero-header">THE MARRIAGE OF</div>
        <h1 class="hero-names"><?php echo $nombres; ?></h1>
        <div class="hero-details">
            <div class="hero-date"><?php echo strtoupper($fecha); ?></div>
            <div class="hero-location"><?php echo strtoupper($ubicacion); ?></div>
        </div>
    </div>
    <div class="hero-background"></div>
</section>

<!-- Imagen de transición full-screen -->
<section class="transition-image">
    <img src="./img/hero.jpg" alt="Victoria & Matthew" />
</section>

<!-- Sección Bienvenida -->
<section class="bienvenida" id="bienvenida">
    <div class="container">
        <div class="bienvenida-content">
            <h2>Dear Family & Friends,</h2>
            <p>WE ARE THRILLED TO INVITE YOU TO CELEBRATE THE BEGINNING OF OUR FOREVER. YOUR LOVE AND SUPPORT MEAN THE WORLD TO US, AND WE CAN'T WAIT TO SHARE THIS SPECIAL DAY WITH YOU.</p>
            
            <div class="bienvenida-image">
                <img src="./img/dedicatoria.jpg" alt="<?php echo $nombres; ?>" />
            </div>
            
            <div class="bienvenida-date-section">
                <div class="bienvenida-date"><?php echo strtoupper($fecha); ?></div>
                <div class="bienvenida-venue">
                    <p>THE GATHERING WILL BE AT <?php echo $hora_ceremonia; ?> AT <?php echo strtoupper($ubicacion); ?>.</p>
                    <p class="venue-address">(<?php echo strtoupper($direccion_completa); ?>)</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sección Historia -->
<section class="historia" id="historia">
    <div class="container">
        <div class="historia-content">
            <h2>Our Story</h2>
            <div class="historia-text">
                <p>IT ALL STARTED WITH A SIMPLE MOMENT, WHICH TURNED INTO MEMORIES, LAUGHTER, AND LOVE. EVERY STEP OF THIS JOURNEY HAS BROUGHT US CLOSER TO OUR SPECIAL DAY.</p>
                
                <p>THROUGH ADVENTURES, LATE-NIGHT TALKS, AND SHARED DREAMS, WE HAVE BUILT SOMETHING TRULY SPECIAL. NOW WE CAN'T WAIT TO TAKE THE NEXT STEP AND BEGIN OUR FOREVER TOGETHER.</p>
                
                <p>JOIN US AS WE CELEBRATE OUR LOVE STORY WITH THE PEOPLE WHO MEAN THE MOST. YOUR PRESENCE WILL MAKE THIS DAY EVEN MORE UNFORGETTABLE.</p>
            </div>
        </div>
    </div>
</section>

<!-- Imagen de transición después de historia -->
<section class="transition-image">
    <img src="./img/hero.jpg" alt="Imagen historia" />
</section>

<!-- Sección Cronograma -->
<section class="cronograma" id="cronograma">
    <div class="container">
        <h2>MINUTO A MINUTO</h2>
        <div class="cronograma-timeline">
            <!-- Línea vertical central -->
            <div class="timeline-line"></div>
            
            <?php 
            $iconos_nombres = ['anillos', 'cena', 'fiesta', 'luna'];
            foreach($cronograma as $index => $item): 
            ?>
            <div class="timeline-item" data-delay="<?php echo $index * 200; ?>">
                <!-- Icono siempre centrado -->
                <div class="timeline-icon">
                    <img src="./img/iconos/<?php echo $iconos_nombres[$index]; ?>.png" alt="<?php echo $item['evento']; ?>" />
                </div>
                
                <!-- Contenido que alterna izquierda/derecha -->
                <div class="timeline-content <?php echo ($index % 2 == 0) ? 'left' : 'right'; ?>">
                    <div class="timeline-event"><?php echo strtoupper($item['evento']); ?></div>
                    <div class="timeline-time"><?php echo $item['hora']; ?></div>
                    <div class="timeline-description">
                        <?php 
                        $descriptions = [
                            "Ceremonia" => "Comparte con nosotros este momento.",
                            "Cóctel de bienvenida" => "Brindemos juntos por nuestro amor.",
                            "Banquete" => "Cenar juntos hace esta noche especial.",
                            "Baile y celebración" => "¡Hey! comienza la diversión."
                        ];
                        echo $descriptions[$item['evento']] ?? '';
                        ?>
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
                    <img src="<?php echo $imagen; ?>" alt="Momento especial" />
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
            <h2>Dresscode</h2>
            <p>KINDLY WEAR ELEGANT ATTIRE TO COMPLEMENT THE SOPHISTICATED ATMOSPHERE OF OUR SPECIAL DAY.</p>
            
            <div class="dresscode-gender-section">
                <div class="gender-section">
                    <h3>MEN</h3>
                    <div class="color-dots">
                        <div class="color-dot black"></div>
                        <div class="color-dot white"></div>
                    </div>
                </div>
                <div class="gender-section">
                    <h3>WOMEN</h3>
                    <div class="color-dots">
                        <div class="color-dot burgundy"></div>
                        <div class="color-dot white"></div>
                    </div>
                </div>
            </div>
            
            <div class="dresscode-examples">
                <div class="dresscode-example-image women">
                    <img src="img/dresscode2.webp" alt="Ejemplo vestimenta femenina" />
                </div>
                <div class="dresscode-example-image men">
                    <img src="img/dresscode.webp" alt="Ejemplo vestimenta masculina" />
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Sección RSVP -->
    <section class="rsvp" id="rsvp">
        <div class="container">
            <h2>Confirma tu Asistencia</h2>
            <p>Por favor, confirma tu asistencia antes del 15 de julio</p>
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
                        <span><?php echo $faq['pregunta']; ?></span>
                        <span class="faq-arrow">▼</span>
                    </button>
                    <div class="faq-answer" id="faq-<?php echo $index; ?>">
                        <p><?php echo $faq['respuesta']; ?></p>
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
                    "El amor es la fuerza más poderosa del mundo, y sin embargo, es la más humilde imaginable."
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
                    Con amor, <?php echo $nombres; ?>
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

    <script src="./js/invitacion.js"></script>
</body>
</html>