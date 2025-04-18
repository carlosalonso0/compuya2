<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/product-functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/home-functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/home-functions-extended.php');

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
                
                case 'estadisticas':
                    // Obtener y mostrar estadísticas
                    $estadisticas = obtener_estadisticas_inicio();
                    mostrar_estadisticas($estadisticas);
                    break;
                
                case 'blogs_guias':
                    // Obtener y mostrar blogs o guías
                    $blogs = obtener_blogs_guias();
                    mostrar_blogs_guias($blogs);
                    break;
                
                case 'ofertas_contador':
                    // Obtener y mostrar oferta con contador
                    $oferta = obtener_oferta_contador();
                    mostrar_oferta_contador($oferta);
                    break;
                
                case 'comparador':
                    // Obtener y mostrar comparador de categorías
                    $comparador = obtener_comparadores_categorias();
                    mostrar_comparador_categorias($comparador);
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