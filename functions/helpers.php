<?php
function getMenuData() {
    $json = file_get_contents('data/menu.json');
    return json_decode($json, true);
}

function getCategoriaById($id) {
    $menuData = getMenuData();
    foreach ($menuData['categorias'] as $categoria) {
        if ($categoria['id'] === $id) {
            return $categoria;
        }
    }
    return null;
}

function formatPrice($price) {
    return '$' . number_format($price, 0, '.', ',');
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>