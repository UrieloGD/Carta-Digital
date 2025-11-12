<?php
require_once './../config/database.php';
require_once './../config/auth.php';

// Solo admin puede acceder
requireLogin();
requireRole('admin'); // Solo admin puede gestionar usuarios

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'crear':
                    $nombre = $_POST['nombre'] ?? '';
                    $email = $_POST['email'] ?? '';
                    $password = $_POST['password'] ?? '';
                    $rol = $_POST['rol'] ?? 'viewer';
                    $activo = isset($_POST['activo']) ? 1 : 0;
                    
                    // Validaciones
                    if (empty($nombre) || empty($email) || empty($password)) {
                        throw new Exception("Todos los campos son obligatorios");
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Email inválido");
                    }
                    
                    if (strlen($password) < 8) {
                        throw new Exception("La contraseña debe tener al menos 8 caracteres");
                    }
                    
                    // Verificar si el email ya existe
                    $stmt = $db->prepare("SELECT id FROM usuarios_admin WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) {
                        throw new Exception("Ya existe un usuario con ese email");
                    }
                    
                    // Hash de la contraseña
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insertar usuario
                    $stmt = $db->prepare("INSERT INTO usuarios_admin (nombre, email, password, rol, activo) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$nombre, $email, $password_hash, $rol, $activo]);
                    
                    $success_message = "Usuario creado exitosamente";
                    break;
                    
                case 'editar':
                    $id = $_POST['id'] ?? 0;
                    $nombre = $_POST['nombre'] ?? '';
                    $email = $_POST['email'] ?? '';
                    $rol = $_POST['rol'] ?? 'viewer';
                    $activo = isset($_POST['activo']) ? 1 : 0;
                    $cambiar_password = !empty($_POST['password']);
                    
                    if (empty($nombre) || empty($email)) {
                        throw new Exception("Nombre y email son obligatorios");
                    }
                    
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Email inválido");
                    }
                    
                    // Verificar que el email no esté en uso por otro usuario
                    $stmt = $db->prepare("SELECT id FROM usuarios_admin WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $id]);
                    if ($stmt->fetch()) {
                        throw new Exception("Ya existe otro usuario con ese email");
                    }
                    
                    // Actualizar usuario
                    if ($cambiar_password) {
                        if (strlen($_POST['password']) < 8) {
                            throw new Exception("La contraseña debe tener al menos 8 caracteres");
                        }
                        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $db->prepare("UPDATE usuarios_admin SET nombre = ?, email = ?, password = ?, rol = ?, activo = ? WHERE id = ?");
                        $stmt->execute([$nombre, $email, $password_hash, $rol, $activo, $id]);
                    } else {
                        $stmt = $db->prepare("UPDATE usuarios_admin SET nombre = ?, email = ?, rol = ?, activo = ? WHERE id = ?");
                        $stmt->execute([$nombre, $email, $rol, $activo, $id]);
                    }
                    
                    $success_message = "Usuario actualizado exitosamente";
                    break;
                    
                case 'eliminar':
                    $id = $_POST['id'] ?? 0;
                    
                    // No permitir eliminar el último admin
                    $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios_admin WHERE rol = 'admin' AND activo = 1");
                    $stmt->execute();
                    $count_admins = $stmt->fetchColumn();
                    
                    $stmt = $db->prepare("SELECT rol FROM usuarios_admin WHERE id = ?");
                    $stmt->execute([$id]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($usuario['rol'] === 'admin' && $count_admins <= 1) {
                        throw new Exception("No puedes eliminar el último administrador del sistema");
                    }
                    
                    // No permitir auto-eliminación
                    if ($id == $_SESSION['admin_id']) {
                        throw new Exception("No puedes eliminar tu propia cuenta");
                    }
                    
                    $stmt = $db->prepare("DELETE FROM usuarios_admin WHERE id = ?");
                    $stmt->execute([$id]);
                    
                    $success_message = "Usuario eliminado exitosamente";
                    break;
            }
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Obtener todos los usuarios
$stmt = $db->query("SELECT * FROM usuarios_admin ORDER BY fecha_creacion DESC");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/usuarios.css?v=<?php echo filemtime('./css/usuarios.css'); ?>" />
    <link rel="shortcut icon" href="./../images/logo.webp" />

</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-people-fill"></i>
                <span class="d-none d-sm-inline">Gestión de Usuarios</span>
                <span class="d-sm-none">Usuarios</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a href="index.php" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>
                        Volver al Panel
                    </a>
                    <a href="logout.php" class="btn btn-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>
                        Salir
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Alertas -->
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header con botón crear -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><i class="bi bi-people me-2"></i>Usuarios Administradores</h2>
                <p class="text-muted mb-0">Gestiona los usuarios con acceso al panel de administración</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">
                <i class="bi bi-plus-circle me-1"></i>
                Nuevo Usuario
            </button>
        </div>

        <!-- Tabla de usuarios -->
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Último acceso</th>
                                <th>Fecha registro</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                                            <?php if ($usuario['id'] == $_SESSION['admin_id']): ?>
                                                <span class="badge bg-info ms-2">Tú</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <?php
                                    $role_class = [
                                        'admin' => 'bg-danger',
                                        'editor' => 'bg-primary',
                                        'viewer' => 'bg-secondary'
                                    ];
                                    $role_text = [
                                        'admin' => 'Administrador',
                                        'editor' => 'Editor',
                                        'viewer' => 'Visor'
                                    ];
                                    ?>
                                    <span class="badge role-badge <?php echo $role_class[$usuario['rol']] ?? 'bg-secondary'; ?>">
                                        <?php echo $role_text[$usuario['rol']] ?? ucfirst($usuario['rol']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($usuario['activo']): ?>
                                        <span class="badge bg-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($usuario['ultimo_login']): ?>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_login'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">Nunca</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?>
                                    </small>
                                </td>
                                <td class="text-end table-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($usuario['id'] != $_SESSION['admin_id']): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#eliminarModal<?php echo $usuario['id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Modal eliminar -->
                            <div class="modal fade" id="eliminarModal<?php echo $usuario['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirmar eliminación</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>¿Estás seguro de eliminar a <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>?</p>
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                Esta acción no se puede deshacer.
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="eliminar">
                                                <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" class="btn btn-danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Usuario -->
    <div class="modal fade" id="crearUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-person-plus me-2"></i>
                            Crear Nuevo Usuario
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Mínimo 8 caracteres</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol *</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="viewer">Visor - Solo puede ver</option>
                                <option value="editor">Editor - Puede editar invitaciones</option>
                                <option value="admin">Administrador - Control total</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">
                                Usuario activo
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>
                            Crear Usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-pencil me-2"></i>
                            Editar Usuario
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre completo *</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Nueva Contraseña (opcional)</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="edit_password" name="password" minlength="8">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('edit_password')">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Dejar vacío para mantener la contraseña actual</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_rol" class="form-label">Rol *</label>
                            <select class="form-select" id="edit_rol" name="rol" required>
                                <option value="viewer">Visor - Solo puede ver</option>
                                <option value="editor">Editor - Puede editar invitaciones</option>
                                <option value="admin">Administrador - Control total</option>
                            </select>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_activo" name="activo">
                            <label class="form-check-label" for="edit_activo">
                                Usuario activo
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarUsuario(usuario) {
            document.getElementById('edit_id').value = usuario.id;
            document.getElementById('edit_nombre').value = usuario.nombre;
            document.getElementById('edit_email').value = usuario.email;
            document.getElementById('edit_rol').value = usuario.rol;
            document.getElementById('edit_activo').checked = usuario.activo == 1;
            document.getElementById('edit_password').value = '';
            
            new bootstrap.Modal(document.getElementById('editarUsuarioModal')).show();
        }
        
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    </script>
</body>
</html>
