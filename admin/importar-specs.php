<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Procesar formulario de importación
$mensaje = '';
$tipo_mensaje = '';
$specs_importadas = 0;
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
            $expected_headers = ['sku', 'nombre', 'valor'];
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
                        $sku = trim($data[0]);
                        $nombre_spec = trim($data[1]);
                        $valor_spec = trim($data[2]);
                        
                        // Validar datos básicos
                        if (empty($sku)) {
                            $errores[] = "Línea $line_number: El SKU no puede estar vacío.";
                            continue;
                        }
                        
                        if (empty($nombre_spec)) {
                            $errores[] = "Línea $line_number: El nombre de la especificación no puede estar vacío.";
                            continue;
                        }
                        
                        // Buscar el producto por SKU
                        $stmt = $conn->prepare("SELECT id FROM productos WHERE sku = ? LIMIT 1");
                        $stmt->execute([$sku]);
                        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$producto) {
                            $errores[] = "Línea $line_number: No se encontró ningún producto con el SKU '$sku'.";
                            continue;
                        }
                        
                        // Verificar si ya existe esta especificación para este producto
                        $stmt = $conn->prepare("SELECT id FROM especificaciones WHERE producto_id = ? AND nombre = ?");
                        $stmt->execute([$producto['id'], $nombre_spec]);
                        $existe = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($existe) {
                            // Actualizar la especificación existente
                            $stmt = $conn->prepare("UPDATE especificaciones SET valor = ? WHERE id = ?");
                            $stmt->execute([$valor_spec, $existe['id']]);
                        } else {
                            // Insertar nueva especificación
                            $stmt = $conn->prepare("INSERT INTO especificaciones (producto_id, nombre, valor) VALUES (?, ?, ?)");
                            $stmt->execute([$producto['id'], $nombre_spec, $valor_spec]);
                        }
                        
                        $specs_importadas++;
                    }
                    
                    // Si no hay errores, confirmar la transacción
                    if (empty($errores)) {
                        $conn->commit();
                        $mensaje = "Importación completada con éxito. Se importaron $specs_importadas especificaciones.";
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

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Importar Especificaciones</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; Importar Especificaciones
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

<!-- Importación de Especificaciones -->
<div class="admin-card">
    <div class="admin-card-title">Importar Especificaciones desde CSV</div>
    
    <p>Suba un archivo CSV con las especificaciones de los productos. El archivo debe seguir el formato especificado.</p>
    
    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="archivo_csv" class="form-label">Archivo CSV</label>
            <input type="file" name="archivo_csv" id="archivo_csv" class="form-control" required accept=".csv">
        </div>
        
        <div class="form-group">
            <button type="submit" name="importar" class="btn btn-primary">Importar Especificaciones</button>
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
                    <td>sku</td>
                    <td>SKU del producto al que pertenece la especificación</td>
                    <td>LAP-02-00001</td>
                </tr>
                <tr>
                    <td>nombre</td>
                    <td>Nombre de la especificación</td>
                    <td>Procesador</td>
                </tr>
                <tr>
                    <td>valor</td>
                    <td>Valor de la especificación</td>
                    <td>Intel Core i7-13700H</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 20px;">
        <h3>Ejemplo de Línea CSV</h3>
        <pre style="background-color: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto;">sku,nombre,valor
LAP-02-00001,Procesador,Intel Core i7-13700H
LAP-02-00001,Memoria RAM,16GB DDR5 4800MHz
LAP-02-00001,Almacenamiento,SSD NVMe 512GB</pre>
    </div>
    
    <div style="margin-top: 20px;">
        <h3>Notas Importantes</h3>
        <ul>
            <li>La primera línea del CSV debe contener los encabezados exactamente como se muestra arriba.</li>
            <li>El SKU debe corresponder a un producto existente en la base de datos.</li>
            <li>Si ya existe una especificación con el mismo nombre para un producto, se actualizará su valor.</li>
            <li>Si no existe, se creará una nueva especificación.</li>
        </ul>
    </div>
</div>

<!-- Ver SKUs de Productos -->
<div class="admin-card">
    <div class="admin-card-title">SKUs de Productos Disponibles</div>
    
    <p>A continuación se muestran los productos con sus SKUs (útil para la columna sku):</p>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nombre del Producto</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $conn->query("SELECT sku, nombre FROM productos ORDER BY id DESC LIMIT 20");
                $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($productos)):
                ?>
                <tr>
                    <td colspan="2" class="text-center">No hay productos registrados.</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?php echo $producto['sku']; ?></td>
                        <td><?php echo $producto['nombre']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if (count($productos) == 20): ?>
        <p style="margin-top: 10px; text-align: center;">Mostrando los 20 productos más recientes. La lista completa está disponible en la sección de Productos.</p>
        <?php endif; ?>
    </div>
</div>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>