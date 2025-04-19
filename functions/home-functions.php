<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');

/**
 * Obtiene los banners del carrusel principal
 * 
 * @return array Arreglo de banners
 */
function obtener_banners_principales() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM banners 
            WHERE activo = 1
            ORDER BY orden ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene los banners dobles de una sección
 * 
 * @param int $seccion_id ID de la sección
 * @return array Arreglo de banners dobles
 */
function obtener_banners_dobles($seccion_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM banners_dobles 
            WHERE seccion_id = ? AND activo = 1
            ORDER BY posicion ASC
        ");
        
        $stmt->execute([$seccion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene las secciones activas de la página de inicio
 * 
 * @return array Arreglo de secciones
 */
function obtener_secciones_inicio() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM secciones_inicio 
            WHERE activo = 1
            ORDER BY orden ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene el nombre de una categoría por ID
 * 
 * @param int $categoria_id ID de la categoría
 * @return string Nombre de la categoría
 */
function obtener_nombre_categoria($categoria_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT nombre FROM categorias WHERE id = ?");
        $stmt->execute([$categoria_id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado ? $resultado['nombre'] : 'Categoría';
        
    } catch(PDOException $e) {
        return 'Categoría';
    }
}

/**
 * Muestra un carrusel de productos
 * 
 * @param string $titulo Título del carrusel
 * @param array $productos Arreglo de productos a mostrar
 */
function mostrar_carrusel_productos($titulo, $productos) {
    if (empty($productos)) {
        return;
    }
    ?>
    <div style="margin: 40px 0;">
        <h2 style="margin-bottom: 20px; font-size: 24px; text-align: center; color: #333; position: relative;">
            <?php echo $titulo; ?>
            <span style="display: block; width: 50px; height: 3px; background-color: #FF0000; margin: 10px auto 0;"></span>
        </h2>
        
        <div style="display: flex; flex-wrap: nowrap; overflow-x: auto; padding: 10px 0; gap: 20px;">
            <?php foreach ($productos as $producto): ?>
                <div style="flex: 0 0 280px; max-width: 280px;">
                    <?php include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/includes/product-card.php'); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Muestra los banners dobles
 * 
 * @param array $banners Arreglo de banners dobles
 */
function mostrar_banners_dobles($banners) {
    if (count($banners) < 2) {
        return;
    }
    
    $banner_izquierda = null;
    $banner_derecha = null;
    
    foreach ($banners as $banner) {
        if ($banner['posicion'] == 'izquierda') {
            $banner_izquierda = $banner;
        } elseif ($banner['posicion'] == 'derecha') {
            $banner_derecha = $banner;
        }
    }
    
    if (!$banner_izquierda || !$banner_derecha) {
        return;
    }
    ?>
    <div style="margin: 40px 0;">
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <div style="flex: 1; min-width: 300px;">
                <a href="<?php echo $banner_izquierda['url']; ?>" style="display: block; position: relative; overflow: hidden; border-radius: 8px; height: 200px;">
                    <img src="<?php echo BANNERS_IMG_URL . '/' . $banner_izquierda['imagen']; ?>" alt="<?php echo $banner_izquierda['titulo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white;">
                        <h3 style="margin: 0; font-size: 20px;"><?php echo $banner_izquierda['titulo']; ?></h3>
                        <p style="margin: 5px 0 0 0;"><?php echo $banner_izquierda['descripcion']; ?></p>
                    </div>
                </a>
            </div>
            <div style="flex: 1; min-width: 300px;">
                <a href="<?php echo $banner_derecha['url']; ?>" style="display: block; position: relative; overflow: hidden; border-radius: 8px; height: 200px;">
                    <img src="<?php echo BANNERS_IMG_URL . '/' . $banner_derecha['imagen']; ?>" alt="<?php echo $banner_derecha['titulo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white;">
                        <h3 style="margin: 0; font-size: 20px;"><?php echo $banner_derecha['titulo']; ?></h3>
                        <p style="margin: 5px 0 0 0;"><?php echo $banner_derecha['descripcion']; ?></p>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Muestra el carrusel principal de banners
 * 
 * @param array $banners Arreglo de banners principales
 */
function mostrar_carrusel_principal($banners) {
    if (empty($banners)) {
        return;
    }
    ?>
    <div class="main-banner">
        <div class="main-banner-slides">
            <?php foreach ($banners as $index => $banner): ?>
                <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                    <a href="<?php echo $banner['url']; ?>">
                        <img src="<?php echo BANNERS_IMG_URL . '/' . $banner['imagen']; ?>" alt="<?php echo $banner['titulo']; ?>">
                        <div class="banner-content">
                            <h2><?php echo $banner['titulo']; ?></h2>
                            <p><?php echo $banner['descripcion']; ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Navegación del carrusel -->
        <div class="banner-dots">
            <?php foreach ($banners as $index => $banner): ?>
                <button class="banner-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.banner-slide');
            const dots = document.querySelectorAll('.banner-dot');
            let currentSlide = 0;
            const slideCount = slides.length;
            
            // Función para cambiar de slide
            function showSlide(index) {
                // Ocultar todos los slides
                slides.forEach(slide => slide.classList.remove('active'));
                dots.forEach(dot => dot.classList.remove('active'));
                
                // Mostrar el slide actual
                slides[index].classList.add('active');
                dots[index].classList.add('active');
                currentSlide = index;
            }
            
            // Cambio automático de slide cada 5 segundos
            setInterval(function() {
                let nextSlide = (currentSlide + 1) % slideCount;
                showSlide(nextSlide);
            }, 5000);
            
            // Eventos para los dots
            dots.forEach((dot, index) => {
                dot.addEventListener('click', function() {
                    showSlide(index);
                });
            });
        });
    </script>
    <?php
}
?>