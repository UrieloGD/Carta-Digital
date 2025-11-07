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
                    $imagenRuta = './images/default-template.png';
                    if (!empty($plantilla['imagen_preview'])) {
                        $imagenRuta = './plantillas/' . $plantilla['carpeta'] . '/' . $plantilla['imagen_preview'];
                    }
                    $tieneEjemplo = !empty($plantilla['ejemplo_slug']);
                    $urlDestino = $tieneEjemplo 
                        ? './invitacion.php?slug=' . urlencode($plantilla['ejemplo_slug'])
                        : '#';
                    $textoBoton = $tieneEjemplo ? 'Ver plantilla' : 'Próximamente';
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

                            <!-- Ver plantilla -->
                            <?php if ($tieneEjemplo): ?>
                                <a href="<?php echo $urlDestino; ?>" 
                                    class="<?php echo $claseBoton; ?>"
                                    target="_blank" 
                                    rel="noopener">
                                    <i class="fas fa-eye"></i> <?php echo $textoBoton; ?>
                                </a>
                            <?php else: ?>
                                <button class="<?php echo $claseBoton; ?>" disabled>
                                    <i class="fas fa-eye"></i> <?php echo $textoBoton; ?>
                                </button>
                            <?php endif; ?>

                            <!-- Botón comprar -->
                            <button type="button" 
                                    class="btn btn-primary template-btn btn-comprar" 
                                    data-plantilla-id="<?php echo $plantilla['id']; ?>"
                                    data-plantilla-nombre="<?php echo htmlspecialchars($plantilla['nombre']); ?>">
                                <i class="fas fa-shopping-cart"></i> Comprar
                            </button>
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

<!-- CTA Section -->
<section class="plantillas-cta">
    <div class="container">
        <div class="cta-content">
            <h2>¿No sabes cuál elegir?</h2>
            <p>Compara nuestros planes y encuentra el que mejor se adapta a tus necesidades</p>
            <a href="./precios.php" class="btn btn-primary">Ver Planes y Precios</a>
        </div>
    </div>
</section>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Selecciona tu plan</h3>
            <button class="modal-close" id="modalClose" aria-label="Cerrar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="plans-list" id="plansList">
                <!-- Se llena dinámicamente con JavaScript -->
            </div>
        </div>
    </div>
</div>

<script src="./js/plantillas.js?v=<?php echo filemtime('./js/plantillas.js'); ?>"></script>

<?php include './includes/footer.php'; ?>