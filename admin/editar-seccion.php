<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/product-functions.php');

// Verificar que se haya especificado una sección
if (!isset($_GET['id'])) {
    redirect(ADMIN_URL . '/editar-inicio.php');
}

// Obtener la sección
$seccion_id = $_GET['id'];
$seccion = admin_obtener_seccion($seccion_id);

if (!$seccion) {
    redirect(ADMIN_URL . '/editar-inicio.php');
}

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

// Gestión de productos en la sección (para tipo carrusel o categoria)
if (isset($_POST['actualizar_productos']) && ($seccion['tipo'] == 'carrusel' || $seccion['tipo'] == 'categoria')) {
    $producto_ids = isset($_POST['producto_ids']) ? $_POST['producto_ids'] : [];
    
    if (admin_actualizar_productos_seccion($seccion_id, $producto_ids)) {
        $mensaje = "Productos actualizados correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar los productos.";
        $tipo_mensaje = "danger";
    }
}

// Gestión de banners dobles (para tipo banner_doble)
if (isset($_POST['actualizar_banner_izquierda']) && $seccion['tipo'] == 'banner_doble') {
    // Obtener banner existente si hay
    $banners = admin_obtener_banners_dobles($seccion_id);
    $banner_izquierda = null;
    
    foreach ($banners as $banner) {
        if ($banner['posicion'] == 'izquierda') {
            $banner_izquierda = $banner;
            break;
        }
    }
    
    $datos = [
        'seccion_id' => $seccion_id,
        'titulo' => $_POST['titulo_izquierda'],
        'descripcion' => $_POST['descripcion_izquierda'],
        'url' => $_POST['url_izquierda'],
        'posicion' => 'izquierda',
        'activo' => isset($_POST['activo_izquierda']) ? 1 : 0
    ];
    
    // Si se ha subido una imagen
    if (isset($_FILES['imagen_izquierda']) && $_FILES['imagen_izquierda']['error'] == 0) {
        $imagen_nombre = admin_subir_imagen($_FILES['imagen_izquierda'], BANNERS_IMG_PATH);
        if ($imagen_nombre) {
            $datos['imagen'] = $imagen_nombre;
            
            // Eliminar imagen anterior si existe
            if ($banner_izquierda && !empty($banner_izquierda['imagen'])) {
                $ruta_imagen = BANNERS_IMG_PATH . '/' . $banner_izquierda['imagen'];
                if (file_exists($ruta_imagen)) {
                    unlink($ruta_imagen);
                }
            }
        }
    }
    
    if ($banner_izquierda) {
        // Actualizar
        if (admin_actualizar_banner_doble($banner_izquierda['id'], $datos)) {
            $mensaje = "Banner izquierdo actualizado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el banner izquierdo.";
            $tipo_mensaje = "danger";
        }
    } else {
        // Crear nuevo
        if (!isset($datos['imagen'])) {
            $mensaje = "Debe seleccionar una imagen para el banner izquierdo.";
            $tipo_mensaje = "danger";
        } else {
            if (admin_crear_banner_doble($datos)) {
                $mensaje = "Banner izquierdo creado correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al crear el banner izquierdo.";
                $tipo_mensaje = "danger";
            }
        }
    }
}

if (isset($_POST['actualizar_banner_derecha']) && $seccion['tipo'] == 'banner_doble') {
    // Obtener banner existente si hay
    $banners = admin_obtener_banners_dobles($seccion_id);
    $banner_derecha = null;
    
    foreach ($banners as $banner) {
        if ($banner['posicion'] == 'derecha') {
            $banner_derecha = $banner;
            break;
        }
    }
    
    $datos = [
        'seccion_id' => $seccion_id,
        'titulo' => $_POST['titulo_derecha'],
        'descripcion' => $_POST['descripcion_derecha'],
        'url' => $_POST['url_derecha'],
        'posicion' => 'derecha',
        'activo' => isset($_POST['activo_derecha']) ? 1 : 0
    ];
    
    // Si se ha subido una imagen
    if (isset($_FILES['imagen_derecha']) && $_FILES['imagen_derecha']['error'] == 0) {
        $imagen_nombre = admin_subir_imagen($_FILES['imagen_derecha'], BANNERS_IMG_PATH);
        if ($imagen_nombre) {
            $datos['imagen'] = $imagen_nombre;
            
            // Eliminar imagen anterior si existe
            if ($banner_derecha && !empty($banner_derecha['imagen'])) {
                $ruta_imagen = BANNERS_IMG_PATH . '/' . $banner_derecha['imagen'];
                if (file_exists($ruta_imagen)) {
                    unlink($ruta_imagen);
                }
            }
        }
    }
    
    if ($banner_derecha) {
        // Actualizar
        if (admin_actualizar_banner_doble($banner_derecha['id'], $datos)) {
            $mensaje = "Banner derecho actualizado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el banner derecho.";
            $tipo_mensaje = "danger";
        }
    } else {
        // Crear nuevo
        if (!isset($datos['imagen'])) {
            $mensaje = "Debe seleccionar una imagen para el banner derecho.";
            $tipo_mensaje = "danger";
        } else {
            if (admin_crear_banner_doble($datos)) {
                $mensaje = "Banner derecho creado correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al crear el banner derecho.";
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Buscar productos para selector
$productos_busqueda = [];
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $productos_busqueda = admin_buscar_productos($_GET['q']);
}

// Obtener productos actuales de la sección
$productos_seccion = admin_obtener_productos_seccion($seccion_id);

// Obtener banners dobles si es tipo banner_doble
$banners_dobles = [];
$banner_izquierda = null;
$banner_derecha = null;

if ($seccion['tipo'] == 'banner_doble') {
    $banners_dobles = admin_obtener_banners_dobles($seccion_id);
    
    foreach ($banners_dobles as $banner) {
        if ($banner['posicion'] == 'izquierda') {
            $banner_izquierda = $banner;
        } elseif ($banner['posicion'] == 'derecha') {
            $banner_derecha = $banner;
        }
    }
}

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Editar Sección: <?php echo $seccion['titulo_mostrar']; ?></h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; 
        <a href="<?php echo ADMIN_URL; ?>/editar-inicio.php">Editar Inicio</a> &gt; 
        Editar Sección
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Información de la sección -->
<div class="admin-card">
    <div class="admin-card-title">Información de la Sección</div>
    
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <div style="flex: 1; min-width: 200px;">
            <p><strong>Nombre interno:</strong> <?php echo $seccion['nombre']; ?></p>
            <p><strong>Título mostrado:</strong> <?php echo $seccion['titulo_mostrar']; ?></p>
            <p><strong>Tipo:</strong> <?php echo ucfirst($seccion['tipo']); ?></p>
            <p><strong>Estado:</strong> 
                <span style="color: <?php echo $seccion['activo'] ? '#28a745' : '#dc3545'; ?>;">
                    <?php echo $seccion['activo'] ? 'Activo' : 'Inactivo'; ?>
                </span>
            </p>
            
            <?php if ($seccion['tipo'] == 'categoria' && $seccion['categoria_id']): ?>
                <?php 
                $stmt = $conn->prepare("SELECT nombre FROM categorias WHERE id = ?");
                $stmt->execute([$seccion['categoria_id']]);
                $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <p><strong>Categoría:</strong> <?php echo $categoria ? $categoria['nombre'] : 'No disponible'; ?></p>
            <?php endif; ?>
        </div>
        
        <div style="flex: 1; min-width: 200px; text-align: right;">
            <a href="<?php echo ADMIN_URL; ?>/editar-inicio.php" class="btn btn-primary">Volver a Editar Inicio</a>
        </div>
    </div>
</div>

<?php if ($seccion['tipo'] == 'carrusel' || $seccion['tipo'] == 'categoria'): ?>
<!-- Gestión de productos en la sección -->
<div class="admin-card">
    <div class="admin-card-title">Gestionar Productos en la Sección</div>
    
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <!-- Lista de productos actuales -->
        <div style="flex: 1; min-width: 300px;">
            <h3>Productos Actuales</h3>
            <p>Arrastra para cambiar el orden. Haz clic en "Eliminar" para quitar un producto.</p>
            
            <form id="productosForm" method="post" action="">
                <ul id="listaProductos" class="sortable-list">
                    <?php foreach ($productos_seccion as $producto): ?>
                        <li class="sortable-item" data-id="<?php echo $producto['id']; ?>">
                            <div>
                                <input type="hidden" name="producto_ids[]" value="<?php echo $producto['id']; ?>">
                                <img src="<?php echo !empty($producto['imagen_principal']) ? PRODUCTS_IMG_URL . '/' . $producto['imagen_principal'] : IMAGES_URL . '/placeholder-product.jpg'; ?>" alt="<?php echo $producto['nombre']; ?>" style="width: 50px; height: 50px; object-fit: contain; margin-right: 10px;">
                                <span><?php echo $producto['nombre']; ?></span>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="quitarProducto(this)">Eliminar</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if (empty($productos_seccion)): ?>
                    <p>No hay productos en esta sección. Añade productos desde el buscador.</p>
                <?php endif; ?>
                
                <div class="mt-10">
                    <button type="submit" name="actualizar_productos" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
        
        <!-- Buscador de productos -->
        <div style="flex: 1; min-width: 300px;">
            <h3>Buscar Productos</h3>
            <p>Busca productos para añadir a la sección.</p>
            
            <div class="form-group">
                <div style="display: flex;">
                    <input type="text" id="buscarProducto" class="form-control" placeholder="Buscar por nombre o SKU..." style="flex: 1; margin-right: 10px;">
                    <button type="button" class="btn btn-primary" onclick="buscarProductos()">Buscar</button>
                </div>
            </div>
            
            <div id="resultadosBusqueda" style="margin-top: 20px;">
                <?php if (!empty($productos_busqueda)): ?>
                    <h4>Resultados de búsqueda</h4>
                    <ul class="sortable-list">
                        <?php foreach ($productos_busqueda as $producto): ?>
                            <li class="sortable-item" data-id="<?php echo $producto['id']; ?>">
                                <div>
                                    <img src="<?php echo !empty($producto['imagen_principal']) ? PRODUCTS_IMG_URL . '/' . $producto['imagen_principal'] : IMAGES_URL . '/placeholder-product.jpg'; ?>" alt="<?php echo $producto['nombre']; ?>" style="width: 50px; height: 50px; object-fit: contain; margin-right: 10px;">
                                    <span><?php echo $producto['nombre']; ?></span>
                                </div>
                                <button type="button" class="btn btn-success btn-sm" onclick="agregarProducto(<?php echo $producto['id']; ?>, '<?php echo addslashes($producto['nombre']); ?>', '<?php echo addslashes(!empty($producto['imagen_principal']) ? $producto['imagen_principal'] : 'placeholder-product.jpg'); ?>')">Añadir</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Funciones para buscar y gestionar productos
    function buscarProductos() {
        const termino = document.getElementById('buscarProducto').value;
        if (termino.length < 2) {
            alert('Ingrese al menos 2 caracteres para buscar.');
            return;
        }
        
        // Redirigir a la misma página con el parámetro de búsqueda
        window.location.href = '<?php echo ADMIN_URL; ?>/editar-seccion.php?id=<?php echo $seccion_id; ?>&q=' + encodeURIComponent(termino);
    }
    
    // También buscar al presionar Enter
    document.getElementById('buscarProducto').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarProductos();
        }
    });
    
    function agregarProducto(id, nombre, imagen) {
        // Verificar si el producto ya está en la lista
        const listaProductos = document.getElementById('listaProductos');
        const productosExistentes = listaProductos.querySelectorAll('input[name="producto_ids[]"]');
        
        for (let input of productosExistentes) {
            if (input.value == id) {
                alert('Este producto ya está en la lista.');
                return;
            }
        }
        
        // Crear nuevo elemento de lista
        const li = document.createElement('li');
        li.className = 'sortable-item';
        li.dataset.id = id;
        
        const imgSrc = imagen !== 'placeholder-product.jpg' 
            ? '<?php echo PRODUCTS_IMG_URL; ?>/' + imagen
            : '<?php echo IMAGES_URL; ?>/placeholder-product.jpg';
        
        li.innerHTML = `
            <div>
                <input type="hidden" name="producto_ids[]" value="${id}">
                <img src="${imgSrc}" alt="${nombre}" style="width: 50px; height: 50px; object-fit: contain; margin-right: 10px;">
                <span>${nombre}</span>
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="quitarProducto(this)">Eliminar</button>
        `;
        
        // Añadir a la lista
        listaProductos.appendChild(li);
    }
    
    function quitarProducto(boton) {
        const li = boton.closest('li');
        li.remove();
    }
    
    // Hacer la lista ordenable
    document.addEventListener('DOMContentLoaded', function() {
        const lista = document.getElementById('listaProductos');
        if (!lista) return;
        
        let draggedItem = null;
        
        lista.querySelectorAll('li').forEach(function(item) {
            item.setAttribute('draggable', 'true');
            
            item.addEventListener('dragstart', function() {
                draggedItem = this;
                setTimeout(() => this.style.opacity = '0.5', 0);
            });
            
            item.addEventListener('dragend', function() {
                draggedItem = null;
                this.style.opacity = '1';
            });
            
            item.addEventListener('dragover', function(e) {
                e.preventDefault();
            });
            
            item.addEventListener('dragenter', function(e) {
                e.preventDefault();
                this.style.background = '#f5f5f5';
            });
            
            item.addEventListener('dragleave', function() {
                this.style.background = '';
            });
            
            item.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.background = '';
                if (draggedItem !== this) {
                    const items = Array.from(lista.querySelectorAll('li'));
                    const indexDragged = items.indexOf(draggedItem);
                    const indexTarget = items.indexOf(this);
                    
                    if (indexDragged < indexTarget) {
                        lista.insertBefore(draggedItem, this.nextElementSibling);
                    } else {
                        lista.insertBefore(draggedItem, this);
                    }
                }
            });
        });
    });
</script>
<?php endif; ?>

<?php if ($seccion['tipo'] == 'banner_doble'): ?>
<!-- Gestión de banners dobles -->
<div class="admin-card">
    <div class="admin-card-title">Banner Izquierdo</div>
    
    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="imagen_izquierda" class="form-label">Imagen</label>
            <input type="file" name="imagen_izquierda" id="imagen_izquierda" class="form-control">
            <?php if ($banner_izquierda && !empty($banner_izquierda['imagen'])): ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo BANNERS_IMG_URL . '/' . $banner_izquierda['imagen']; ?>" alt="Banner actual" style="max-width: 200px;">
                    <p>Imagen actual. Sube una nueva imagen para reemplazarla.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="titulo_izquierda" class="form-label">Título</label>
            <input type="text" name="titulo_izquierda" id="titulo_izquierda" class="form-control" value="<?php echo $banner_izquierda ? $banner_izquierda['titulo'] : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="descripcion_izquierda" class="form-label">Descripción</label>
            <textarea name="descripcion_izquierda" id="descripcion_izquierda" class="form-control" rows="3"><?php echo $banner_izquierda ? $banner_izquierda['descripcion'] : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="url_izquierda" class="form-label">URL de destino</label>
            <input type="text" name="url_izquierda" id="url_izquierda" class="form-control" value="<?php echo $banner_izquierda ? $banner_izquierda['url'] : '#'; ?>">
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo_izquierda" value="1" class="form-check-input" <?php echo (!$banner_izquierda || $banner_izquierda['activo']) ? 'checked' : ''; ?>>
                <span class="form-check-label">Banner Activo</span>
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" name="actualizar_banner_izquierda" class="btn btn-primary">
                <?php echo $banner_izquierda ? 'Actualizar Banner' : 'Guardar Banner'; ?>
            </button>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-title">Banner Derecho</div>
    
    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="imagen_derecha" class="form-label">Imagen</label>
            <input type="file" name="imagen_derecha" id="imagen_derecha" class="form-control">
            <?php if ($banner_derecha && !empty($banner_derecha['imagen'])): ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo BANNERS_IMG_URL . '/' . $banner_derecha['imagen']; ?>" alt="Banner actual" style="max-width: 200px;">
                    <p>Imagen actual. Sube una nueva imagen para reemplazarla.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="titulo_derecha" class="form-label">Título</label>
            <input type="text" name="titulo_derecha" id="titulo_derecha" class="form-control" value="<?php echo $banner_derecha ? $banner_derecha['titulo'] : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="descripcion_derecha" class="form-label">Descripción</label>
            <textarea name="descripcion_derecha" id="descripcion_derecha" class="form-control" rows="3"><?php echo $banner_derecha ? $banner_derecha['descripcion'] : ''; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="url_derecha" class="form-label">URL de destino</label>
            <input type="text" name="url_derecha" id="url_derecha" class="form-control" value="<?php echo $banner_derecha ? $banner_derecha['url'] : '#'; ?>">
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo_derecha" value="1" class="form-check-input" <?php echo (!$banner_derecha || $banner_derecha['activo']) ? 'checked' : ''; ?>>
                <span class="form-check-label">Banner Activo</span>
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" name="actualizar_banner_derecha" class="btn btn-primary">
                <?php echo $banner_derecha ? 'Actualizar Banner' : 'Guardar Banner'; ?>
            </button>
        </div>
    </form>
</div>
<!-- Vista previa de los banners -->
<div class="admin-card">
    <div class="admin-card-title">Vista Previa de Banners Dobles</div>
    
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <div style="flex: 1; min-width: 300px;">
            <h3>Banner Izquierdo</h3>
            <?php if ($banner_izquierda && !empty($banner_izquierda['imagen'])): ?>
                <div style="position: relative; overflow: hidden; border-radius: 8px; height: 200px;">
                    <img src="<?php echo BANNERS_IMG_URL . '/' . $banner_izquierda['imagen']; ?>" alt="<?php echo $banner_izquierda['titulo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white;">
                        <h3 style="margin: 0; font-size: 20px;"><?php echo $banner_izquierda['titulo']; ?></h3>
                        <p style="margin: 5px 0 0 0;"><?php echo $banner_izquierda['descripcion']; ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div style="height: 200px; background-color: #f5f5f5; border-radius: 8px; display: flex; justify-content: center; align-items: center;">
                    <p>No hay banner configurado</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="flex: 1; min-width: 300px;">
            <h3>Banner Derecho</h3>
            <?php if ($banner_derecha && !empty($banner_derecha['imagen'])): ?>
                <div style="position: relative; overflow: hidden; border-radius: 8px; height: 200px;">
                    <img src="<?php echo BANNERS_IMG_URL . '/' . $banner_derecha['imagen']; ?>" alt="<?php echo $banner_derecha['titulo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 20px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: white;">
                        <h3 style="margin: 0; font-size: 20px;"><?php echo $banner_derecha['titulo']; ?></h3>
                        <p style="margin: 5px 0 0 0;"><?php echo $banner_derecha['descripcion']; ?></p>
                    </div>
                </div>
            <?php else: ?>
                <div style="height: 200px; background-color: #f5f5f5; border-radius: 8px; display: flex; justify-content: center; align-items: center;">
                    <p>No hay banner configurado</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>