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
            <a href="./precios.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'precios.php' ? 'active' : ''; ?>">Precios</a>
            <a href="./contacto.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contacto.php' ? 'active' : ''; ?>">Contacto</a>
            
            <!-- Botón CTA de Compra -->
            <a href="./plantillas.php" class="nav-cta-btn">
                <i class="fas fa-shopping-cart"></i> Comenzar
            </a>
        </div>
    </div>
</nav>

<script>
    // Toggle del menú hamburguesa
    const navToggle = document.getElementById('nav-toggle');
    const navMenu = document.getElementById('nav-menu');
    
    navToggle.addEventListener('click', function() {
        navMenu.classList.toggle('active');
        navToggle.classList.toggle('active');
        navToggle.setAttribute('aria-expanded', navToggle.classList.contains('active'));
    });
    
    // Cerrar menú al hacer clic en un link
    const navLinks = document.querySelectorAll('.nav-link, .nav-cta-btn');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navMenu.classList.remove('active');
            navToggle.classList.remove('active');
            navToggle.setAttribute('aria-expanded', 'false');
        });
    });
    
    // Efecto de scroll en el header
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.header');
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
</script>
