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
                    <div class="template-card" data-category="<?php echo htmlspecialchars($plantilla['categoria'] ?? 'todas'); ?>">
                        <div class="template-image">
                            <img src="<?php echo htmlspecialchars($imagenRuta); ?>" 
                                alt="Preview de <?php echo htmlspecialchars($plantilla['nombre']); ?>"
                                onerror="this.src='./images/default-template.png'">
                        </div>
                        <div class="template-info">
                            <h3><?php echo htmlspecialchars($plantilla['nombre']); ?></h3>

                            <div>
                                <a href="<?php echo $urlDestino; ?>" 
                                class="<?php echo $claseBoton; ?>"
                                target="_blank" 
                                rel="noopener">
                                    <i class="fas fa-eye"></i> <?php echo $textoBoton; ?>
                                </a>
                            </div>

                            <!-- Dropdown solo visible en hover -->
                            <div class="buy-options">
                                <select id="select-plan-<?php echo $plantilla['id']; ?>" class="select-plan">
                                    <option value="escencial">Plan Escencial - $699 MXN</option>
                                    <option value="premium" selected>Plan Premium - $899 MXN</option>
                                    <option value="exclusivo">Plan Exclusivo - $1,199 MXN</option>
                                </select>
                            </div>

                            <!-- Botón comprar SIEMPRE visible -->
                            <a href="./checkout.php?plan=premium&plantilla=<?php echo $plantilla['id']; ?>" 
                            id="boton-comprar-<?php echo $plantilla['id']; ?>"
                            class="btn btn-primary template-btn">
                                <i class="fas fa-shopping-cart"></i> Comprar
                            </a>
                        </div>
                    </div>

                    <script>
                    // Actualizar el enlace del botón comprar cuando el usuario cambia el plan
                    document.addEventListener('DOMContentLoaded', function () {
                        const selectPlan = document.getElementById('select-plan-<?php echo $plantilla['id']; ?>');
                        const btnComprar = document.getElementById('boton-comprar-<?php echo $plantilla['id']; ?>');
                        btnComprar.href = `./checkout.php?plan=${selectPlan.value}&plantilla=<?php echo $plantilla['id']; ?>`;
                        
                        selectPlan.addEventListener('change', function () {
                            btnComprar.href = `./checkout.php?plan=${this.value}&plantilla=<?php echo $plantilla['id']; ?>`;
                        });
                    });
                    </script>
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

<?php include './includes/footer.php'; ?>
