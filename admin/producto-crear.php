<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/especificaciones-functions.php');

// Procesar el formulario de producto
$mensaje = '';
$tipo_mensaje = '';

if (isset($_POST['guardar_producto'])) {
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
            
            // Generar slug
            $slug = generate_slug($nombre);
            
            // Verificar si el slug ya existe
            $stmt = $conn->prepare("SELECT COUNT(*) FROM productos WHERE slug = ?");
            $stmt->execute([$slug]);
            $slug_existe = $stmt->fetchColumn() > 0;
            
            if ($slug_existe) {
                // Añadir un sufijo único al slug
                $slug = $slug . '-' . time();
            }
            
            // Procesar imagen si se ha subido
            $imagen_principal = '';
            if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
                $imagen_principal = admin_subir_imagen($_FILES['imagen_principal'], PRODUCTS_IMG_PATH);
                
                if (!$imagen_principal) {
                    throw new Exception("Error al subir la imagen principal.");
                }
            }
            
            // Insertar el producto
            $stmt = $conn->prepare("
                INSERT INTO productos (
                    sku, nombre, slug, precio, precio_oferta, descripcion,
                    stock, categoria_id, marca, modelo, destacado, nuevo, activo, imagen_principal
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Generar SKU
            $sku = generate_sku($nombre, $categoria_id, $modelo);
            
            $stmt->execute([
                $sku,
                $nombre,
                $slug,
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
            ]);
            
            $producto_id = $conn->lastInsertId();
            
            // Procesar las especificaciones
            if (isset($_POST['spec_nombre']) && is_array($_POST['spec_nombre'])) {
                $especificaciones = [];
                
                for ($i = 0; $i < count($_POST['spec_nombre']); $i++) {
                    if (!empty($_POST['spec_nombre'][$i]) && !empty($_POST['spec_valor'][$i])) {
                        $especificaciones[] = [
                            'nombre' => $_POST['spec_nombre'][$i],
                            'valor' => $_POST['spec_valor'][$i]
                        ];
                    }
                }
                
                guardar_especificaciones_producto($producto_id, $especificaciones);
            }
            
            // Procesar imágenes adicionales
            if (isset($_FILES['imagenes_adicionales']) && is_array($_FILES['imagenes_adicionales']['name'])) {
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
                            $stmt->execute([$producto_id, $imagen_nombre, $i]);
                        }
                    }
                }
            }
            
            $conn->commit();
            
            $mensaje = "Producto guardado correctamente con ID: $producto_id";
            $tipo_mensaje = "success";
            
            // Redirigir a la página de edición
            header("Location: " . ADMIN_URL . "/producto-editar.php?id=$producto_id&mensaje=creado");
            exit;
            
        } catch (Exception $e) {
            $conn->rollBack();
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
    <h1 class="admin-title">Crear Nuevo Producto</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; 
        <a href="<?php echo ADMIN_URL; ?>/productos.php">Productos</a> &gt; 
        Crear Producto
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
                    <button type="submit" name="guardar_producto" class="btn btn-success" style="width: 100%;">Guardar Producto</button>
                    <a href="<?php echo ADMIN_URL; ?>/productos.php" class="btn btn-danger" style="width: 100%; margin-top: 10px;">Cancelar</a>
                </div>
            </div>
        </div>
        
        <!-- Contenido principal -->
        <div style="flex: 1; min-width: 600px;">
            <!-- Información Básica -->
            <div id="informacion-basica" class="admin-card">
                <div class="admin-card-title">Información Básica</div>
                
                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre del Producto *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" required value="<?php echo isset($_POST['nombre']) ? $_POST['nombre'] : ''; ?>">
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="precio" class="form-label">Precio *</label>
                        <input type="text" name="precio" id="precio" class="form-control" required value="<?php echo isset($_POST['precio']) ? $_POST['precio'] : ''; ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="precio_oferta" class="form-label">Precio de Oferta</label>
                        <input type="text" name="precio_oferta" id="precio_oferta" class="form-control" value="<?php echo isset($_POST['precio_oferta']) ? $_POST['precio_oferta'] : ''; ?>">
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="categoria_id" class="form-label">Categoría *</label>
                        <select name="categoria_id" id="categoria_id" class="form-select" required onchange="cargarEspecificacionesCategoria()">
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias as $id => $nombre): ?>
                                <option value="<?php echo $id; ?>" <?php echo (isset($_POST['categoria_id']) && $_POST['categoria_id'] == $id) ? 'selected' : ''; ?>><?php echo $nombre; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" name="stock" id="stock" class="form-control" value="<?php echo isset($_POST['stock']) ? $_POST['stock'] : '0'; ?>">
                    </div>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label for="marca" class="form-label">Marca</label>
                        <input type="text" name="marca" id="marca" class="form-control" value="<?php echo isset($_POST['marca']) ? $_POST['marca'] : ''; ?>">
                    </div>
                    
                    <div class="form-group" style="flex: 1;">
                        <label for="modelo" class="form-label">Modelo</label>
                        <input type="text" name="modelo" id="modelo" class="form-control" value="<?php echo isset($_POST['modelo']) ? $_POST['modelo'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea name="descripcion" id="descripcion" class="form-control" rows="5"><?php echo isset($_POST['descripcion']) ? $_POST['descripcion'] : ''; ?></textarea>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-check" style="flex: 1;">
                        <input type="checkbox" name="destacado" id="destacado" class="form-check-input" <?php echo (isset($_POST['destacado']) && $_POST['destacado']) ? 'checked' : ''; ?>>
                        <label for="destacado" class="form-check-label">Producto Destacado</label>
                    </div>
                    
                    <div class="form-check" style="flex: 1;">
                        <input type="checkbox" name="nuevo" id="nuevo" class="form-check-input" <?php echo (!isset($_POST['nuevo']) || $_POST['nuevo']) ? 'checked' : ''; ?>>
                        <label for="nuevo" class="form-check-label">Producto Nuevo</label>
                    </div>
                    
                    <div class="form-check" style="flex: 1;">
                        <input type="checkbox" name="activo" id="activo" class="form-check-input" <?php echo (!isset($_POST['activo']) || $_POST['activo']) ? 'checked' : ''; ?>>
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
                    <small>Tamaño recomendado: 800x800 píxeles</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Imágenes Adicionales</label>
                    <input type="file" name="imagenes_adicionales[]" class="form-control" multiple accept="image/*">
                    <small>Puede seleccionar múltiples archivos</small>
                </div>
                
                <div id="preview-container" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px;">
                    <!-- Aquí se mostrarán las vistas previas de las imágenes -->
                </div>
            </div>
            
            <!-- Especificaciones -->
            <div id="especificaciones" class="admin-card">
                <div class="admin-card-title">Especificaciones del Producto</div>
                
                <p>Seleccione una categoría para cargar las especificaciones predefinidas o añada especificaciones personalizadas.</p>
                
                <div id="especificaciones-container">
                    <!-- Aquí se cargarán las especificaciones predefinidas -->
                    <div style="display: flex; margin-bottom: 10px; align-items: center; background-color: #f8f9fa; padding: 10px; border-radius: 4px;">
                        <div style="flex: 1; margin-right: 10px;">
                            <input type="text" name="spec_nombre[]" placeholder="Nombre" class="form-control">
                        </div>
                        <div style="flex: 2; margin-right: 10px;">
                            <input type="text" name="spec_valor[]" placeholder="Valor" class="form-control">
                        </div>
                        <div>
                            <button type="button" class="btn btn-danger" onclick="eliminarEspecificacion(this)">x</button>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 10px;">
                    <button type="button" class="btn btn-primary" onclick="agregarEspecificacion()">Añadir Especificación</button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    // Función para cargar especificaciones predefinidas según la categoría
    function cargarEspecificacionesCategoria() {
        const categoriaId = document.getElementById('categoria_id').value;
        const container = document.getElementById('especificaciones-container');
        
        if (!categoriaId) {
            return;
        }
        
        // Hacer petición AJAX para obtener las especificaciones
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '<?php echo ADMIN_URL; ?>/ajax/get_especificaciones.php?categoria_id=' + categoriaId, true);
        
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const especificaciones = JSON.parse(this.responseText);
                    
                    // Limpiar el contenedor
                    container.innerHTML = '';
                    
                    // Añadir las especificaciones predefinidas
                    especificaciones.forEach(function(spec) {
                        const row = document.createElement('div');
                        row.style.display = 'flex';
                        row.style.marginBottom = '10px';
                        row.style.alignItems = 'center';
                        row.style.backgroundColor = '#f8f9fa';
                        row.style.padding = '10px';
                        row.style.borderRadius = '4px';
                        
                        row.innerHTML = `
                            <div style="flex: 1; margin-right: 10px;">
                                <input type="text" name="spec_nombre[]" value="${spec.nombre}" class="form-control" readonly>
                            </div>
                            <div style="flex: 2; margin-right: 10px;">
                                <input type="text" name="spec_valor[]" placeholder="Valor" class="form-control">
                            </div>
                            <div>
                                <button type="button" class="btn btn-danger" onclick="eliminarEspecificacion(this)">x</button>
                            </div>
                        `;
                        
                        container.appendChild(row);
                    });
                    
                    // Añadir una fila vacía para especificaciones personalizadas
                    agregarEspecificacion();
                    
                } catch (e) {
                    console.error('Error al parsear las especificaciones:', e);
                }
            }
        };
        
        xhr.send();
    }
    
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
        const previewContainer = document.getElementById('preview-container');
        previewContainer.innerHTML = ''; // Limpiar vistas previas anteriores
        
        for (let i = 0; i < this.files.length; i++) {
            mostrarVistaPrevia(this.files[i], 'adicional-' + i);
        }
    });
    
    function mostrarVistaPrevia(file, id) {
        if (!file || !file.type.match('image.*')) {
            return;
        }
        
        const reader = new FileReader();
        const previewContainer = document.getElementById('preview-container');
        
        reader.onload = function(e) {
            const previewDiv = document.createElement('div');
            previewDiv.style.width = '150px';
            previewDiv.style.height = '150px';
            previewDiv.style.overflow = 'hidden';
            previewDiv.style.border = '1px solid #ddd';
            previewDiv.style.borderRadius = '4px';
            previewDiv.style.position = 'relative';
            
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'contain';
            
            const label = document.createElement('div');
            label.style.position = 'absolute';
            label.style.bottom = '0';
            label.style.left = '0';
            label.style.right = '0';
            label.style.backgroundColor = 'rgba(0,0,0,0.5)';
            label.style.color = 'white';
            label.style.padding = '5px';
            label.style.textAlign = 'center';
            label.textContent = id.includes('principal') ? 'Principal' : 'Adicional';
            
            previewDiv.appendChild(img);
            previewDiv.appendChild(label);
            previewContainer.appendChild(previewDiv);
        };
        
        reader.readAsDataURL(file);
    }
</script>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>