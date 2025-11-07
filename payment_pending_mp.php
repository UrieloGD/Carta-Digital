<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Pendiente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .payment-pending {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #ffa751 0%, #ffe259 100%);
            padding: 20px;
        }
        .pending-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            margin: 0 auto;
        }
        .pending-content i {
            font-size: 80px;
            color: #ffc107;
            margin-bottom: 20px;
            animation: spin 2s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .pending-content h1 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .pending-content p {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .alert-info {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 30px;
            color: #ff6f00;
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
    <section class="payment-pending">
        <div class="pending-content">
            <i class="bi bi-hourglass-split"></i>
            <h1>Pago Pendiente</h1>
            <p>Tu pago está siendo procesado.</p>
            
            <div class="alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Te notificaremos cuando se confirme.
            </div>
            
            <a href="./" class="btn btn-primary">Volver al inicio</a>
        </div>
    </section>
</body>
</html>
