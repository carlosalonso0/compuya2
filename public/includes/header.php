<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Tienda de Computadoras</title>
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/reset.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/variables.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/layout.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/typography.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/components.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/cards.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/carousels.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/headers.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/footer.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/animations.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/assets/css/front/responsive.css">
    
    <!-- CSS específico de página -->
    <?php 
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    $page_css = ROOT_PATH . '/public/assets/css/front/pages/' . $current_page . '.css';
    if (file_exists($page_css)) {
        echo '<link rel="stylesheet" href="' . BASE_URL . '/public/assets/css/front/pages/' . $current_page . '.css">';
    }
    ?>
</head>
<body>
  <!-- Header -->
<header class="header">
    <div class="container">
        <div class="header-top">
            <!-- Logo -->
            <a href="<?php echo BASE_URL; ?>" class="logo">
                <img src="<?php echo IMAGES_URL; ?>/logo.svg" alt="<?php echo SITE_NAME; ?>">
            </a>
            
            <!-- Barra de búsqueda -->
            <div class="search-bar">
                <form action="<?php echo BASE_URL; ?>/public/busqueda.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Búsqueda en catálogo" class="search-input">
                    <button type="submit" class="search-button">
                        Buscar
                    </button>
                </form>
            </div>
            
            <!-- Acciones de usuario -->
            <div class="user-actions">
                <a href="<?php echo BASE_URL; ?>/public/carrito.php">
                    Carrito (0)
                </a>
                <a href="<?php echo BASE_URL; ?>/public/cuenta.php">
                    Iniciar sesión
                </a>
            </div>
        </div>
        
        <!-- Menú de categorías -->
        <nav class="nav-categories">
            <ul>
                <?php
                // Obtener categorías principales
                try {
                    $stmt = $conn->prepare("
                        SELECT id, nombre, slug FROM categorias 
                        WHERE categoria_padre_id IS NULL AND activo = 1
                        ORDER BY nombre ASC
                    ");
                    $stmt->execute();
                    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($categorias as $categoria) {
                        // Verificar si tiene subcategorías
                        $stmt_sub = $conn->prepare("
                            SELECT id, nombre, slug FROM categorias 
                            WHERE categoria_padre_id = ? AND activo = 1
                            ORDER BY nombre ASC
                        ");
                        $stmt_sub->execute([$categoria['id']]);
                        $subcategorias = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo '<li>';
                        echo '<a href="' . BASE_URL . '/public/categoria.php?slug=' . $categoria['slug'] . '">' . $categoria['nombre'] . '</a>';
                        
                        // Mostrar subcategorías si existen
                        if (count($subcategorias) > 0) {
                            echo '<div class="dropdown-menu">';
                            foreach ($subcategorias as $subcategoria) {
                                echo '<a href="' . BASE_URL . '/public/categoria.php?slug=' . $subcategoria['slug'] . '">' . $subcategoria['nombre'] . '</a>';
                            }
                            echo '</div>';
                        }
                        
                        echo '</li>';
                    }
                    
                } catch(PDOException $e) {
                    echo '<li><a href="#">Error al cargar categorías</a></li>';
                }
                ?>
            </ul>
        </nav>
    </div>
</header>
<!-- Fin del Header -->