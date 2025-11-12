<?php
require_once './../config/database.php';
require_once './../config/auth.php';

// Requiere login
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Obtener rol del usuario actual
$rol_usuario = $_SESSION['admin_rol'] ?? 'viewer';

// Definir permisos por rol
$puede_editar = in_array($rol_usuario, ['admin', 'editor']);
$puede_eliminar = ($rol_usuario === 'admin');
$puede_crear = in_array($rol_usuario, ['admin', 'editor']);

// Procesar eliminación (SOLO ADMIN)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar') {
    if (!$puede_eliminar) {
        $error_message = "No tienes permisos para eliminar invitaciones.";
    } else {
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
                    $db->prepare("DELETE FROM invitacion_ubicaciones WHERE invitacion_id = ?")->execute([$id]);
                    $db->prepare("DELETE FROM invitacion_mesa_regalos WHERE invitacion_id = ?")->execute([$id]);
                    
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
}

// Obtener todas las invitaciones con plantilla y plan
$query = "SELECT 
    i.*, 
    p.nombre as plantilla_nombre,
    pl.nombre as plan_nombre,
    pl.precio as precio_plan,
    ped.estado as pedido_estado
FROM invitaciones i 
LEFT JOIN plantillas p ON i.plantilla_id = p.id
LEFT JOIN planes pl ON i.plan_id = pl.id
LEFT JOIN pedidos ped ON i.id = ped.invitacion_id
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
    
    if ($estadoA['orden'] !== $estadoB['orden']) {
        return $estadoA['orden'] - $estadoB['orden'];
    }
    
    if ($a['fecha_evento'] && $b['fecha_evento']) {
        return strtotime($a['fecha_evento']) - strtotime($b['fecha_evento']);
    }
    
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
    <style>
        /* Estilos para indicador de permisos */
        .permission-badge {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .disabled-action {
            opacity: 0.5;
            cursor: not-allowed !important;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <!-- Navbar Mejorado -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-envelope-heart"></i>
                <span class="d-none d-sm-inline">Panel de Administración</span>
                <span class="d-sm-none">Admin</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Botones alineados a la derecha -->
                <div class="navbar-nav ms-auto">
                    <?php if ($rol_usuario === 'admin'): ?>
                    <a href="usuarios.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-people me-1"></i>
                        <span class="d-none d-md-inline">Gestionar</span> Usuarios
                    </a>
                    <a href="plantillas.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-layout-text-window-reverse me-1"></i>
                        <span class="d-none d-md-inline">Gestionar</span> Plantillas
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($puede_crear): ?>
                    <a href="./functions/crear.php" class="btn btn-light btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>
                        <span class="d-none d-md-inline">Nueva</span> Invitación
                    </a>
                    <?php endif; ?>
                    
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Panel de Control y Filtros -->
    <div class="bg-white border-bottom">
        <div class="filter-container">
            <div class="filter-row">
                <!-- Búsqueda -->
                <div class="search-section">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control" 
                            id="searchInput" placeholder="Buscar por nombre o código...">
                    </div>
                </div>
                
                <!-- Filtros -->
                <div class="filters-section">
                    <div class="filters-wrapper">
                        <div class="filter-item">
                            <select class="form-select" id="estadoFilter">
                                <option value="">Todos los estados</option>
                                <option value="proxima">Próximas</option>
                                <option value="activa">Activas</option>
                                <option value="finalizada">Finalizadas</option>
                            </select>
                        </div>
                        <div class="filter-item">
                            <select class="form-select" id="fechaFilter">
                                <option value="">Ordenar por fecha</option>
                                <option value="evento_asc">Evento (más próximo)</option>
                                <option value="evento_desc">Evento (más lejano)</option>
                                <option value="creacion_desc" selected>Creación (reciente)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="stats-section">
                    <div class="stats-wrapper">
                        <div class="stat-item">
                            <i class="bi bi-calendar-check icon-warning"></i>
                            <span class="count" id="proximasCount"><?php echo $contadores['proximas']; ?></span>
                            <span>próximas</span>
                        </div>
                        <div class="stat-item">
                            <i class="bi bi-check-circle icon-success"></i>
                            <span class="count" id="activasCount"><?php echo $contadores['activas']; ?></span>
                            <span>activas</span>
                        </div>
                        <div class="stat-item">
                            <i class="bi bi-archive icon-secondary"></i>
                            <span class="count" id="finalizadasCount"><?php echo $contadores['finalizadas']; ?></span>
                            <span>finalizadas</span>
                        </div>
                    </div>
                </div>
            </div>
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

        <!-- Alerta informativa sobre permisos para visores -->
        <?php if ($rol_usuario === 'viewer'): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Modo de solo lectura:</strong> 
                Tu rol de Visor solo permite visualizar las invitaciones. No puedes crear, editar ni eliminar.
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
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
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

                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-box me-1"></i>
                                    <strong><?php echo htmlspecialchars($invitacion['plan_nombre'] ?? 'Sin plan'); ?></strong>
                                </small>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-cash-coin me-1"></i>
                                    $<?php echo number_format($invitacion['precio_plan'] ?? 0, 2); ?> MXN
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
                        </div>
                        
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <div class="btn-group" role="group">
                                    <!-- Botón Editar - Solo para admin y editor -->
                                    <?php if ($puede_editar): ?>
                                    <a href="./functions/editar.php?id=<?php echo $invitacion['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm"
                                       title="Editar invitación">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-outline-secondary btn-sm disabled-action" 
                                            disabled
                                            title="No tienes permisos para editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <!-- Botón Ver - Todos pueden ver -->
                                    <a href="../invitacion.php?slug=<?php echo htmlspecialchars($invitacion['slug'] ?? ''); ?>" 
                                       class="btn btn-outline-success btn-sm" 
                                       target="_blank"
                                       title="Ver invitación">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                                
                                <!-- Botón Eliminar - Solo para admin -->
                                <?php if ($puede_eliminar): ?>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal<?php echo $invitacion['id']; ?>"
                                        title="Eliminar invitación">
                                    <i class="bi bi-trash me-1"></i>
                                    Eliminar
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm disabled-action" 
                                        disabled
                                        title="No tienes permisos para eliminar">
                                    <i class="bi bi-trash me-1"></i>
                                    Eliminar
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Modal de confirmación de eliminación - Solo si es admin -->
                <?php if ($puede_eliminar): ?>
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
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="empty-state text-center">
                <i class="bi bi-envelope-plus"></i>
                <h3 class="mt-3 mb-2">No hay invitaciones creadas</h3>
                <p class="text-muted mb-4">
                    <?php if ($puede_crear): ?>
                        Comienza creando tu primera invitación digital.
                    <?php else: ?>
                        Aún no hay invitaciones disponibles para visualizar.
                    <?php endif; ?>
                </p>
                <?php if ($puede_crear): ?>
                <a href="./functions/crear.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>
                    Crear Primera Invitación
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Indicador de rol actual (badge fijo) -->
    <div class="permission-badge">
        <i class="bi bi-person-badge"></i>
        <span>
            <?php 
            $roles_texto = [
                'admin' => 'Admin',
                'editor' => 'Editor',
                'viewer' => 'Visor'
            ];
            echo $roles_texto[$rol_usuario] ?? ucfirst($rol_usuario);
            ?>
        </span>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Custom JS -->
    <script src="./js/index.js?v=<?php echo filemtime('./js/index.js'); ?>"></script>
</body>
</html>
