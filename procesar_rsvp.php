<?php
require_once 'config/database.php';

// Crear tabla RSVP si no existe
$database = new Database();
$db = $database->getConnection();

$create_table = "CREATE TABLE IF NOT EXISTS invitacion_rsvp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitacion_id INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    asistencia ENUM('si', 'no') NOT NULL,
    acompanantes INT DEFAULT 0,
    comentario TEXT,
    fecha_respuesta DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitacion_id) REFERENCES invitaciones(id) ON DELETE CASCADE
)";

$db->exec($create_table);

if ($_POST) {
    header('Content-Type: application/json');
    
    try {
        $stmt = $db->prepare("INSERT INTO invitacion_rsvp (invitacion_id, nombre, asistencia, acompanantes, comentario) VALUES (?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $_POST['invitacion_id'],
            $_POST['nombre'],
            $_POST['asistencia'],
            $_POST['acompanantes'] ?? 0,
            $_POST['comentario'] ?? ''
        ]);
        
        echo json_encode(['success' => true, 'message' => 'RSVP registrado correctamente']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al registrar RSVP: ' . $e->getMessage()]);
    }
}
?>