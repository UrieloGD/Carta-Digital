<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <img src="./images/logo.webp" alt="Carta Digital" class="logo-img">
            <span>Carta Digital</span>
        </div>
        
        <!-- Botón hamburguesa -->
        <button class="nav-toggle" id="nav-toggle" aria-label="Abrir menú" aria-expanded="false">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        
        <!-- Menú de navegación -->
        <div class="nav-menu" id="nav-menu">
            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Inicio</a>
            <a href="./plantillas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'plantillas.php' ? 'active' : ''; ?>">Plantillas</a>
            <a href="./contacto.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contacto.php' ? 'active' : ''; ?>">Contacto</a>
        </div>
    </div>
</nav>