<?php
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("HTTP/1.0 404 Not Found");
    exit("Invitación no encontrada");
}

$database = new Database();
$db = $database->getConnection();

// Obtener datos de la invitación con información de plantilla
$query = "SELECT i.*, p.nombre as plantilla_nombre 
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

// Obtener imágenes de dresscode
$dresscode_query = "SELECT hombres, mujeres FROM invitacion_dresscode WHERE invitacion_id = ?";
$dresscode_stmt = $db->prepare($dresscode_query);
$dresscode_stmt->execute([$invitacion['id']]);
$dresscode_img = $dresscode_stmt->fetch(PDO::FETCH_ASSOC);

// Preparar variables globales para las plantillas
$nombres = $invitacion['nombres_novios'];
$fecha = date('j \d\e F \d\e Y', strtotime($invitacion['fecha_evento']));
$hora_ceremonia = date('H:i', strtotime($invitacion['hora_evento']));
$ubicacion = $invitacion['ubicacion'];  
$direccion_completa = $invitacion['direccion_completa'];
$coordenadas = $invitacion['coordenadas'];
$historia_texto = $invitacion['historia'] ?: "Todo comenzó con un momento simple, que se convirtió en recuerdos, risas y amor. Cada paso de este viaje nos ha acercado más a nuestro día especial.";
$dresscode = $invitacion['dresscode'] ?: "Por favor, viste atuendo elegante para complementar la atmósfera sofisticada de nuestro día especial.";

// Determinar qué plantilla usar
$plantilla_id = $invitacion['plantilla_id'] ?? 1;
$plantilla_file = "./plantillas/plantilla-{$plantilla_id}/invitacion-{$plantilla_id}.php";

// Cargar la plantilla específica
if (file_exists($plantilla_file)) {
    include $plantilla_file;
} else {
    // Fallback a plantilla 1 si no existe la especificada
    include "./plantillas/plantilla-1/invitacion-1.php";
}
?>