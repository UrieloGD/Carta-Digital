<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['cliente_logueado'])) {
    header('Location: dashboard_cliente.php');
    exit;
}

// Procesar login
if ($_POST && isset($_POST['slug'])) {
    $slug = trim($_POST['slug']);
    
    if (empty($slug)) {
        $error = 'Por favor ingresa el código de acceso de tu invitación';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Buscar el cliente por slug (usando PDO)
            $stmt = $conn->prepare("SELECT id, slug, usuario, email FROM clientes_login WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            $cliente = $stmt->fetch();
            
            if ($cliente) {
                // Crear sesión
                $_SESSION['cliente_logueado'] = true;
                $_SESSION['cliente_id'] = $cliente['id'];
                $_SESSION['cliente_slug'] = $cliente['slug'];
                $_SESSION['cliente_usuario'] = $cliente['usuario'];
                $_SESSION['cliente_email'] = $cliente['email'];
                $_SESSION['login_time'] = time();
                
                header('Location: dashboard_cliente.php');
                exit;
            } else {
                $error = 'Código de acceso no válido. Verifica que sea correcto.';
            }
            
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            $error = 'Error interno del sistema. Inténtalo de nuevo.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso - Carta Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="./images/logo.webp" />
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="login-card">
                        <div class="login-header">
                            <h2 class="mb-0">🎊 Carta Digital</h2>
                            <p class="mb-0 mt-2">Accede a tu invitación</p>
                        </div>
                        <div class="login-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo htmlspecialchars($success); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label for="slug" class="form-label fw-bold">Código de Acceso</label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="slug" 
                                           name="slug" 
                                           placeholder="Ejemplo: maria-juan-2025"
                                           value="<?php echo isset($_POST['slug']) ? htmlspecialchars($_POST['slug']) : ''; ?>"
                                           required>
                                    <div class="form-text">
                                        <small>Ingresa el código que recibiste para acceder a tu invitación</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-login">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Acceder a mi Invitación
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    ¿No tienes tu código? 
                                    <a href="contacto.php" class="text-decoration-none">Contáctanos</a>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
</body>
</html>