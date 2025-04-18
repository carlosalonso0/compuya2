<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/product-functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/home-functions.php');

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/includes/header.php');
?>

<main style="padding: 20px 0;">
    <div class="container">
        <?php
        // Obtener y mostrar banners principales
        $banners_principales = obtener_banners_principales();
        if (!empty($banners_principales)) {
            mostrar_carrusel_principal($banners_principales);
        }
        
        // Obtener secciones de inicio
        $secciones = obtener_secciones_inicio();
        
        // Recorrer y mostrar secciones
        foreach ($secciones as $seccion) {
            switch ($seccion['tipo']) {
                case 'carrusel':
                    // Obtener productos de la sección
                    $productos = obtener_productos_seccion($seccion['nombre']);
                    mostrar_carrusel_productos($seccion['titulo_mostrar'], $productos);
                    break;
                    
                case 'banner_doble':
                    // Obtener banners dobles
                    $banners = obtener_banners_dobles($seccion['id']);
                    mostrar_banners_dobles($banners);
                    break;
                    
                case 'categoria':
                    if ($seccion['categoria_id']) {
                        // Obtener productos de la categoría
                        $productos = obtener_productos_categoria($seccion['categoria_id']);
                        $titulo = !empty($seccion['titulo_mostrar']) ? $seccion['titulo_mostrar'] : obtener_nombre_categoria($seccion['categoria_id']);
                        mostrar_carrusel_productos($titulo, $productos);
                    }
                    break;
            }
        }
        ?>
    </div>
</main>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/includes/footer.php');
?>