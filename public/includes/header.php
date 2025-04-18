<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Tienda de Computadoras</title>
    <style>
        /* Estilos básicos para visualizar la página */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header y navegación */
        .header {
            background-color: #000;
            color: white;
            padding: 10px 0;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .search-bar {
            flex: 1;
            margin: 0 20px;
        }
        
        .search-form {
            display: flex;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 3px 0 0 3px;
        }
        
        .search-button {
            background-color: #FF0000;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 0 3px 3px 0;
        }
        
        .user-actions {
            display: flex;
            align-items: center;
        }
        
        .user-actions a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            display: flex;
            align-items: center;
        }
        
        /* Menú de categorías */
        .nav-categories {
            background-color: #FF0000;
            padding: 10px 0;
        }
        
        .nav-categories ul {
            display: flex;
            list-style: none;
        }
        
        .nav-categories li {
            margin-right: 10px;
            position: relative;
        }
        
        .nav-categories a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        
        .nav-categories a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        /* Dropdown para subcategorías */
        .dropdown-menu {
            display: none;
            position: absolute;
            background-color: white;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1;
            min-width: 200px;
        }
        
        .dropdown-menu a {
            color: black;
            padding: 10px;
            display: block;
        }
        
        .dropdown-menu a:hover {
            background-color: #f5f5f5;
        }
        
        .nav-categories li:hover .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-top">
                <!-- Logo -->
                <a href="<?php echo BASE_URL; ?>" class="logo">
                    <?php echo SITE_NAME; ?>
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
        </div>
        
        <!-- Menú de categorías -->
        <nav class="nav-categories">
            <div class="container">
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
            </div>
        </nav>
    </header>
    <!-- Fin del Header --> 