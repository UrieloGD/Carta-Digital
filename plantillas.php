<?php include './includes/header.php'; ?>
<link rel="stylesheet" href="./css/plantillas.css">

<section class="page-header">
    <div class="container">
        <div class="header-content">
            <h1>Explora nuestras <span class="highlight">invitaciones</span></h1>
            <p class="header-subtitle">Descubre diseños exclusivos que harán de tu día especial un momento inolvidable.</p>
            
            <!-- Navigation tabs -->
            <div class="template-nav">
                <button class="nav-btn active" data-category="todas">Todas</button>
                <button class="nav-btn" data-category="clasicas">Clásicas</button>
                <button class="nav-btn" data-category="modernas">Modernas</button>
                <button class="nav-btn" data-category="bohemias">Bohemias</button>
            </div>
        </div>
    </div>
</section>

<!-- El resto del HTML permanece igual -->
<section class="templates">
    <div class="container">
        <!-- First row -->
        <div class="templates-grid">
            <div class="template-card" data-category="clasicas">
                <div class="template-image">
                    <img src="images/plantilla-1.png" alt="Plantilla Clásica">
                    <div class="template-overlay">
                        <a href="./plantilla-clasica.php" class="btn btn-secondary">Ver plantilla</a>
                    </div>
                </div>
                <div class="template-info">
                    <h3>Clásica</h3>
                </div>
            </div>

            <div class="template-card" data-category="modernas">
                <div class="template-image">
                    <img src="images/plantilla-1.png" alt="Plantilla Moderna">
                    <div class="template-overlay">
                        <a href="./plantilla-moderna.php" class="btn btn-secondary">Ver plantilla</a>
                    </div>
                </div>
                <div class="template-info">
                    <h3>Moderna</h3>
                </div>
            </div>

            <div class="template-card" data-category="bohemias">
                <div class="template-image">
                    <img src="images/plantilla-1.png" alt="Plantilla Bohemia">
                    <div class="template-overlay">
                        <a href="./plantilla-bohemia.php" class="btn btn-secondary">Ver plantilla</a>
                    </div>
                </div>
                <div class="template-info">
                    <h3>Bohemia</h3>
                </div>
            </div>
        </div>

        <!-- Second row -->
        <div class="templates-grid">
            <div class="template-card" data-category="clasicas">
                <div class="template-image">
                    <img src="images/plantilla-1.png" alt="Plantilla Elegante">
                    <div class="template-overlay">
                        <a href="./plantilla-elegante.php" class="btn btn-secondary">Ver plantilla</a>
                    </div>
                </div>
                <div class="template-info">
                    <h3>Elegante</h3>
                </div>
            </div>

            <div class="template-card" data-category="modernas">
                <div class="template-image">
                    <img src="images/plantilla-1.png" alt="Plantilla Minimalista">
                    <div class="template-overlay">
                        <a href="./plantilla-minimalista.php" class="btn btn-secondary">Ver plantilla</a>
                    </div>
                </div>
                <div class="template-info">
                    <h3>Minimalista</h3>
                </div>
            </div>

            <div class="template-card" data-category="bohemias">
                <div class="template-image">
                    <img src="images/plantilla-1.png" alt="Plantilla Vintage">
                    <div class="template-overlay">
                        <a href="./plantilla-vintage.php" class="btn btn-secondary">Ver plantilla</a>
                    </div>
                </div>
                <div class="template-info">
                    <h3>Vintage</h3>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="./js/plantillas.js"></script>
<?php include './includes/footer.php'; ?>