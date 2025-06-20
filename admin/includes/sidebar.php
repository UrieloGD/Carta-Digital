<aside class="admin-sidebar">
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="plantillas.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'plantillas.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🎨</span>
                    Plantillas
                </a>
            </li>
            <li class="nav-item">
                <a href="invitaciones.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'invitaciones.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">💌</span>
                    Invitaciones
                </a>
            </li>
            <li class="nav-item">
                <a href="confirmaciones.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'confirmaciones.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">✅</span>
                    Confirmaciones
                </a>
            </li>
            <li class="nav-item">
                <a href="mensajes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'mensajes.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">💬</span>
                    Mensajes
                </a>
            </li>
            <li class="nav-item">
                <a href="ajustes.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ajustes.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">⚙️</span>
                    Ajustes
                </a>
            </li>
        </ul>
    </nav>
</aside>