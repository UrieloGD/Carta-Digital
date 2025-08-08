<?php
require_once './config/database.php';

$plantilla_id = $_GET['id'] ?? 1;

// Validar que la plantilla existe
$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM plantillas WHERE id = ? AND activa = 1";
$stmt = $db->prepare($query);
$stmt->execute([$plantilla_id]);
$plantilla = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plantilla) {
    header("HTTP/1.0 404 Not Found");
    exit("Plantilla no encontrada");
}

// Funciones helper (copiadas de invitacion.php)
function fechaEnEspanol($fecha) {
    $meses = [
        'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
        'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
        'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
        'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'
    ];
    
    $fechaIngles = date('j \d\e F \d\e Y', strtotime($fecha));
    return str_replace(array_keys($meses), array_values($meses), $fechaIngles);
}

function formatearHora($hora) {
    if (empty($hora)) return '';
    
    $dateTime = DateTime::createFromFormat('H:i:s', $hora);
    if (!$dateTime) {
        $dateTime = DateTime::createFromFormat('H:i', $hora);
    }
    
    if ($dateTime) {
        return $dateTime->format('g:i A');
    }
    
    return $hora;
}

// Determinar qué plantilla usar
$carpeta_plantilla = $plantilla['carpeta'];
$archivo_plantilla = $plantilla['archivo_principal'];

// ==========================================
// DATOS DE DEMOSTRACIÓN
// ==========================================

// Datos principales de ejemplo
$invitacion = [
    'id' => 999999,
    'nombres_novios' => 'Ana & Carlos',
    'fecha_evento' => '2024-12-15',
    'hora_evento' => '16:00:00',
    'ubicacion' => 'Jardín Botánico',
    'direccion_completa' => 'Av. Revolución 123, Col. Centro, Ciudad de México',
    'historia' => 'Nuestra historia comenzó una tarde de primavera en un café pequeño del centro. Entre risas y conversaciones que parecían no tener fin, supimos que habíamos encontrado algo especial. Desde entonces, cada día ha sido una nueva aventura juntos.',
    'frase_historia' => 'Donde todo comenzó...',
    'dresscode' => 'Te pedimos usar colores elegantes y evitar el blanco. ¡Queremos que luzcas increíble!',
    'texto_rsvp' => 'Por favor confirma tu asistencia antes del 1 de diciembre',
    'mensaje_footer' => '"El amor es la única fuerza capaz de transformar a un enemigo en amigo." - Martin Luther King Jr.',
    'firma_footer' => 'Ana & Carlos',
    'imagen_hero' => './img/hero-demo.jpg',
    'imagen_dedicatoria' => './img/dedicatoria-demo.jpg',
    'imagen_destacada' => './img/destacada-demo.jpg',
    'padres_novia' => 'María González y José López',
    'padres_novio' => 'Carmen Martínez y Roberto Hernández',
    'padrinos_novia' => 'Ana María López y Pedro González',
    'padrinos_novio' => 'Sofía Martínez y Miguel Hernández',
    'mostrar_contador' => 1,
    'musica_youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'musica_autoplay' => 0,
    'musica_volumen' => 50
];

// Ubicaciones de ejemplo
$ubicaciones_result = [
    [
        'tipo' => 'ceremonia',
        'nombre_lugar' => 'Iglesia San José',
        'direccion' => 'Calle Principal 456, Col. Centro',
        'hora_inicio' => '16:00:00',
        'hora_fin' => '17:00:00',
        'descripcion' => 'Ceremonia religiosa en una hermosa iglesia colonial',
        'google_maps_url' => 'https://maps.google.com',
        'imagen' => './img/iglesia-demo.jpg'
    ],
    [
        'tipo' => 'evento',
        'nombre_lugar' => 'Jardín Botánico',
        'direccion' => 'Av. Revolución 123, Col. Centro',
        'hora_inicio' => '18:00:00',
        'hora_fin' => '23:00:00',
        'descripcion' => 'Recepción en un hermoso jardín al aire libre',
        'google_maps_url' => 'https://maps.google.com',
        'imagen' => './img/jardin-demo.jpg'
    ]
];

// Galería de ejemplo
$galeria = [
    "./img/galeria/demo1.jpg",
    "./img/galeria/demo2.jpg",
    "./img/galeria/demo3.jpg",
    "./img/galeria/demo4.jpg",
    "./img/galeria/demo5.jpg"
];

// Dresscode de ejemplo
$img_dresscode_hombres = "./img/dresscode-hombres.webp";
$img_dresscode_mujeres = "./img/dresscode-mujeres.webp";

// Mesa de regalos de ejemplo
$mesa_regalos = [
    [
        'nombre_tienda' => 'Liverpool',
        'numero_evento' => '12345678',
        'codigo_evento' => 'DEMO2024',
        'descripcion' => 'Encuentra regalos perfectos para nuestro hogar',
        'url' => 'https://liverpool.com.mx',
        'icono' => './img/liverpool-icon.png'
    ],
    [
        'nombre_tienda' => 'Amazon',
        'codigo_evento' => 'ANACARLOZ',
        'descripcion' => 'Lista de deseos con productos para nuestro nuevo hogar',
        'url' => 'https://amazon.com.mx',
        'icono' => './img/amazon-icon.png'
    ]
];

// Variables para compatibilidad con plantillas
$nombres = $invitacion['nombres_novios'];
$fecha = fechaEnEspanol($invitacion['fecha_evento']);
$hora_ceremonia = formatearHora($invitacion['hora_evento']);
$ubicacion = $invitacion['ubicacion'];
$direccion_completa = $invitacion['direccion_completa'];
$historia_texto = $invitacion['historia'];
$frase_historia = $invitacion['frase_historia'];
$dresscode = $invitacion['dresscode'];
$texto_rsvp = $invitacion['texto_rsvp'];
$mensaje_footer = $invitacion['mensaje_footer'];
$firma_footer = $invitacion['firma_footer'];
$imagen_hero = $invitacion['imagen_hero'];
$imagen_dedicatoria = $invitacion['imagen_dedicatoria'];
$imagen_destacada = $invitacion['imagen_destacada'];
$padres_novia = $invitacion['padres_novia'];
$padres_novio = $invitacion['padres_novio'];
$padrinos_novia = $invitacion['padrinos_novia'];
$padrinos_novio = $invitacion['padrinos_novio'];
$mostrar_contador = (bool)$invitacion['mostrar_contador'];
$musica_youtube_url = $invitacion['musica_youtube_url'];
$musica_autoplay = (bool)$invitacion['musica_autoplay'];
$musica_volumen = $invitacion['musica_volumen'];

// Construir rutas posibles
$rutas_posibles = [
    "./plantillas/{$carpeta_plantilla}/{$archivo_plantilla}",
    "./plantillas/plantilla-{$plantilla_id}/{$archivo_plantilla}",
    "./plantillas/plantilla-{$plantilla_id}/index.php",
    "./plantillas/plantilla-{$plantilla_id}/invitacion.php"
];

$plantilla_cargada = false;

// Agregar banner de vista previa
echo '<div style="position: fixed; top: 0; left: 0; right: 0; background: #ff6b35; color: white; text-align: center; padding: 10px; z-index: 10000; font-family: Arial, sans-serif;">
        <strong>VISTA PREVIA</strong> - Esta es una demostración de la plantilla con datos de ejemplo
        <a href="./plantillas.php" style="color: white; margin-left: 20px; text-decoration: underline;">← Volver a plantillas</a>
      </div>';
echo '<div style="margin-top: 50px;">';

// Intentar cargar la plantilla
foreach ($rutas_posibles as $ruta) {
    if (file_exists($ruta)) {
        
        // Iniciar captura de output
        ob_start();
        
        include $ruta;
        
        // Obtener el contenido generado
        $contenido = ob_get_contents();
        ob_end_clean();
        
        // Corregir rutas duplicadas
        $contenido = preg_replace('#plantillas//plantillas/#', 'plantillas/', $contenido);
        $contenido = preg_replace('#/plantillas//plantillas/#', '/plantillas/', $contenido);
        
        // Mostrar el contenido corregido
        echo $contenido;
        
        $plantilla_cargada = true;
        break;
    }
}

echo '</div>';

// Fallback si no se encontró ninguna plantilla
if (!$plantilla_cargada) {
    exit("Error: No se puede cargar la plantilla solicitada");
}
?>