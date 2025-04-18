<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/product-functions.php'); // Añade esta línea

// Procesamiento de acciones
$mensaje = '';
$tipo_mensaje = '';

// Activar/Desactivar producto
if (isset($_GET['accion']) && $_GET['accion'] == 'toggle_activo' && isset($_GET['id'])) {
    $producto_id = (int)$_GET['id'];
    
    try {
        // Obtener estado actual
        $stmt = $conn->prepare("SELECT activo FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($producto) {
            $nuevo_estado = $producto['activo'] ? 0 : 1;
            
            // Actualizar estado
            $stmt = $conn->prepare("UPDATE productos SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $producto_id]);
            
            $mensaje = "Estado del producto actualizado correctamente.";
            $tipo_mensaje = "success";
        }
    } catch(PDOException $e) {
        $mensaje = "Error al actualizar el estado del producto: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Eliminar producto
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    $producto_id = (int)$_GET['id'];
    
    try {
        $conn->beginTransaction();
        
        // Obtener imágenes para eliminar archivos físicos
        $stmt = $conn->prepare("SELECT imagen_principal FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Eliminar imagen principal
        if ($producto && !empty($producto['imagen_principal'])) {
            $ruta_imagen = PRODUCTS_IMG_PATH . '/' . $producto['imagen_principal'];
            if (file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }
        }
        
        // Obtener imágenes adicionales
        $stmt = $conn->prepare("SELECT ruta_imagen FROM imagenes_producto WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Eliminar imágenes adicionales
        foreach ($imagenes as $imagen) {
            $ruta_imagen = PRODUCTS_IMG_PATH . '/' . $imagen['ruta_imagen'];
            if (file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }
        }
        
        // Eliminar registros en la base de datos
        // Las tablas con foreign keys se eliminarán automáticamente por ON DELETE CASCADE
        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$producto_id]);
        
        $conn->commit();
        
        $mensaje = "Producto eliminado correctamente.";
        $tipo_mensaje = "success";
    } catch(PDOException $e) {
        $conn->rollBack();
        $mensaje = "Error al eliminar el producto: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Paginación y filtrado
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$items_por_pagina = 10;
$offset = ($pagina - 1) * $items_por_pagina;

// Búsqueda y filtros
$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$categoria_id = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;
$ordenar_por = isset($_GET['ordenar_por']) ? $_GET['ordenar_por'] : 'id';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'DESC';

// Validar ordenamiento para evitar inyección SQL
$ordenamientos_validos = ['id', 'nombre', 'precio', 'categoria_id', 'stock', 'activo'];
if (!in_array($ordenar_por, $ordenamientos_validos)) {
    $ordenar_por = 'id';
}

$ordenes_validos = ['ASC', 'DESC'];
if (!in_array($orden, $ordenes_validos)) {
    $orden = 'DESC';
}

// Construir la consulta base
$sql_base = "FROM productos p
             LEFT JOIN categorias c ON p.categoria_id = c.id
             WHERE 1=1";

$params = [];

// Añadir condiciones de búsqueda
if (!empty($busqueda)) {
    $sql_base .= " AND (p.nombre LIKE ? OR p.sku LIKE ? OR p.descripcion LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
}

// Filtro por categoría
if ($categoria_id > 0) {
    $sql_base .= " AND p.categoria_id = ?";
    $params[] = $categoria_id;
}

// Consulta para el total de resultados
$sql_count = "SELECT COUNT(*) as total $sql_base";
$stmt = $conn->prepare($sql_count);
$stmt->execute($params);
$total_resultados = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calcular total de páginas
$total_paginas = ceil($total_resultados / $items_por_pagina);

// Consulta para obtener los productos paginados
$sql = "SELECT p.*, c.nombre as categoria_nombre $sql_base
        ORDER BY p.$ordenar_por $orden
        LIMIT $offset, $items_por_pagina";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el filtro
$categorias = admin_obtener_categorias_select();

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Gestión de Productos</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; Productos
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Filtros y búsqueda -->
<div class="admin-card">
    <div class="admin-card-title">Buscar y Filtrar</div>
    
    <form action="" method="get" class="d-flex" style="flex-wrap: wrap; gap: 10px; align-items: flex-end;">
        <div style="flex: 2; min-width: 200px;">
            <label for="buscar" class="form-label">Buscar</label>
            <input type="text" name="buscar" id="buscar" class="form-control" placeholder="Nombre, SKU o descripción" value="<?php echo $busqueda; ?>">
        </div>
        
        <div style="flex: 1; min-width: 150px;">
            <label for="categoria_id" class="form-label">Categoría</label>
            <select name="categoria_id" id="categoria_id" class="form-select">
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $id => $nombre): ?>
                    <option value="<?php echo $id; ?>" <?php echo $categoria_id == $id ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="flex: 1; min-width: 150px;">
            <label for="ordenar_por" class="form-label">Ordenar por</label>
            <select name="ordenar_por" id="ordenar_por" class="form-select">
                <option value="id" <?php echo $ordenar_por == 'id' ? 'selected' : ''; ?>>ID</option>
                <option value="nombre" <?php echo $ordenar_por == 'nombre' ? 'selected' : ''; ?>>Nombre</option>
                <option value="precio" <?php echo $ordenar_por == 'precio' ? 'selected' : ''; ?>>Precio</option>
                <option value="stock" <?php echo $ordenar_por == 'stock' ? 'selected' : ''; ?>>Stock</option>
            </select>
        </div>
        
        <div style="flex: 0 0 auto; min-width: 150px;">
            <label for="orden" class="form-label">Orden</label>
            <select name="orden" id="orden" class="form-select">
                <option value="ASC" <?php echo $orden == 'ASC' ? 'selected' : ''; ?>>Ascendente</option>
                <option value="DESC" <?php echo $orden == 'DESC' ? 'selected' : ''; ?>>Descendente</option>
            </select>
        </div>
        
        <div style="flex: 0 0 auto;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?php echo ADMIN_URL; ?>/productos.php" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<!-- Botones de acción -->
<div style="margin: 20px 0; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <a href="<?php echo ADMIN_URL; ?>/producto-crear.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Nuevo Producto
        </a>
        <a href="<?php echo ADMIN_URL; ?>/importar-productos.php" class="btn btn-primary">
            <i class="fas fa-file-import"></i> Importar Productos
        </a>
    </div>
    
    <div>
        <span>Total: <?php echo $total_resultados; ?> productos</span>
    </div>
</div>

<!-- Lista de productos -->
<div class="admin-card">
    <div class="admin-card-title">Productos</div>
    
    <?php if (empty($productos)): ?>
        <p class="text-center">No se encontraron productos con los criterios seleccionados.</p>
    <?php else: ?>
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Imagen</th>
                    <th>SKU</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Oferta</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?php echo $producto['id']; ?></td>
                    <td>
                        <?php if (!empty($producto['imagen_principal'])): ?>
                            <img src="<?php echo PRODUCTS_IMG_URL . '/' . $producto['imagen_principal']; ?>" alt="<?php echo $producto['nombre']; ?>" style="width: 50px; height: 50px; object-fit: contain;">
                        <?php else: ?>
                            <div style="width: 50px; height: 50px; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #999;">Sin imagen</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $producto['sku']; ?></td>
                    <td><?php echo $producto['nombre']; ?></td>
                    <td><?php echo $producto['categoria_nombre']; ?></td>
                    <td><?php echo formatear_precio($producto['precio']); ?></td>
                    <td>
                        <?php if ($producto['precio_oferta'] > 0 && $producto['precio_oferta'] < $producto['precio']): ?>
                            <?php echo formatear_precio($producto['precio_oferta']); ?>
                            <span style="color: #dc3545;">(<?php echo calcular_descuento($producto['precio'], $producto['precio_oferta']); ?>% off)</span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?php echo $producto['stock']; ?></td>
                    <td>
                        <span style="color: <?php echo $producto['activo'] ? '#28a745' : '#dc3545'; ?>;">
                            <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 5px;">
                            <a href="<?php echo ADMIN_URL; ?>/producto-editar.php?id=<?php echo $producto['id']; ?>" class="btn btn-primary btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo ADMIN_URL; ?>/productos.php?accion=toggle_activo&id=<?php echo $producto['id']; ?>" class="btn btn-<?php echo $producto['activo'] ? 'warning' : 'success'; ?> btn-sm" title="<?php echo $producto['activo'] ? 'Desactivar' : 'Activar'; ?>">
                                <i class="fas fa-<?php echo $producto['activo'] ? 'eye-slash' : 'eye'; ?>"></i>
                            </a>
                            <a href="<?php echo ADMIN_URL; ?>/productos.php?accion=eliminar&id=<?php echo $producto['id']; ?>" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Está seguro que desea eliminar este producto? Esta acción no se puede deshacer.');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
    <div style="margin-top: 20px; display: flex; justify-content: center;">
        <ul style="display: flex; list-style: none; gap: 5px; padding: 0;">
            <?php if ($pagina > 1): ?>
                <li>
                    <a href="?pagina=1<?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?><?php echo $categoria_id > 0 ? '&categoria_id=' . $categoria_id : ''; ?><?php echo !empty($ordenar_por) ? '&ordenar_por=' . $ordenar_por : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="btn btn-sm btn-outline-primary">
                        &laquo; Primera
                    </a>
                </li>
                <li>
                    <a href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?><?php echo $categoria_id > 0 ? '&categoria_id=' . $categoria_id : ''; ?><?php echo !empty($ordenar_por) ? '&ordenar_por=' . $ordenar_por : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="btn btn-sm btn-outline-primary">
                        &lt; Anterior
                    </a>
                </li>
            <?php endif; ?>
            
            <?php
            // Mostrar enlaces de página
            $pagina_inicio = max(1, $pagina - 2);
            $pagina_fin = min($total_paginas, $pagina + 2);
            
            for ($i = $pagina_inicio; $i <= $pagina_fin; $i++):
            ?>
                <li>
                    <a href="?pagina=<?php echo $i; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?><?php echo $categoria_id > 0 ? '&categoria_id=' . $categoria_id : ''; ?><?php echo !empty($ordenar_por) ? '&ordenar_por=' . $ordenar_por : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="btn btn-sm <?php echo $i == $pagina ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if ($pagina < $total_paginas): ?>
                <li>
                    <a href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?><?php echo $categoria_id > 0 ? '&categoria_id=' . $categoria_id : ''; ?><?php echo !empty($ordenar_por) ? '&ordenar_por=' . $ordenar_por : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="btn btn-sm btn-outline-primary">
                        Siguiente &gt;
                    </a>
                </li>
                <li>
                    <a href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?><?php echo $categoria_id > 0 ? '&categoria_id=' . $categoria_id : ''; ?><?php echo !empty($ordenar_por) ? '&ordenar_por=' . $ordenar_por : ''; ?><?php echo !empty($orden) ? '&orden=' . $orden : ''; ?>" class="btn btn-sm btn-outline-primary">
                        Última &raquo;
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php endif; ?>
</div>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>