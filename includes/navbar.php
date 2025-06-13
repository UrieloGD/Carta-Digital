<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <h1>Carta Digital</h1>
        </div>
        <div class="nav-menu" id="nav-menu">
            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == './index.php' ? 'active' : ''; ?>">Inicio</a>
            <a href="./plantillas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == './plantillas.php' ? 'active' : ''; ?>">Plantillas</a>
            <a href="./contacto.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == './contacto.php' ? 'active' : ''; ?>">Contacto</a>
        </div>
        <div class="nav-toggle" id="nav-toggle">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </div>
</nav>