<?php
$page_title = 'Dashboard';
$page_css = 'dashboard.css';
$page_js = 'dashboard.js';
include_once 'includes/header.php';
include_once 'includes/sidebar.php';

// Datos de ejemplo - aquí conectarías con tu base de datos
$stats = [
    'total_invitaciones' => 24,
    'confirmaciones_pendientes' => 8,
    'mensajes_nuevos' => 12,
    'plantillas_activas' => 6
];
?>

<main class="admin-content">
    <div class="container">
        <div class="page-header">
            <h2 class="page-title">Dashboard</h2>
            <p class="page-subtitle">Resumen general de la plataforma</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card fade-in">
                <div class="stat-icon">💌</div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $stats['total_invitaciones']; ?></h3>
                    <p class="stat-label">Invitaciones creadas</p>
                </div>
                <a href="invitaciones.php" class="stat-link">Ver todas</a>
            </div>

            <div class="stat-card fade-in">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $stats['confirmaciones_pendientes']; ?></h3>
                    <p class="stat-label">Confirmaciones pendientes</p>
                </div>
                <a href="confirmaciones.php" class="stat-link">Revisar</a>
            </div>

            <div class="stat-card fade-in">
                <div class="stat-icon">💬</div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $stats['mensajes_nuevos']; ?></h3>
                    <p class="stat-label">Mensajes nuevos</p>
                </div>
                <a href="mensajes.php" class="stat-link">Leer</a>
            </div>

            <div class="stat-card fade-in">
                <div class="stat-icon">🎨</div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo $stats['plantillas_activas']; ?></h3>
                    <p class="stat-label">Plantillas activas</p>
                </div>
                <a href="plantillas.php" class="stat-link">Gestionar</a>
            </div>
        </div>

        <div class="dashboard-actions">
            <div class="quick-actions">
                <h3>Acciones rápidas</h3>
                <div class="action-buttons">
                    <a href="invitaciones.php" class="btn btn-primary">Nueva invitación</a>
                    <a href="plantillas.php" class="btn btn-secondary">Crear plantilla</a>
                    <a href="confirmaciones.php" class="btn btn-outline">Ver confirmaciones</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>