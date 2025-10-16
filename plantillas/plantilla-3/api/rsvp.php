<?php
// Log básico
error_log("=== RSVP: Script iniciado ===");

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    error_log("RSVP: Datos POST: " . print_r($_POST, true));
    
    $action = $_POST['action'] ?? $_GET['action'] ?? 'no_action';
    error_log("RSVP: Action: " . $action);
    
    // Incluir database
    $database_path = './../../../config/database.php';
    if (!file_exists($database_path)) {
        throw new Exception("Archivo database.php no encontrado");
    }
    
    require_once $database_path;
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo instanceof PDO) {
        throw new Exception("Error: pdo no es una instancia válida de PDO");
    }
    
    // Procesar acciones
    switch($action) {
        case 'validar_codigo':
            error_log("RSVP: Ejecutando validar_codigo");
            validarCodigo($pdo);
            break;
        case 'guardar_rsvp':
            error_log("RSVP: Ejecutando guardar_rsvp");
            guardarRSVP($pdo);
            break;
        case 'cargar_respuesta':
            error_log("RSVP: Ejecutando cargar_respuesta");
            cargarRespuesta($pdo);
            break;
        default:
            $response = [
                'success' => true,
                'message' => 'Servidor y BD funcionando',
                'action' => $action,
                'timestamp' => date('Y-m-d H:i:s'),
                'database_connected' => true,
                'environment' => $database->getCurrentEnvironment()
            ];
            echo json_encode($response);
    }
    
} catch (Exception $e) {
    error_log("RSVP: Exception: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'action' => $action ?? 'unknown'
        ]
    ]);
} catch (Error $e) {
    error_log("RSVP: Fatal Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error fatal: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function validarCodigo($pdo) {
    error_log("validarCodigo: Iniciando función");
    
    $codigo = strtoupper(trim($_POST['codigo_grupo'] ?? ''));
    $slug = $_POST['slug'] ?? '';
    
    error_log("validarCodigo: codigo='$codigo', slug='$slug'");
    
    if (empty($codigo)) {
        throw new Exception('El código de invitación es requerido');
    }
    
    if (empty($slug)) {
        throw new Exception('Slug de invitación no encontrado');
    }
    
    try {
        // Buscar el grupo
        $stmt = $pdo->prepare("
            SELECT g.*, i.fecha_evento 
            FROM invitados_grupos g
            JOIN invitaciones i ON g.slug_invitacion = i.slug
            WHERE g.token_unico = ? AND g.slug_invitacion = ?
        ");
        
        $stmt->execute([$codigo, $slug]);
        $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$grupo) {
            throw new Exception('Código de invitación no válido para esta celebración');
        }
        
        // Verificar si ya hay una respuesta
        $stmt = $pdo->prepare("SELECT * FROM rsvp_respuestas WHERE id_grupo = ?");
        $stmt->execute([$grupo['id_grupo']]);
        $respuesta_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar si aún puede editar (30 días antes del evento)
        $fecha_evento = new DateTime($grupo['fecha_evento']);
        $fecha_limite = clone $fecha_evento;
        $fecha_limite->modify('-30 days');
        $puede_editar = new DateTime() <= $fecha_limite;
        
        $response = [
            'success' => true,
            'grupo' => [
                'id_grupo' => $grupo['id_grupo'],
                'nombre_grupo' => $grupo['nombre_grupo'],
                'boletos_asignados' => (int)$grupo['boletos_asignados']
            ],
            'respuesta_existente' => $respuesta_existente ? $respuesta_existente : null,
            'puede_editar' => $puede_editar,
            'fecha_limite' => $fecha_limite->format('d/m/Y')
        ];
        
        error_log("validarCodigo: Enviando respuesta exitosa");
        echo json_encode($response);
        
    } catch (PDOException $e) {
        error_log("validarCodigo: Error PDO: " . $e->getMessage());
        throw new Exception("Error de base de datos: " . $e->getMessage());
    }
}

function guardarRSVP($pdo) {
    error_log("guardarRSVP: Iniciando función");
    
    $id_grupo = $_POST['id_grupo'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $boletos_confirmados = intval($_POST['boletos_confirmados'] ?? 0);
    $comentarios = trim($_POST['comentarios'] ?? '');
    
    error_log("guardarRSVP: Datos recibidos - id_grupo=$id_grupo, estado=$estado, boletos_confirmados=$boletos_confirmados");
    
    if (empty($id_grupo) || empty($estado)) {
        throw new Exception('Todos los campos requeridos deben estar completos');
    }
    
    // Recoger nombres de todos los invitados (ya no hay principal)
    $nombres_invitados = [];
    if ($estado === 'aceptado' && $boletos_confirmados > 0) {
        for ($i = 1; $i <= $boletos_confirmados; $i++) {
            $nombre = trim($_POST["nombre_invitado_{$i}"] ?? '');
            if (!empty($nombre)) {
                $nombres_invitados[] = $nombre;
            }
        }
        
        // Validar que se ingresaron todos los nombres requeridos
        if (count($nombres_invitados) < $boletos_confirmados) {
            throw new Exception('Debe ingresar el nombre completo de todos los invitados');
        }
    }
    
    $nombres_invitados_str = implode('; ', $nombres_invitados);
    
    error_log("guardarRSVP: Nombres invitados: " . $nombres_invitados_str);
    
    // Verificar si el grupo existe y obtener boletos asignados
    $stmt = $pdo->prepare("SELECT boletos_asignados FROM invitados_grupos WHERE id_grupo = ?");
    $stmt->execute([$id_grupo]);
    $grupo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$grupo) {
        throw new Exception('Grupo no encontrado');
    }
    
    if ($estado === 'aceptado' && $boletos_confirmados > $grupo['boletos_asignados']) {
        throw new Exception('No puedes confirmar más boletos de los asignados');
    }
    
    if ($estado === 'rechazado') {
        $boletos_confirmados = 0;
        $nombres_invitados_str = '';
    }
    
    // Verificar si ya existe una respuesta
    $stmt = $pdo->prepare("SELECT id_respuesta FROM rsvp_respuestas WHERE id_grupo = ?");
    $stmt->execute([$id_grupo]);
    $respuesta_existente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    try {
        if ($respuesta_existente) {
            // Actualizar respuesta existente
            error_log("guardarRSVP: Actualizando respuesta existente");
            $stmt = $pdo->prepare("
                UPDATE rsvp_respuestas 
                SET estado = ?, boletos_confirmados = ?, 
                    nombres_acompanantes = ?, comentarios = ?, fecha_respuesta = NOW()
                WHERE id_grupo = ?
            ");
            $stmt->execute([
                $estado, $boletos_confirmados, 
                $nombres_invitados_str, $comentarios, $id_grupo
            ]);
            $mensaje = 'Respuesta actualizada correctamente';
        } else {
            // Crear nueva respuesta
            error_log("guardarRSVP: Creando nueva respuesta");
            $stmt = $pdo->prepare("
                INSERT INTO rsvp_respuestas 
                (id_grupo, estado, boletos_confirmados, 
                 nombres_acompanantes, comentarios, fecha_respuesta)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $id_grupo, $estado, $boletos_confirmados, 
                $nombres_invitados_str, $comentarios
            ]);
            $mensaje = 'Confirmación enviada correctamente';
        }
        
        error_log("guardarRSVP: Operación exitosa");
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'data' => [
                'estado' => $estado,
                'boletos_confirmados' => $boletos_confirmados,
                'nombres_invitados' => $nombres_invitados,
                'comentarios' => $comentarios
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("guardarRSVP: Error PDO: " . $e->getMessage());
        throw new Exception("Error al guardar en la base de datos: " . $e->getMessage());
    }
}

function cargarRespuesta($pdo) {
    error_log("cargarRespuesta: Iniciando función");
    
    $id_grupo = $_GET['id_grupo'] ?? $_POST['id_grupo'] ?? '';
    
    error_log("cargarRespuesta: id_grupo=$id_grupo");
    
    if (empty($id_grupo)) {
        throw new Exception('ID de grupo requerido');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, g.nombre_grupo, g.boletos_asignados
            FROM rsvp_respuestas r
            JOIN invitados_grupos g ON r.id_grupo = g.id_grupo
            WHERE r.id_grupo = ?
        ");
        $stmt->execute([$id_grupo]);
        $respuesta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$respuesta) {
            throw new Exception('Respuesta no encontrada');
        }
        
        // Separar nombres de invitados
        $nombres_invitados = [];
        if (!empty($respuesta['nombres_acompanantes'])) {
            $nombres_invitados = explode('; ', $respuesta['nombres_acompanantes']);
        }
        
        error_log("cargarRespuesta: Respuesta encontrada");
        echo json_encode([
            'success' => true,
            'respuesta' => $respuesta,
            'nombres_invitados' => $nombres_invitados
        ]);
        
    } catch (PDOException $e) {
        error_log("cargarRespuesta: Error PDO: " . $e->getMessage());
        throw new Exception("Error al cargar respuesta: " . $e->getMessage());
    }
}

error_log("=== RSVP: Script terminado ===");
?>