<?php
require_once './../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar') {
    $id = $_POST['id'] ?? 0;
    
    if ($id > 0) {
        try {
            // Iniciar transacción
            $db->beginTransaction();
            
            // Obtener información de la invitación para eliminar archivos
            $query = "SELECT slug FROM invitaciones WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $invitacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($invitacion) {
                // Eliminar registros relacionados
                $db->prepare("DELETE FROM invitacion_cronograma WHERE invitacion_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM invitacion_galeria WHERE invitacion_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM invitacion_dresscode WHERE invitacion_id = ?")->execute([$id]);
                
                // Eliminar la invitación principal
                $db->prepare("DELETE FROM invitaciones WHERE id = ?")->execute([$id]);
                
                // Eliminar carpeta de archivos subidos (opcional)
                $upload_dir = "../uploads/" . $invitacion['slug'];
                if (is_dir($upload_dir)) {
                    function eliminarDirectorio($dir) {
                        if (is_dir($dir)) {
                            $files = array_diff(scandir($dir), array('.', '..'));
                            foreach ($files as $file) {
                                $path = $dir . '/' . $file;
                                is_dir($path) ? eliminarDirectorio($path) : unlink($path);
                            }
                            rmdir($dir);
                        }
                    }
                    eliminarDirectorio($upload_dir);
                }
                
                $db->commit();
                $success_message = "Invitación eliminada correctamente.";
            } else {
                $db->rollback();
                $error_message = "Invitación no encontrada.";
            }
        } catch (Exception $e) {
            $db->rollback();
            $error_message = "Error al eliminar la invitación: " . $e->getMessage();
        }
    }
}

// Obtener todas las invitaciones con información adicional
$query = "SELECT i.*, p.nombre as plantilla_nombre
          FROM invitaciones i 
          LEFT JOIN plantillas p ON i.plantilla_id = p.id 
          ORDER BY i.fecha_creacion DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$invitaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para calcular el estado de una invitación
function calcularEstado($fecha_evento) {
    if (!$fecha_evento) {
        return ['key' => 'activa', 'texto' => 'Activa', 'class' => 'bg-primary', 'orden' => 2];
    }
    
    $hoy = new DateTime();
    $fecha = new DateTime($fecha_evento);
    $dias_restantes = $hoy->diff($fecha)->days;
    $es_futuro = $fecha >= $hoy;
    
    if (!$es_futuro) {
        return ['key' => 'finalizada', 'texto' => 'Finalizada', 'class' => 'bg-secondary', 'orden' => 3];
    } elseif ($dias_restantes <= 7) {
        return ['key' => 'proxima', 'texto' => 'Próxima', 'class' => 'bg-warning text-dark', 'orden' => 0];
    } else {
        return ['key' => 'activa', 'texto' => 'Activa', 'class' => 'bg-success', 'orden' => 1];
    }
}

// Ordenar invitaciones: próximas primero, activas después, finalizadas al final
usort($invitaciones, function($a, $b) {
    $estadoA = calcularEstado($a['fecha_evento']);
    $estadoB = calcularEstado($b['fecha_evento']);
    
    // Primero ordenar por estado (orden: proxima, activa, finalizada)
    if ($estadoA['orden'] !== $estadoB['orden']) {
        return $estadoA['orden'] - $estadoB['orden'];
    }
    
    // Si son del mismo estado, ordenar por fecha de evento
    if ($a['fecha_evento'] && $b['fecha_evento']) {
        return strtotime($a['fecha_evento']) - strtotime($b['fecha_evento']);
    }
    
    // Si no tienen fecha, ordenar por fecha de creación
    return strtotime($b['fecha_creacion']) - strtotime($a['fecha_creacion']);
});

// Contar invitaciones por estado
$contadores = [
    'proximas' => 0,
    'activas' => 0,
    'finalizadas' => 0,
    'total' => count($invitaciones)
];

foreach ($invitaciones as $inv) {
    $estado = calcularEstado($inv['fecha_evento']);
    switch ($estado['key']) {
        case 'proxima':
            $contadores['proximas']++;
            break;
        case 'activa':
            $contadores['activas']++;
            break;
        case 'finalizada':
            $contadores['finalizadas']++;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Invitaciones</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="./css/index.css?v=<?php echo filemtime('./css/index.css'); ?>" />
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Icon page -->
    <link rel="shortcut icon" href="./../images/logo.webp" />
</head>
<body>
    <!-- Navbar Mejorado -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-envelope-heart me-2"></i>
                <span class="d-none d-sm-inline">Panel de Administración</span>
                <span class="d-sm-none">Admin</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <div class="d-lg-flex flex-lg-row flex-column gap-2 mt-2 mt-lg-0">
                        <a href="plantillas.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-layout-text-window-reverse me-1"></i>
                            <span class="d-none d-md-inline">Gestionar </span>Plantillas
                        </a>
                        <a href="./functions/crear.php" class="btn btn-light btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>
                            <span class="d-none d-md-inline">Nueva </span>Invitación
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Panel de Control y Filtros -->
    <div class="container-fluid py-3 bg-white border-bottom">
        <div class="row align-items-center">
            <!-- Búsqueda -->
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" 
                        id="searchInput" placeholder="Buscar por nombre o código...">
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="col-lg-6 col-md-6 mb-3 mb-lg-0">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <select class="form-select form-select-sm" id="estadoFilter">
                            <option value="">Todos los estados</option>
                            <option value="proxima">Próximas (<?php echo $contadores['proximas']; ?>)</option>
                            <option value="activa">Activas (<?php echo $contadores['activas']; ?>)</option>
                            <option value="finalizada">Finalizadas (<?php echo $contadores['finalizadas']; ?>)</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <select class="form-select form-select-sm" id="fechaFilter">
                            <option value="">Ordenar por fecha</option>
                            <option value="evento_asc">Evento (más próximo)</option>
                            <option value="evento_desc">Evento (más lejano)</option>
                            <option value="creacion_desc" selected>Creación (más reciente)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Estadísticas mejoradas -->
            <div class="col-lg-2 col-12">
                <div class="d-flex justify-content-between justify-content-lg-end gap-3">
                    <small class="text-muted d-flex align-items-center">
                        <i class="bi bi-calendar-check me-1 text-warning"></i>
                        <span id="proximasCount"><?php echo $contadores['proximas']; ?></span> próximas
                    </small>
                    <small class="text-muted d-flex align-items-center">
                        <i class="bi bi-check-circle me-1 text-success"></i>
                        <span id="activasCount"><?php echo $contadores['activas']; ?></span> activas
                    </small>
                    <small class="text-muted d-flex align-items-center">
                        <i class="bi bi-archive me-1 text-secondary"></i>
                        <span id="finalizadasCount"><?php echo $contadores['finalizadas']; ?></span> finalizadas
                    </small>
                </div>
            </div>

        <div class="container-fluid py-4">
        <!-- Alertas -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Alerta de invitaciones próximas -->
        <?php
        $invitaciones_proximas = array_filter($invitaciones, function($inv) {
            if (!$inv['fecha_evento']) return false;
            $hoy = new DateTime();
            $fecha_evento = new DateTime($inv['fecha_evento']);
            $dias_restantes = $hoy->diff($fecha_evento)->days;
            return $fecha_evento >= $hoy && $dias_restantes <= 7;
        });

        if (!empty($invitaciones_proximas)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-clock me-2"></i>
                <strong>Eventos próximos:</strong> 
                Tienes <?php echo count($invitaciones_proximas); ?> invitación(es) con eventos en los próximos 7 días.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($invitaciones)): ?>
            <!-- Grid de invitaciones -->
            <div class="row g-4">
                <?php foreach($invitaciones as $invitacion): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card h-100">
                        <!-- Imagen hero si existe -->
                        <?php if (!empty($invitacion['imagen_hero'])): ?>
                        <img src="<?php echo htmlspecialchars('../' . $invitacion['imagen_hero']); ?>"
                             alt="Imagen de <?php echo htmlspecialchars($invitacion['nombres_novios']); ?>"
                             class="preview-image"
                             onerror="this.style.display='none';">
                        <?php else: ?>
                        <div class="preview-image bg-light d-flex align-items-center justify-content-center">
                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <?php echo htmlspecialchars($invitacion['nombres_novios'] ?? 'Sin nombre'); ?>
                            </h5>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?php 
                                    if ($invitacion['fecha_evento']) {
                                        $fecha = new DateTime($invitacion['fecha_evento']);
                                        echo $fecha->format('d/m/Y');
                                        if ($invitacion['hora_evento']) {
                                            echo ' - ' . date('H:i', strtotime($invitacion['hora_evento']));
                                        }
                                    } else {
                                        echo 'No definida';
                                    }
                                    ?>
                                </small>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-palette me-1"></i>
                                    <?php echo htmlspecialchars($invitacion['plantilla_nombre'] ?? 'Sin plantilla'); ?>
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <code class="small bg-light px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($invitacion['slug'] ?? 'sin-slug'); ?>
                                </code>
                            </div>
                            
                            <!-- Estado basado en fecha -->
                            <?php 
                            $estado = calcularEstado($invitacion['fecha_evento']);
                            ?>
                            <div class="mb-3">
                                <span class="badge <?php echo $estado['class']; ?>">
                                    <?php if ($estado['key'] === 'finalizada'): ?>
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                    <?php elseif ($estado['key'] === 'proxima'): ?>
                                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                                    <?php else: ?>
                                        <i class="bi bi-calendar-event me-1"></i>
                                    <?php endif; ?>
                                    <?php echo $estado['texto']; ?>
                                </span>
                                
                                <?php if ($invitacion['fecha_evento']): ?>
                                    <?php
                                    $hoy = new DateTime();
                                    $fecha = new DateTime($invitacion['fecha_evento']);
                                    $dias = $hoy->diff($fecha)->days;
                                    $es_futuro = $fecha >= $hoy;
                                    ?>
                                    <small class="text-muted ms-2">
                                        <?php if ($es_futuro): ?>
                                            <?php if ($dias === 0): ?>
                                                ¡Hoy!
                                            <?php elseif ($dias === 1): ?>
                                                Mañana
                                            <?php else: ?>
                                                En <?php echo $dias; ?> días
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Hace <?php echo $dias; ?> días
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <div class="btn-group" role="group">
                                    <a href="./functions/editar.php?id=<?php echo $invitacion['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="../invitacion.php?slug=<?php echo htmlspecialchars($invitacion['slug'] ?? ''); ?>" 
                                       class="btn btn-outline-success btn-sm" target="_blank">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                                
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal<?php echo $invitacion['id']; ?>">
                                    <i class="bi bi-trash me-1"></i>
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal de confirmación de eliminación -->
                    <div class="modal fade" id="deleteModal<?php echo $invitacion['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmar eliminación</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>¿Estás seguro de que quieres eliminar la invitación de <strong><?php echo htmlspecialchars($invitacion['nombres_novios']); ?></strong>?</p>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Esta acción eliminará también todos los archivos asociados.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $invitacion['id']; ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-trash me-1"></i>
                                            Eliminar definitivamente
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="empty-state text-center">
                <i class="bi bi-envelope-plus"></i>
                <h3 class="mt-3 mb-2">No hay invitaciones creadas</h3>
                <p class="text-muted mb-4">Comienza creando tu primera invitación digital.</p>
                <a href="./functions/crear.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>
                    Crear Primera Invitación
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script src="./js/index.js?v=<?php echo filemtime('./js/index.js'); ?>"></script>
</body>
</html>