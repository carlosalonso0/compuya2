<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Manejar acciones
$mensaje = '';
$tipo_mensaje = '';

// Obtener categorías para mostrar
$categorias = [];
try {
    // Primero obtener categorías principales
    $stmt = $conn->prepare("
        SELECT c.*, (SELECT COUNT(*) FROM categorias WHERE categoria_padre_id = c.id) as subcategorias_count
        FROM categorias c 
        WHERE c.categoria_padre_id IS NULL
        ORDER BY c.nombre ASC
    ");
    $stmt->execute();
    $categorias_principales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para cada categoría principal, obtener sus subcategorías
    foreach ($categorias_principales as $categoria) {
        $categorias[] = $categoria;
        
        if ($categoria['subcategorias_count'] > 0) {
            $stmt = $conn->prepare("
                SELECT c.*, NULL as subcategorias_count 
                FROM categorias c 
                WHERE c.categoria_padre_id = ?
                ORDER BY c.nombre ASC
            ");
            $stmt->execute([$categoria['id']]);
            $subcategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($subcategorias as $subcategoria) {
                $subcategoria['es_subcategoria'] = true;
                $categorias[] = $subcategoria;
            }
        }
    }
} catch(PDOException $e) {
    $mensaje = "Error al cargar categorías: " . $e->getMessage();
    $tipo_mensaje = "danger";
}

// Acción: Crear categoría
if (isset($_POST['accion']) && $_POST['accion'] == 'crear_categoria') {
    $nombre = trim($_POST['nombre']);
    $slug = generate_slug($nombre);
    $categoria_padre_id = !empty($_POST['categoria_padre_id']) ? $_POST['categoria_padre_id'] : null;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validar que no exista otra categoría con el mismo slug
    $stmt = $conn->prepare("SELECT COUNT(*) FROM categorias WHERE slug = ?");
    $stmt->execute([$slug]);
    $existe = $stmt->fetchColumn() > 0;
    
    if ($existe) {
        $mensaje = "Ya existe una categoría con nombre similar. Por favor, elige otro nombre.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Procesar imagen si se ha subido
            $imagen = '';
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $imagen = admin_subir_imagen($_FILES['imagen'], CATEGORIES_IMG_PATH);
            }
            
            $stmt = $conn->prepare("
                INSERT INTO categorias (nombre, slug, categoria_padre_id, imagen, activo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nombre, $slug, $categoria_padre_id, $imagen, $activo]);
            
            $mensaje = "Categoría creada correctamente.";
            $tipo_mensaje = "success";
            
            // Redireccionar para actualizar la lista
            header("Location: " . ADMIN_URL . "/categorias.php?mensaje=creada");
            exit;
        } catch(PDOException $e) {
            $mensaje = "Error al crear categoría: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// Acción: Actualizar categoría
if (isset($_POST['accion']) && $_POST['accion'] == 'actualizar_categoria') {
    $id = $_POST['categoria_id'];
    $nombre = trim($_POST['nombre']);
    $slug = generate_slug($nombre);
    $categoria_padre_id = !empty($_POST['categoria_padre_id']) ? $_POST['categoria_padre_id'] : null;
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validar que no exista otra categoría con el mismo slug (excepto la actual)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM categorias WHERE slug = ? AND id != ?");
    $stmt->execute([$slug, $id]);
    $existe = $stmt->fetchColumn() > 0;
    
    if ($existe) {
        $mensaje = "Ya existe otra categoría con nombre similar. Por favor, elige otro nombre.";
        $tipo_mensaje = "danger";
    } else {
        try {
            // Obtener categoría actual para verificar imagen
            $stmt = $conn->prepare("SELECT imagen FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            $categoria_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Procesar imagen si se ha subido
            $imagen = $categoria_actual['imagen'];
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
                $nueva_imagen = admin_subir_imagen($_FILES['imagen'], CATEGORIES_IMG_PATH);
                
                if ($nueva_imagen) {
                    // Eliminar imagen anterior si existe
                    if (!empty($imagen)) {
                        $ruta_imagen = CATEGORIES_IMG_PATH . '/' . $imagen;
                        if (file_exists($ruta_imagen)) {
                            unlink($ruta_imagen);
                        }
                    }
                    
                    $imagen = $nueva_imagen;
                }
            }
            
            $stmt = $conn->prepare("
                UPDATE categorias 
                SET nombre = ?, slug = ?, categoria_padre_id = ?, imagen = ?, activo = ?
                WHERE id = ?
            ");
            $stmt->execute([$nombre, $slug, $categoria_padre_id, $imagen, $activo, $id]);
            
            $mensaje = "Categoría actualizada correctamente.";
            $tipo_mensaje = "success";
            
            // Redireccionar para actualizar la lista
            header("Location: " . ADMIN_URL . "/categorias.php?mensaje=actualizada");
            exit;
        } catch(PDOException $e) {
            $mensaje = "Error al actualizar categoría: " . $e->getMessage();
            $tipo_mensaje = "danger";
        }
    }
}

// Acción: Eliminar categoría
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Verificar si tiene subcategorías
        $stmt = $conn->prepare("SELECT COUNT(*) FROM categorias WHERE categoria_padre_id = ?");
        $stmt->execute([$id]);
        $tiene_subcategorias = $stmt->fetchColumn() > 0;
        
        // Verificar si tiene productos asociados
        $stmt = $conn->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
        $stmt->execute([$id]);
        $tiene_productos = $stmt->fetchColumn() > 0;
        
        if ($tiene_subcategorias) {
            $mensaje = "No se puede eliminar la categoría porque tiene subcategorías asociadas.";
            $tipo_mensaje = "danger";
        } elseif ($tiene_productos) {
            $mensaje = "No se puede eliminar la categoría porque tiene productos asociados.";
            $tipo_mensaje = "danger";
        } else {
            // Obtener imagen para eliminarla
            $stmt = $conn->prepare("SELECT imagen FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($categoria && !empty($categoria['imagen'])) {
                $ruta_imagen = CATEGORIES_IMG_PATH . '/' . $categoria['imagen'];
                if (file_exists($ruta_imagen)) {
                    unlink($ruta_imagen);
                }
            }
            
            // Eliminar la categoría
            $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            
            $mensaje = "Categoría eliminada correctamente.";
            $tipo_mensaje = "success";
        }
    } catch(PDOException $e) {
        $mensaje = "Error al eliminar categoría: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Acción: Activar/Desactivar categoría
if (isset($_GET['accion']) && $_GET['accion'] == 'toggle_activo' && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // Obtener estado actual
        $stmt = $conn->prepare("SELECT activo FROM categorias WHERE id = ?");
        $stmt->execute([$id]);
        $activo = $stmt->fetchColumn();
        
        // Invertir estado
        $nuevo_estado = $activo ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE categorias SET activo = ? WHERE id = ?");
        $stmt->execute([$nuevo_estado, $id]);
        
        $mensaje = "Estado de la categoría actualizado correctamente.";
        $tipo_mensaje = "success";
    } catch(PDOException $e) {
        $mensaje = "Error al actualizar estado: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener categoría para editar si es necesario
$categoria_editar = null;
if (isset($_GET['accion']) && $_GET['accion'] == 'editar' && isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $categoria_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $mensaje = "Error al obtener categoría: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Verificar si hay mensajes en la URL
if (isset($_GET['mensaje'])) {
    if ($_GET['mensaje'] == 'creada') {
        $mensaje = "Categoría creada correctamente.";
        $tipo_mensaje = "success";
    } elseif ($_GET['mensaje'] == 'actualizada') {
        $mensaje = "Categoría actualizada correctamente.";
        $tipo_mensaje = "success";
    }
}

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Gestión de Categorías</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; Categorías
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<div style="display: flex; flex-wrap: wrap; gap: 20px;">
    <!-- Listado de categorías -->
    <div style="flex: 2; min-width: 600px;">
        <div class="admin-card">
            <div class="admin-card-title">Categorías</div>
            
            <?php if (empty($categorias)): ?>
                <p>No hay categorías para mostrar.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $categoria): ?>
                            <tr>
                                <td><?php echo $categoria['id']; ?></td>
                                <td>
                                    <?php if (!empty($categoria['imagen'])): ?>
                                        <img src="<?php echo CATEGORIES_IMG_URL . '/' . $categoria['imagen']; ?>" alt="<?php echo htmlspecialchars($categoria['nombre']); ?>" style="width: 50px; height: 50px; object-fit: contain;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #999;">Sin imagen</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($categoria['es_subcategoria']) && $categoria['es_subcategoria']): ?>
                                        <span style="margin-left: 20px; color: #666;">└─ </span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </td>
                                <td>
                                    <?php if (isset($categoria['es_subcategoria']) && $categoria['es_subcategoria']): ?>
                                        <span class="badge" style="background-color: #6c757d; color: white; padding: 5px 8px; border-radius: 4px; font-size: 12px;">Subcategoría</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: #007bff; color: white; padding: 5px 8px; border-radius: 4px; font-size: 12px;">Categoría principal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="color: <?php echo $categoria['activo'] ? '#28a745' : '#dc3545'; ?>;">
                                        <?php echo $categoria['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo ADMIN_URL; ?>/categorias.php?accion=editar&id=<?php echo $categoria['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                    <a href="<?php echo ADMIN_URL; ?>/categorias.php?accion=toggle_activo&id=<?php echo $categoria['id']; ?>" class="btn btn-<?php echo $categoria['activo'] ? 'warning' : 'success'; ?> btn-sm">
                                        <?php echo $categoria['activo'] ? 'Desactivar' : 'Activar'; ?>
                                    </a>
                                    <a href="<?php echo ADMIN_URL; ?>/categorias.php?accion=eliminar&id=<?php echo $categoria['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro que desea eliminar esta categoría?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Formulario para crear/editar categoría -->
    <div style="flex: 1; min-width: 300px;">
        <div class="admin-card">
            <div class="admin-card-title">
                <?php echo $categoria_editar ? 'Editar Categoría' : 'Crear Nueva Categoría'; ?>
            </div>
            
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="<?php echo $categoria_editar ? 'actualizar_categoria' : 'crear_categoria'; ?>">
                <?php if ($categoria_editar): ?>
                    <input type="hidden" name="categoria_id" value="<?php echo $categoria_editar['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" value="<?php echo $categoria_editar ? htmlspecialchars($categoria_editar['nombre']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="categoria_padre_id" class="form-label">Categoría Padre</label>
                    <select name="categoria_padre_id" id="categoria_padre_id" class="form-select">
                        <option value="">Ninguna (Categoría Principal)</option>
                        <?php 
                        // Mostrar solo categorías principales como posibles padres
                        foreach ($categorias as $cat): 
                            if (!isset($cat['es_subcategoria']) && (!$categoria_editar || $cat['id'] != $categoria_editar['id'])):
                        ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($categoria_editar && $categoria_editar['categoria_padre_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </select>
                    <small class="form-text">Si selecciona una categoría padre, esta se convertirá en subcategoría.</small>
                </div>
                
                <div class="form-group">
                    <label for="imagen" class="form-label">Imagen de la Categoría</label>
                    <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
                    
                    <?php if ($categoria_editar && !empty($categoria_editar['imagen'])): ?>
                        <div style="margin-top: 10px; display: flex; align-items: center; gap: 10px;">
                            <img src="<?php echo CATEGORIES_IMG_URL . '/' . $categoria_editar['imagen']; ?>" alt="<?php echo htmlspecialchars($categoria_editar['nombre']); ?>" style="max-width: 100px; max-height: 100px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                            <div>
                                <p>Imagen actual. Sube una nueva imagen para reemplazarla.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="activo" id="activo" class="form-check-input" <?php echo (!$categoria_editar || $categoria_editar['activo']) ? 'checked' : ''; ?>>
                        <span class="form-check-label">Categoría Activa</span>
                    </label>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $categoria_editar ? 'Actualizar Categoría' : 'Crear Categoría'; ?>
                    </button>
                    
                    <?php if ($categoria_editar): ?>
                        <a href="<?php echo ADMIN_URL; ?>/categorias.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>