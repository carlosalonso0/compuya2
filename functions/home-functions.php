<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');

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

