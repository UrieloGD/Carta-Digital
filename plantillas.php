<?php 
include './includes/header.php';

try {
    require_once './config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM plantillas WHERE activa = 1 ORDER BY fecha_creacion DESC");
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
                    ?>
                    <div class="template-card">
                        <div class="template-image">
                            <img src="<?php echo htmlspecialchars($imagenRuta); ?>" 
                                 alt="Preview de <?php echo htmlspecialchars($plantilla['nombre']); ?>"
                                 onerror="this.src='./images/default-template.png'">
                            <!-- Overlay removido de aquí -->
                        </div>
                        <div class="template-info">
                            <h3><?php echo htmlspecialchars($plantilla['nombre']); ?></h3>
                            
                            <!-- Descripción comentada temporalmente -->
                            <?php /* if (!empty($plantilla['descripcion'])): ?>
                                <p class="template-description"><?php echo htmlspecialchars($plantilla['descripcion']); ?></p>
                            <?php endif; */ ?>
                            
                            <!-- Botón movido aquí -->
                            <a href="./plantillas/<?php echo htmlspecialchars($plantilla['carpeta']); ?>/<?php echo htmlspecialchars($plantilla['archivo_principal']); ?>" 
                               class="btn btn-secondary template-btn">Ver plantilla</a>
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