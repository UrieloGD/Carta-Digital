<?php
session_start();
require_once 'config/database.php';
header('Content-Type: text/html; charset=UTF-8');

// Verificar si hay sesión activa
if (!isset($_SESSION['cliente_logueado']) || $_SESSION['cliente_logueado'] !== true) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// ========================================
// API AJAX - DEVOLVER TODOS LOS GRUPOS
// ========================================
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    
    $invitacion_slug_ajax = $_GET['slug'] ?? '';
    
    if (!$invitacion_slug_ajax) {
        echo json_encode(['success' => false, 'message' => 'Slug no proporcionado']);
        exit;
    }
    
    try {
        // Obtener TODOS los grupos (sin límite)
        $stmt = $conn->prepare("
            SELECT 
                ig.*,
                r.estado,
                r.boletos_confirmados,
                r.nombres_acompanantes,
                r.comentarios,
                r.fecha_respuesta,
                r.nombre_invitado_principal
            FROM invitados_grupos ig 
            LEFT JOIN rsvp_respuestas r ON ig.id_grupo = r.id_grupo 
            WHERE ig.slug_invitacion = ? 
            ORDER BY ig.fecha_creacion DESC
        ");
        $stmt->execute([$invitacion_slug_ajax]);
        $grupos_ajax = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener estadísticas actualizadas
        $stmt_stats = $conn->prepare("
            SELECT 
                COUNT(*) as total_grupos,
                SUM(ig.boletos_asignados) as total_boletos,
                SUM(CASE WHEN r.estado = 'aceptado' THEN r.boletos_confirmados ELSE 0 END) as confirmados,
                SUM(CASE WHEN r.estado = 'rechazado' THEN ig.boletos_asignados ELSE 0 END) as rechazados,
                SUM(
                    CASE 
                        WHEN r.estado = 'pendiente' OR r.estado IS NULL THEN ig.boletos_asignados 
                        WHEN r.estado = 'aceptado' THEN (ig.boletos_asignados - r.boletos_confirmados)
                        ELSE 0 
                    END
                ) as pendientes
            FROM invitados_grupos ig 
            LEFT JOIN rsvp_respuestas r ON ig.id_grupo = r.id_grupo 
            WHERE ig.slug_invitacion = ?
        ");
        $stmt_stats->execute([$invitacion_slug_ajax]);
        $stats_ajax = $stmt_stats->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats_ajax,
            'grupos' => $grupos_ajax
        ]);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// ========================================
// EXPORTAR A EXCEL/CSV
// ========================================
if (isset($_GET['exportar']) && $_GET['exportar'] === 'excel') {
    $invitacion_slug_export = isset($_GET['slug']) ? $_GET['slug'] : '';
    
    if ($invitacion_slug_export) {
        $stmt = $conn->prepare("
            SELECT 
                ig.nombre_grupo,
                ig.boletos_asignados,
                COALESCE(r.estado, 'pendiente') as estado,
                COALESCE(r.boletos_confirmados, 0) as boletos_confirmados,
                r.nombre_invitado_principal,
                r.nombres_acompanantes,
                r.comentarios,
                DATE_FORMAT(r.fecha_respuesta, '%d/%m/%Y %H:%i') as fecha_respuesta
            FROM invitados_grupos ig
            LEFT JOIN rsvp_respuestas r ON ig.id_grupo = r.id_grupo
            WHERE ig.slug_invitacion = ?
            ORDER BY r.fecha_respuesta DESC, ig.nombre_grupo ASC
        ");
        $stmt->execute([$invitacion_slug_export]);
        $grupos_export = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="invitados_' . $invitacion_slug_export . '_' . date('Ymd_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, [
            'Grupo',
            'Invitado Principal',
            'Estado',
            'Boletos Asignados',
            'Boletos Confirmados',
            'Acompañantes',
            'Comentarios',
            'Fecha Respuesta'
        ]);
        
        foreach ($grupos_export as $grupo) {
            fputcsv($output, [
                $grupo['nombre_grupo'],
                $grupo['nombre_invitado_principal'] ?: 'Sin respuesta',
                ucfirst($grupo['estado']),
                $grupo['boletos_asignados'],
                $grupo['boletos_confirmados'],
                $grupo['nombres_acompanantes'] ?: '-',
                $grupo['comentarios'] ?: '-',
                $grupo['fecha_respuesta'] ?: '-'
            ]);
        }
        
        fclose($output);
        exit;
    }
}

// Obtener TODAS las invitaciones del cliente
$stmt = $conn->prepare("
    SELECT i.*, p.nombre as plantilla_nombre 
    FROM invitaciones i 
    INNER JOIN plantillas p ON i.plantilla_id = p.id 
    WHERE i.cliente_id = ?
    ORDER BY i.fecha_creacion DESC
");
$stmt->execute([$_SESSION['cliente_id']]);
$invitaciones = $stmt->fetchAll();

if (empty($invitaciones)) {
    die("Error: No se encontraron invitaciones asociadas a tu cuenta.");
}

// Determinar qué invitación mostrar
$invitacion_seleccionada_id = isset($_GET['invitacion_id']) ? (int)$_GET['invitacion_id'] : $invitaciones[0]['id'];

// Obtener la invitación seleccionada
$invitacion = null;
foreach ($invitaciones as $inv) {
    if ($inv['id'] == $invitacion_seleccionada_id) {
        $invitacion = $inv;
        break;
    }
}

if (!$invitacion) {
    $invitacion = $invitaciones[0];
}

$invitacion_slug = $invitacion['slug'];

// Obtener estadísticas RSVP
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_grupos,
        SUM(ig.boletos_asignados) as total_boletos,
        SUM(CASE WHEN r.estado = 'aceptado' THEN r.boletos_confirmados ELSE 0 END) as confirmados,
        SUM(CASE WHEN r.estado = 'rechazado' THEN ig.boletos_asignados ELSE 0 END) as rechazados,
        SUM(
            CASE 
                WHEN r.estado = 'pendiente' OR r.estado IS NULL THEN ig.boletos_asignados 
                WHEN r.estado = 'aceptado' THEN (ig.boletos_asignados - r.boletos_confirmados)
                ELSE 0 
            END
        ) as pendientes
    FROM invitados_grupos ig 
    LEFT JOIN rsvp_respuestas r ON ig.id_grupo = r.id_grupo 
    WHERE ig.slug_invitacion = ?
");
$stmt->execute([$invitacion_slug]);
$stats = $stmt->fetch();

// Procesar acciones CRUD
$message = '';
$message_type = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'crear_grupo':
                $nombre_grupo = trim($_POST['nombre_grupo']);
                $boletos = (int)$_POST['boletos_asignados'];
                
                if (!empty($nombre_grupo) && $boletos > 0) {
                    try {
                        $token_unico = strtoupper(substr(md5(uniqid()), 0, 15));
                        
                        $stmt = $conn->prepare("
                            INSERT INTO invitados_grupos (slug_invitacion, nombre_grupo, boletos_asignados, token_unico) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$invitacion_slug, $nombre_grupo, $boletos, $token_unico]);
                        $nuevo_id = $conn->lastInsertId();
                        
                        $stmt2 = $conn->prepare("
                            INSERT INTO rsvp_respuestas (id_grupo, nombre_invitado_principal, estado, boletos_confirmados) 
                            VALUES (?, ?, 'pendiente', 0)
                        ");
                        $stmt2->execute([$nuevo_id, $nombre_grupo]);
                        
                        $message = "Grupo de invitados creado exitosamente";
                        $message_type = "success";
                    } catch (Exception $e) {
                        $message = "Error al crear el grupo";
                        $message_type = "danger";
                        error_log("Error crear grupo: " . $e->getMessage());
                    }
                }
                break;
                
            case 'editar_grupo':
                $id_grupo = (int)$_POST['id_grupo'];
                $nombre_grupo = trim($_POST['nombre_grupo']);
                $boletos = (int)$_POST['boletos_asignados'];
                
                if (!empty($nombre_grupo) && $boletos > 0 && $id_grupo > 0) {
                    try {
                        $stmt = $conn->prepare("
                            UPDATE invitados_grupos 
                            SET nombre_grupo = ?, boletos_asignados = ? 
                            WHERE id_grupo = ? AND slug_invitacion = ?
                        ");
                        $stmt->execute([$nombre_grupo, $boletos, $id_grupo, $invitacion_slug]);
                        
                        if ($stmt->rowCount() > 0) {
                            $message = "Grupo actualizado exitosamente";
                            $message_type = "success";
                        } else {
                            $message = "Error al actualizar el grupo";
                            $message_type = "danger";
                        }
                    } catch (Exception $e) {
                        $message = "Error al actualizar el grupo";
                        $message_type = "danger";
                        error_log("Error editar grupo: " . $e->getMessage());
                    }
                }
                break;
                
            case 'eliminar_grupo':
                $id_grupo = (int)$_POST['id_grupo'];
                
                if ($id_grupo > 0) {
                    try {
                        $stmt = $conn->prepare("
                            DELETE FROM invitados_grupos 
                            WHERE id_grupo = ? AND slug_invitacion = ?
                        ");
                        $stmt->execute([$id_grupo, $invitacion_slug]);
                        
                        if ($stmt->rowCount() > 0) {
                            $message = "Grupo eliminado exitosamente";
                            $message_type = "success";
                        } else {
                            $message = "Error al eliminar el grupo";
                            $message_type = "danger";
                        }
                    } catch (Exception $e) {
                        $message = "Error al eliminar el grupo";
                        $message_type = "danger";
                        error_log("Error eliminar grupo: " . $e->getMessage());
                    }
                }
                break;
        }
    }
}

// Obtener grupos iniciales (primeros 25 para carga inicial)
$stmt = $conn->prepare("
    SELECT 
        ig.*,
        r.estado,
        r.boletos_confirmados,
        r.nombres_acompanantes,
        r.comentarios,
        r.fecha_respuesta,
        r.nombre_invitado_principal
    FROM invitados_grupos ig 
    LEFT JOIN rsvp_respuestas r ON ig.id_grupo = r.id_grupo 
    WHERE ig.slug_invitacion = ? 
    ORDER BY ig.fecha_creacion DESC
    LIMIT 25
");
$stmt->execute([$invitacion_slug]);
$grupos = $stmt->fetchAll();

// URL base de la invitación
$invitacion_url = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/invitacion.php?slug=' . $invitacion['slug'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($invitacion['nombres_novios']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/dashboard_rsvp.css?v=<?php echo filemtime('./css/dashboard_rsvp.css'); ?>" />
    <link rel="shortcut icon" href="./images/logo.webp" />
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="header-title">
                        <i class="fas fa-heart me-2"></i>
                        <?php echo htmlspecialchars($invitacion['nombres_novios']); ?>
                    </h1>
                    
                    <div class="header-info">
                        <span class="info-item">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d/m/Y', strtotime($invitacion['fecha_evento'])); ?>
                        </span>
                        
                        <span class="info-separator">—</span>
                        
                        <span class="info-item">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date('H:i', strtotime($invitacion['hora_evento'])); ?>
                        </span>
                    </div>
                    
                    <div class="header-actions">
                        <a href="invitacion.php?slug=<?php echo $invitacion['slug']; ?>" 
                           target="_blank" 
                           class="btn btn-light btn-action">
                            <i class="fas fa-eye me-2"></i>
                            <span class="btn-text">Ver Invitación</span>
                        </a>
                        <a href="logout.php" class="btn btn-outline-light btn-action">
                            <i class="fas fa-sign-out-alt me-2"></i>
                            <span class="btn-text">Salir</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($invitaciones) > 1): ?>
    <div class="container mt-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="mb-2 mb-md-0">
                        <h6 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Tienes <?php echo count($invitaciones); ?> invitaciones
                        </h6>
                    </div>
                    <div>
                        <select class="form-select" id="selector-invitaciones" onchange="cambiarInvitacion(this.value)">
                            <?php foreach ($invitaciones as $inv): ?>
                                <option value="<?php echo $inv['id']; ?>" <?php echo $inv['id'] == $invitacion['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($inv['nombres_novios']); ?> 
                                    - <?php echo date('d/m/Y', strtotime($inv['fecha_evento'])); ?>
                                    <?php if ($inv['plantilla_nombre']): ?>
                                        (<?php echo htmlspecialchars($inv['plantilla_nombre']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="container main-container">
        <!-- Mensajes -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row stats-row">
            <div class="col-6 col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number" id="stat-total"><?php echo $stats['total_boletos'] ?? 0; ?></div>
                        <div class="stats-label">Invitados totales</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="stats-card success">
                    <div class="stats-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number" id="stat-confirmados"><?php echo $stats['confirmados'] ?? 0; ?></div>
                        <div class="stats-label">Confirmados</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="stats-card danger">
                    <div class="stats-icon">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number" id="stat-rechazados"><?php echo $stats['rechazados'] ?? 0; ?></div>
                        <div class="stats-label">No Asisten</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="stats-card warning">
                    <div class="stats-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-content">
                        <div class="stats-number" id="stat-pendientes"><?php echo $stats['pendientes'] ?? 0; ?></div>
                        <div class="stats-label">Pendientes</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- URL Pública -->
        <div class="card url-card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-link me-2"></i>URL de tu Invitación
                </h5>
            </div>
            <div class="card-body">
                <div class="url-input-group">
                    <input type="text" 
                           class="form-control url-input" 
                           id="invitacion-url" 
                           value="<?php echo $invitacion_url; ?>" 
                           readonly>
                    <button class="btn btn-primary" onclick="copiarURL()">
                        <i class="fas fa-copy"></i>
                        <span class="btn-text">Copiar</span>
                    </button>
                </div>
                <small class="text-muted mt-2 d-block">
                    <i class="fas fa-info-circle me-1"></i>
                    Comparte esta URL pública de tu invitación
                </small>
            </div>
        </div>

        <!-- Gestión de Invitados -->
        <div class="card guests-card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0">
                        <i class="fas fa-users-cog me-2"></i>Gestión de Invitados
                        <span class="badge bg-secondary ms-2" id="total-grupos-badge"><?php echo $stats['total_grupos'] ?? 0; ?></span>
                    </h5>
                    <div class="header-buttons">
                        <a href="?invitacion_id=<?php echo $invitacion_seleccionada_id; ?>&exportar=excel&slug=<?php echo $invitacion_slug; ?>" 
                           class="btn btn-success btn-action btn-sm">
                            <i class="fas fa-file-excel me-2"></i>
                            <span class="btn-text">Exportar Excel</span>
                        </a>
                        <button onclick="actualizarDatos()" class="btn btn-secondary btn-action btn-sm" id="btn-actualizar">
                            <i class="fas fa-sync-alt me-2" id="icono-actualizar"></i>
                            <span class="btn-text">Actualizar</span>
                        </button>
                        <button class="btn btn-primary btn-action btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalCrearGrupo">
                            <i class="fas fa-plus me-2"></i>
                            <span class="btn-text">Nuevo Grupo</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filters-section">
                <div class="filters-content">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" 
                                       class="form-control" 
                                       id="busquedaInput"
                                       placeholder="Buscar por nombre de grupo o invitado...">
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <select class="form-control" id="filtroEstado">
                                <option value="">Todos los estados</option>
                                <option value="aceptado">Confirmados</option>
                                <option value="rechazado">Rechazados</option>
                                <option value="pendiente">Pendientes</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <button type="button" class="btn btn-outline-secondary w-100" onclick="limpiarFiltros()">
                                <i class="fas fa-times me-1"></i> Limpiar Filtros
                            </button>
                        </div>
                    </div>
                    
                    <!-- Badges de filtros activos -->
                    <div id="filtros-activos" class="filter-badges mt-2" style="display: none;"></div>
                </div>
            </div>

            <!-- Paginación Superior -->
            <div id="paginacion-container-top" class="pagination-wrapper mb-3" style="display: none;">
                <nav aria-label="Paginación de invitados">
                    <ul class="pagination justify-content-center mb-2" id="pagination-list-top">
                        <!-- Se genera dinámicamente con JS -->
                    </ul>
                </nav>
                <p class="pagination-info text-center mb-0" id="pagination-info-top">
                    <!-- Se genera dinámicamente con JS -->
                </p>
            </div>
            
            <div class="card-body p-0">
                <!-- Loading Spinner -->
                <div id="loading-spinner" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="text-muted mt-2">Cargando invitados...</p>
                </div>
                
                <!-- Vista Mobile -->
                <div class="mobile-guests d-md-none" id="mobile-container">
                    <?php if (empty($grupos)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay grupos aún</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearGrupo">
                                <i class="fas fa-plus me-2"></i>Crear Primer Grupo
                            </button>
                        </div>
                    <?php else: ?>
                        <?php foreach ($grupos as $grupo): 
                            $estados = [
                                'pendiente' => 'Sin respuesta',
                                'aceptado' => 'Confirmado',
                                'rechazado' => 'No asistirá'
                            ];
                            $estado = $grupo['estado'] ?? 'pendiente';
                        ?>
                        <div class="mobile-guest-card">
                            <div class="guest-header">
                                <div class="guest-name"><?php echo htmlspecialchars($grupo['nombre_grupo']); ?></div>
                                <div class="guest-status">
                                    <span class="status-badge status-<?php echo $estado; ?>">
                                        <?php echo $estados[$estado] ?? 'Pendiente'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="guest-details">
                                <div class="detail-item">
                                    <i class="fas fa-ticket-alt me-2"></i>
                                    <?php echo $grupo['boletos_asignados']; ?> boletos
                                    <?php if ($grupo['estado'] == 'aceptado'): ?>
                                        <small class="text-success">(<?php echo $grupo['boletos_confirmados']; ?> confirmados)</small>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($grupo['nombres_acompanantes']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-users me-2"></i>
                                        <?php echo htmlspecialchars($grupo['nombres_acompanantes']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="detail-item">
                                    <i class="fas fa-key me-2"></i>
                                    Token: <code class="text-dark"><?php echo $grupo['token_unico']; ?></code>
                                    <button class="btn btn-sm btn-outline-secondary ms-1" 
                                            onclick="copiarToken('<?php echo $grupo['token_unico']; ?>')">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                
                                <?php if ($grupo['fecha_respuesta']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($grupo['fecha_respuesta'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="guest-actions">
                                <button class="btn btn-sm btn-action btn-secondary" 
                                        onclick="editarGrupo(<?php echo $grupo['id_grupo']; ?>, '<?php echo htmlspecialchars($grupo['nombre_grupo'], ENT_QUOTES); ?>', <?php echo $grupo['boletos_asignados']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-action btn-info" 
                                        onclick="compartirInvitacion('<?php echo htmlspecialchars($grupo['nombre_grupo'], ENT_QUOTES); ?>', '<?php echo $grupo['token_unico']; ?>')">
                                    <i class="fas fa-share"></i>
                                </button>
                                <?php if ($grupo['estado'] && $grupo['estado'] !== 'pendiente'): ?>
                                <button class="btn btn-sm btn-action btn-success" 
                                        onclick="verDetallesRespuesta(<?php echo $grupo['id_grupo']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-action btn-danger" 
                                        onclick="eliminarGrupo(<?php echo $grupo['id_grupo']; ?>, '<?php echo htmlspecialchars($grupo['nombre_grupo'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Vista Desktop -->
                <div class="d-none d-md-block">
                    <div class="table-responsive">
                        <?php if (empty($grupos)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay grupos aún</h5>
                                <p class="text-muted">Crea tu primer grupo de invitados</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearGrupo">
                                    <i class="fas fa-plus me-2"></i>Crear Primer Grupo
                                </button>
                            </div>

                        <?php else: ?>
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Grupo/Invitados</th>
                                        <th>Boletos</th>
                                        <th>Estado</th>
                                        <th>Token</th>
                                        <th>Respuesta</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla-grupos">
                                    <?php foreach ($grupos as $grupo): 
                                        $estados = [
                                            'pendiente' => 'Sin respuesta',
                                            'aceptado' => 'Confirmado',
                                            'rechazado' => 'No asistirá'
                                        ];
                                        $estado = $grupo['estado'] ?? 'pendiente';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($grupo['nombre_grupo']); ?></strong>
                                            <?php if ($grupo['nombres_acompanantes']): ?>
                                                <br><small class="text-muted">Con: <?php echo htmlspecialchars($grupo['nombres_acompanantes']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo $grupo['boletos_asignados']; ?></span>
                                            <?php if ($grupo['estado'] == 'aceptado'): ?>
                                                <br><small class="text-success">Confirmados: <?php echo $grupo['boletos_confirmados']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $estado; ?>">
                                                <?php echo $estados[$estado] ?? 'Pendiente'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <code class="text-dark"><?php echo htmlspecialchars($grupo['token_unico']); ?></code>
                                            <button class="btn btn-sm btn-outline-secondary ms-1" 
                                                    onclick="copiarToken('<?php echo htmlspecialchars($grupo['token_unico'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <?php if ($grupo['fecha_respuesta']): ?>
                                                <small><?php echo date('d/m/Y H:i', strtotime($grupo['fecha_respuesta'])); ?></small>
                                                <?php if ($grupo['comentarios']): ?>
                                                    <br><small class="text-info">
                                                        <i class="fas fa-comment"></i> Con comentarios
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <small class="text-muted">Sin respuesta</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="table-actions">
                                            <button class="btn btn-sm btn-action btn-secondary" 
                                                    onclick="editarGrupo(<?php echo $grupo['id_grupo']; ?>, '<?php echo htmlspecialchars($grupo['nombre_grupo'], ENT_QUOTES); ?>', <?php echo $grupo['boletos_asignados']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-action btn-info" 
                                                    onclick="compartirInvitacion('<?php echo htmlspecialchars($grupo['nombre_grupo'], ENT_QUOTES); ?>', '<?php echo $grupo['token_unico']; ?>')">
                                                <i class="fas fa-share"></i>
                                            </button>
                                            <?php if ($grupo['estado'] && $grupo['estado'] !== 'pendiente'): ?>
                                            <button class="btn btn-sm btn-action btn-success" 
                                                    onclick="verDetallesRespuesta(<?php echo $grupo['id_grupo']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-action btn-danger" 
                                                    onclick="eliminarGrupo(<?php echo $grupo['id_grupo']; ?>, '<?php echo htmlspecialchars($grupo['nombre_grupo'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Paginación Inferior -->
                <div id="paginacion-container-bottom" class="pagination-wrapper mt-3" style="display: none;">
                    <nav aria-label="Paginación de invitados">
                        <ul class="pagination justify-content-center mb-2" id="pagination-list-bottom">
                            <!-- Se genera dinámicamente con JS -->
                        </ul>
                    </nav>
                    <p class="pagination-info text-center mb-0" id="pagination-info-bottom">
                        <!-- Se genera dinámicamente con JS -->
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Grupo -->
    <div class="modal fade" id="modalCrearGrupo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="crear_grupo">
                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo Grupo de Invitados</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre_grupo" class="form-label">Nombre del Grupo/Invitados</label>
                            <input type="text" class="form-control" id="nombre_grupo" name="nombre_grupo" required>
                        </div>
                        <div class="mb-3">
                            <label for="boletos_asignados" class="form-label">Boletos Asignados</label>
                            <input type="number" class="form-control" id="boletos_asignados" name="boletos_asignados" min="1" value="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Grupo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Grupo -->
    <div class="modal fade" id="modalEditarGrupo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="editar_grupo">
                    <input type="hidden" name="id_grupo" id="edit_id_grupo">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Grupo de Invitados</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre_grupo" class="form-label">Nombre del Grupo/Invitados</label>
                            <input type="text" class="form-control" id="edit_nombre_grupo" name="nombre_grupo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_boletos_asignados" class="form-label">Boletos Asignados</label>
                            <input type="number" class="form-control" id="edit_boletos_asignados" name="boletos_asignados" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Compartir -->
    <div class="modal fade" id="modalCompartir" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compartir Invitación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="share-section mb-4">
                        <label class="form-label fw-bold">Grupo:</label>
                        <div id="grupo-nombre" class="text-primary fs-5"></div>
                    </div>
                    
                    <div class="share-section mb-4">
                        <label class="form-label fw-bold">Link de Invitación:</label>
                        <div class="url-input-group">
                            <input type="text" class="form-control" id="link-invitacion" readonly>
                            <button class="btn btn-outline-primary" onclick="copiarLinkInvitacion()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="share-section mb-4">
                        <label class="form-label fw-bold">Token de Acceso:</label>
                        <div class="token-box">
                            <code id="token-display" class="fs-4"></code>
                            <button class="btn btn-outline-secondary btn-sm ms-2" onclick="copiarTokenDisplay()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted">Compártelo junto con el link para acceso directo</small>
                    </div>
                    
                    <div class="share-buttons">
                        <button class="btn btn-success w-100 mb-2" onclick="compartirWhatsApp()">
                            <i class="fab fa-whatsapp me-2"></i>Compartir por WhatsApp
                        </button>
                        <button class="btn btn-primary w-100 mb-2" onclick="compartirTelegram()">
                            <i class="fab fa-telegram me-2"></i>Compartir por Telegram
                        </button>
                        <button class="btn btn-info w-100" onclick="copiarMensajeCompleto()">
                            <i class="fas fa-copy me-2"></i>Copiar Mensaje Completo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div class="modal fade" id="modalDetallesRespuesta" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles de la Respuesta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detalles-respuesta-content">
                    <!-- Se llena dinámicamente -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Variables globales
        window.dashboardConfig = {
            invitacionSlug: '<?php echo $invitacion_slug; ?>',
            invitacionId: <?php echo $invitacion_seleccionada_id; ?>,
            invitacionUrl: '<?php echo $invitacion_url; ?>',
            nombresNovios: '<?php echo htmlspecialchars($invitacion['nombres_novios'], ENT_QUOTES); ?>'
        };

        function cambiarInvitacion(invitacionId) {
            window.location.href = 'dashboard_cliente.php?invitacion_id=' + invitacionId;
        }
    </script>
    
    <!-- JS principal -->
    <script src="js/dashboard_filtros.js?v=<?php echo filemtime('./js/dashboard_filtros.js'); ?>"></script>
    <script src="js/dashboard.js?v=<?php echo filemtime('./js/dashboard.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</body>
</html>
