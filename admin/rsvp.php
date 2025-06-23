<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$invitacion_id = $_GET['id'] ?? 0;

if (!$invitacion_id) {
    header("Location: index.php");
    exit();
}

// Obtener información de la invitación
$invitacion_query = "SELECT nombres_novios FROM invitaciones WHERE id = ?";
$invitacion_stmt = $db->prepare($invitacion_query);
$invitacion_stmt->execute([$invitacion_id]);
$invitacion = $invitacion_stmt->fetch(PDO::FETCH_ASSOC);

// Obtener RSVPs
$rsvp_query = "SELECT * FROM invitacion_rsvp WHERE invitacion_id = ? ORDER BY fecha_respuesta DESC";
$rsvp_stmt = $db->prepare($rsvp_query);
$rsvp_stmt->execute([$invitacion_id]);
$rsvps = $rsvp_stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN asistencia = 'si' THEN 1 ELSE 0 END) as confirmados,
    SUM(CASE WHEN asistencia = 'no' THEN 1 ELSE 0 END) as no_asisten,
    SUM(CASE WHEN asistencia = 'si' THEN acompanantes + 1 ELSE 0 END) as total_personas
    FROM invitacion_rsvp WHERE invitacion_id = ?";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$invitacion_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RSVPs - <?php echo htmlspecialchars($invitacion['nombres_novios']); ?></title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>RSVPs - <?php echo htmlspecialchars($invitacion['nombres_novios']); ?></h1>
            <a href="index.php" class="btn btn-secondary">Volver</a>
        </header>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Respuestas</h3>
                <span class="stat-number"><?php echo $stats['total']; ?></span>
            </div>
            <div class="stat-card">
                <h3>Confirmados</h3>
                <span class="stat-number"><?php echo $stats['confirmados']; ?></span>
            </div>
            <div class="stat-card">
                <h3>No Asisten</h3>
                <span class="stat-number"><?php echo $stats['no_asisten']; ?></span>
            </div>
            <div class="stat-card">
                <h3>Total Personas</h3>
                <span class="stat-number"><?php echo $stats['total_personas']; ?></span>
            </div>
        </div>

        <div class="rsvps-table">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Asistencia</th>
                        <th>Acompañantes</th>
                        <th>Comentario</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rsvps as $rsvp): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rsvp['nombre']); ?></td>
                        <td>
                            <span class="status <?php echo $rsvp['asistencia']; ?>">
                                <?php echo $rsvp['asistencia'] == 'si' ? 'Sí asiste' : 'No asiste'; ?>
                            </span>
                        </td>
                        <td><?php echo $rsvp['acompanantes']; ?></td>
                        <td><?php echo htmlspecialchars($rsvp['comentario']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($rsvp['fecha_respuesta'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>