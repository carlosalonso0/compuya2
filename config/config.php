<?php
// Configuración general del sitio
define('SITE_NAME', 'CompuYaTienda');
define('BASE_URL', 'http://localhost/compuyatienda');
define('ADMIN_URL', BASE_URL . '/admin');

// Rutas para imágenes
define('IMAGES_URL', BASE_URL . '/public/assets/images');
define('PRODUCTS_IMG_URL', IMAGES_URL . '/productos');
define('BANNERS_IMG_URL', IMAGES_URL . '/banners');
define('CATEGORIES_IMG_URL', IMAGES_URL . '/categorias');

// Rutas para archivos del sistema
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda');
define('IMAGES_PATH', ROOT_PATH . '/public/assets/images');
define('PRODUCTS_IMG_PATH', IMAGES_PATH . '/productos');
define('BANNERS_IMG_PATH', IMAGES_PATH . '/banners');
define('CATEGORIES_IMG_PATH', IMAGES_PATH . '/categorias');

// Función para redireccionar
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

// Función para limpiar entradas de datos
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para generar slug desde un texto
function generate_slug($text) {
    // Convertir a minúsculas
    $text = strtolower($text);
    // Reemplazar espacios con guiones
    $text = str_replace(' ', '-', $text);
    // Eliminar caracteres especiales
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    // Eliminar guiones múltiples
    $text = preg_replace('/-+/', '-', $text);
    return $text;
}

// Función para generar SKU
function generate_sku($product_name, $category_id, $product_id = null) {
    // Reemplazar espacios y caracteres especiales por guiones
    $sku = trim(preg_replace('/[^a-zA-Z0-9]/', '-', $product_name));
    // Eliminar guiones múltiples
    $sku = preg_replace('/-+/', '-', $sku);
    // Convertir a mayúsculas
    $sku = strtoupper($sku);
    
    return $sku;
}
?>