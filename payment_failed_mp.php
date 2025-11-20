<?php
require_once './includes/header.php';

// Obtener parámetros opcionales (si MP envía razón del error)
$error_status = $_GET['status'] ?? null;
?>

<!-- Vincular CSS Específico -->
<link rel="stylesheet" href="./css/payment_success.css?v=<?php echo filemtime('./css/payment_success.css'); ?>" />

<section class="payment-success-section">
    <div class="container success-container">
        
        <!-- Usamos la clase .error-box que ya tiene estilos rojos por defecto -->
        <div class="error-box">
            <div class="error-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            
            <h1>Pago Rechazado</h1>
            <p class="error-message">
                Lo sentimos, tu pago no pudo ser procesado correctamente.
            </p>
            
            <!-- Bloque de información / sugerencia -->
            <div class="email-confirmation" style="background: #f8d7da; border-left-color: #dc3545;">
                <p style="color: #721c24;">
                    <i class="fas fa-info-circle" style="color: #721c24;"></i>
                    &nbsp; Posible causa: Fondos insuficientes o bloqueo de seguridad del banco.
                </p>
            </div>
            
            <div class="error-actions">
                <p>No te preocupes, no se ha realizado ningún cargo a tu tarjeta.</p>
                
                <div class="action-buttons">
                    <!-- Botón principal: Intentar de nuevo -->
                    <a href="./precios.php" class="btn btn-primary">Intentar de Nuevo</a>
                    
                    <!-- Botón secundario: Inicio -->
                    <a href="./" class="btn btn-secondary">Volver al Inicio</a>
                </div>
            </div>
            
            <!-- Opción de contacto sutil -->
            <div style="margin-top: 2rem; font-size: 0.9rem; color: #6c757d;">
                ¿Problemas persistentes? <a href="https://wa.me/523339047672" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">Contáctanos</a>
            </div>
        </div>
        
    </div>
</section>

<?php include './includes/footer.php'; ?>
