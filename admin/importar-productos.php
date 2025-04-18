<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Procesar formulario de importación
$mensaje = '';
$tipo_mensaje = '';
$productos_importados = 0;
$errores = [];

if (isset($_POST['importar']) && isset($_FILES['archivo_csv'])) {
    // Verificar que sea un archivo CSV
    $file_ext = strtolower(pathinfo($_FILES['archivo_csv']['name'], PATHINFO_EXTENSION));
    
    if ($file_ext == 'csv') {
        // Mover archivo a una ubicación temporal
        $temp_file = $_FILES['archivo_csv']['tmp_name'];
        
        // Abrir archivo CSV
        if (($handle = fopen($temp_file, "r")) !== FALSE) {
            // Leer la primera línea (encabezados)
            $headers = fgetcsv($handle, 0, ",");
            
            // Verificar que los encabezados sean correctos
            $expected_headers = ['nombre', 'precio', 'categoria_id', 'descripcion', 'precio_oferta', 'stock', 'marca', 'modelo', 'destacado', 'nuevo', 'activo'];
            $headers_valid = true;
            
            foreach ($expected_headers as $index => $header) {
                if (!isset($headers[$index]) || strtolower(trim($headers[$index])) != $header) {
                    $headers_valid = false;
                    break;
                }
            }
            
            if (!$headers_valid) {
                $mensaje = "El formato del CSV no es válido. Por favor, asegúrese de usar el formato correcto.";
                $tipo_mensaje = "danger";
                $errores[] = "Encabezados esperados: " . implode(', ', $expected_headers);
                $errores[] = "Encabezados encontrados: " . implode(', ', $headers);
            } else {
                // Procesar las líneas de datos
                $line_number = 1; // Empezamos desde 1 porque ya leímos la cabecera
                
                try {
                    $conn->beginTransaction();
                    
                    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
                        $line_number++;
                        
                        // Validar que tenga suficientes columnas
                        if (count($data) < count($expected_headers)) {
                            $errores[] = "Línea $line_number: Faltan columnas. Se esperaban " . count($expected_headers) . " columnas, pero se encontraron " . count($data);
                            continue;
                        }
                        
                        // Extraer los datos
                        $nombre = trim($data[0]);
                        $precio = floatval(str_replace(',', '.', $data[1]));
                        $categoria_id = intval($data[2]);
                        $descripcion = trim($data[3]);
                        $precio_oferta = floatval(str_replace(',', '.', $data[4]));
                        $stock = intval($data[5]);
                        $marca = trim($data[6]);
                        $modelo = trim($data[7]);
                        $destacado = intval($data[8]) ? 1 : 0;
                        $nuevo = intval($data[9]) ? 1 : 0;
                        $activo = intval($data[10]) ? 1 : 0;
                        
                        // Validar datos básicos
                        if (empty($nombre)) {
                            $errores[] = "Línea $line_number: El nombre no puede estar vacío.";
                            continue;
                        }
                        
                        if ($precio <= 0) {
                            $errores[] = "Línea $line_number: El precio debe ser mayor que cero.";
                            continue;
                        }
                        
                        // Verificar que la categoría exista
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM categorias WHERE id = ?");
                        $stmt->execute([$categoria_id]);
                        $categoria_existe = $stmt->fetchColumn() > 0;
                        
                        if (!$categoria_existe) {
                            $errores[] = "Línea $line_number: La categoría ID $categoria_id no existe.";
                            continue;
                        }
                        
                        // Generar slug desde el nombre
                        $slug = generate_slug($nombre);
                        
                        // Verificar si el slug ya existe
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM productos WHERE slug = ?");
                        $stmt->execute([$slug]);
                        $slug_existe = $stmt->fetchColumn() > 0;
                        
                        if ($slug_existe) {
                            // Añadir un sufijo único al slug
                            $slug = $slug . '-' . time() . rand(10, 99);
                        }
                        
                        // Insertar el producto
                        $stmt = $conn->prepare("
                            INSERT INTO productos (
                                sku, nombre, slug, precio, precio_oferta, descripcion,
                                stock, categoria_id, marca, modelo, destacado, nuevo, activo
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        // El SKU se genera a partir del nombre y categoría
                        $stmt->execute([
                            generate_sku($nombre, $categoria_id),
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
                            $activo
                        ]);
                        
                        $productos_importados++;
                    }
                    
                    // Si no hay errores, confirmar la transacción
                    if (empty($errores)) {
                        $conn->commit();
                        $mensaje = "Importación completada con éxito. Se importaron $productos_importados productos.";
                        $tipo_mensaje = "success";
                    } else {
                        // Si hay errores, hacer rollback
                        $conn->rollBack();
                        $mensaje = "La importación falló debido a errores en los datos.";
                        $tipo_mensaje = "danger";
                    }
                    
                } catch (PDOException $e) {
                    $conn->rollBack();
                    $mensaje = "Error en la base de datos: " . $e->getMessage();
                    $tipo_mensaje = "danger";
                }
            }
            
            fclose($handle);
        } else {
            $mensaje = "No se pudo abrir el archivo CSV.";
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = "El archivo debe estar en formato CSV.";
        $tipo_mensaje = "danger";
    }
}

// Obtener categorías para el ejemplo
$categorias = admin_obtener_categorias_select();

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Importar Productos</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; Importar Productos
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
    
    <?php if (!empty($errores)): ?>
    <ul style="margin-top: 10px;">
        <?php foreach ($errores as $error): ?>
            <li><?php echo $error; ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Importación de Productos -->
<div class="admin-card">
    <div class="admin-card-title">Importar Productos desde CSV</div>
    
    <p>Suba un archivo CSV con los datos de los productos a importar. El archivo debe seguir el formato especificado.</p>
    
    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="archivo_csv" class="form-label">Archivo CSV</label>
            <input type="file" name="archivo_csv" id="archivo_csv" class="form-control" required accept=".csv">
        </div>
        
        <div class="form-group">
            <button type="submit" name="importar" class="btn btn-primary">Importar Productos</button>
        </div>
    </form>
</div>

<!-- Formato CSV -->
<div class="admin-card">
    <div class="admin-card-title">Formato del Archivo CSV</div>
    
    <p>El archivo CSV debe tener las siguientes columnas (en este orden):</p>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Columna</th>
                    <th>Descripción</th>
                    <th>Ejemplo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>nombre</td>
                    <td>Nombre del producto</td>
                    <td>Laptop Asus TUF Gaming</td>
                </tr>
                <tr>
                    <td>precio</td>
                    <td>Precio normal (numérico)</td>
                    <td>4299.99</td>
                </tr>
                <tr>
                    <td>categoria_id</td>
                    <td>ID de la categoría (numérico)</td>
                    <td>2</td>
                </tr>
                <tr>
                    <td>descripcion</td>
                    <td>Descripción del producto</td>
                    <td>Laptop gaming con Intel Core i7 16GB RAM SSD 512GB RTX 3050Ti</td>
                </tr>
                <tr>
                    <td>precio_oferta</td>
                    <td>Precio en oferta (numérico, 0 si no hay oferta)</td>
                    <td>3999.99</td>
                </tr>
                <tr>
                    <td>stock</td>
                    <td>Cantidad en inventario (numérico)</td>
                    <td>8</td>
                </tr>
                <tr>
                    <td>marca</td>
                    <td>Marca del producto</td>
                    <td>Asus</td>
                </tr>
                <tr>
                    <td>modelo</td>
                    <td>Modelo del producto</td>
                    <td>TUF A15</td>
                </tr>
                <tr>
                    <td>destacado</td>
                    <td>Si es producto destacado (1 = sí, 0 = no)</td>
                    <td>1</td>
                </tr>
                <tr>
                    <td>nuevo</td>
                    <td>Si es producto nuevo (1 = sí, 0 = no)</td>
                    <td>0</td>
                </tr>
                <tr>
                    <td>activo</td>
                    <td>Si el producto está activo (1 = sí, 0 = no)</td>
                    <td>1</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 20px;">
        <h3>Categorías Disponibles</h3>
        <p>A continuación se muestran las categorías disponibles con sus IDs (útil para la columna categoria_id):</p>
        
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $id => $nombre): ?>
                        <tr>
                            <td><?php echo $id; ?></td>
                            <td><?php echo $nombre; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div style="margin-top: 20px;">
        <h3>Ejemplo de Línea CSV</h3>
        <pre style="background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto;">nombre,precio,categoria_id,descripcion,precio_oferta,stock,marca,modelo,destacado,nuevo,activo
Laptop Asus TUF Gaming,4299.99,2,Laptop gaming con Intel Core i7 16GB RAM SSD 512GB RTX 3050Ti,3999.99,8,Asus,TUF A15,1,0,1</pre>
    </div>
    
    <div style="margin-top: 20px;">
        <h3>Notas Importantes</h3>
        <ul>
            <li>La primera línea del CSV debe contener los encabezados exactamente como se muestra arriba.</li>
            <li>Los valores numéricos pueden usar punto (.) o coma (,) como separador decimal.</li>
            <li>El SKU y el slug se generarán automáticamente a partir del nombre y la categoría.</li>
            <li>Si el nombre genera un slug que ya existe, se le añadirá un sufijo único.</li>
        </ul>
    </div>
</div>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>