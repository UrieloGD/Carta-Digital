<?php
error_log("=== RSVP: Script iniciado ===");

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    error_log("RSVP: Datos POST: " . print_r($_POST, true));

    $action = $_POST['action'] ?? $_GET['action'] ?? 'no_action';

    // Normalizar action — acepta con y sin guiones bajos
    $action = str_replace('_', '', strtolower($action));
    // Ahora: 'validarcodigo', 'guardarrsvp', 'cargarrespuesta'

    error_log("RSVP: Action normalizada: " . $action);

    $database_path = './../../../config/database.php';
    if (!file_exists($database_path)) {
        throw new Exception("Archivo database.php no encontrado en: " . realpath('./../../../'));
    }

    require_once $database_path;
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo instanceof PDO) {
        throw new Exception("Error: pdo no es una instancia válida de PDO");
    }

    switch ($action) {
        case 'validarcodigo':
            error_log("RSVP: Ejecutando validarCodigo");
            validarCodigo($pdo);
            break;
        case 'guardarrsvp':
            error_log("RSVP: Ejecutando guardarRSVP");
            guardarRSVP($pdo);
            break;
        case 'cargarrespuesta':
            error_log("RSVP: Ejecutando cargarRespuesta");
            cargarRespuesta($pdo);
            break;
        default:
            echo json_encode([
                'success'            => true,
                'message'            => 'Servidor y BD funcionando',
                'action_recibida'    => $_POST['action'] ?? $_GET['action'] ?? 'ninguna',
                'action_normalizada' => $action,
                'timestamp'          => date('Y-m-d H:i:s'),
                'database_connected' => true,
            ]);
    }

} catch (Exception $e) {
    error_log("RSVP: Exception: " . $e->getMessage());
    echo json_encode([
        'success'   => false,
        'message'   => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
        'debug'     => [
            'file'   => $e->getFile(),
            'line'   => $e->getLine(),
            'action' => $action ?? 'unknown',
        ],
    ]);
} catch (Error $e) {
    error_log("RSVP: Fatal Error: " . $e->getMessage());
    echo json_encode([
        'success'   => false,
        'message'   => 'Error fatal: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
}


/* ================================================
   FUNCIÓN: validarCodigo
   Acepta campo: 'codigogrupo' o 'codigo_grupo'
   ================================================ */
function validarCodigo($pdo) {
    error_log("validarCodigo: Iniciando función");

    // Acepta ambas variantes del campo
    $codigo = strtoupper(trim(
        $_POST['codigogrupo'] ?? $_POST['codigo_grupo'] ?? ''
    ));
    $slug = trim($_POST['slug'] ?? '');

    error_log("validarCodigo: codigo='$codigo', slug='$slug'");

    if (empty($codigo)) {
        throw new Exception('El código de invitación es requerido');
    }
    if (empty($slug)) {
        throw new Exception('Slug de invitación no encontrado');
    }

    try {
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

        // Verificar respuesta existente
        $stmt = $pdo->prepare("SELECT * FROM rsvp_respuestas WHERE id_grupo = ?");
        $stmt->execute([$grupo['id_grupo']]);
        $respuesta_existente = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Calcular si puede editar (30 días antes del evento)
        $fecha_evento = new DateTime($grupo['fecha_evento']);
        $fecha_limite = clone $fecha_evento;
        $fecha_limite->modify('-30 days');
        $puede_editar = new DateTime() <= $fecha_limite;

        error_log("validarCodigo: Grupo encontrado: " . $grupo['nombre_grupo']);

        echo json_encode([
            'success' => true,
            'grupo'   => [
                'id_grupo'         => $grupo['id_grupo'],
                // El JS usa .nombregrupo (sin guion bajo) y .idgrupo
                'idgrupo'          => $grupo['id_grupo'],
                'nombre_grupo'     => $grupo['nombre_grupo'],
                'nombregrupo'      => $grupo['nombre_grupo'],
                'boletos_asignados'=> (int)$grupo['boletos_asignados'],
                'boletosasignados' => (int)$grupo['boletos_asignados'],
            ],
            'respuesta_existente' => $respuesta_existente,
            'respuestaexistente'  => $respuesta_existente, // alias para el JS
            'puede_editar'        => $puede_editar,
            'fecha_limite'        => $fecha_limite->format('d/m/Y'),
        ]);

    } catch (PDOException $e) {
        error_log("validarCodigo: Error PDO: " . $e->getMessage());
        throw new Exception("Error de base de datos: " . $e->getMessage());
    }
}


/* ================================================
   FUNCIÓN: guardarRSVP
   Acepta campos: 'idgrupo' o 'id_grupo', etc.
   ================================================ */
function guardarRSVP($pdo) {
    error_log("guardarRSVP: Iniciando función");

    // Acepta ambas variantes de cada campo
    $id_grupo            = trim($_POST['idgrupo']            ?? $_POST['id_grupo']             ?? '');
    $estado              = trim($_POST['estado']             ?? '');
    $boletos_confirmados = intval($_POST['boletosconfirmados'] ?? $_POST['boletos_confirmados'] ?? 0);
    $comentarios         = trim($_POST['comentarios']        ?? '');

    error_log("guardarRSVP: id_grupo=$id_grupo, estado=$estado, boletos=$boletos_confirmados");

    if (empty($id_grupo) || empty($estado)) {
        throw new Exception('Todos los campos requeridos deben estar completos');
    }

    // Recoger nombres — acepta nombreinvitado1 o nombre_invitado_1
    $nombres_invitados = [];
    if ($estado === 'aceptado' && $boletos_confirmados > 0) {
        for ($i = 1; $i <= $boletos_confirmados; $i++) {
            $nombre = trim(
                $_POST["nombreinvitado{$i}"]   ??
                $_POST["nombre_invitado_{$i}"] ??
                ''
            );
            if (!empty($nombre)) {
                $nombres_invitados[] = $nombre;
            }
        }

        if (count($nombres_invitados) < $boletos_confirmados) {
            throw new Exception('Debe ingresar el nombre completo de todos los invitados');
        }
    }

    $nombres_str = implode('; ', $nombres_invitados);
    error_log("guardarRSVP: Nombres: $nombres_str");

    // Verificar grupo y boletos asignados
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
        $nombres_str = '';
    }

    // ¿Ya existe respuesta?
    $stmt = $pdo->prepare("SELECT id_respuesta FROM rsvp_respuestas WHERE id_grupo = ?");
    $stmt->execute([$id_grupo]);
    $respuesta_existente = $stmt->fetch(PDO::FETCH_ASSOC);

    try {
        if ($respuesta_existente) {
            error_log("guardarRSVP: Actualizando respuesta existente");
            $stmt = $pdo->prepare("
                UPDATE rsvp_respuestas
                SET estado = ?, boletos_confirmados = ?,
                    nombres_acompanantes = ?, comentarios = ?, fecha_respuesta = NOW()
                WHERE id_grupo = ?
            ");
            $stmt->execute([$estado, $boletos_confirmados, $nombres_str, $comentarios, $id_grupo]);
            $mensaje = 'Respuesta actualizada correctamente';
        } else {
            error_log("guardarRSVP: Creando nueva respuesta");
            $stmt = $pdo->prepare("
                INSERT INTO rsvp_respuestas
                    (id_grupo, estado, boletos_confirmados, nombres_acompanantes, comentarios, fecha_respuesta)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$id_grupo, $estado, $boletos_confirmados, $nombres_str, $comentarios]);
            $mensaje = 'Confirmación enviada correctamente';
        }

        error_log("guardarRSVP: Operación exitosa");
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'data'    => [
                'estado'             => $estado,
                'boletos_confirmados'=> $boletos_confirmados,
                'boletosconfirmados' => $boletos_confirmados, // alias JS
                'nombres_invitados'  => $nombres_invitados,
                'nombresinvitados'   => $nombres_invitados,  // alias JS
                'comentarios'        => $comentarios,
            ],
        ]);

    } catch (PDOException $e) {
        error_log("guardarRSVP: Error PDO: " . $e->getMessage());
        throw new Exception("Error al guardar en la base de datos: " . $e->getMessage());
    }
}


/* ================================================
   FUNCIÓN: cargarRespuesta
   ================================================ */
function cargarRespuesta($pdo) {
    error_log("cargarRespuesta: Iniciando función");

    // Acepta GET o POST, con o sin guion bajo
    $id_grupo = trim(
        $_GET['idgrupo']   ?? $_GET['id_grupo']   ??
        $_POST['idgrupo']  ?? $_POST['id_grupo']  ??
        ''
    );

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

        // Separar nombres
        $nombres_invitados = [];
        if (!empty($respuesta['nombres_acompanantes'])) {
            $nombres_invitados = array_map('trim', explode(';', $respuesta['nombres_acompanantes']));
        }

        error_log("cargarRespuesta: Respuesta encontrada");

        echo json_encode([
            'success'          => true,
            'respuesta'        => $respuesta,
            'nombres_invitados'=> $nombres_invitados,
            'nombresinvitados' => $nombres_invitados, // alias JS
        ]);

    } catch (PDOException $e) {
        error_log("cargarRespuesta: Error PDO: " . $e->getMessage());
        throw new Exception("Error al cargar respuesta: " . $e->getMessage());
    }
}

error_log("=== RSVP: Script terminado ===");
?>