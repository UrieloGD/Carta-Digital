<?php
$page_title = 'Seleccionar Plantilla';
include './includes/header.php';

require_once './config/database.php';

$plan = $_GET['plan'] ?? 'premium';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT p.*, ie.slug as ejemplo_slug FROM plantillas p LEFT JOIN invitaciones ie ON p.invitacion_ejemplo_id = ie.id WHERE p.activa = 1 ORDER BY p.fecha_creacion DESC");
$stmt->execute();
$plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link rel="stylesheet" href="./css/seleccionar_plantilla.css?v=<?php echo filemtime('./css/seleccionar_plantilla.css'); ?>" />

<section class="page-header">
    <div class="container">
        <div class="header-content">
            <h1>Selecciona tu plantilla para el plan <span class="highlight"><?php echo htmlspecialchars(ucfirst($plan)); ?></span></h1>
            <p class="header-subtitle">Elige una plantilla para tu invitación digital</p>
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
                            <!-- Botón elegir plantilla -->
                            <a href="./checkout.php?plan=<?php echo urlencode($plan); ?>&plantilla=<?php echo $plantilla['id']; ?>"
                               class="btn btn-primary template-btn"
                               title="Elegir plantilla para este plan">
                                <i class="fas fa-shopping-cart"></i> Elegir esta plantilla
                            </a>
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
