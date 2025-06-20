<?php

include_once 'includes/header.php';
include_once 'includes/sidebar.php';

// Datos de ejemplo
$plantillas = [
    [
        'id' => 1,
        'nombre' => 'Elegante Rosa',
        'tipo' => 'boda',
        'estado' => 'activa',
        'imagen' => 'template1.jpg'
    ],
    [
        'id' => 2,
        'nombre' => 'Quinceañera Dorada',
        'tipo' => 'xv',
        'estado' => 'activa',
        'imagen' => 'template2.jpg'
        ]
    ];
    ?>

<link rel="stylesheet" href="./css/plantillas.css">
<main class="admin-content">
    <div class="container">
        <div class="page-header">
            <h2 class="page-title">Gestión de Plantillas</h2>
            <button class="btn btn-primary" onclick="openTemplateModal()">Nueva plantilla</button>
        </div>

        <div class="templates-grid">
            <?php foreach ($plantillas as $plantilla): ?>
                <div class="template-card fade-in">
                    <div class="template-preview">
                        <img src="../images/templates/<?php echo $plantilla['imagen']; ?>" 
                             alt="<?php echo $plantilla['nombre']; ?>" 
                             class="template-image">
                        <div class="template-overlay">
                            <button class="btn btn-outline btn-sm" onclick="editTemplate(<?php echo $plantilla['id']; ?>)">
                                Editar
                            </button>
                        </div>
                    </div>
                    <div class="template-info">
                        <h3 class="template-name"><?php echo $plantilla['nombre']; ?></h3>
                        <span class="template-type"><?php echo ucfirst($plantilla['tipo']); ?></span>
                        <span class="template-status status-<?php echo $plantilla['estado']; ?>">
                            <?php echo ucfirst($plantilla['estado']); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Modal para crear/editar plantilla -->
<div id="templateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Nueva Plantilla</h3>
            <button class="modal-close" onclick="closeTemplateModal()">&times;</button>
        </div>
        <form id="templateForm" class="modal-body">
            <div class="form-group">
                <label for="templateName">Nombre de la plantilla</label>
                <input type="text" id="templateName" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="templateType">Tipo de evento</label>
                <select id="templateType" name="tipo" required>
                    <option value="">Seleccionar tipo</option>
                    <option value="boda">Boda</option>
                    <option value="xv">XV Años</option>
                    <option value="bautizo">Bautizo</option>
                    <option value="comunion">Primera Comunión</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="templateBackground">Color de fondo</label>
                <input type="color" id="templateBackground" name="fondo" value="#c8a882">
            </div>
            
            <div class="form-group">
                <label for="templateFont">Fuente</label>
                <select id="templateFont" name="fuente">
                    <option value="DM Sans">DM Sans</option>
                    <option value="Playfair Display">Playfair Display</option>
                    <option value="Dancing Script">Dancing Script</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="templateImage">Imagen de fondo</label>
                <input type="file" id="templateImage" name="imagen" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="templateMusic">Música de fondo</label>
                <input type="file" id="templateMusic" name="musica" accept="audio/*">
            </div>
        </form>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeTemplateModal()">Cancelar</button>
            <button type="submit" form="templateForm" class="btn btn-primary">Guardar</button>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>