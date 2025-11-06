<?php
session_start();
require_once 'config/database.php';

// Verificar si hay sesión activa
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Obtener invitación del cliente
$stmt = $conn->prepare("
    SELECT 
        i.*, 
        p.nombre as plantilla_nombre,
        ped.plan
    FROM invitaciones i 
    INNER JOIN plantillas p ON i.plantilla_id = p.id
    INNER JOIN pedidos ped ON i.id = ped.invitacion_id
    WHERE i.cliente_id = ?
    ORDER BY i.fecha_creacion DESC
");
$stmt->execute([$_SESSION['cliente_id']]);
$invitaciones = $stmt->fetchAll();

if (empty($invitaciones)) {
    die("Error: No se encontraron invitaciones.");
}

// Determinar qué invitación mostrar
$invitacion_id = isset($_GET['invitacion_id']) ? (int)$_GET['invitacion_id'] : $invitaciones[0]['id'];
$invitacion = null;

foreach ($invitaciones as $inv) {
    if ($inv['id'] == $invitacion_id) {
        $invitacion = $inv;
        break;
    }
}

if (!$invitacion) {
    $invitacion = $invitaciones[0];
}

// Determinar si tiene plan exclusivo
$tiene_rsvp = ($invitacion['plan'] === 'exclusivo');

// URL pública
$invitacion_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/invitacion.php?slug=' . $invitacion['slug'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Invitación - Carta Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/invitacion_cliente.css?v=<?php echo filemtime('./css/invitacion_cliente.css'); ?>" />
    <link rel="shortcut icon" href="./images/logo.webp" />
</head>
<body>
    <!-- Header del Dashboard -->
    <div class="dashboard-header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 1; text-align: center; margin-right: -14rem;">
                    <div class="header-title">
                        <i class="fas fa-heart me-2"></i><?php echo htmlspecialchars($invitacion['nombres_novios']); ?>
                    </div>
                    <div class="header-info">
                        <span class="info-item">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d/m/Y', strtotime($invitacion['fecha_evento'])); ?>
                        </span>
                        <span class="info-separator">•</span>
                        <span class="info-item">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date('H:i', strtotime($invitacion['hora_evento'])); ?>
                        </span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="./index.php" class="btn-action btn-outline-light">
                        <i class="fas fa-home"></i>Inicio
                    </a>
                    <a href="./logout.php" class="btn-action btn-outline-light">
                        <i class="fas fa-sign-out-alt"></i>Cerrar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal -->
    <div class="container main-container">
        <!-- Selector de invitaciones (si tiene múltiples) -->
        <?php if (count($invitaciones) > 1): ?>
        <div class="selector-section">
            <label for="invitacion-select">Selecciona una invitación:</label>
            <select id="invitacion-select" onchange="window.location.href='invitacion_cliente.php?invitacion_id=' + this.value">
                <?php foreach ($invitaciones as $inv): ?>
                    <option value="<?php echo $inv['id']; ?>" <?php echo $inv['id'] == $invitacion['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($inv['nombres_novios']); ?> - <?php echo date('d/m/Y', strtotime($inv['fecha_evento'])); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <!-- Card de Información -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i>Información del Evento
            </div>
            <div class="card-body">
                <!-- Grid 2x2 -->
                <div class="info-grid">
                    <div class="info-item-grid">
                        <span class="info-label">Novios</span>
                        <span class="info-value"><?php echo htmlspecialchars($invitacion['nombres_novios']); ?></span>
                    </div>
                    <div class="info-item-grid">
                        <span class="info-label">Fecha</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($invitacion['fecha_evento'])); ?></span>
                    </div>
                    <div class="info-item-grid">
                        <span class="info-label">Hora</span>
                        <span class="info-value"><?php echo date('H:i', strtotime($invitacion['hora_evento'])); ?></span>
                    </div>
                    <div class="info-item-grid">
                        <span class="info-label">Plantilla</span>
                        <span class="info-value"><?php echo htmlspecialchars($invitacion['plantilla_nombre']); ?></span>
                    </div>
                </div>

                <div class="share-section">
                    <h3><i class="fas fa-share-alt me-2"></i>Comparte tu Invitación</h3>
                    <div class="url-box">
                        <input type="text" id="url-invitacion" value="<?php echo htmlspecialchars($invitacion_url); ?>" readonly>
                        <button class="btn" onclick="copiarURL()">
                            <i class="fas fa-copy me-1"></i>Copiar
                        </button>
                    </div>

                    <div class="share-buttons">
                        <a href="<?php echo htmlspecialchars($invitacion_url); ?>" target="_blank" class="btn-custom btn-view">
                            <i class="fas fa-eye me-2"></i>Ver Invitación
                        </a>
                        <?php if ($tiene_rsvp): ?>
                        <a href="./dashboard_rsvp.php?invitacion_id=<?php echo $invitacion['id']; ?>" class="btn-custom btn-rsvp">
                            <i class="fas fa-users me-2"></i>Mi Panel
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="alert-section">
                    <i class="fas fa-check-circle"></i>
                    <strong>Tu invitación está activa</strong>
                    <p style="margin: 0; font-size: 0.95rem;">Nos comunicaremos pronto contigo para personalizar los detalles de tu evento.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copiarURL() {
            const input = document.getElementById('url-invitacion');
            input.select();
            document.execCommand('copy');

            const btn = event.target.closest('.btn');
            const textoOriginal = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-1"></i>¡Copiado!';
            setTimeout(() => {
                btn.innerHTML = textoOriginal;
            }, 2000);
        }
    </script>
</body>
</html>
