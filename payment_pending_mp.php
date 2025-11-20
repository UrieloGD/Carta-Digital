<?php
// Incluir header para cargar estilos globales
require_once './includes/header.php';

// Obtener parámetros opcionales por si quieres mostrar algo dinámico
$collection_id = $_GET['collection_id'] ?? null;
?>

<!-- Vincular CSS Específico (Reutilizamos el mismo CSS de success/error) -->
<link rel="stylesheet" href="./css/payment_success.css?v=<?php echo filemtime('./css/payment_success.css'); ?>" />

<section class="payment-success-section">
    <div class="container success-container">
        
        <!-- Reutilizamos la clase .error-box pero con un tono de advertencia si quisieras personalizar más -->
        <div class="error-box" style="border-top: 5px solid #c8a882;"> <!-- Pequeño override inline para color amarillo -->
            
            <div class="error-icon" style="background: #fff3cd;">
                <!-- Icono de reloj/espera -->
                <i class="fas fa-hourglass-half" style="color: #c8a882;"></i>
            </div>
            
            <h1>Pago en Proceso</h1>
            
            <p class="error-message">
                Estamos verificando tu pago. Esto puede tardar unos minutos o hasta 24 horas dependiendo del método elegido (OXXO, Transferencia, etc).
            </p>
            
            <!-- Bloque de información adicional -->
            <div class="email-confirmation" style="background: #fff3cd; border-left-color: #c8a882;">
                <p style="color: #856404;">
                    <i class="fas fa-info-circle" style="color: #856404;"></i>
                    &nbsp; No te preocupes, te enviaremos un correo en cuanto se confirme.
                </p>
            </div>
            
            <div class="error-actions">
                <p>Si ya realizaste el pago en ventanilla, conserva tu comprobante.</p>
                
                <div class="action-buttons">
                    <a href="./" class="btn btn-primary">Volver al Inicio</a>
                    <a href="https://wa.me/523339047672" class="btn btn-secondary">
                        <i class="fab fa-whatsapp"></i> Soporte
                    </a>
                </div>
            </div>
            
        </div>
        
    </div>
</section>

<?php include './includes/footer.php'; ?>
