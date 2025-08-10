<?php 
include './includes/header.php';

try {
    require_once './config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener plantillas con información de invitaciones de ejemplo
    $stmt = $db->prepare("
        SELECT p.*, 
               ie.slug as ejemplo_slug
        FROM plantillas p 
        LEFT JOIN invitaciones ie ON p.invitacion_ejemplo_id = ie.id
        WHERE p.activa = 1 
        ORDER BY p.fecha_creacion DESC
    ");
    $stmt->execute();
    $plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $plantillas = [];
    error_log("Error al obtener plantillas: " . $e->getMessage());
}
?>

<link rel="stylesheet" href="./css/plantillas.css?v=<?php echo filemtime('./css/plantillas.css'); ?>" />

<section class="page-header">
    <div class="container">
        <div class="header-content">
            <h1>Explora nuestras <span class="highlight">invitaciones</span></h1>
            <p class="header-subtitle">Descubre diseños exclusivos que harán de tu día especial un momento inolvidable.</p>
        </div>
    </div>
</section>

<section class="templates">
    <div class="container">
        <div class="templates-grid">
            <?php if (!empty($plantillas)): ?>
                <?php foreach ($plantillas as $plantilla): ?>
                    <?php 
                    // Construir la ruta completa de la imagen preview
                    $imagenRuta = './images/default-template.png'; // Por defecto
                    
                    if (!empty($plantilla['imagen_preview'])) {
                        // La ruta completa sería: ./plantillas/plantilla-{id}/{imagen_preview}
                        $imagenRuta = './plantillas/plantilla-' . $plantilla['id'] . '/' . $plantilla['imagen_preview'];
                    }
                    
                    // Determinar si tiene ejemplo
                    $tieneEjemplo = !empty($plantilla['ejemplo_slug']);
                    $urlDestino = $tieneEjemplo 
                        ? './invitacion.php?slug=' . urlencode($plantilla['ejemplo_slug'])
                        : '#';
                    $textoBoton = $tieneEjemplo ? 'Ver ejemplo' : 'Próximamente';
                    $claseBoton = $tieneEjemplo ? 'btn btn-secondary template-btn' : 'btn btn-secondary template-btn disabled';
                    ?>
                    <div class="template-card">
                        <div class="template-image">
                            <img src="<?php echo htmlspecialchars($imagenRuta); ?>" 
                                 alt="Preview de <?php echo htmlspecialchars($plantilla['nombre']); ?>"
                                 onerror="this.src='./images/default-template.png'">
                        </div>
                        <div class="template-info">
                            <h3><?php echo htmlspecialchars($plantilla['nombre']); ?></h3>
                            
                            <?php if ($tieneEjemplo): ?>
                                <a href="<?php echo $urlDestino; ?>" 
                                   class="<?php echo $claseBoton; ?>"
                                   target="_blank"><?php echo $textoBoton; ?></a>
                            <?php else: ?>
                                <span class="<?php echo $claseBoton; ?>"><?php echo $textoBoton; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-templates">
                    <p>No hay plantillas disponibles en este momento.</p>
                    <small>Agrega algunas plantillas desde el panel de administración.</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include './includes/footer.php'; ?>