<?php
// dashboard_cliente.php (Solo las partes corregidas)
session_start();
require_once 'config/database.php';
require_once 'functions/auth-check.php';

$db = new Database();
$conn = $db->getConnection();

// Obtener información de la invitación del cliente (CORREGIDO para PDO y collations)
$stmt = $conn->prepare("
    SELECT i.*, p.nombre as plantilla_nombre 
    FROM invitaciones i 
    INNER JOIN plantillas p ON i.plantilla_id = p.id 
    WHERE EXISTS (
        SELECT 1 FROM clientes_login cl 
        WHERE cl.slug COLLATE utf8mb4_unicode_ci = i.slug COLLATE utf8mb4_unicode_ci 
        AND cl.id = ?
    ) 
    LIMIT 1
");
$stmt->execute([$_SESSION['cliente_id']]);
$invitacion = $stmt->fetch();

if (!$invitacion) {
    die("Error: No se encontró la invitación asociada a tu cuenta.");
}

$invitacion_slug = $invitacion['slug'];

// Obtener estadísticas RSVP (CORREGIDO para PDO)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_grupos,
        SUM(boletos_asignados) as total_boletos,
        SUM(CASE WHEN r.estado = 'aceptado' THEN r.boletos_confirmados ELSE 0 END) as confirmados,
        SUM(CASE WHEN r.estado = 'rechazado' THEN r.boletos_confirmados ELSE 0 END) as rechazados,
        SUM(CASE WHEN r.estado = 'pendiente' OR r.estado IS NULL THEN ig.boletos_asignados ELSE 0 END) as pendientes
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
                        $token_unico = bin2hex(random_bytes(16));
                        
                        $stmt = $conn->prepare("
                            INSERT INTO invitados_grupos (slug_invitacion, nombre_grupo, boletos_asignados, token_unico) 
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$invitacion_slug, $nombre_grupo, $boletos, $token_unico]);
                        $nuevo_id = $conn->lastInsertId();
                        
                        // Crear respuesta pendiente
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

// Obtener todos los grupos de invitados (CORREGIDO para PDO)
$stmt = $conn->prepare("
    SELECT 
        ig.*,
        r.estado,
        r.boletos_confirmados,
        r.nombres_acompanantes,
        r.comentarios,
        r.fecha_respuesta
    FROM invitados_grupos ig 
    LEFT JOIN rsvp_respuestas r ON ig.id_grupo = r.id_grupo 
    WHERE ig.slug_invitacion = ? 
    ORDER BY ig.fecha_creacion DESC
");
$stmt->execute([$invitacion_slug]);
$grupos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($invitacion['nombres_novios']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
        }
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 4px solid #667eea;
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .btn-action {
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 500;
        }
        .url-box {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 1rem;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
        }
        .status-badge {
            border-radius: 15px;
            padding: 5px 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-pendiente { background: #fff3cd; color: #856404; }
        .status-aceptado { background: #d1e7dd; color: #0f5132; }
        .status-rechazado { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-heart me-2"></i>
                        <?php echo htmlspecialchars($invitacion['nombres_novios']); ?>
                    </h1>
                    <p class="mb-0 mt-2">
                        <i class="fas fa-calendar me-2"></i>
                        <?php echo date('d/m/Y', strtotime($invitacion['fecha_evento'])); ?>
                        <span class="ms-3">
                            <i class="fas fa-clock me-2"></i>
                            <?php echo date('H:i', strtotime($invitacion['hora_evento'])); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="invitacion.php?slug=<?php echo $invitacion['slug']; ?>" 
                       target="_blank" 
                       class="btn btn-light btn-action me-2">
                        <i class="fas fa-eye me-2"></i>Ver Invitación
                    </a>
                    <a href="logout.php" class="btn btn-outline-light btn-action">
                        <i class="fas fa-sign-out-alt me-2"></i>Salir
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas RSVP -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-number"><?php echo $stats['total_grupos'] ?? 0; ?></div>
                    <div class="text-muted">Grupos Invitados</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-number text-success"><?php echo $stats['confirmados'] ?? 0; ?></div>
                    <div class="text-muted">Confirmados</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-number text-danger"><?php echo $stats['rechazados'] ?? 0; ?></div>
                    <div class="text-muted">No Asistirán</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card text-center">
                    <div class="stats-number text-warning"><?php echo $stats['pendientes'] ?? 0; ?></div>
                    <div class="text-muted">Sin Respuesta</div>
                </div>
            </div>
        </div>

        <!-- URL Pública -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-link me-2"></i>URL de tu Invitación
                </h5>
            </div>
            <div class="card-body">
                <div class="url-box">
                    <div class="d-flex align-items-center">
                        <input type="text" 
                               class="form-control border-0 bg-transparent" 
                               id="invitacion-url" 
                               value="<?php echo 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/invitacion.php?slug=' . $invitacion['slug']; ?>" 
                               readonly>
                        <button class="btn btn-primary btn-action ms-2" onclick="copiarURL()">
                            <i class="fas fa-copy me-2"></i>Copiar
                        </button>
                    </div>
                </div>
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Esta es la URL pública de tu invitación que puedes compartir
                </small>
            </div>
        </div>

        <!-- CRUD Grupos de Invitados -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>Gestión de Invitados
                </h5>
                <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#modalCrearGrupo">
                    <i class="fas fa-plus me-2"></i>Nuevo Grupo
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Grupo/Familia</th>
                                <th>Boletos</th>
                                <th>Estado</th>
                                <th>Respuesta</th>
                                <th>Token Único</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grupos as $grupo): ?>
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
                                    <span class="status-badge status-<?php echo $grupo['estado']; ?>">
                                        <?php 
                                        $estados = ['pendiente' => 'Sin respuesta', 'aceptado' => 'Confirmado', 'rechazado' => 'No asistirá'];
                                        echo $estados[$grupo['estado']] ?? 'Pendiente';
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($grupo['fecha_respuesta']): ?>
                                        <small><?php echo date('d/m/Y H:i', strtotime($grupo['fecha_respuesta'])); ?></small>
                                        <?php if ($grupo['comentarios']): ?>
                                            <br><small class="text-info" title="<?php echo htmlspecialchars($grupo['comentarios']); ?>">
                                                <i class="fas fa-comment"></i> Con comentarios
                                            </small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <small class="text-muted">Sin respuesta</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="small"><?php echo substr($grupo['token_unico'], 0, 8); ?>...</code>
                                    <button class="btn btn-sm btn-outline-secondary ms-1" 
                                            onclick="copiarToken('<?php echo $grupo['token_unico']; ?>')"
                                            title="Copiar token completo">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="editarGrupo(<?php echo $grupo['id_grupo']; ?>, '<?php echo htmlspecialchars($grupo['nombre_grupo'], ENT_QUOTES); ?>', <?php echo $grupo['boletos_asignados']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info ms-1" 
                                            onclick="verURLToken('<?php echo $grupo['token_unico']; ?>')">
                                        <i class="fas fa-link"></i>
                                    </button>
                                    <?php if ($grupo['estado'] && $grupo['estado'] !== 'pendiente'): ?>
                                    <button class="btn btn-sm btn-info ms-1" 
                                            onclick="verDetallesRespuesta(<?php echo $grupo['id_grupo']; ?>)"
                                            title="Ver detalles de la respuesta">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-danger ms-1" 
                                            onclick="eliminarGrupo(<?php echo $grupo['id_grupo']; ?>, '<?php echo htmlspecialchars($grupo['nombre_grupo'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                            <label for="nombre_grupo" class="form-label">Nombre del Grupo/Familia</label>
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
                            <label for="edit_nombre_grupo" class="form-label">Nombre del Grupo/Familia</label>
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

    <!-- Modal URL con Token -->
    <div class="modal fade" id="modalURLToken" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">URL Personalizada para este Grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="url-box">
                        <input type="text" class="form-control" id="url-con-token" readonly>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary btn-action" onclick="copiarURLToken()">
                            <i class="fas fa-copy me-2"></i>Copiar URL
                        </button>
                        <button class="btn btn-success btn-action ms-2" onclick="compartirWhatsApp()">
                            <i class="fab fa-whatsapp me-2"></i>Compartir por WhatsApp
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Esta URL permite al grupo acceder directamente al RSVP
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copiarURL() {
            const urlField = document.getElementById('invitacion-url');
            urlField.select();
            document.execCommand('copy');
            
            // Mostrar feedback
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2"></i>Copiado!';
            btn.classList.replace('btn-primary', 'btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.replace('btn-success', 'btn-primary');
            }, 2000);
        }

        function copiarToken(token) {
            navigator.clipboard.writeText(token).then(() => {
                // Mostrar feedback
                const btn = event.target.closest('button');
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.classList.replace('btn-outline-secondary', 'btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-copy"></i>';
                    btn.classList.replace('btn-success', 'btn-outline-secondary');
                }, 2000);
            });
        }

        function editarGrupo(id, nombre, boletos) {
            document.getElementById('edit_id_grupo').value = id;
            document.getElementById('edit_nombre_grupo').value = nombre;
            document.getElementById('edit_boletos_asignados').value = boletos;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEditarGrupo'));
            modal.show();
        }

        function eliminarGrupo(id, nombre) {
            if (confirm(`¿Estás seguro de eliminar el grupo "${nombre}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="eliminar_grupo">
                    <input type="hidden" name="id_grupo" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function verURLToken(token) {
            const baseURL = window.location.protocol + '//' + window.location.host + 
                           window.location.pathname.replace('dashboard_cliente.php', 'invitacion.php');
            const urlConToken = baseURL + '?slug=<?php echo $invitacion['slug']; ?>&token=' + token;
            
            document.getElementById('url-con-token').value = urlConToken;
            
            const modal = new bootstrap.Modal(document.getElementById('modalURLToken'));
            modal.show();
        }

        function copiarURLToken() {
            const urlField = document.getElementById('url-con-token');
            urlField.select();
            document.execCommand('copy');
            
            // Mostrar feedback
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check me-2"></i>Copiado!';
            btn.classList.replace('btn-primary', 'btn-success');
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.replace('btn-success', 'btn-primary');
            }, 2000);
        }

        function compartirWhatsApp() {
            const url = document.getElementById('url-con-token').value;
            const mensaje = encodeURIComponent(`¡Estás invitado a nuestra boda! 💒\n\nConfirma tu asistencia aquí: ${url}`);
            window.open(`https://wa.me/?text=${mensaje}`, '_blank');
        }

        function verDetallesRespuesta(id_grupo) {
            // Mostrar loading
            document.getElementById('detalles-respuesta-content').innerHTML = 
                '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
            
            const modal = new bootstrap.Modal(document.getElementById('modalDetallesRespuesta'));
            modal.show();
            
            // Cargar detalles usando tu API existente
            fetch(`./plantillas/plantilla-1/api/rsvp.php?action=cargar_respuesta&id_grupo=${id_grupo}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const respuesta = data.respuesta;
                    const nombresInvitados = data.nombres_invitados;
                    
                    let html = `
                        <div class="card border-0">
                            <div class="card-body">
                                <h5 class="card-title">${respuesta.nombre_grupo}</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Estado:</strong> 
                                            <span class="status-badge status-${respuesta.estado}">
                                                ${respuesta.estado === 'aceptado' ? 'Confirmados' : 
                                                respuesta.estado === 'rechazado' ? 'No asistirán' : 'Sin respuesta'}
                                            </span>
                                        </p>
                                        <p><strong>Boletos asignados:</strong> ${respuesta.boletos_asignados}</p>
                                        ${respuesta.estado === 'aceptado' ? 
                                            `<p><strong>Boletos confirmados:</strong> ${respuesta.boletos_confirmados}</p>` : ''}
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Fecha de respuesta:</strong><br>
                                        <small>${new Date(respuesta.fecha_respuesta).toLocaleString()}</small></p>
                                    </div>
                                </div>
                    `;
                    
                    if (nombresInvitados && nombresInvitados.length > 0) {
                        html += `
                            <hr>
                            <p><strong>Invitados confirmados:</strong></p>
                            <ul class="list-unstyled">`;
                        
                        nombresInvitados.forEach(nombre => {
                            html += `<li><i class="fas fa-user me-2 text-primary"></i>${nombre}</li>`;
                        });
                        
                        html += `</ul>`;
                    }
                    
                    if (respuesta.comentarios) {
                        html += `
                            <hr>
                            <p><strong>Comentarios:</strong></p>
                            <div class="alert alert-light">
                                ${respuesta.comentarios}
                            </div>`;
                    }
                    
                    html += `</div></div>`;
                    
                    document.getElementById('detalles-respuesta-content').innerHTML = html;
                } else {
                    document.getElementById('detalles-respuesta-content').innerHTML = 
                        `<div class="alert alert-danger">Error: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('detalles-respuesta-content').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar los detalles</div>';
            });
        }

        // Auto-refresh estadísticas cada 30 segundos
        setInterval(() => {
            fetch(window.location.href + '&ajax=1') // Agrega parámetro para respuesta JSON
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Actualizar estadísticas
                    const statsNumbers = document.querySelectorAll('.stats-number');
                    const newStatsNumbers = doc.querySelectorAll('.stats-number');
                    
                    statsNumbers.forEach((stat, index) => {
                        if (newStatsNumbers[index] && stat.textContent !== newStatsNumbers[index].textContent) {
                            stat.textContent = newStatsNumbers[index].textContent;
                            // Animate the change
                            stat.style.animation = 'none';
                            stat.offsetHeight; // trigger reflow
                            stat.style.animation = 'pulse 0.5s';
                        }
                    });
                    
                    // Actualizar tabla si hay cambios
                    const currentTable = document.querySelector('tbody');
                    const newTable = doc.querySelector('tbody');
                    if (newTable && currentTable.innerHTML !== newTable.innerHTML) {
                        currentTable.innerHTML = newTable.innerHTML;
                    }
                })
                .catch(console.error);
        }, 30000);
    </script>
</body>
</html>