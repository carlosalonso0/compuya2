<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/product-functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/especificaciones-functions.php');

// Verificar slug del producto
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    redirect(BASE_URL);
}

$slug = $_GET['slug'];

// Obtener datos del producto
try {
    $stmt = $conn->prepare("SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id WHERE p.slug = ? AND p.activo = 1");
    $stmt->execute([$slug]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        redirect(BASE_URL);
    }
    
    // Obtener categoría padre si existe
    $categoria_padre = null;
    if ($producto['categoria_id']) {
        $stmt = $conn->prepare("SELECT * FROM categorias WHERE id = (SELECT categoria_padre_id FROM categorias WHERE id = ?)");
        $stmt->execute([$producto['categoria_id']]);
        $categoria_padre = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtener imágenes adicionales
    $stmt = $conn->prepare("SELECT * FROM imagenes_producto WHERE producto_id = ? ORDER BY orden ASC");
    $stmt->execute([$producto['id']]);
    $imagenes_adicionales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener especificaciones
    $especificaciones = obtener_especificaciones_producto($producto['id']);
    
    // Obtener productos relacionados (misma categoría)
    $stmt = $conn->prepare("
        SELECT * FROM productos 
        WHERE categoria_id = ? AND id != ? AND activo = 1
        ORDER BY RAND()
        LIMIT 6
    ");
    $stmt->execute([$producto['categoria_id'], $producto['id']]);
    $productos_relacionados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Error al obtener producto: " . $e->getMessage());
    redirect(BASE_URL);
}

// Calcular descuento si hay precio de oferta
$tiene_oferta = $producto['precio_oferta'] > 0 && $producto['precio_oferta'] < $producto['precio'];
$descuento = $tiene_oferta ? calcular_descuento($producto['precio'], $producto['precio_oferta']) : 0;

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/includes/header.php');
?>

<main style="padding: 20px 0;">
    <div class="container">
        <!-- Breadcrumb -->
        <div style="margin-bottom: 20px;">
            <a href="<?php echo BASE_URL; ?>" style="text-decoration: none; color: #333;">Inicio</a> &gt; 
            <?php if ($categoria_padre): ?>
                <a href="<?php echo BASE_URL . '/public/categoria.php?slug=' . $categoria_padre['slug']; ?>" style="text-decoration: none; color: #333;"><?php echo $categoria_padre['nombre']; ?></a> &gt; 
            <?php endif; ?>
            <a href="<?php echo BASE_URL . '/public/categoria.php?slug=' . $producto['categoria_slug']; ?>" style="text-decoration: none; color: #333;"><?php echo $producto['categoria_nombre']; ?></a> &gt; 
            <span style="font-weight: bold;"><?php echo $producto['nombre']; ?></span>
        </div>
</main>

<script>
    // Cambiar imagen principal al hacer clic en miniaturas
    function cambiarImagen(src, miniatura) {
        document.getElementById('imagen-principal').src = src;
        
        // Actualizar estilos de miniaturas
        const miniaturas = document.querySelectorAll('.miniatura');
        miniaturas.forEach(item => {
            item.style.border = '2px solid #ddd';
        });
        
        miniatura.style.border = '2px solid #FF0000';
    }
    
    // Añadir al carrito (funcionamiento básico para demostración)
    document.getElementById('btn-comprar').addEventListener('click', function() {
        const cantidad = document.getElementById('cantidad').value;
        alert('Producto añadido al carrito: ' + cantidad + ' unidad(es)');
        // Aquí implementarías la lógica real de carrito
    });
    
    document.getElementById('btn-comprar-ahora').addEventListener('click', function() {
        const cantidad = document.getElementById('cantidad').value;
        alert('Redirigiendo al proceso de compra directa: ' + cantidad + ' unidad(es)');
        // Aquí implementarías la lógica real de compra directa
    });
</script>

<?php
// Incluir el footer
?>
        
        <!-- Contenido del producto -->
        <div class="producto-detalle" style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 40px;">
            <!-- Galería de imágenes -->
            <div class="galeria" style="flex: 0 0 40%; min-width: 300px;">
                <!-- Imagen principal -->
                <div class="imagen-principal" style="margin-bottom: 15px; border: 1px solid #eee; border-radius: 5px; background-color: white; padding: 20px; display: flex; align-items: center; justify-content: center; height: 400px;">
                    <?php if (!empty($producto['imagen_principal'])): ?>
                        <img id="imagen-principal" src="<?php echo PRODUCTS_IMG_URL . '/' . $producto['imagen_principal']; ?>" alt="<?php echo $producto['nombre']; ?>" style="max-width: 100%; max-height: 350px; object-fit: contain;">
                    <?php else: ?>
                        <div style="width: 100%; height: 350px; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #999;">Sin imagen</div>
                    <?php endif; ?>
                </div>
                
                <!-- Miniaturas -->
                <?php if (!empty($producto['imagen_principal']) || !empty($imagenes_adicionales)): ?>
                <div class="miniaturas" style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-start;">
                    <?php if (!empty($producto['imagen_principal'])): ?>
                        <div class="miniatura active" style="width: 80px; height: 80px; border: 2px solid #FF0000; border-radius: 4px; overflow: hidden; cursor: pointer;" onclick="cambiarImagen('<?php echo PRODUCTS_IMG_URL . '/' . $producto['imagen_principal']; ?>', this)">
                            <img src="<?php echo PRODUCTS_IMG_URL . '/' . $producto['imagen_principal']; ?>" alt="Principal" style="width: 100%; height: 100%; object-fit: contain; padding: 5px;">
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($imagenes_adicionales as $imagen): ?>
                        <div class="miniatura" style="width: 80px; height: 80px; border: 2px solid #ddd; border-radius: 4px; overflow: hidden; cursor: pointer;" onclick="cambiarImagen('<?php echo PRODUCTS_IMG_URL . '/' . $imagen['ruta_imagen']; ?>', this)">
                            <img src="<?php echo PRODUCTS_IMG_URL . '/' . $imagen['ruta_imagen']; ?>" alt="Imagen adicional" style="width: 100%; height: 100%; object-fit: contain; padding: 5px;">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Información del producto -->
            <div class="info-producto" style="flex: 1; min-width: 300px;">
                <h1 style="font-size: 28px; margin-bottom: 5px;"><?php echo $producto['nombre']; ?></h1>
                
                <!-- Marca y SKU -->
                <div style="margin-bottom: 15px; color: #666;">
                    <?php if (!empty($producto['marca'])): ?>
                        <span style="margin-right: 20px;">Marca: <strong><?php echo $producto['marca']; ?></strong></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($producto['modelo'])): ?>
                        <span style="margin-right: 20px;">Modelo: <strong><?php echo $producto['modelo']; ?></strong></span>
                    <?php endif; ?>
                    
                    <span>SKU: <strong><?php echo $producto['sku']; ?></strong></span>
                </div>
                
                <!-- Precio -->
                <div style="margin: 20px 0; padding: 15px; background-color: #f9f9f9; border-radius: 5px;">
                    <?php if ($tiene_oferta): ?>
                        <div style="font-size: 16px; text-decoration: line-through; color: #999; margin-bottom: 5px;">
                            Precio regular: <?php echo formatear_precio($producto['precio']); ?>
                        </div>
                        <div style="font-size: 28px; font-weight: 700; color: #FF0000; display: flex; align-items: center;">
                            <?php echo formatear_precio($producto['precio_oferta']); ?>
                            <span style="font-size: 16px; background-color: #FF0000; color: white; padding: 3px 8px; border-radius: 4px; margin-left: 10px;">
                                <?php echo $descuento; ?>% OFF
                            </span>
                        </div>
                    <?php else: ?>
                        <div style="font-size: 28px; font-weight: 700; color: #333;">
                            <?php echo formatear_precio($producto['precio']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Stock -->
                    <div style="margin-top: 10px; font-size: 16px; color: <?php echo $producto['stock'] > 0 ? '#4CAF50' : '#FF0000'; ?>;">
                        <?php if ($producto['stock'] > 0): ?>
                            <span style="font-weight: 600;">En stock</span> (<?php echo $producto['stock']; ?> unidades disponibles)
                        <?php else: ?>
                            <span style="font-weight: 600;">Agotado</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Etiquetas -->
                <div style="margin-bottom: 20px; display: flex; gap: 10px;">
                    <?php if ($producto['nuevo']): ?>
                        <span style="background-color: #4CAF50; color: white; padding: 5px 10px; border-radius: 4px; font-size: 14px;">Nuevo</span>
                    <?php endif; ?>
                    
                    <?php if ($producto['destacado']): ?>
                        <span style="background-color: #FFC107; color: #333; padding: 5px 10px; border-radius: 4px; font-size: 14px;">Destacado</span>
                    <?php endif; ?>
                </div>
                
                <!-- Acciones -->
                <div style="margin-top: 30px;">
                    <?php if ($producto['stock'] > 0): ?>
                    <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
                        <label for="cantidad">Cantidad:</label>
                        <select id="cantidad" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 80px;">
                            <?php for ($i = 1; $i <= min(10, $producto['stock']); $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <button id="btn-comprar" class="btn-comprar" style="display: block; width: 100%; background-color: #FF0000; color: white; border: none; padding: 15px; border-radius: 4px; font-size: 16px; font-weight: 700; margin-bottom: 10px; cursor: pointer; transition: background-color 0.3s;">
                        Añadir al Carrito
                    </button>
                    
                    <button id="btn-comprar-ahora" class="btn-comprar-ahora" style="display: block; width: 100%; background-color: #333; color: white; border: none; padding: 15px; border-radius: 4px; font-size: 16px; font-weight: 700; cursor: pointer; transition: background-color 0.3s;">
                        Comprar Ahora
                    </button>
                    <?php else: ?>
                    <button disabled style="display: block; width: 100%; background-color: #ccc; color: #666; border: none; padding: 15px; border-radius: 4px; font-size: 16px; font-weight: 700; margin-bottom: 10px; cursor: not-allowed;">
                        Producto Agotado
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Descripción y especificaciones -->
        <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 40px;">
            <!-- Descripción -->
            <div style="flex: 1; min-width: 300px;">
                <div style="border: 1px solid #ddd; border-radius: 5px; background-color: white; padding: 20px;">
                    <h2 style="font-size: 20px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">Descripción</h2>
                    <div style="line-height: 1.6; color: #333;">
                        <?php echo nl2br($producto['descripcion']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Especificaciones -->
            <?php if (!empty($especificaciones)): ?>
            <div style="flex: 1; min-width: 300px;">
                <div style="border: 1px solid #ddd; border-radius: 5px; background-color: white; padding: 20px;">
                    <h2 style="font-size: 20px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">Especificaciones</h2>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tbody>
                            <?php foreach ($especificaciones as $index => $spec): ?>
                                <tr style="background-color: <?php echo $index % 2 == 0 ? '#f9f9f9' : 'white'; ?>;">
                                    <td style="padding: 10px; border-bottom: 1px solid #eee; font-weight: 600; width: 40%;"><?php echo $spec['nombre']; ?></td>
                                    <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo $spec['valor']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Productos relacionados -->
        <?php if (!empty($productos_relacionados)): ?>
        <div style="margin: 40px 0;">
            <h2 style="font-size: 24px; margin-bottom: 20px; text-align: center; color: #333; position: relative;">
                Productos Relacionados
                <span style="display: block; width: 50px; height: 3px; background-color: #FF0000; margin: 10px auto 0;"></span>
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
                <?php foreach ($productos_relacionados as $prod_rel): ?>
                    <div style="height: 100%;">
                        <?php include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/includes/product-card.php'); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/includes/footer.php');
?>
    
        <?php endif; ?>
    </div>