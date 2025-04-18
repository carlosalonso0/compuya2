<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/product-functions.php');

// Verificar slug de categoría
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    redirect(BASE_URL);
}

$slug = $_GET['slug'];

// Obtener datos de la categoría
try {
    $stmt = $conn->prepare("SELECT * FROM categorias WHERE slug = ? AND activo = 1");
    $stmt->execute([$slug]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$categoria) {
        redirect(BASE_URL);
    }
    
    // Si es una subcategoría, obtener la categoría padre
    $categoria_padre = null;
    if ($categoria['categoria_padre_id']) {
        $stmt = $conn->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->execute([$categoria['categoria_padre_id']]);
        $categoria_padre = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obtener subcategorías si es una categoría principal
    $subcategorias = [];
    $stmt = $conn->prepare("SELECT * FROM categorias WHERE categoria_padre_id = ? AND activo = 1 ORDER BY nombre ASC");
    $stmt->execute([$categoria['id']]);
    $subcategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Error al obtener categoría: " . $e->getMessage());
    redirect(BASE_URL);
}

// Paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$items_por_pagina = 12;
$offset = ($pagina - 1) * $items_por_pagina;

// Filtros
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'nuevo';
$precio_min = isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : 0;
$precio_max = isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : 0;
$marcas = isset($_GET['marcas']) && is_array($_GET['marcas']) ? $_GET['marcas'] : [];

// Validar orden
$ordenes_validos = ['nuevo', 'precio_asc', 'precio_desc', 'nombre_asc', 'nombre_desc'];
if (!in_array($orden, $ordenes_validos)) {
    $orden = 'nuevo';
}

// Construir consulta base para productos
$sql_base = "FROM productos WHERE activo = 1";
$params = [];

// Filtrar por categoría
if (!empty($subcategorias)) {
    // Si es categoría principal, incluir también productos de subcategorías
    $subcategoria_ids = array_column($subcategorias, 'id');
    $subcategoria_ids[] = $categoria['id'];
    $placeholders = implode(',', array_fill(0, count($subcategoria_ids), '?'));
    
    $sql_base .= " AND categoria_id IN ($placeholders)";
    $params = array_merge($params, $subcategoria_ids);
} else {
    $sql_base .= " AND categoria_id = ?";
    $params[] = $categoria['id'];
}

// Filtrar por precio
if ($precio_min > 0) {
    $sql_base .= " AND (precio_oferta > 0 AND precio_oferta >= ? OR (precio_oferta = 0 AND precio >= ?))";
    $params[] = $precio_min;
    $params[] = $precio_min;
}

if ($precio_max > 0) {
    $sql_base .= " AND (precio_oferta > 0 AND precio_oferta <= ? OR (precio_oferta = 0 AND precio <= ?))";
    $params[] = $precio_max;
    $params[] = $precio_max;
}

// Filtrar por marcas
if (!empty($marcas)) {
    $placeholders = implode(',', array_fill(0, count($marcas), '?'));
    $sql_base .= " AND marca IN ($placeholders)";
    $params = array_merge($params, $marcas);
}

// Ordenamiento
$sql_orden = '';
switch ($orden) {
    case 'precio_asc':
        $sql_orden = "ORDER BY CASE WHEN precio_oferta > 0 THEN precio_oferta ELSE precio END ASC";
        break;
    case 'precio_desc':
        $sql_orden = "ORDER BY CASE WHEN precio_oferta > 0 THEN precio_oferta ELSE precio END DESC";
        break;
    case 'nombre_asc':
        $sql_orden = "ORDER BY nombre ASC";
        break;
    case 'nombre_desc':
        $sql_orden = "ORDER BY nombre DESC";
        break;
    default: // nuevo
        $sql_orden = "ORDER BY id DESC";
}

// Consulta para el total de resultados
$sql_count = "SELECT COUNT(*) as total $sql_base";
$stmt = $conn->prepare($sql_count);
$stmt->execute($params);
$total_resultados = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Calcular total de páginas
$total_paginas = ceil($total_resultados / $items_por_pagina);

// Consulta para obtener los productos paginados
$sql = "SELECT * $sql_base $sql_orden LIMIT $offset, $items_por_pagina";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener marcas disponibles para filtrar
$sql_marcas = "SELECT DISTINCT marca $sql_base";
$stmt = $conn->prepare($sql_marcas);
$stmt->execute($params);
$marcas_disponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener rango de precios para filtrar
$sql_precios = "SELECT 
                    MIN(CASE WHEN precio_oferta > 0 THEN precio_oferta ELSE precio END) as min_precio,
                    MAX(CASE WHEN precio_oferta > 0 THEN precio_oferta ELSE precio END) as max_precio
                $sql_base";
$stmt = $conn->prepare($sql_precios);
$stmt->execute($params);
$rango_precios = $stmt->fetch(PDO::FETCH_ASSOC);

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
            <span style="font-weight: bold;"><?php echo $categoria['nombre']; ?></span>
        </div>
        
        <!-- Título de la categoría -->
        <div style="margin-bottom: 30px;">
            <h1 style="font-size: 28px; margin-bottom: 10px;"><?php echo $categoria['nombre']; ?></h1>
            <!-- Descripción de la categoría si existe -->
            <?php if (!empty($categoria['descripcion'])): ?>
                <p style="color: #666;"><?php echo $categoria['descripcion']; ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Subcategorías si existen -->
        <?php if (!empty($subcategorias)): ?>
        <div style="margin-bottom: 30px;">
            <h2 style="font-size: 20px; margin-bottom: 15px;">Subcategorías</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 15px;">
                <?php foreach ($subcategorias as $subcategoria): ?>
                    <a href="<?php echo BASE_URL . '/public/categoria.php?slug=' . $subcategoria['slug']; ?>" style="text-decoration: none; color: #333; display: block; width: 150px;">
                        <div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; text-align: center; transition: transform 0.3s ease, box-shadow 0.3s ease;">
                            <?php if (!empty($subcategoria['imagen'])): ?>
                                <img src="<?php echo CATEGORIES_IMG_URL . '/' . $subcategoria['imagen']; ?>" alt="<?php echo $subcategoria['nombre']; ?>" style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 10px;">
                            <?php else: ?>
                                <div style="width: 80px; height: 80px; background-color: #f5f5f5; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                    <span style="color: #999;">Sin imagen</span>
                                </div>
                            <?php endif; ?>
                            <div style="font-weight: 600;"><?php echo $subcategoria['nombre']; ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <!-- Filtros -->
            <div style="flex: 0 0 250px; margin-bottom: 20px;">
                <div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px; background-color: white;">
                    <h3 style="font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee;">Filtros</h3>
                    
                    <form action="" method="get" id="filtroForm">
                        <input type="hidden" name="slug" value="<?php echo $slug; ?>">
                        
                        <!-- Ordenar por -->
                        <div style="margin-bottom: 20px;">
                            <label for="orden" style="font-weight: bold; display: block; margin-bottom: 8px;">Ordenar por</label>
                            <select name="orden" id="orden" class="form-control" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" onchange="submitForm()">
                                <option value="nuevo" <?php echo $orden == 'nuevo' ? 'selected' : ''; ?>>Más recientes</option>
                                <option value="precio_asc" <?php echo $orden == 'precio_asc' ? 'selected' : ''; ?>>Precio: de menor a mayor</option>
                                <option value="precio_desc" <?php echo $orden == 'precio_desc' ? 'selected' : ''; ?>>Precio: de mayor a menor</option>
                                <option value="nombre_asc" <?php echo $orden == 'nombre_asc' ? 'selected' : ''; ?>>Nombre: A-Z</option>
                                <option value="nombre_desc" <?php echo $orden == 'nombre_desc' ? 'selected' : ''; ?>>Nombre: Z-A</option>
                            </select>
                        </div>
                        
                        <!-- Filtro de precio -->
                        <div style="margin-bottom: 20px;">
                            <label style="font-weight: bold; display: block; margin-bottom: 8px;">Rango de precio</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" name="precio_min" id="precio_min" placeholder="Mín" value="<?php echo $precio_min > 0 ? $precio_min : ''; ?>" style="width: 45%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                <span>-</span>
                                <input type="number" name="precio_max" id="precio_max" placeholder="Máx" value="<?php echo $precio_max > 0 ? $precio_max : ''; ?>" style="width: 45%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div style="margin-top: 10px; font-size: 12px; color: #666;">
                                Rango disponible: <?php echo formatear_precio($rango_precios['min_precio']); ?> - <?php echo formatear_precio($rango_precios['max_precio']); ?>
                            </div>
                        </div>
                        
                        <!-- Filtro de marcas -->
                        <?php if (!empty($marcas_disponibles)): ?>
                        <div style="margin-bottom: 20px;">
                            <label style="font-weight: bold; display: block; margin-bottom: 8px;">Marcas</label>
                            <div style="max-height: 200px; overflow-y: auto; padding-right: 10px;">
                                <?php foreach ($marcas_disponibles as $marca): ?>
                                    <?php if (!empty($marca)): ?>
                                    <div style="margin-bottom: 8px;">
                                        <label style="display: flex; align-items: center; cursor: pointer;">
                                            <input type="checkbox" name="marcas[]" value="<?php echo $marca; ?>" <?php echo in_array($marca, $marcas) ? 'checked' : ''; ?> style="margin-right: 8px;" onchange="submitForm()">
                                            <?php echo $marca; ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Botones de acción -->
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" class="btn" style="flex: 1; background-color: #FF0000; color: white; border: none; padding: 10px; border-radius: 4px; cursor: pointer;">Aplicar</button>
                            <a href="<?php echo BASE_URL . '/public/categoria.php?slug=' . $slug; ?>" class="btn" style="flex: 1; background-color: #666; color: white; border: none; padding: 10px; border-radius: 4px; text-align: center; text-decoration: none;">Limpiar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Lista de productos -->
            <div style="flex: 1; min-width: 0;">
                <?php if (empty($productos)): ?>
                    <div style="text-align: center; padding: 40px 20px; background-color: white; border-radius: 5px; border: 1px solid #ddd;">
                        <h3 style="margin-bottom: 10px; color: #666;">No se encontraron productos</h3>
                        <p>Prueba a cambiar los filtros de búsqueda.</p>
                    </div>
                <?php else: ?>
                    <!-- Contador de resultados y paginación -->
                    <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Total:</strong> <?php echo $total_resultados; ?> productos
                        </div>
                        
                        <?php if ($total_paginas > 1): ?>
                        <div>
                            <div style="display: flex; gap: 5px; align-items: center;">
                                <span>Página:</span>
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <a href="?slug=<?php echo $slug; ?>&pagina=<?php echo $i; ?><?php echo $orden != 'nuevo' ? '&orden=' . $orden : ''; ?><?php echo $precio_min > 0 ? '&precio_min=' . $precio_min : ''; ?><?php echo $precio_max > 0 ? '&precio_max=' . $precio_max : ''; ?><?php foreach ($marcas as $marca) echo '&marcas[]=' . urlencode($marca); ?>" style="display: inline-block; min-width: 30px; height: 30px; line-height: 30px; text-align: center; border: 1px solid #ddd; border-radius: 3px; text-decoration: none; color: <?php echo $i == $pagina ? 'white' : '#333'; ?>; background-color: <?php echo $i == $pagina ? '#FF0000' : 'white'; ?>;">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Grid de productos -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px;">
                        <?php foreach ($productos as $producto): ?>
                            <div style="height: 100%;">
                                <?php include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/includes/product-card.php'); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Paginación inferior -->
                    <?php if ($total_paginas > 1): ?>
                    <div style="margin-top: 30px; display: flex; justify-content: center;">
                        <div style="display: flex; gap: 5px; align-items: center;">
                            <?php if ($pagina > 1): ?>
                                <a href="?slug=<?php echo $slug; ?>&pagina=1<?php echo $orden != 'nuevo' ? '&orden=' . $orden : ''; ?><?php echo $precio_min > 0 ? '&precio_min=' . $precio_min : ''; ?><?php echo $precio_max > 0 ? '&precio_max=' . $precio_max : ''; ?><?php foreach ($marcas as $marca) echo '&marcas[]=' . urlencode($marca); ?>" style="display: inline-block; padding: 5px 10px; border: 1px solid #ddd; border-radius: 3px; text-decoration: none; color: #333;">
                                    &laquo; Primera
                                </a>
                                <a href="?slug=<?php echo $slug; ?>&pagina=<?php echo $pagina - 1; ?><?php echo $orden != 'nuevo' ? '&orden=' . $orden : ''; ?><?php echo $precio_min > 0 ? '&precio_min=' . $precio_min : ''; ?><?php echo $precio_max > 0 ? '&precio_max=' . $precio_max : ''; ?><?php foreach ($marcas as $marca) echo '&marcas[]=' . urlencode($marca); ?>" style="display: inline-block; padding: 5px 10px; border: 1px solid #ddd; border-radius: 3px; text-decoration: none; color: #333;">
                                    &lt; Anterior
                                </a>
                            <?php endif; ?>
                            
                            <span style="margin: 0 10px;">Página <?php echo $pagina; ?> de <?php echo $total_paginas; ?></span>
                            
                            <?php if ($pagina < $total_paginas): ?>
                                <a href="?slug=<?php echo $slug; ?>&pagina=<?php echo $pagina + 1; ?><?php echo $orden != 'nuevo' ? '&orden=' . $orden : ''; ?><?php echo $precio_min > 0 ? '&precio_min=' . $precio_min : ''; ?><?php echo $precio_max > 0 ? '&precio_max=' . $precio_max : ''; ?><?php foreach ($marcas as $marca) echo '&marcas[]=' . urlencode($marca); ?>" style="display: inline-block; padding: 5px 10px; border: 1px solid #ddd; border-radius: 3px; text-decoration: none; color: #333;">
                                    Siguiente &gt;
                                </a>
                                <a href="?slug=<?php echo $slug; ?>&pagina=<?php echo $total_paginas; ?><?php echo $orden != 'nuevo' ? '&orden=' . $orden : ''; ?><?php echo $precio_min > 0 ? '&precio_min=' . $precio_min : ''; ?><?php echo $precio_max > 0 ? '&precio_max=' . $precio_max : ''; ?><?php foreach ($marcas as $marca) echo '&marcas[]=' . urlencode($marca); ?>" style="display: inline-block; padding: 5px 10px; border: 1px solid #ddd; border-radius: 3px; text-decoration: none; color: #333;">
                                    Última &raquo;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    function submitForm() {
        document.getElementById('filtroForm').submit();
    }
</script>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/includes/footer.php');
?>