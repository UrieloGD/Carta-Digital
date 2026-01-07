<?php
require_once './../config/database.php';
require_once './../config/auth.php';

// Requiere login de admin
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Obtener rol del usuario actual
$rol_usuario = $_SESSION['admin_rol'] ?? 'viewer';

// Solo admin puede crear usuarios
if ($rol_usuario !== 'admin') {
    header('Location: index.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';
$usuario_creado = null;

// Obtener invitaciones
try {
    $query = "SELECT 
        i.id, 
        i.slug, 
        i.nombres_novios,
        i.fecha_evento,
        i.activa,
        p.nombre as plantilla_nombre,
        pl.nombre as plan_nombre
    FROM invitaciones i 
    LEFT JOIN plantillas p ON i.plantilla_id = p.id
    LEFT JOIN planes pl ON i.plan_id = pl.id
    ORDER BY i.fecha_creacion DESC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $invitaciones_todas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $invitaciones_activas = array_filter($invitaciones_todas, function($inv) {
        return $inv['activa'] == 1;
    });
    
    $invitaciones = !empty($invitaciones_activas) ? $invitaciones_activas : $invitaciones_todas;
    
} catch (PDOException $e) {
    $mensaje = "❌ Error al cargar invitaciones: " . $e->getMessage();
    $tipo_mensaje = 'danger';
    $invitaciones = [];
}

// Procesar creación de usuario
// Procesar creación de usuario
// Procesar creación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $invitacion_id = $_POST['invitacion_id'] ?? 0;
    $nombre = trim($_POST['nombre_cliente']);
    $apellido = trim($_POST['apellido_cliente'] ?? '');
    $email = trim($_POST['email_cliente'] ?? '');
    $telefono = trim($_POST['telefono_cliente'] ?? '');
    
    if ($invitacion_id > 0 && !empty($nombre)) {
        try {
            // ✅ INICIAR TRANSACCIÓN
            $db->beginTransaction();
            
            // ✅ Obtener TODA la información de la invitación
            $query_inv = "SELECT 
                i.id, 
                i.slug, 
                i.nombres_novios, 
                i.fecha_evento,
                i.plantilla_id,
                i.plan_id,
                pl.nombre as plan_nombre,
                pl.precio as plan_precio
            FROM invitaciones i
            LEFT JOIN planes pl ON i.plan_id = pl.id
            WHERE i.id = ?";
            
            $stmt_inv = $db->prepare($query_inv);
            $stmt_inv->execute([$invitacion_id]);
            $invitacion = $stmt_inv->fetch(PDO::FETCH_ASSOC);
            
            if (!$invitacion) {
                throw new Exception("Invitación no encontrada");
            }
            
            // Generar contraseña
            $raw_password = $invitacion['slug'] . str_replace('-', '', $invitacion['fecha_evento']);
            $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
            
            error_log("=== CREAR USUARIO MANUAL ===");
            error_log("Slug: " . $invitacion['slug']);
            error_log("Plan: " . $invitacion['plan_nombre']);
            error_log("Contraseña: " . $raw_password);
            
            $nombres_completos = !empty($apellido) ? $nombre . ' ' . $apellido : $nombre;
            
            // Verificar si existe cliente
            $check_cliente = $db->prepare("SELECT id FROM clientes WHERE slug = ?");
            $check_cliente->execute([$invitacion['slug']]);
            $cliente_existe = $check_cliente->fetch(PDO::FETCH_ASSOC);
            
            $cliente_id = null;
            
            if ($cliente_existe) {
                // ✅ ACTUALIZAR cliente existente
                $stmt = $db->prepare("
                    UPDATE clientes 
                    SET nombre = ?, 
                        apellido = ?, 
                        nombres_novios = ?,
                        email = ?, 
                        telefono = ?,
                        password = ?,
                        fecha_actualizacion = NOW()
                    WHERE slug = ?
                ");
                
                $stmt->execute([
                    $nombre,
                    $apellido,
                    $invitacion['nombres_novios'],
                    $email,
                    $telefono,
                    $password_hash,
                    $invitacion['slug']
                ]);
                
                $cliente_id = $cliente_existe['id'];
                error_log("✅ Cliente actualizado - ID: " . $cliente_id);
                $mensaje = "✅ Usuario actualizado exitosamente";
                
            } else {
                // ✅ CREAR nuevo cliente
                $stmt = $db->prepare("
                    INSERT INTO clientes (
                        slug, 
                        nombre, 
                        apellido, 
                        nombres_novios,
                        email, 
                        telefono, 
                        password,
                        fecha_registro
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $invitacion['slug'],
                    $nombre,
                    $apellido,
                    $invitacion['nombres_novios'],
                    $email,
                    $telefono,
                    $password_hash
                ]);
                
                $cliente_id = $db->lastInsertId();
                error_log("✅ Cliente creado - ID: " . $cliente_id);
                $mensaje = "✅ Usuario creado exitosamente";
            }
            
            // ✅ VINCULAR invitación con cliente_id
            if ($cliente_id) {
                $stmt_update_inv = $db->prepare("
                    UPDATE invitaciones 
                    SET cliente_id = ? 
                    WHERE id = ?
                ");
                $stmt_update_inv->execute([$cliente_id, $invitacion_id]);
                
                error_log("✅ Invitación vinculada - invitacion_id: {$invitacion_id} -> cliente_id: {$cliente_id}");
                
                // ✅ VERIFICAR si existe pedido
                $check_pedido = $db->prepare("SELECT id, plan FROM pedidos WHERE invitacion_id = ?");
                $check_pedido->execute([$invitacion_id]);
                $pedido_existe = $check_pedido->fetch(PDO::FETCH_ASSOC);
                
                if ($pedido_existe) {
                    // ✅ ACTUALIZAR pedido existente con el plan correcto
                    $stmt_update_pedido = $db->prepare("
                        UPDATE pedidos 
                        SET cliente_id = ?,
                            plan = ?,
                            monto = ?,
                            estado = 'completado',
                            fecha_pago = COALESCE(fecha_pago, NOW())
                        WHERE id = ?
                    ");
                    
                    $stmt_update_pedido->execute([
                        $cliente_id,
                        $invitacion['plan_nombre'],
                        $invitacion['plan_precio'],
                        $pedido_existe['id']
                    ]);
                    
                    error_log("✅ Pedido actualizado - ID: {$pedido_existe['id']} - Plan: {$invitacion['plan_nombre']}");
                    
                } else {
                    // ✅ CREAR pedido nuevo
                    $referencia_manual = 'MANUAL-ADMIN-' . time();
                    
                    $stmt_pedido = $db->prepare("
                        INSERT INTO pedidos (
                            cliente_id,
                            plantilla_id,
                            invitacion_id,
                            metodo_pago,
                            estado,
                            plan,
                            monto,
                            fecha_pago,
                            payment_intent_id
                        ) VALUES (?, ?, ?, 'spei', 'completado', ?, ?, NOW(), ?)
                    ");
                    
                    $stmt_pedido->execute([
                        $cliente_id,
                        $invitacion['plantilla_id'],
                        $invitacion_id,
                        $invitacion['plan_nombre'],
                        $invitacion['plan_precio'],
                        $referencia_manual
                    ]);
                    
                    $pedido_id = $db->lastInsertId();
                    error_log("✅ Pedido creado - ID: {$pedido_id} - Plan: {$invitacion['plan_nombre']}");
                }
            }
            
            // ✅ COMMIT TRANSACCIÓN
            $db->commit();
            
            // ✅ Verificación final completa
            $verify = $db->prepare("
                SELECT 
                    c.id as cliente_id,
                    c.slug,
                    c.password,
                    i.id as invitacion_id,
                    i.cliente_id as inv_cliente_id,
                    i.tipo_rsvp,
                    i.plan_id,
                    ped.id as pedido_id,
                    ped.plan as pedido_plan,
                    ped.estado as pedido_estado
                FROM clientes c
                LEFT JOIN invitaciones i ON c.id = i.cliente_id
                LEFT JOIN pedidos ped ON i.id = ped.invitacion_id
                WHERE c.slug = ?
            ");
            $verify->execute([$invitacion['slug']]);
            $verify_data = $verify->fetch(PDO::FETCH_ASSOC);
            
            if ($verify_data) {
                error_log("✅ VERIFICACIÓN FINAL:");
                error_log("  Cliente ID: " . $verify_data['cliente_id']);
                error_log("  Invitación ID: " . $verify_data['invitacion_id']);
                error_log("  Invitación vinculada: " . ($verify_data['inv_cliente_id'] ? 'SÍ' : 'NO'));
                error_log("  tipo_rsvp: " . ($verify_data['tipo_rsvp'] ?? 'NULL'));
                error_log("  plan_id: " . ($verify_data['plan_id'] ?? 'NULL'));
                error_log("  Pedido ID: " . ($verify_data['pedido_id'] ?? 'NULL'));
                error_log("  Pedido Plan: " . ($verify_data['pedido_plan'] ?? 'NULL'));
                error_log("  Pedido Estado: " . ($verify_data['pedido_estado'] ?? 'NULL'));
                error_log("  Test password: " . (password_verify($raw_password, $verify_data['password']) ? 'OK' : 'FAIL'));
            }
            
            $tipo_mensaje = 'success';
            $usuario_creado = [
                'slug' => $invitacion['slug'],
                'password' => $raw_password,
                'nombre' => $nombres_completos,
                'email' => $email,
                'telefono' => $telefono,
                'invitacion' => $invitacion['nombres_novios'],
                'fecha_evento' => $invitacion['fecha_evento'],
                'cliente_id' => $cliente_id,
                'plan' => $invitacion['plan_nombre']
            ];
            
        } catch (Exception $e) {
            // ✅ ROLLBACK en caso de error
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $mensaje = "❌ Error: " . $e->getMessage();
            $tipo_mensaje = 'danger';
            error_log("❌ Error en creación: " . $e->getMessage());
        }
    } else {
        $mensaje = "❌ Por favor selecciona una invitación y completa el nombre del cliente";
        $tipo_mensaje = 'danger';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Usuario Manual - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/index.css?v=<?php echo time(); ?>" />
    <link rel="shortcut icon" href="./../images/logo.webp" />
    <style>
        body {
            background: linear-gradient(135deg, #faf7f5, #f5ece8);
            min-height: 100vh;
        }
        
        .main-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 25px 30px;
            border-radius: 16px 16px 0 0;
            margin-bottom: 0;
        }
        
        .card {
            border: none;
            border-radius: 0 0 16px 16px;
            box-shadow: 0 8px 25px rgba(200, 168, 130, 0.15);
        }
        
        .resultado-box {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 2px solid var(--primary-color);
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
        }
        
        .dato-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: white;
            margin: 8px 0;
            border-radius: 8px;
        }
        
        .dato-row.highlight {
            background: linear-gradient(135deg, #fff9e6, #fff3cd);
            border: 2px solid #ffc107;
        }
        
        .dato-valor {
            color: var(--primary-dark);
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }
        
        .btn-copiar {
            background: #2c3e50;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            margin-left: 10px;
        }
        
        .btn-copiar:hover {
            background: var(--primary-color);
        }
        
        .btn-crear {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            color: white;
            padding: 14px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-crear:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        /* 🔍 Nuevo: Botón de prueba de login */
        .btn-test-login {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            margin-top: 15px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-test-login:hover {
            background: #138496;
            color: white;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="index.php" class="back-link btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Volver al Panel
        </a>
        
        <div class="page-header">
            <h2><i class="bi bi-person-plus-fill"></i> Crear Usuario Manual</h2>
            <p>Para clientes que pagaron por transferencia bancaria</p>
        </div>
        
        <div class="card">
            <div class="card-body p-4">
                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show">
                        <?php echo $mensaje; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($usuario_creado): ?>
                    <div class="resultado-box">
                        <h4><i class="bi bi-check-circle-fill text-success me-2"></i>Credenciales Generadas</h4>
                        
                        <div class="dato-row highlight">
                            <span class="dato-label">👤 Usuario (Slug):</span>
                            <span class="dato-valor" id="slug"><?php echo htmlspecialchars($usuario_creado['slug']); ?></span>
                            <button class="btn-copiar" onclick="copiar('slug', this)">
                                <i class="bi bi-clipboard"></i> Copiar
                            </button>
                        </div>
                        
                        <div class="dato-row highlight">
                            <span class="dato-label">🔑 Contraseña:</span>
                            <span class="dato-valor" id="password"><?php echo htmlspecialchars($usuario_creado['password']); ?></span>
                            <button class="btn-copiar" onclick="copiar('password', this)">
                                <i class="bi bi-clipboard"></i> Copiar
                            </button>
                        </div>
                        
                        <hr>
                        
                        <?php if (!empty($usuario_creado['email'])): ?>
                        <div class="dato-row">
                            <span class="dato-label">Email:</span>
                            <span class="dato-valor"><?php echo htmlspecialchars($usuario_creado['email']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="dato-row">
                            <span class="dato-label">Invitación:</span>
                            <span class="dato-valor"><?php echo htmlspecialchars($usuario_creado['invitacion']); ?></span>
                        </div>
                        
                        <!-- 🔍 Botón para probar login -->
                        <form action="../login.php" method="POST" target="_blank">
                            <input type="hidden" name="slug" value="<?php echo htmlspecialchars($usuario_creado['slug']); ?>">
                            <input type="hidden" name="password" value="<?php echo htmlspecialchars($usuario_creado['password']); ?>">
                            <button type="submit" class="btn-test-login">
                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                Probar Login Automático (Nueva Ventana)
                            </button>
                        </form>
                        
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Instrucciones:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Usuario: <code><?php echo $usuario_creado['slug']; ?></code></li>
                                <li>Contraseña: <code><?php echo $usuario_creado['password']; ?></code></li>
                                <li>Login: <a href="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']; ?>/login.php" target="_blank">Ir al login</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-warning mt-3">
                    <i class="bi bi-lightbulb-fill me-2"></i>
                    <strong>Formato:</strong> Usuario = <code>slug</code> | Contraseña = <code>slug + fecha_evento</code>
                    <br><small>Ejemplo: <code>boda-ana-juan20261231</code></small>
                </div>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Seleccionar Invitación *</label>
                        <select name="invitacion_id" class="form-select" required>
                            <option value="">-- Selecciona --</option>
                            <?php foreach ($invitaciones as $inv): ?>
                                <option value="<?php echo $inv['id']; ?>">
                                    <?php echo htmlspecialchars($inv['nombres_novios']); ?> 
                                    (<?php echo htmlspecialchars($inv['slug']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Nombre *</label>
                            <input type="text" name="nombre_cliente" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellido</label>
                            <input type="text" name="apellido_cliente" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email_cliente" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" name="telefono_cliente" class="form-control">
                        </div>
                    </div>
                    
                    <button type="submit" name="crear_usuario" class="btn-crear">
                        <i class="bi bi-person-plus-fill me-2"></i>
                        Generar Usuario y Contraseña
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copiar(id, btn) {
            const texto = document.getElementById(id).textContent;
            navigator.clipboard.writeText(texto).then(() => {
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check"></i> ¡Copiado!';
                btn.style.background = '#28a745';
                setTimeout(() => {
                    btn.innerHTML = original;
                    btn.style.background = '';
                }, 2000);
            });
        }
    </script>
</body>
</html>
