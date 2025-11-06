<?php
session_start();
require_once 'config/database.php';

$error = '';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['cliente_logueado'])) {
    header('Location: dashboard.php');
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = trim($_POST['slug']);
    $password = $_POST['password'];
    
    if (empty($slug) || empty($password)) {
        $error = 'Por favor ingresa tu código de acceso y contraseña.';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Buscar cliente por slug
            $stmt = $conn->prepare("SELECT id, slug, nombre, apellido, email, password FROM clientes WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            $cliente = $stmt->fetch();
            
            if ($cliente && password_verify($password, $cliente['password'])) {
                // Login exitoso - crear sesión
                $_SESSION['cliente_logueado'] = true;
                $_SESSION['cliente_id'] = $cliente['id'];
                $_SESSION['cliente_slug'] = $cliente['slug'];
                $_SESSION['cliente_nombre'] = $cliente['nombre'];
                $_SESSION['cliente_apellido'] = $cliente['apellido'];
                $_SESSION['cliente_email'] = $cliente['email'];
                $_SESSION['login_time'] = time();
                
                error_log("✅ Login exitoso: Slug={$slug}, ID={$cliente['id']}");
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Código de acceso o contraseña incorrectos.';
                error_log("❌ Login fallido: Slug={$slug}");
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
    <title>Iniciar Sesión - Carta Digital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="./images/logo.webp" />
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #c8a882 0%, #b8956b 100%);
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(45deg, #c8a882, #b8956b);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header h2 {
            margin-bottom: 0;
            font-weight: 300;
        }
        .login-body {
            padding: 2rem;
        }
        .btn-login {
            background: linear-gradient(45deg, #c8a882, #b8956b);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        .form-control:focus {
            border-color: #c8a882;
            box-shadow: 0 0 0 0.2rem rgba(200, 168, 130, 0.25);
        }
        .help-text {
            background: #f8f9fa;
            border-left: 3px solid #c8a882;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 15px;
            font-size: 13px;
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
                            <h2 class="mb-0">Carta Digital</h2>
                            <p class="mb-0 mt-2">Accede a tu invitación</p>
                        </div>
                        <div class="login-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="slug" class="form-label fw-bold">Código de Acceso</label>
                                    <input type="text" 
                                           class="form-control form-control-lg" 
                                           id="slug" 
                                           name="slug" 
                                           placeholder="Ej: maria-juan"
                                           required
                                           autocomplete="username">
                                    <div class="form-text">
                                        <small>Lo recibiste en tu correo de confirmación</small>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label fw-bold">Contraseña</label>
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Contraseña"
                                           required
                                           autocomplete="current-password">
                                    <div class="form-text">
                                        <small>Usa la contraseña que recibiste por correo</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-login">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Acceder
                                    </button>
                                </div>
                            </form>
                            
                            <div class="help-text">
                                <strong>💡 ¿No encuentras tus credenciales?</strong><br>
                                Revisa tu correo electrónico, ahí están tu código de acceso y contraseña.
                            </div>
                            
                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    ¿No tienes cuenta? 
                                    <a href="plantillas.php" style="color: #c8a882; font-weight: 500;">Compra una invitación</a>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
