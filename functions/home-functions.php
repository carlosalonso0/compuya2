<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/product-functions.php');

/**
 * Obtiene las estadísticas para mostrar en la página de inicio
 * 
 * @return array Arreglo de estadísticas
 */
function obtener_estadisticas_inicio() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM estadisticas_inicio 
            WHERE activo = 1
            ORDER BY orden ASC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en obtener_estadisticas_inicio: " . $e->getMessage());
        return [];
    }
}

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
        error_log("Error en obtener_banners_principales: " . $e->getMessage());
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
        error_log("Error en obtener_secciones_inicio: " . $e->getMessage());
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
        error_log("Error en obtener_banners_dobles: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene el nombre de una categoría por su ID
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
        
        if ($resultado) {
            return $resultado['nombre'];
        }
        
        return 'Categoría';
        
    } catch(PDOException $e) {
        error_log("Error en obtener_nombre_categoria: " . $e->getMessage());
        return 'Categoría';
    }
}

/**
 * Obtiene las guías o blogs destacados para mostrar en la página de inicio
 * 
 * @param int $limit Número máximo de guías a obtener
 * @return array Arreglo de guías
 */
function obtener_blogs_guias($limit = 3) {
    global $conn;
    
    try {
        // Convertir a entero para evitar inyección SQL
        $limit = (int)$limit;
        
        $stmt = $conn->prepare("
            SELECT * FROM blogs_guias 
            WHERE activo = 1
            ORDER BY destacado DESC, orden ASC, fecha_publicacion DESC
            LIMIT {$limit}
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en obtener_blogs_guias: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene la oferta con contador activa
 * 
 * @return array|false Datos de la oferta o false si no hay ninguna activa
 */
function obtener_oferta_contador() {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM ofertas_contador 
            WHERE activo = 1 
            AND fecha_inicio <= NOW() 
            AND fecha_fin >= NOW()
            ORDER BY fecha_fin ASC
            LIMIT 1
        ");
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en obtener_oferta_contador: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene los comparadores visuales de categorías
 * 
 * @param int $limit Número máximo de comparadores a obtener
 * @return array Arreglo de comparadores
 */
function obtener_comparadores_categorias($limit = 1) {
    global $conn;
    
    try {
        // Convertir a entero para evitar inyección SQL
        $limit = (int)$limit;
        
        $stmt = $conn->prepare("
            SELECT * FROM comparador_categorias 
            WHERE activo = 1
            ORDER BY orden ASC
            LIMIT {$limit}
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en obtener_comparadores_categorias: " . $e->getMessage());
        return [];
    }
}

/**
 * Muestra el carrusel principal de la página de inicio
 * 
 * @param array $banners Arreglo de banners
 */
function mostrar_carrusel_principal($banners) {
    if (empty($banners)) {
        return;
    }
    ?>
    <div class="carrusel-principal" style="margin-bottom: 30px; border-radius: 8px; overflow: hidden; position: relative;">
        <div class="carrusel-items" style="display: flex; transition: transform 0.5s ease; position: relative;">
            <?php foreach ($banners as $index => $banner): ?>
                <div class="carrusel-item" style="min-width: 100%; <?php echo $index == 0 ? 'display: block;' : 'display: none;'; ?>">
                    <a href="<?php echo $banner['url']; ?>" style="display: block; position: relative;">
                        <?php if (!empty($banner['imagen'])): ?>
                            <img src="<?php echo BANNERS_IMG_URL . '/' . $banner['imagen']; ?>" alt="<?php echo $banner['titulo']; ?>" style="width: 100%; height: 400px; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 400px; background-color: #333;"></div>
                        <?php endif; ?>
                        
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 30px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white;">
                            <h2 style="font-size: 28px; margin-bottom: 10px;"><?php echo $banner['titulo']; ?></h2>
                            <p style="font-size: 16px; margin-bottom: 15px;"><?php echo $banner['descripcion']; ?></p>
                            <span style="display: inline-block; background-color: #FF0000; color: white; padding: 8px 15px; border-radius: 4px; font-weight: 600;">Ver más</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Controles del carrusel -->
        <button class="carrusel-prev" style="position: absolute; top: 50%; left: 20px; transform: translateY(-50%); background-color: rgba(0,0,0,0.5); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 20px; cursor: pointer;">&#8249;</button>
        <button class="carrusel-next" style="position: absolute; top: 50%; right: 20px; transform: translateY(-50%); background-color: rgba(0,0,0,0.5); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 20px; cursor: pointer;">&#8250;</button>
        
        <!-- Indicadores -->
        <div class="carrusel-indicadores" style="position: absolute; bottom: 15px; left: 0; right: 0; display: flex; justify-content: center; gap: 10px;">
            <?php foreach ($banners as $index => $banner): ?>
                <button class="carrusel-indicador <?php echo $index == 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>" style="width: 12px; height: 12px; border-radius: 50%; border: none; background-color: <?php echo $index == 0 ? 'white' : 'rgba(255,255,255,0.5)'; ?>; cursor: pointer;"></button>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carrusel = document.querySelector('.carrusel-principal');
            const items = carrusel.querySelectorAll('.carrusel-item');
            const prev = carrusel.querySelector('.carrusel-prev');
            const next = carrusel.querySelector('.carrusel-next');
            const indicadores = carrusel.querySelectorAll('.carrusel-indicador');
            
            let currentSlide = 0;
            const totalSlides = items.length;
            
            function showSlide(index) {
                // Ocultar todos los slides
                items.forEach(item => {
                    item.style.display = 'none';
                });
                
                // Desactivar todos los indicadores
                indicadores.forEach(ind => {
                    ind.style.backgroundColor = 'rgba(255,255,255,0.5)';
                });
                
                // Mostrar el slide actual
                items[index].style.display = 'block';
                
                // Activar el indicador actual
                indicadores[index].style.backgroundColor = 'white';
                
                currentSlide = index;
            }
            
            function nextSlide() {
                let nextIndex = currentSlide + 1;
                if (nextIndex >= totalSlides) {
                    nextIndex = 0;
                }
                showSlide(nextIndex);
            }
            
            function prevSlide() {
                let prevIndex = currentSlide - 1;
                if (prevIndex < 0) {
                    prevIndex = totalSlides - 1;
                }
                showSlide(prevIndex);
            }
            
            // Configurar eventos para botones de navegación
            prev.addEventListener('click', prevSlide);
            next.addEventListener('click', nextSlide);
            
            // Configurar eventos para indicadores
            indicadores.forEach((ind, index) => {
                ind.addEventListener('click', () => {
                    showSlide(index);
                });
            });
            
            // Autoplay del carrusel
            let autoplayInterval = setInterval(nextSlide, 5000);
            
            // Detener autoplay al pasar el mouse sobre el carrusel
            carrusel.addEventListener('mouseover', () => {
                clearInterval(autoplayInterval);
            });
            
            // Reanudar autoplay al quitar el mouse del carrusel
            carrusel.addEventListener('mouseout', () => {
                autoplayInterval = setInterval(nextSlide, 5000);
            });
        });
    </script>
    <?php
}

/**
 * Muestra la sección de comparador visual de categorías
 * 
 * @param array $comparador Datos del comparador
 */
function mostrar_comparador_categorias($comparador) {
    if (empty($comparador)) {
        return;
    }
    
    $comparador = $comparador[0]; // Tomar el primer comparador
    ?>
    <div style="margin: 40px 0;">
        <h2 style="margin-bottom: 30px; font-size: 24px; text-align: center; color: #333; position: relative;">
            <?php echo $comparador['titulo']; ?>
            <span style="display: block; width: 50px; height: 3px; background-color: #FF0000; margin: 10px auto 0;"></span>
        </h2>
        
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <!-- Primera categoría -->
            <div style="flex: 1; min-width: 300px; position: relative; overflow: hidden; border-radius: 8px; transition: transform 0.3s;">
                <a href="<?php echo BASE_URL; ?>/public/categoria.php?slug=<?php echo $comparador['categoria1_id'] ? 'pc-gamer' : '#'; ?>" style="display: block; text-decoration: none; color: inherit;">
                    <div style="position: relative;">
                        <?php if (!empty($comparador['categoria1_imagen'])): ?>
                            <img src="<?php echo BASE_URL; ?>/public/assets/images/comparadores/<?php echo $comparador['categoria1_imagen']; ?>" alt="<?php echo $comparador['categoria1_titulo']; ?>" style="width: 100%; height: 300px; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 300px; background-color: #333;"></div>
                        <?php endif; ?>
                        
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.8)); color: white;">
                            <h3 style="font-size: 24px; margin-bottom: 10px;"><?php echo $comparador['categoria1_titulo']; ?></h3>
                            <p style="margin-bottom: 15px;"><?php echo $comparador['categoria1_descripcion']; ?></p>
                            <button style="background-color: #FF0000; color: white; border: none; padding: 8px 15px; font-size: 14px; font-weight: 600; border-radius: 4px; cursor: pointer;">
                                Ver opciones
                            </button>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Segunda categoría -->
            <div style="flex: 1; min-width: 300px; position: relative; overflow: hidden; border-radius: 8px; transition: transform 0.3s;">
                <a href="<?php echo BASE_URL; ?>/public/categoria.php?slug=<?php echo $comparador['categoria2_id'] ? 'laptops' : '#'; ?>" style="display: block; text-decoration: none; color: inherit;">
                    <div style="position: relative;">
                        <?php if (!empty($comparador['categoria2_imagen'])): ?>
                            <img src="<?php echo BASE_URL; ?>/public/assets/images/comparadores/<?php echo $comparador['categoria2_imagen']; ?>" alt="<?php echo $comparador['categoria2_titulo']; ?>" style="width: 100%; height: 300px; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 300px; background-color: #333;"></div>
                        <?php endif; ?>
                        
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.8)); color: white;">
                            <h3 style="font-size: 24px; margin-bottom: 10px;"><?php echo $comparador['categoria2_titulo']; ?></h3>
                            <p style="margin-bottom: 15px;"><?php echo $comparador['categoria2_descripcion']; ?></p>
                            <button style="background-color: #FF0000; color: white; border: none; padding: 8px 15px; font-size: 14px; font-weight: 600; border-radius: 4px; cursor: pointer;">
                                Ver opciones
                            </button>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <?php
}
?>
    <?php
}

/**
 * Muestra un carrusel de productos
 * 
 * @param string $titulo Título de la sección
 * @param array $productos Arreglo de productos
 */
function mostrar_carrusel_productos($titulo, $productos) {
    if (empty($productos)) {
        return;
    }
    ?>
    <div class="seccion-productos" style="margin: 40px 0;">
        <h2 style="font-size: 24px; margin-bottom: 20px; text-align: center; color: #333; position: relative;">
            <?php echo $titulo; ?>
            <span style="display: block; width: 50px; height: 3px; background-color: #FF0000; margin: 10px auto 0;"></span>
        </h2>
        
        <!-- Carrusel de productos -->
        <div class="productos-carrusel" style="position: relative;">
            <!-- Contenedor de productos -->
            <div class="productos-wrapper" style="overflow: hidden;">
                <div class="productos-container" style="display: flex; flex-wrap: nowrap; transition: transform 0.5s ease; margin: 0 -10px;">
                    <?php foreach ($productos as $index => $producto): ?>
                        <div class="producto-slide" style="flex: 0 0 25%; max-width: 25%; padding: 0 10px; box-sizing: border-box;">
                            <?php include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/includes/product-card.php'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Controles del carrusel -->
            <?php if (count($productos) > 4): ?>
            <button class="carrusel-prev" style="position: absolute; top: 50%; left: -15px; transform: translateY(-50%); background-color: white; color: #333; border: 1px solid #ddd; width: 40px; height: 40px; border-radius: 50%; font-size: 20px; cursor: pointer; z-index: 1;">&#8249;</button>
            <button class="carrusel-next" style="position: absolute; top: 50%; right: -15px; transform: translateY(-50%); background-color: white; color: #333; border: 1px solid #ddd; width: 40px; height: 40px; border-radius: 50%; font-size: 20px; cursor: pointer; z-index: 1;">&#8250;</button>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar cada carrusel de productos
            const carruseles = document.querySelectorAll('.productos-carrusel');
            
            carruseles.forEach(carrusel => {
                const container = carrusel.querySelector('.productos-container');
                const prev = carrusel.querySelector('.carrusel-prev');
                const next = carrusel.querySelector('.carrusel-next');
                
                if (!prev || !next) return; // No hay controles, no inicializar
                
                const slides = carrusel.querySelectorAll('.producto-slide');
                const totalSlides = slides.length;
                const slidesPerView = 4; // Número de slides visibles a la vez
                
                let currentIndex = 0;
                
                // Función para mover el carrusel
                function moveSlides(direction) {
                    if (direction === 'next') {
                        currentIndex = Math.min(currentIndex + slidesPerView, totalSlides - slidesPerView);
                    } else {
                        currentIndex = Math.max(currentIndex - slidesPerView, 0);
                    }
                    
                    container.style.transform = `translateX(-${currentIndex * (100 / totalSlides)}%)`;
                    
                    // Actualizar estado de los botones
                    prev.style.opacity = currentIndex > 0 ? '1' : '0.5';
                    next.style.opacity = currentIndex < totalSlides - slidesPerView ? '1' : '0.5';
                }
                
                // Configurar eventos para botones de navegación
                prev.addEventListener('click', () => moveSlides('prev'));
                next.addEventListener('click', () => moveSlides('next'));
                
                // Configurar estado inicial de los botones
                prev.style.opacity = '0.5'; // Inicialmente desactivado
                next.style.opacity = totalSlides > slidesPerView ? '1' : '0.5';
            });
        });
    </script>
    <?php
}

/**
 * Muestra banners dobles
 * 
 * @param array $banners Arreglo de banners dobles
 */
function mostrar_banners_dobles($banners) {
    if (empty($banners) || count($banners) < 2) {
        return;
    }
    
    // Separar banners por posición
    $banner_izquierdo = null;
    $banner_derecho = null;
    
    foreach ($banners as $banner) {
        if ($banner['posicion'] == 'izquierda') {
            $banner_izquierdo = $banner;
        } elseif ($banner['posicion'] == 'derecha') {
            $banner_derecho = $banner;
        }
    }
    
    // Si falta alguno de los banners, salir
    if (!$banner_izquierdo || !$banner_derecho) {
        return;
    }
    ?>
    <div style="margin: 40px 0;">
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <!-- Banner izquierdo -->
            <div style="flex: 1; min-width: 300px; position: relative; overflow: hidden; border-radius: 8px; transition: transform 0.3s ease;">
                <a href="<?php echo $banner_izquierdo['url']; ?>" style="display: block;">
                    <div style="position: relative;">
                        <?php if (!empty($banner_izquierdo['imagen'])): ?>
                            <img src="<?php echo BANNERS_IMG_URL . '/' . $banner_izquierdo['imagen']; ?>" alt="<?php echo $banner_izquierdo['titulo']; ?>" style="width: 100%; height: 250px; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 250px; background-color: #333;"></div>
                        <?php endif; ?>
                        
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white;">
                            <h3 style="font-size: 20px; margin-bottom: 5px;"><?php echo $banner_izquierdo['titulo']; ?></h3>
                            <?php if (!empty($banner_izquierdo['descripcion'])): ?>
                                <p style="font-size: 14px;"><?php echo $banner_izquierdo['descripcion']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Banner derecho -->
            <div style="flex: 1; min-width: 300px; position: relative; overflow: hidden; border-radius: 8px; transition: transform 0.3s ease;">
                <a href="<?php echo $banner_derecho['url']; ?>" style="display: block;">
                    <div style="position: relative;">
                        <?php if (!empty($banner_derecho['imagen'])): ?>
                            <img src="<?php echo BANNERS_IMG_URL . '/' . $banner_derecho['imagen']; ?>" alt="<?php echo $banner_derecho['titulo']; ?>" style="width: 100%; height: 250px; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 250px; background-color: #333;"></div>
                        <?php endif; ?>
                        
                        <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white;">
                            <h3 style="font-size: 20px; margin-bottom: 5px;"><?php echo $banner_derecho['titulo']; ?></h3>
                            <?php if (!empty($banner_derecho['descripcion'])): ?>
                                <p style="font-size: 14px;"><?php echo $banner_derecho['descripcion']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Muestra la sección de estadísticas animadas
 * 
 * @param array $estadisticas Arreglo de estadísticas
 */
function mostrar_estadisticas($estadisticas) {
    if (empty($estadisticas)) {
        return;
    }
    ?>
    <div style="margin: 40px 0; background-color: #f8f9fa; padding: 40px 0; text-align: center;">
        <div class="container">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-around; gap: 20px;">
                <?php foreach ($estadisticas as $estadistica): ?>
                    <div style="flex: 1; min-width: 200px; padding: 20px;">
                        <?php if (!empty($estadistica['icono'])): ?>
                            <div style="font-size: 40px; margin-bottom: 15px; color: #FF0000;">
                                <i class="fas fa-<?php echo $estadistica['icono']; ?>"></i>
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;" class="contador-animado" data-valor="<?php echo preg_replace('/[^0-9]/', '', $estadistica['valor']); ?>">
                            0
                        </div>
                        <div style="font-size: 16px; color: #666;"><?php echo $estadistica['titulo']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animación de contadores
            const contadores = document.querySelectorAll('.contador-animado');
            const velocidad = 2000; // Duración en milisegundos
            
            // Función para verificar si un elemento está visible en la ventana
            function esVisible(el) {
                const rect = el.getBoundingClientRect();
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
            
            // Función para animar el contador
            function animarContador(contador) {
                const valorFinal = parseInt(contador.getAttribute('data-valor'));
                const valorActual = parseInt(contador.innerText);
                const incremento = valorFinal / (velocidad / 16); // 60 FPS
                
                if (valorActual < valorFinal) {
                    contador.innerText = Math.ceil(valorActual + incremento);
                    setTimeout(function() {
                        animarContador(contador);
                    }, 16);
                } else {
                    contador.innerText = contador.getAttribute('data-valor').includes('+') ? 
                        '+' + valorFinal : valorFinal;
                }
            }
            
            // Iniciar animación cuando los contadores estén visibles
            function iniciarAnimaciones() {
                contadores.forEach(contador => {
                    if (esVisible(contador) && contador.innerText === '0') {
                        animarContador(contador);
                    }
                });
            }
            
            // Verificar visibilidad al cargar y al hacer scroll
            iniciarAnimaciones();
            window.addEventListener('scroll', iniciarAnimaciones);
        });
    </script>
    <?php
}

/**
 * Muestra la sección de blogs o guías de compra
 * 
 * @param array $blogs Arreglo de blogs
 */
function mostrar_blogs_guias($blogs) {
    if (empty($blogs)) {
        return;
    }
    ?>
    <div style="margin: 40px 0;">
        <h2 style="margin-bottom: 20px; font-size: 24px; text-align: center; color: #333; position: relative;">
            Guías y Consejos de Compra
            <span style="display: block; width: 50px; height: 3px; background-color: #FF0000; margin: 10px auto 0;"></span>
        </h2>
        
        <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-top: 30px;">
            <?php foreach ($blogs as $blog): ?>
                <div style="flex: 1; min-width: 300px; border: 1px solid #eee; border-radius: 8px; overflow: hidden; background-color: white; transition: transform 0.3s, box-shadow 0.3s;">
                    <div style="height: 200px; overflow: hidden;">
                        <?php if (!empty($blog['imagen'])): ?>
                            <img src="<?php echo BASE_URL; ?>/public/assets/images/blogs/<?php echo $blog['imagen']; ?>" alt="<?php echo $blog['titulo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                                <span style="color: #999;">Sin imagen</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="padding: 20px;">
                        <h3 style="margin-bottom: 10px; font-size: 18px;"><?php echo $blog['titulo']; ?></h3>
                        <p style="color: #666; margin-bottom: 15px;">
                            <?php echo substr(strip_tags($blog['contenido']), 0, 120) . '...'; ?>
                        </p>
                        <a href="<?php echo BASE_URL; ?>/public/blog.php?id=<?php echo $blog['id']; ?>" style="display: inline-block; padding: 8px 15px; background-color: #FF0000; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">
                            Leer más
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Muestra la sección de oferta con contador
 * 
 * @param array $oferta Datos de la oferta
 */
function mostrar_oferta_contador($oferta) {
    if (!$oferta) {
        return;
    }
    
    // Calcular tiempo restante
    $fecha_fin = new DateTime($oferta['fecha_fin']);
    $fecha_actual = new DateTime();
    $intervalo = $fecha_fin->diff($fecha_actual);
    
    // Solo mostrar si queda al menos un minuto
    if ($fecha_fin <= $fecha_actual) {
        return;
    }
    ?>
    <div style="margin: 40px 0; position: relative; overflow: hidden; border-radius: 8px;">
        <a href="<?php echo $oferta['url']; ?>" style="display: block; text-decoration: none; color: inherit;">
            <div style="position: relative;">
                <?php if (!empty($oferta['imagen'])): ?>
                    <img src="<?php echo BASE_URL; ?>/public/assets/images/ofertas/<?php echo $oferta['imagen']; ?>" alt="<?php echo $oferta['titulo']; ?>" style="width: 100%; height: 300px; object-fit: cover; filter: brightness(0.7);">
                <?php else: ?>
                    <div style="width: 100%; height: 300px; background: linear-gradient(135deg, #FF0000, #990000);"></div>
                <?php endif; ?>
                
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 20px; color: white;">
                    <h2 style="font-size: 32px; margin-bottom: 15px; text-shadow: 1px 1px 3px rgba(0,0,0,0.6);"><?php echo $oferta['titulo']; ?></h2>
                    
                    <?php if (!empty($oferta['descripcion'])): ?>
                        <p style="font-size: 18px; margin-bottom: 25px; max-width: 800px; text-shadow: 1px 1px 2px rgba(0,0,0,0.6);">
                            <?php echo $oferta['descripcion']; ?>
                        </p>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <div style="background-color: rgba(0,0,0,0.5); padding: 15px; border-radius: 5px; min-width: 80px; text-align: center;">
                            <div style="font-size: 28px; font-weight: 700;" id="contador-dias">
                                <?php echo $intervalo->days; ?>
                            </div>
                            <div style="font-size: 14px;">Días</div>
                        </div>
                        
                        <div style="background-color: rgba(0,0,0,0.5); padding: 15px; border-radius: 5px; min-width: 80px; text-align: center;">
                            <div style="font-size: 28px; font-weight: 700;" id="contador-horas">
                                <?php echo $intervalo->h; ?>
                            </div>
                            <div style="font-size: 14px;">Horas</div>
                        </div>
                        
                        <div style="background-color: rgba(0,0,0,0.5); padding: 15px; border-radius: 5px; min-width: 80px; text-align: center;">
                            <div style="font-size: 28px; font-weight: 700;" id="contador-minutos">
                                <?php echo $intervalo->i; ?>
                            </div>
                            <div style="font-size: 14px;">Minutos</div>
                        </div>
                        
                        <div style="background-color: rgba(0,0,0,0.5); padding: 15px; border-radius: 5px; min-width: 80px; text-align: center;">
                            <div style="font-size: 28px; font-weight: 700;" id="contador-segundos">
                                <?php echo $intervalo->s; ?>
                            </div>
                            <div style="font-size: 14px;">Segundos</div>
                        </div>
                    </div>
                    
                    <button style="background-color: white; color: #FF0000; border: none; padding: 12px 25px; font-size: 16px; font-weight: 700; border-radius: 4px; cursor: pointer; transition: background-color 0.3s, transform 0.3s;">
                        ¡Aprovechar ahora!
                    </button>
                </div>
            </div>
        </a>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Contador regresivo
            const fechaFin = new Date("<?php echo $oferta['fecha_fin']; ?>").getTime();
            
            const contadorInterval = setInterval(function() {
                const ahora = new Date().getTime();
                const diferencia = fechaFin - ahora;
                
                if (diferencia <= 0) {
                    clearInterval(contadorInterval);
                    document.getElementById('contador-dias').textContent = "0";
                    document.getElementById('contador-horas').textContent = "0";
                    document.getElementById('contador-minutos').textContent = "0";
                    document.getElementById('contador-segundos').textContent = "0";
                    return;
                }
                
                const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
                const horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
                const segundos = Math.floor((diferencia % (1000 * 60)) / 1000);
                
                document.getElementById('contador-dias').textContent = dias;
                document.getElementById('contador-horas').textContent = horas;
                document.getElementById('contador-minutos').textContent = minutos;
                document.getElementById('contador-segundos').textContent = segundos;
                
            }, 1000);
        });
    </script>