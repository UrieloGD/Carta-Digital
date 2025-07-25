<?php
require_once './config/database.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    exit("Invitación no encontrada");
}

$database = new Database();
$db = $database->getConnection();

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
$todas_ubicaciones = [];

foreach ($ubicaciones_result as $ubicacion) {
    $todas_ubicaciones[] = $ubicacion;
    if ($ubicacion['tipo'] === 'ceremonia' && !$ubicacion_ceremonia) {
        $ubicacion_ceremonia = $ubicacion;
    } elseif ($ubicacion['tipo'] === 'evento' && !$ubicacion_evento) {
        $ubicacion_evento = $ubicacion;
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
$galeria_completa = $galeria_result; // Para acceder a descripciones si las necesitas

// Obtener información de dresscode
$dresscode_query = "SELECT * FROM invitacion_dresscode WHERE invitacion_id = ?";
$dresscode_stmt = $db->prepare($dresscode_query);
$dresscode_stmt->execute([$invitacion['id']]);
$dresscode_info = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);

// Obtener mesa de regalos activa
$mesa_regalos_query = "SELECT * FROM invitacion_mesa_regalos WHERE invitacion_id = ? AND activa = 1 ORDER BY orden";
$mesa_regalos_stmt = $db->prepare($mesa_regalos_query);
$mesa_regalos_stmt->execute([$invitacion['id']]);
$mesa_regalos = $mesa_regalos_stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener configuraciones adicionales
$config_query = "SELECT clave, valor, tipo FROM invitacion_configuraciones WHERE invitacion_id = ?";
$config_stmt = $db->prepare($config_query);
$config_stmt->execute([$invitacion['id']]);
$configuraciones_result = $config_stmt->fetchAll(PDO::FETCH_ASSOC);

// Convertir configuraciones a array asociativo
$configuraciones = [];
foreach ($configuraciones_result as $config) {
    $valor = $config['valor'];
    // Convertir según el tipo
    switch ($config['tipo']) {
        case 'numero':
            $valor = (int)$valor;
            break;
        case 'booleano':
            $valor = (bool)$valor;
            break;
        case 'json':
            $valor = json_decode($valor, true);
            break;
    }
    $configuraciones[$config['clave']] = $valor;
}

// Preparar variables globales para las plantillas (manteniendo compatibilidad)
$nombres = $invitacion['nombres_novios'];
$fecha = date('j \d\e F \d\e Y', strtotime($invitacion['fecha_evento']));
$hora_ceremonia = date('H:i', strtotime($invitacion['hora_evento']));

// Variables de ubicación (compatibilidad con versión anterior)
$ubicacion = $invitacion['ubicacion'] ?: ($ubicacion_ceremonia['nombre_lugar'] ?? '');
$direccion_completa = $invitacion['direccion_completa'] ?: ($ubicacion_ceremonia['direccion'] ?? '');

// Contenido principal
$historia_texto = $invitacion['historia'] ?: "Todo comenzó con un momento simple, que se convirtió en recuerdos, risas y amor. Cada paso de este viaje nos ha acercado más a nuestro día especial.";
$frase_historia = $invitacion['frase_historia'] ?: '';
$dresscode = $invitacion['dresscode'] ?: "Por favor, viste atuendo elegante para complementar la atmósfera sofisticada de nuestro día especial.";
$texto_rsvp = $invitacion['texto_rsvp'] ?: 'Por favor confirma tu asistencia';
$mensaje_footer = $invitacion['mensaje_footer'] ?: '';
$firma_footer = $invitacion['firma_footer'] ?: '';

// Imágenes
$imagen_hero = $invitacion['imagen_hero'];
$imagen_dedicatoria = $invitacion['imagen_dedicatoria'];
$imagen_destacada = $invitacion['imagen_destacada'];

// Información familiar
$padres_novia = $invitacion['padres_novia'];
$padres_novio = $invitacion['padres_novio'];
$padrinos_novia = $invitacion['padrinos_novia'];
$padrinos_novio = $invitacion['padrinos_novio'];

// Configuraciones
$mostrar_contador = (bool)$invitacion['mostrar_contador'];

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

// Determinar qué plantilla usar
$plantilla_id = $invitacion['plantilla_id'] ?? 1;
$carpeta_plantilla = $invitacion['plantilla_carpeta'] ?? "plantilla-{$plantilla_id}";
$archivo_plantilla = $invitacion['archivo_principal'] ?? "invitacion-{$plantilla_id}.php";

$ruta_plantilla = "./{$carpeta_plantilla}/{$archivo_plantilla}";

// Cargar la plantilla específica
if (file_exists($ruta_plantilla)) {
    include $ruta_plantilla;
} else {
    // Log del error antes del fallback
    error_log("ERROR: No se encontró la plantilla en {$ruta_plantilla}");
    
    // Fallback más inteligente
    $fallback_path = "./plantillas/plantilla-1/invitacion-1.php";
    if (file_exists($fallback_path)) {
        include $fallback_path;
    } else {
        exit("Error: No se puede cargar ninguna plantilla");
    }
}
?>