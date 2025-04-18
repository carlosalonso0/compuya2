<?php
/**
 * Tarjeta de producto reutilizable
 * Requiere que se le pase un array con los datos del producto
 * 
 * @param array $producto Datos del producto
 */

// Si no hay producto, salir
if (!isset($producto) || empty($producto)) {
    return;
}

// Calcular descuento si hay precio de oferta
$tiene_oferta = $producto['precio_oferta'] > 0 && $producto['precio_oferta'] < $producto['precio'];
$descuento = $tiene_oferta ? calcular_descuento($producto['precio'], $producto['precio_oferta']) : 0;

// Verificar ruta de imagen
$imagen = !empty($producto['imagen_principal']) 
    ? PRODUCTS_IMG_URL . '/' . htmlspecialchars($producto['imagen_principal'])
    : IMAGES_URL . '/placeholder-product.jpg';

// URL del producto
$producto_url = BASE_URL . '/public/producto.php?slug=' . urlencode($producto['slug']);
?>

<div style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background-color: white; transition: transform 0.3s ease; height: 100%; display: flex; flex-direction: column; position: relative;">
    <!-- Etiquetas de oferta y nuevo -->
    <?php if ($tiene_oferta): ?>
    <div style="position: absolute; top: 10px; left: 0; background-color: #FF0000; color: white; padding: 5px 10px; font-weight: bold; z-index: 1;">
        -<?php echo $descuento; ?>%
    </div>
    <?php endif; ?>
    
    <?php if (isset($producto['nuevo']) && $producto['nuevo']): ?>
    <div style="position: absolute; top: 10px; right: 0; background-color: #4CAF50; color: white; padding: 5px 10px; font-weight: bold; z-index: 1;">
        Nuevo
    </div>
    <?php endif; ?>
    
    <!-- Imagen del producto -->
    <a href="<?php echo $producto_url; ?>" style="display: block; padding: 15px; text-align: center; flex: 0 0 200px;">
        <img src="<?php echo $imagen; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" style="max-width: 100%; max-height: 170px; object-fit: contain;">
    </a>
    
    <!-- Información del producto -->
    <div style="padding: 15px; flex-grow: 1; display: flex; flex-direction: column;">
        <!-- Marca -->
        <div style="color: #666; font-size: 14px; margin-bottom: 5px;">
            <?php echo isset($producto['marca']) ? htmlspecialchars($producto['marca']) : ''; ?>
        </div>
        
        <!-- Nombre del producto -->
        <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 600; line-height: 1.3; flex-grow: 1;">
            <a href="<?php echo $producto_url; ?>" style="color: #333; text-decoration: none;">
                <?php echo htmlspecialchars($producto['nombre']); ?>
            </a>
        </h3>
        
        <!-- Precios -->
        <div style="margin-top: auto;">
            <?php if ($tiene_oferta): ?>
            <div style="text-decoration: line-through; color: #999; font-size: 14px;">
                <?php echo formatear_precio($producto['precio']); ?>
            </div>
            <div style="font-size: 20px; font-weight: 700; color: #FF0000; margin: 5px 0;">
                <?php echo formatear_precio($producto['precio_oferta']); ?>
            </div>
            <?php else: ?>
            <div style="font-size: 20px; font-weight: 700; color: #333; margin: 5px 0;">
                <?php echo formatear_precio($producto['precio']); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Stock -->
        <div style="color: <?php echo isset($producto['stock']) && $producto['stock'] > 0 ? '#4CAF50' : '#FF0000'; ?>; font-size: 14px; margin: 5px 0;">
            <?php echo isset($producto['stock']) && $producto['stock'] > 0 ? 'En stock' : 'Agotado'; ?>
        </div>
        
        <!-- Botón de acción -->
        <a href="<?php echo $producto_url; ?>" style="display: inline-block; background-color: #FF0000; color: white; text-align: center; padding: 10px; border-radius: 4px; text-decoration: none; width: 100%; margin-top: 10px; font-weight: 600;">
            Ver Producto
        </a>
    </div>
</div>