<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Procesar acciones (eliminar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    
    if ($action === 'eliminar') {
        $delete_query = "DELETE FROM plantillas WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        if ($delete_stmt->execute([$id])) {
            $success_message = "Plantilla eliminada correctamente.";
        } else {
            $error_message = "Error al eliminar la plantilla.";
        }
    }
}

$query = "SELECT * FROM plantillas ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$plantillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Plantillas</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Icon page -->
    <link rel="shortcut icon" href="./../images/logo.webp" />
    <style>
        :root {
            --primary-color: #6f42c1;
            --secondary-color: #6c757d;
            --border-radius: 12px;
        }

        body {
            background-color: #f8f9fa;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-brand {
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: all 0.2s ease;
        }

        .card:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.375rem 0.75rem rgba(0, 0, 0, 0.1);
        }

        .plantilla-card {
            overflow: hidden;
        }

        .preview-container {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
        }

        .preview-image-compact {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .plantilla-card:hover .preview-image-compact {
            transform: scale(1.05);
        }

        .preview-placeholder {
            width: 100%;
            height: 200px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #dee2e6;
        }

        .preview-placeholder i {
            font-size: 2rem;
            color: #dee2e6;
        }

        .badge {
            font-size: 0.7rem;
            font-weight: 500;
        }

        .empty-state {
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
        }

        /* Mejoras para el navbar responsivo */
        .navbar-toggler {
            border: none;
            padding: 0.25rem 0.5rem;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        @media (max-width: 991.98px) {
            .navbar-collapse {
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .navbar-nav .btn {
                margin-bottom: 0.5rem;
                width: 100%;
                justify-content: center;
            }
        }

        /* Estilos para filtros */
        .input-group-text {
            border-color: #dee2e6;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }

        /* Transiciones suaves para filtros */
        .col-xl-3, .col-lg-4, .col-md-6 {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .input-group {
                margin-bottom: 1rem;
            }
            
            .card-body {
                padding: 0.75rem !important;
            }
            
            .preview-container,
            .preview-placeholder {
                height: 160px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <i class="bi bi-layout-text-window-reverse me-2"></i>
                <span class="d-none d-sm-inline">Gestor de Plantillas</span>
                <span class="d-sm-none">Plantillas</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <div class="d-lg-flex flex-lg-row flex-column gap-2 mt-2 mt-lg-0">
                        <a href="plantilla_nueva.php" class="btn btn-light btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>
                            <span class="d-none d-md-inline">Nueva </span>Plantilla
                        </a>
                        <a href="./functions/crear.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-envelope-plus me-1"></i>
                            <span class="d-none d-md-inline">Nueva </span>Invitación
                        </a>
                        <a href="index.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>
                            <span class="d-none d-md-inline">Volver al </span>Panel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Panel de Control y Filtros -->
    <div class="container-fluid py-3 bg-white border-bottom">
        <div class="row align-items-center">
            <!-- Búsqueda -->
            <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" 
                        id="searchInput" placeholder="Buscar plantillas...">
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="col-lg-6 col-md-6 mb-3 mb-lg-0">
                <div class="row g-2">
                    <div class="col-sm-6">
                        <select class="form-select form-select-sm" id="estadoFilter">
                            <option value="">Todos los estados</option>
                            <option value="activa">Activas</option>
                            <option value="inactiva">Inactivas</option>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <select class="form-select form-select-sm" id="ordenFilter">
                            <option value="">Orden por defecto</option>
                            <option value="nombre_asc">Nombre (A-Z)</option>
                            <option value="nombre_desc">Nombre (Z-A)</option>
                            <option value="reciente">Más recientes</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="col-lg-2 col-12">
                <div class="d-flex justify-content-between justify-content-lg-end gap-3">
                    <small class="text-muted d-flex align-items-center">
                        <i class="bi bi-check-circle me-1 text-success"></i>
                        <span id="activasCount">0</span> activas
                    </small>
                    <small class="text-muted d-flex align-items-center">
                        <i class="bi bi-collection me-1 text-primary"></i>
                        <span id="totalCount"><?php echo count($plantillas); ?></span> total
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Alertas -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($plantillas)): ?>
            <!-- Grid de plantillas -->
            <div class="row g-4">
                <?php foreach ($plantillas as $plantilla): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card h-100 plantilla-card">
                        <!-- Imagen de preview más compacta -->
                        <?php if (!empty($plantilla['imagen_preview'])): ?>
                            <?php 
                            $imagen_preview = ltrim($plantilla['imagen_preview'], './');
                            $ruta_preview = '../plantillas/' . $plantilla['carpeta'] . '/' . $imagen_preview;
                            ?>
                            <div class="preview-container">
                                <img src="<?php echo htmlspecialchars($ruta_preview); ?>" 
                                    alt="Preview de <?php echo htmlspecialchars($plantilla['nombre']); ?>"
                                    class="preview-image-compact"
                                    onerror="this.style.display='none'; this.parentElement.querySelector('.no-image').classList.remove('d-none');">
                                <div class="preview-placeholder d-none no-image">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="preview-placeholder">
                                <i class="bi bi-image text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body p-3">
                            <h5 class="card-title mb-2 fs-6">
                                <?php echo htmlspecialchars($plantilla['nombre']); ?>
                            </h5>
                            
                            <div class="mb-2">
                                <small class="text-muted d-block text-truncate">
                                    <i class="bi bi-folder me-1"></i>
                                    <?php echo htmlspecialchars($plantilla['carpeta']); ?>
                                </small>
                            </div>
                            
                            <?php if (!empty($plantilla['descripcion'])): ?>
                            <p class="card-text text-muted small mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($plantilla['descripcion']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                                <?php if ($plantilla['activa']): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Activa
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-pause-circle me-1"></i>
                                        Inactiva
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-transparent p-2">
                            <div class="btn-group w-100" role="group">
                                <a href="plantilla_editar.php?id=<?php echo $plantilla['id']; ?>" 
                                class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteModal<?php echo $plantilla['id']; ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal de confirmación de eliminación -->
                    <div class="modal fade" id="deleteModal<?php echo $plantilla['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmar eliminación</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>¿Estás seguro de que quieres eliminar la plantilla <strong><?php echo htmlspecialchars($plantilla['nombre']); ?></strong>?</p>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Esta acción no se puede deshacer.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="eliminar">
                                        <input type="hidden" name="id" value="<?php echo $plantilla['id']; ?>">
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-trash me-1"></i>
                                            Eliminar definitivamente
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Estado vacío -->
            <div class="empty-state text-center">
                <i class="bi bi-layout-text-window-reverse"></i>
                <h3 class="mt-3 mb-2">No hay plantillas registradas</h3>
                <p class="text-muted mb-4">Comienza creando tu primera plantilla base.</p>
                <a href="plantilla_nueva.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-plus-circle me-2"></i>
                    Crear Primera Plantilla
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const estadoFilter = document.getElementById('estadoFilter');
        const ordenFilter = document.getElementById('ordenFilter');
        
        const plantillaCards = document.querySelectorAll('.plantilla-card');
        let allPlantillas = Array.from(plantillaCards).map(card => card.closest('.col-xl-3, .col-lg-4, .col-md-6')).filter(col => col !== null);
        
        // Función para actualizar contadores
        function updateCounters() {
            const visibleCards = allPlantillas.filter(card => 
                card.style.display !== 'none' && !card.hasAttribute('data-hidden')
            );
            
            const activasCards = visibleCards.filter(card => {
                const badge = card.querySelector('.badge');
                return badge && badge.textContent.trim().toLowerCase().includes('activa');
            });
            
            const totalCountEl = document.getElementById('totalCount');
            const activasCountEl = document.getElementById('activasCount');
            
            if (totalCountEl) totalCountEl.textContent = visibleCards.length;
            if (activasCountEl) activasCountEl.textContent = activasCards.length;
        }
        
        // Función de filtrado
        function filterPlantillas() {
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const selectedEstado = estadoFilter ? estadoFilter.value : '';
            
            allPlantillas.forEach(cardContainer => {
                if (!cardContainer) return;
                
                const card = cardContainer.querySelector('.plantilla-card');
                if (!card) return;
                
                // Buscar en título y descripción
                const titleElement = card.querySelector('.card-title');
                const title = titleElement ? titleElement.textContent.toLowerCase() : '';
                
                const descElement = card.querySelector('.card-text');
                const description = descElement ? descElement.textContent.toLowerCase() : '';
                
                const folderElement = card.querySelector('.bi-folder').parentElement;
                const folder = folderElement ? folderElement.textContent.toLowerCase() : '';
                
                // Buscar estado
                const badge = card.querySelector('.badge');
                const estado = badge ? badge.textContent.trim().toLowerCase() : '';
                
                let shouldShow = true;
                
                // Filtro de búsqueda
                if (searchTerm && !title.includes(searchTerm) && !description.includes(searchTerm) && !folder.includes(searchTerm)) {
                    shouldShow = false;
                }
                
                // Filtro de estado
                if (selectedEstado) {
                    if (selectedEstado === 'activa' && !estado.includes('activa')) {
                        shouldShow = false;
                    } else if (selectedEstado === 'inactiva' && !estado.includes('inactiva')) {
                        shouldShow = false;
                    }
                }
                
                // Aplicar visibilidad
                if (shouldShow) {
                    cardContainer.style.display = 'block';
                    cardContainer.style.opacity = '1';
                    cardContainer.removeAttribute('data-hidden');
                } else {
                    cardContainer.style.display = 'none';
                    cardContainer.style.opacity = '0';
                    cardContainer.setAttribute('data-hidden', 'true');
                }
            });
            
            updateCounters();
            showNoResultsMessage();
        }
        
        // Función de ordenamiento
        function sortPlantillas() {
            const sortBy = ordenFilter ? ordenFilter.value : '';
            if (!sortBy) return;
            
            const container = document.querySelector('.row.g-4');
            if (!container) return;
            
            const sortedCards = [...allPlantillas].sort((a, b) => {
                const getTitleA = a.querySelector('.card-title').textContent.toLowerCase();
                const getTitleB = b.querySelector('.card-title').textContent.toLowerCase();
                
                if (sortBy === 'nombre_asc') {
                    return getTitleA.localeCompare(getTitleB);
                } else if (sortBy === 'nombre_desc') {
                    return getTitleB.localeCompare(getTitleA);
                }
                
                return 0;
            });
            
            sortedCards.forEach(card => {
                if (card && container.contains(card)) {
                    container.appendChild(card);
                }
            });
            
            allPlantillas = sortedCards.filter(card => card !== null);
        }
        
        // Mostrar mensaje cuando no hay resultados
        function showNoResultsMessage() {
            const visibleCards = allPlantillas.filter(card => 
                card && card.style.display !== 'none' && !card.hasAttribute('data-hidden')
            );
            
            const container = document.querySelector('.row.g-4');
            if (!container) return;
            
            const existingMessage = document.getElementById('no-results-message');
            
            if (visibleCards.length === 0 && !existingMessage && allPlantillas.length > 0) {
                const message = document.createElement('div');
                message.id = 'no-results-message';
                message.className = 'col-12 text-center py-5';
                message.innerHTML = `
                    <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">No se encontraron plantillas</h5>
                    <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                    <button class="btn btn-outline-primary" onclick="clearFilters()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Limpiar filtros
                    </button>
                `;
                container.appendChild(message);
            } else if (visibleCards.length > 0 && existingMessage) {
                existingMessage.remove();
            }
        }
        
        // Función para limpiar filtros
        window.clearFilters = function() {
            if (searchInput) searchInput.value = '';
            if (estadoFilter) estadoFilter.value = '';
            if (ordenFilter) ordenFilter.value = '';
            
            allPlantillas.forEach(card => {
                if (card) {
                    card.style.display = 'block';
                    card.style.opacity = '1';
                    card.removeAttribute('data-hidden');
                }
            });
            
            updateCounters();
            
            const existingMessage = document.getElementById('no-results-message');
            if (existingMessage) {
                existingMessage.remove();
            }
        };
        
        // Event listeners
        if (searchInput) {
            searchInput.addEventListener('input', filterPlantillas);
        }
        
        if (estadoFilter) {
            estadoFilter.addEventListener('change', filterPlantillas);
        }
        
        if (ordenFilter) {
            ordenFilter.addEventListener('change', sortPlantillas);
        }
        
        // Inicializar contadores
        setTimeout(() => {
            updateCounters();
        }, 100);
        
        // Auto-ocultar alertas después de 5 segundos
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-warning)');
            alerts.forEach(function(alert) {
                try {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } catch (e) {
                    alert.style.display = 'none';
                }
            });
        }, 5000);
    });
    </script>
</body>
</html>