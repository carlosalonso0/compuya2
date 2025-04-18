<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/especificaciones-functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/product-functions.php');

// Verificar que se ha especificado un producto
if (!isset($_GET['id'])) {
    redirect(ADMIN_URL . '/productos.php');
}

$producto_id = (int)$_GET['id'];

// Obtener datos del producto
$producto = obtener_producto($producto_id);

if (!$producto) {
    redirect(ADMIN_URL . '/productos.php');
}

// Obtener especificaciones del producto
$especificaciones = obtener_especificaciones_producto($producto_id);

// Obtener imágenes adicionales
$stmt = $conn->prepare("SELECT * FROM imagenes_producto WHERE producto_id = ? ORDER BY orden ASC");
$stmt->execute([$producto_id]);
$imagenes_adicionales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario de producto
$mensaje = '';
$tipo_mensaje = '';

// Verificar si hay un mensaje en la URL
if (isset($_GET['mensaje'])) {
    if ($_GET['mensaje'] == 'creado') {
        $mensaje = "Producto creado correctamente.";
        $tipo_mensaje = "success";
    } elseif ($_GET['mensaje'] == 'actualizado') {
        $mensaje = "Producto actualizado correctamente.";
        $tipo_mensaje = "success";
    }
}

if (isset($_POST['actualizar_producto'])) {
    // Validar datos básicos
    if (empty($_POST['nombre']) || empty($_POST['precio']) || empty($_POST['categoria_id'])) {
        $mensaje = "Los campos Nombre, Precio y Categoría son obligatorios.";
        $tipo_mensaje = "danger";
    } else {
        try {
            $conn->beginTransaction();
            
            // Datos del producto
            $nombre = $_POST['nombre'];
            $precio = floatval(str_replace(',', '.', $_POST['precio']));
            $precio_oferta = !empty($_POST['precio_oferta']) ? floatval(str_replace(',', '.', $_POST['precio_oferta'])) : 0;
            $categoria_id = (int)$_POST['categoria_id'];
            $descripcion = $_POST['descripcion'];
            $stock = !empty($_POST['stock']) ? (int)$_POST['stock'] : 0;
            $marca = $_POST['marca'];
            $modelo = $_POST['modelo'];
            $destacado = isset($_POST['destacado']) ? 1 : 0;
            $nuevo = isset($_POST['nuevo']) ? 1 : 0;
            $activo = isset($_POST['activo']) ? 1 : 0;
            
            // Verificar si cambió la categoría, para regenerar el SKU si es necesario
            $regenerar_sku = $categoria_id != $producto['categoria_id'];
            
            // Procesar imagen si se ha subido
            if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
                $imagen_principal = admin_subir_imagen($_FILES['imagen_principal'], PRODUCTS_IMG_PATH);
                
                if ($imagen_principal) {
                    // Eliminar imagen anterior si existe
                    if (!empty($producto['imagen_principal'])) {
                        $ruta_imagen = PRODUCTS_IMG_PATH . '/' . $producto['imagen_principal'];
                        if (file_exists($ruta_imagen)) {
                            unlink($ruta_imagen);
                        }
                    }
                } else {
                    throw new Exception("Error al subir la imagen principal.");
                }
            } else {
                $imagen_principal = $producto['imagen_principal'];
            }
            
            // Actualizar el producto
            $sql = "
                UPDATE productos SET
                    nombre = ?,
                    precio = ?,
                    precio_oferta = ?,
                    descripcion = ?,
                    stock = ?,
                    categoria_id = ?,
                    marca = ?,
                    modelo = ?,
                    destacado = ?,
                    nuevo = ?,
                    activo = ?,
                    imagen_principal = ?
            ";
            
            $params = [
                $nombre,
                $precio,
                $precio_oferta,
                $descripcion,
                $stock,
                $categoria_id,
                $marca,
                $modelo,
                $destacado,
                $nuevo,
                $activo,
                $imagen_principal
            ];
            
            // Si cambió la categoría, regenerar el SKU
            if ($regenerar_sku) {
                $sku = generate_sku($nombre, $categoria_id, $modelo);
                $sql .= ", sku = ?";
                $params[] = $sku;
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $producto_id;
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            
            // Procesar las especificaciones
            if (isset($_POST['spec_nombre']) && is_array($_POST['spec_nombre'])) {
                $especificaciones_nuevas = [];
                
                for ($i = 0; $i < count($_POST['spec_nombre']); $i++) {
                    if (!empty($_POST['spec_nombre'][$i]) && !empty($_POST['spec_valor'][$i])) {
                        $especificaciones_nuevas[] = [
                            'nombre' => $_POST['spec_nombre'][$i],
                            'valor' => $_POST['spec_valor'][$i]
                        ];
                    }
                }
                
                guardar_especificaciones_producto($producto_id, $especificaciones_nuevas);
            }
            
            // Procesar imágenes adicionales nuevas
            if (isset($_FILES['imagenes_adicionales']) && is_array($_FILES['imagenes_adicionales']['name'])) {
                $orden_maximo = 0;
                
                // Obtener el máximo orden actual
                if (!empty($imagenes_adicionales)) {
                    foreach ($imagenes_adicionales as $img) {
                        if ($img['orden'] > $orden_maximo) {
                            $orden_maximo = $img['orden'];
                        }
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO imagenes_producto (producto_id, ruta_imagen, orden) VALUES (?, ?, ?)");
                
                for ($i = 0; $i < count($_FILES['imagenes_adicionales']['name']); $i++) {
                    if ($_FILES['imagenes_adicionales']['error'][$i] == 0) {
                        // Crear un array temporal para el archivo actual
                        $archivo_temp = [
                            'name' => $_FILES['imagenes_adicionales']['name'][$i],
                            'type' => $_FILES['imagenes_adicionales']['type'][$i],
                            'tmp_name' => $_FILES['imagenes_adicionales']['tmp_name'][$i],
                            'error' => $_FILES['imagenes_adicionales']['error'][$i],
                            'size' => $_FILES['imagenes_adicionales']['size'][$i]
                        ];
                        
                        $imagen_nombre = admin_subir_imagen($archivo_temp, PRODUCTS_IMG_PATH);
                        
                        if ($imagen_nombre) {
                            $orden_maximo++;
                            $stmt->execute([$producto_id, $imagen_nombre, $orden_maximo]);
                        }
                    }
                }
            }
            
            // Eliminar imágenes seleccionadas
            if (isset($_POST['eliminar_imagen']) && is_array($_POST['eliminar_imagen'])) {
                foreach ($_POST['eliminar_imagen'] as $imagen_id) {
                    $stmt = $conn->prepare("SELECT ruta_imagen FROM imagenes_producto WHERE id = ? AND producto_id = ?");
                    $stmt->execute([$imagen_id, $producto_id]);
                    $imagen = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($imagen) {
                        // Eliminar archivo físico
                        $ruta_imagen = PRODUCTS_IMG_PATH . '/' . $imagen['ruta_imagen'];
                        if (file_exists($ruta_imagen)) {
                            unlink($ruta_imagen);
                        }
                        
                        // Eliminar de la base de datos
                        $stmt = $conn->prepare("DELETE FROM imagenes_producto WHERE id = ?");
                        $stmt->execute([$imagen_id]);
                    }
                }
            }
            
            $conn->commit();
            
            $mensaje = "Producto actualizado correctamente.";
            $tipo_mensaje = "success";
            
            // Recargar los datos del producto
            $producto = obtener_producto($producto_id);
            $especificaciones = obtener_especificaciones_producto($producto_id);
            
            $stmt = $conn->prepare("SELECT * FROM imagenes_producto WHERE producto_id = ? ORDER BY orden ASC");
            $stmt->execute([$producto_id]);
            $imagenes_adicionales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            // Verificar si hay una transacción activa antes de hacer rollBack
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener categorías para el selector
$categorias = admin_obtener_categorias_select();

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Editar Producto: <?php echo $producto['nombre']; ?></h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; 
        <a href="<?php echo ADMIN_URL; ?>/productos.php">Productos</a> &gt; 
        Editar Producto
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Formulario de producto -->
<form action="" method="post" enctype="multipart/form-data">
    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
        <!-- Panel de navegación lateral -->
        <div style="flex: 0 0 200px;">
            <div class="admin-card">
                <div class="admin-card-title">Navegación</div>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><a href="#informacion-basica" style="text-decoration: none; color: #007bff;">Información Básica</a></li>
                    <li style="margin-bottom: 10px;"><a href="#imagenes" style="text-decoration: none; color: #007bff;">Imágenes</a></li>
                    <li style="margin-bottom: 10px;"><a href="#especificaciones" style="text-decoration: none; color: #007bff;">Especificaciones</a></li>
                </ul>
                
                <div style="margin-top: 20px;">
                    <button type="submit" name="actualizar_producto" class="btn btn-success" style="width: 100%;">Guardar Cambios</button>
                    <a href="<?php echo ADMIN_URL; ?>/productos.php" class="btn btn-danger" style="width: 100%; margin-top: 10px;">Cancelar</a>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="<?php echo BASE_URL; ?>/public/producto.php?slug=<?php echo $producto['slug']; ?>" target="_blank" class="btn btn-primary" style="width: 100%;">Ver en Tienda</a>
                </div>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div style="flex: 1; min-width: 600px;">
            <!-- Información Básica -->
            <div id="informacion-basica" class="admin-card">
                <div class="admin-card-title">Información Básica</div>
                
                <div class="form-group">
                    <label for="sku" class="form-label">SKU</label>
                    <input type="text" id="sku" class="form-control" value="<?php echo $producto['sku']; ?>" readonly>
                    <small>El SKU se genera automáticamente y cambiará si modifica la categoría.</small>
                </div>
                
                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre del Producto *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" required value="<?php echo $producto['nombre']; ?>">
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="precio" class="form-label">Precio *</label>
                        <input type="text" name="precio" id="precio" class="form-control" required value="<?php echo $producto['precio']; ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="precio_oferta" class="form-label">Precio de Oferta</label>
                        <input type="text" name="precio_oferta" id="precio_oferta" class="form-control" value="<?php echo $producto['precio_oferta']; ?>">
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="categoria_id" class="form-label">Categoría *</label>
                        <select name="categoria_id" id="categoria_id" class="form-select" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias as $id => $nombre): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($producto['categoria_id'] == $id) ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" name="stock" id="stock" class="form-control" value="<?php echo $producto['stock']; ?>">
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="marca" class="form-label">Marca</label>
                        <input type="text" name="marca" id="marca" class="form-control" value="<?php echo $producto['marca']; ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="modelo" class="form-label">Modelo</label>
                        <input type="text" name="modelo" id="modelo" class="form-control" value="<?php echo $producto['modelo']; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea name="descripcion" id="descripcion" class="form-control" rows="5"><?php echo $producto['descripcion']; ?></textarea>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-check" style="flex: 1;">
                        <input type="checkbox" name="destacado" id="destacado" class="form-check-input" <?php echo $producto['destacado'] ? 'checked' : ''; ?>>
                        <label for="destacado" class="form-check-label">Producto Destacado</label>
                    </div>
                    
                    <div class="form-check" style="flex: 1;">
                        <input type="checkbox" name="nuevo" id="nuevo" class="form-check-input" <?php echo $producto['nuevo'] ? 'checked' : ''; ?>>
                        <label for="nuevo" class="form-check-label">Producto Nuevo</label>
                    </div>
                    
                    <div class="form-check" style="flex: 1;">
                        <input type="checkbox" name="activo" id="activo" class="form-check-input" <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                        <label for="activo" class="form-check-label">Producto Activo</label>
                    </div>
                </div>
            </div>
            
            <!-- Imágenes -->
            <div id="imagenes" class="admin-card">
                <div class="admin-card-title">Imágenes del Producto</div>
                
                <div class="form-group">
                    <label for="imagen_principal" class="form-label">Imagen Principal</label>
                    <input type="file" name="imagen_principal" id="imagen_principal" class="form-control" accept="image/*">
                    
                    <?php if (!empty($producto['imagen_principal'])): ?>
                    <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                        <img src="<?php echo PRODUCTS_IMG_URL . '/' . $producto['imagen_principal']; ?>" alt="<?php echo $producto['nombre']; ?>" style="max-width: 100px; max-height: 100px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                        <div>
                            <p>Imagen actual. Sube una nueva imagen para reemplazarla.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Imágenes Adicionales</label>
                    <input type="file" name="imagenes_adicionales[]" class="form-control" multiple accept="image/*">
                    <small>Puede seleccionar múltiples archivos para añadir más imágenes.</small>
                </div>
                
                <?php if (!empty($imagenes_adicionales)): ?>
                <div style="margin-top: 20px;">
                    <h4>Imágenes Actuales</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                        <?php foreach ($imagenes_adicionales as $imagen): ?>
                        <div style="width: 150px; position: relative; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                            <img src="<?php echo PRODUCTS_IMG_URL . '/' . $imagen['ruta_imagen']; ?>" alt="Imagen adicional" style="width: 100%; height: 120px; object-fit: contain;">
                            <div style="margin-top: 5px; display: flex; justify-content: center;">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="eliminar_imagen[]" value="<?php echo $imagen['id']; ?>" style="margin-right: 5px;">
                                    Eliminar
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p style="margin-top: 10px; color: #dc3545;"><small>Marque las casillas para eliminar las imágenes seleccionadas al guardar.</small></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Especificaciones -->
            <div id="especificaciones" class="admin-card">
                <div class="admin-card-title">Especificaciones del Producto</div>
                
                <div id="especificaciones-container">
                    <?php if (!empty($especificaciones)): ?>
                        <?php foreach ($especificaciones as $spec): ?>
                            <div style="display: flex; margin-bottom: 10px; align-items: center; background-color: #f8f9fa; padding: 10px; border-radius: 4px;">
                                <div style="flex: 1; margin-right: 10px;">
                                    <input type="text" name="spec_nombre[]" value="<?php echo $spec['nombre']; ?>" class="form-control" <?php echo in_array($spec['nombre'], ['Procesador', 'Memoria RAM', 'Almacenamiento', 'Tarjeta de Video']) ? 'readonly' : ''; ?>>
                                </div>
                                <div style="flex: 2; margin-right: 10px;">
                                    <input type="text" name="spec_valor[]" value="<?php echo $spec['valor']; ?>" class="form-control">
                                </div>
                                <div>
                                    <button type="button" class="btn btn-danger" onclick="eliminarEspecificacion(this)">x</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No hay especificaciones definidas para este producto.</p>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 10px;">
                    <button type="button" class="btn btn-primary" onclick="agregarEspecificacion()">Añadir Especificación</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // Función para añadir una nueva especificación personalizada
    function agregarEspecificacion() {
        const container = document.getElementById('especificaciones-container');
        const row = document.createElement('div');
        row.style.display = 'flex';
        row.style.marginBottom = '10px';
        row.style.alignItems = 'center';
        row.style.backgroundColor = '#f8f9fa';
        row.style.padding = '10px';
        row.style.borderRadius = '4px';
        
        row.innerHTML = `
            <div style="flex: 1; margin-right: 10px;">
                <input type="text" name="spec_nombre[]" placeholder="Nombre" class="form-control">
            </div>
            <div style="flex: 2; margin-right: 10px;">
                <input type="text" name="spec_valor[]" placeholder="Valor" class="form-control">
            </div>
            <div>
                <button type="button" class="btn btn-danger" onclick="eliminarEspecificacion(this)">x</button>
            </div>
        `;
        
        container.appendChild(row);
    }
    
    // Función para eliminar una especificación
    function eliminarEspecificacion(button) {
        const row = button.closest('div[style*="display: flex"]');
        row.remove();
    }
    
    // Vista previa de imágenes
    document.getElementById('imagen_principal').addEventListener('change', function(e) {
        mostrarVistaPrevia(this.files[0], 'principal');
    });
    
    document.querySelector('input[name="imagenes_adicionales[]"]').addEventListener('change', function(e) {
        const previewContainer = document.createElement('div');
        previewContainer.style.display = 'flex';
        previewContainer.style.flexWrap = 'wrap';
        previewContainer.style.gap = '15px';
        previewContainer.style.marginTop = '20px';
        
        const heading = document.createElement('h4');
        heading.textContent = 'Nuevas imágenes a añadir';
        heading.style.width = '100%';
        
        previewContainer.appendChild(heading);
        
        for (let i = 0; i < this.files.length; i++) {
            const file = this.files[i];
            
            if (!file || !file.type.match('image.*')) {
                continue;
            }
            
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const previewDiv = document.createElement('div');
                previewDiv.style.width = '150px';
                previewDiv.style.border = '1px solid #ddd';
                previewDiv.style.borderRadius = '4px';
                previewDiv.style.padding = '5px';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100%';
                img.style.height = '120px';
                img.style.objectFit = 'contain';
                
                const label = document.createElement('div');
                label.style.marginTop = '5px';
                label.style.textAlign = 'center';
                label.textContent = 'Nueva imagen';
                
                previewDiv.appendChild(img);
                previewDiv.appendChild(label);
                previewContainer.appendChild(previewDiv);
            };
            
            reader.readAsDataURL(file);
        }
        
        // Insertar después del input de imágenes adicionales
        const inputContainer = this.parentNode;
        
        // Verificar si ya hay una vista previa y removerla
        const existingPreview = inputContainer.nextElementSibling;
        if (existingPreview && existingPreview.hasAttribute('data-preview')) {
            existingPreview.remove();
        }
        
        previewContainer.setAttribute('data-preview', 'true');
        inputContainer.parentNode.insertBefore(previewContainer, inputContainer.nextSibling);
    });
    
    function mostrarVistaPrevia(file, id) {
        if (!file || !file.type.match('image.*')) {
            return;
        }
        
        const reader = new FileReader();
        const input = document.getElementById('imagen_principal');
        
        reader.onload = function(e) {
            // Crear contenedor para la vista previa
            const previewContainer = document.createElement('div');
            previewContainer.style.marginTop = '10px';
            previewContainer.style.display = 'flex';
            previewContainer.style.alignItems = 'center';
            previewContainer.style.gap = '10px';
            previewContainer.setAttribute('data-preview', 'true');
            
            // Crear imagen
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.maxWidth = '100px';
            img.style.maxHeight = '100px';
            img.style.objectFit = 'contain';
            img.style.border = '1px solid #ddd';
            img.style.borderRadius = '4px';
            img.style.padding = '5px';
            
            // Crear texto
            const text = document.createElement('div');
            text.innerHTML = '<p>Nueva imagen principal. Reemplazará la actual al guardar.</p>';
            
            // Añadir al contenedor
            previewContainer.appendChild(img);
            previewContainer.appendChild(text);
            
            // Buscar si ya hay una vista previa
            const parent = input.parentNode;
            const existingPreview = parent.querySelector('[data-preview="true"]');
            
            if (existingPreview) {
                existingPreview.remove();
            }
            
            // Añadir después del input
            parent.appendChild(previewContainer);
        };
        
        reader.readAsDataURL(file);
    }
</script>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>