<?php 
require_once './includes/header.php';
require_once './config/database.php';

// Obtener código de error
$error_type = isset($_GET['error']) ? $_GET['error'] : 'general';
$error_message = isset($_GET['message']) ? $_GET['message'] : 'Ocurrió un error durante el procesamiento';
$pedido_id = isset($_GET['pedido_id']) ? (int)$_GET['pedido_id'] : null;

// Mapeo de errores
$error_details = [
    'card_declined' => [
        'title' => 'Tarjeta Rechazada',
        'message' => 'Tu tarjeta fue rechazada. Por favor, verifica los datos o intenta con otro medio de pago.',
        'suggestion' => 'Contáctate con tu banco si el problema persiste.'
    ],
    'expired_card' => [
        'title' => 'Tarjeta Vencida',
        'message' => 'Tu tarjeta ha expirado. Por favor, usa otra tarjeta.',
        'suggestion' => 'Intenta con una tarjeta vigente.'
    ],
    'incorrect_cvc' => [
        'title' => 'CVC Incorrecto',
        'message' => 'El código de seguridad (CVC) es incorrecto.',
        'suggestion' => 'Verifica el código en la parte posterior de tu tarjeta.'
    ],
    'processing_error' => [
        'title' => 'Error de Procesamiento',
        'message' => 'Hubo un error al procesar tu pago.',
        'suggestion' => 'Por favor, intenta más tarde.'
    ],
    'network_error' => [
        'title' => 'Error de Conexión',
        'message' => 'No pudimos conectar con el servidor de pagos.',
        'suggestion' => 'Verifica tu conexión a internet e intenta de nuevo.'
    ],
    'general' => [
        'title' => 'Error General',
        'message' => $error_message,
        'suggestion' => 'Por favor, intenta de nuevo.'
    ]
];

$details = $error_details[$error_type] ?? $error_details['general'];
?>

<link rel="stylesheet" href="./css/payment_error.css?v=<?php echo filemtime('./css/payment_error.css'); ?>" />

<section class="payment-error-section">
    <div class="container">
        <div class="error-container">
            
            <div class="error-box">
                <div class="error-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                
                <h1><?php echo $details['title']; ?></h1>
                <p class="error-message"><?php echo $details['message']; ?></p>
                
                <div class="error-details">
                    <p class="suggestion">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Sugerencia:</strong> <?php echo $details['suggestion']; ?>
                    </p>
                </div>
                
                <!-- Opciones de Recuperación -->
                <div class="recovery-options">
                    <h3>¿Qué puedo hacer?</h3>
                    <ul>
                        <li>
                            <strong>Intenta de nuevo:</strong> 
                            <?php if ($pedido_id): ?>
                                <a href="./checkout.php?pedido_id=<?php echo $pedido_id; ?>">Volver al checkout</a>
                            <?php else: ?>
                                <a href="./plantillas.php">Volver a plantillas</a>
                            <?php endif; ?>
                        </li>
                        <li>
                            <strong>Usa otro método:</strong> 
                            <a href="./contacto.php">Contacta para SPEI</a>
                        </li>
                        <li>
                            <strong>¿Tienes preguntas?</strong> 
                            <a href="./contacto.php">Escribenos</a>
                        </li>
                    </ul>
                </div>
                
                <!-- Información de Soporte -->
                <div class="support-info">
                    <h4>Necesitas Ayuda?</h4>
                    <p>Si el problema persiste, no dudes en contactarnos:</p>
                    <div class="contact-options">
                        <a href="https://wa.me/525512345678" target="_blank" class="contact-option">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp</span>
                        </a>
                        <a href="mailto:info@cartadigital.com" class="contact-option">
                            <i class="fas fa-envelope"></i>
                            <span>Email</span>
                        </a>
                    </div>
                </div>
                
                <!-- Botones de Acción -->
                <div class="action-buttons">
                    <a href="./plantillas.php" class="btn btn-primary">Volver a Plantillas</a>
                    <a href="./index.php" class="btn btn-secondary">Ir a Inicio</a>
                </div>
            </div>
            
        </div>
    </div>
</section>

<?php include './includes/footer.php'; ?>
