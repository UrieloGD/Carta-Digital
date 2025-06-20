<?php
$page_title = 'Invitaciones';
$page_css = 'invitaciones.css';
$page_js = 'invitaciones.js';
include_once 'includes/header.php';
include_once 'includes/sidebar.php';

// Datos de ejemplo
$invitaciones = [
    [
        'id' => 1,
        'pareja' => 'Ana & Carlos',
        'fecha' => '2025-08-15',
        'plantilla' => 'Elegante Rosa',
        'enlace' => 'ana-carlos-2025',
        'estado' => 'activa'
    ],
    [
        'id' => 2,
        'pareja' => 'María Fernanda',
        'fecha' => '2025-09-20',
        'plantilla' => 'Quinceañera Dorada',
        'enlace' => 'maria-xv-2025',
        'estado' => 'borrador'
    ]
];
?>

<main class="admin-content">
    <div class="container">
        <div class="page-header">
            <h2 class="page-title">Gestión de Invitaciones</h2>
            <button class="btn btn-primary" onclick="openInvitationModal()">Nueva invitación</button>
        </div>

        <div class="invitations-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pareja/Quinceañera</th>
                        <th>Fecha del evento</th>
                        <th>Plantilla</th>
                        <th>Enlace</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invitaciones as $invitacion): ?>
                        <tr>
                            <td class="invitation-couple"><?php echo $invitacion['pareja']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($invitacion['fecha'])); ?></td>
                            <td><?php echo $invitacion['plantilla']; ?></td>
                            <td>
                                <a href="../<?php echo $invitacion['enlace']; ?>" 
                                   target="_blank" 
                                   class="invitation-link">
                                    <?php echo $invitacion['enlace']; ?>
                                </a>
                            </td>
                            <td>
                                <span class="status status-<?php echo $invitacion['estado']; ?>">
                                    <?php echo ucfirst($invitacion['estado']); ?>
                                </span>
                            </td>
                            <td class="actions">
                                <button class="btn-icon" onclick="viewInvitation(<?php echo $invitacion['id']; ?>)" title="Ver">
                                    👁️
                                </button>
                                <button class="btn-icon" onclick="editInvitation(<?php echo $invitacion['id']; ?>)" title="Editar">
                                    ✏️
                                </button>
                                <button class="btn-icon btn-danger" onclick="deleteInvitation(<?php echo $invitacion['id']; ?>)" title="Eliminar">
                                    🗑️
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal para crear/editar invitación -->
<div id="invitationModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3 id="invitationModalTitle">Nueva Invitación</h3>
            <button class="modal-close" onclick="closeInvitationModal()">&times;</button>
        </div>
        <form id="invitationForm" class="modal-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="eventType">Tipo de evento</label>
                    <select id="eventType" name="tipo_evento" required>
                        <option value="">Seleccionar tipo</option>
                        <option value="boda">Boda</option>
                        <option value="xv">XV Años</option>
                        <option value="bautizo">Bautizo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="invitationTemplate">Plantilla</label>
                    <select id="invitationTemplate" name="plantilla" required>
                        <option value="">Seleccionar plantilla</option>
                        <option value="1">Elegante Rosa</option>
                        <option value="2">Quinceañera Dorada</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="coupleName">Nombres (Pareja/Quinceañera)</label>
                    <input type="text" id="coupleName" name="pareja" required>
                </div>
                
                <div class="form-group">
                    <label for="eventDate">Fecha del evento</label>
                    <input type="datetime-local" id="eventDate" name="fecha" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="eventLocation">Lugar del evento</label>
                    <input type="text" id="eventLocation" name="lugar" required>
                </div>
                
                <div class="form-group">
                    <label for="invitationSlug">Enlace personalizado</label>
                    <input type="text" id="invitationSlug" name="enlace" placeholder="mi-evento-2025">
                </div>
            </div>
            
            <div class="form-group">
                <label for="eventMessage">Mensaje personalizado</label>
                <textarea id="eventMessage" name="mensaje" rows="3"></textarea>
            </div>
        </form>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeInvitationModal()">Cancelar</button>
            <button type="submit" form="invitationForm" class="btn btn-primary">Guardar</button>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>