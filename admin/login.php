<?php
require_once '../config/database.php';

session_start();

$error = '';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: ./index.php');
    exit;
}

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor ingresa tu email y contraseña.';
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Buscar usuario admin por email
            $stmt = $conn->prepare("
                SELECT id, nombre, email, password, rol 
                FROM usuarios_admin 
                WHERE email = ? AND activo = 1
            ");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($password, $usuario['password'])) {
                // Login exitoso - crear sesión
                $_SESSION['admin_id'] = $usuario['id'];
                $_SESSION['admin_nombre'] = $usuario['nombre'];
                $_SESSION['admin_email'] = $usuario['email'];
                $_SESSION['admin_rol'] = $usuario['rol'];
                $_SESSION['ultimo_acceso'] = time();
                
                // Actualizar último login
                $stmt = $conn->prepare("UPDATE usuarios_admin SET ultimo_login = NOW() WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                
                error_log("✅ Login admin exitoso: Email={$email}, ID={$usuario['id']}");
                header('Location: ./index.php');
                exit;
            } else {
                $error = 'Email o contraseña incorrectos.';
                error_log("❌ Login admin fallido: Email={$email}");
            }
            
        } catch (Exception $e) {
            error_log("Error en admin login: " . $e->getMessage());
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
    <title>Iniciar Sesión - Panel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="shortcut icon" href="../images/logo.webp" />
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
        .login-header p {
            margin: 0.5rem 0 0 0;
            font-size: 0.95rem;
            opacity: 0.95;
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
            text-decoration: none;
        }
        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
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
        .form-label {
            color: #2c3e50;
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .admin-badge {
            display: inline-block;
            background: #c8a882;
            color: white;
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
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
                            <h2 class="mb-0">🔐 Panel Administrativo</h2>
                            <p class="mb-0">Carta Digital</p>
                            <span class="admin-badge">Acceso Restringido</span>
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
                                    <label for="email" class="form-label fw-bold">
                                        <i class="fas fa-envelope me-2" style="color: #c8a882;"></i>
                                        Email
                                    </label>
                                    <input type="email" 
                                           class="form-control form-control-lg" 
                                           id="email" 
                                           name="email" 
                                           placeholder="admin@cartadigital.com"
                                           required
                                           autocomplete="email"
                                           autofocus>
                                    <div class="form-text">
                                        <small>Tu correo de administrador</small>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label fw-bold">
                                        <i class="fas fa-lock me-2" style="color: #c8a882;"></i>
                                        Contraseña
                                    </label>
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Tu contraseña"
                                           required
                                           autocomplete="current-password">
                                    <div class="form-text">
                                        <small>Contraseña segura con caracteres especiales</small>
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-login btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Ingresar al Panel
                                    </button>
                                </div>
                            </form>
                            
                            <div class="help-text">
                                <strong><i class="fas fa-info-circle me-2"></i>Nota de Seguridad</strong><br>
                                Este panel es exclusivo para administradores. Solo ingresa si tienes credenciales autorizadas.
                            </div>
                            
                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    <a href="../" style="color: #c8a882; font-weight: 500; text-decoration: none;">
                                        <i class="fas fa-arrow-left me-1"></i>Volver al inicio
                                    </a>
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
