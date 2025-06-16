<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Carta Digital' : 'Carta Digital - Invitaciones Digitales de Boda'; ?></title>
    <meta name="description" content="<?php echo isset($page_description) ? $page_description : 'Invitaciones digitales de boda con estilo y elegancia. Diseños únicos para tu día especial.'; ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($page_title) ? $page_title . ' - Carta Digital' : 'Carta Digital - Invitaciones Digitales de Boda'; ?>">
    <meta property="og:description" content="<?php echo isset($page_description) ? $page_description : 'Invitaciones digitales de boda con estilo y elegancia. Diseños únicos para tu día especial.'; ?>">
    <meta property="og:image" content="<?php echo isset($page_image) ? $page_image : './images/og-image.jpg'; ?>">
    <meta property="og:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_title) ? $page_title . ' - Carta Digital' : 'Carta Digital - Invitaciones Digitales de Boda'; ?>">
    <meta name="twitter:description" content="<?php echo isset($page_description) ? $page_description : 'Invitaciones digitales de boda con estilo y elegancia. Diseños únicos para tu día especial.'; ?>">
    <meta name="twitter:image" content="<?php echo isset($page_image) ? $page_image : './images/og-image.jpg'; ?>">
    
    <!-- Favicon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="shortcut icon" href="./images/logo.webp" />
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="./css/global.css">
    <link rel="stylesheet" href="./css/navbar.css">
    <link rel="stylesheet" href="./css/footer.css">
    
    <!-- Additional CSS can be added by individual pages -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <?php require_once './includes/navbar.php'; ?>
    </header>
    <main class="main-content">