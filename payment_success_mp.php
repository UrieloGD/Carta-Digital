<?php
require_once './config/database.php';

$collection_id = $_GET['collection_id'] ?? null;
$status = $_GET['status'] ?? 'pending';

if (!$collection_id) {
    header('Location: ./');
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar pedido por ID de preferencia MP
    $stmt = $db->prepare("
        SELECT p.*, i.slug, i.nombres_novios, c.email, c.telefono
        FROM pedidos p
        JOIN invitaciones i ON p.invitacion_id = i.id
        JOIN clientes c ON p.cliente_id = c.id
        WHERE p.payment_intent_id = ?
        LIMIT 1
    ");
    $stmt->execute([$collection_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pedido) {
        // Actualizar estado
        $stmt = $db->prepare("UPDATE pedidos SET estado = 'completado', fecha_pago = NOW() WHERE id = ?");
        $stmt->execute([$pedido['id']]);
        
        error_log("✅ Pago completado por MP: Pedido ID=" . $pedido['id']);
    }
    
} catch (Exception $e) {
    error_log("Error en payment_success_mp: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Exitoso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .payment-success {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .success-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            margin: 0 auto;
        }
        .success-content i {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        .success-content h1 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .success-content p {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .order-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
            border-left: 4px solid #28a745;
        }
        .order-info h3 {
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }
        .order-info p {
            margin-bottom: 10px;
            color: #495057;
            font-size: 0.95rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 40px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 6px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #673a94 100%);
            color: white;
        }
    </style>
</head>
<body>
    <section class="payment-success">
        <div class="success-content">
            <i class="bi bi-check-circle"></i>
            <h1>¡Pago Exitoso!</h1>
            <p>Tu invitación ha sido registrada correctamente.</p>
            
            <?php if ($pedido): ?>
                <div class="order-info">
                    <h3>Información de tu Pedido</h3>
                    <p><strong>Novios:</strong> <?php echo htmlspecialchars($pedido['nombres_novios']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['email']); ?></p>
                    <p><strong>Plan:</strong> <?php echo ucfirst($pedido['plan']); ?></p>
                    <p><strong>Monto:</strong> $<?php echo number_format($pedido['monto'], 2); ?> MXN</p>
                </div>
                
                <p>Te contactaremos pronto al número <strong><?php echo htmlspecialchars($pedido['telefono']); ?></strong> para los detalles finales.</p>
            <?php endif; ?>
            
            <a href="./" class="btn btn-primary">Volver al inicio</a>
        </div>
    </section>
</body>
</html>
