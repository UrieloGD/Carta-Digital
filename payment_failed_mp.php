<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Rechazado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .payment-failed {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 20px;
        }
        .failed-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            margin: 0 auto;
        }
        .failed-content i {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .failed-content h1 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .failed-content p {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .alert-info {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 30px;
            color: #0066cc;
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
    <section class="payment-failed">
        <div class="failed-content">
            <i class="bi bi-times-circle"></i>
            <h1>Pago Rechazado</h1>
            <p>Lamentablemente tu pago fue rechazado.</p>
            
            <div class="alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Por favor, intenta nuevamente con otra tarjeta o método de pago.
            </div>
            
            <a href="./precios.php" class="btn btn-primary">Intentar de nuevo</a>
        </div>
    </section>
</body>
</html>
