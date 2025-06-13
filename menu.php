<?php
$pageTitle = "Menú - " . SITE_NAME;
$menuData = getMenuData();
?>

<main class="main-content">
    <section class="hero-section">
        <div class="hero-content">
            <h1>Nuestra Carta Digital</h1>
            <p>Descubre nuestros deliciosos platillos</p>
        </div>
    </section>

    <section class="menu-section">
        <div class="container">
            <div class="categories-grid">
                <?php foreach ($menuData['categorias'] as $categoria): ?>
                <div class="category-card" data-category="<?php echo $categoria['id']; ?>">
                    <div class="category-image">
                        <img src="<?php echo asset('images/' . $categoria['imagen']); ?>" 
                             alt="<?php echo $categoria['nombre']; ?>">
                    </div>
                    <div class="category-info">
                        <h3><?php echo $categoria['nombre']; ?></h3>
                        <p><?php echo $categoria['descripcion']; ?></p>
                        <a href="<?php echo url('?page=categoria&id=' . $categoria['id']); ?>" 
                           class="btn btn-primary">Ver Platillos</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>