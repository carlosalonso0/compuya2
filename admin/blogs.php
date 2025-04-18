<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

// Guardar nuevo blog/guía
if (isset($_POST['guardar_blog'])) {
    try {
        $titulo = $_POST['titulo'];
        $contenido = $_POST['contenido'];
        $fecha_publicacion = $_POST['fecha_publicacion'];
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $activo = isset($_POST['activo']) ? 1 : 0;
        $orden = (int)$_POST['orden'];
        
        if (empty($titulo) || empty($contenido)) {
            throw new Exception("Los campos Título y Contenido son obligatorios.");
        }
        
        // Procesar imagen si se ha subido
        $imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            // Crear directorio si no existe
            $directorio = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/blogs';
            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }
            
            $imagen = admin_subir_imagen($_FILES['imagen'], $directorio);
            
            if (!$imagen) {
                throw new Exception("Error al subir la imagen.");
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO blogs_guias (titulo, contenido, imagen, fecha_publicacion, destacado, activo, orden)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$titulo, $contenido, $imagen, $fecha_publicacion, $destacado, $activo, $orden]);
        
        $mensaje = "Blog/guía guardado correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Actualizar blog/guía existente
if (isset($_POST['actualizar_blog']) && isset($_POST['blog_id'])) {
    try {
        $id = (int)$_POST['blog_id'];
        $titulo = $_POST['titulo'];
        $contenido = $_POST['contenido'];
        $fecha_publicacion = $_POST['fecha_publicacion'];
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $activo = isset($_POST['activo']) ? 1 : 0;
        $orden = (int)$_POST['orden'];
        
        if (empty($titulo) || empty($contenido)) {
            throw new Exception("Los campos Título y Contenido son obligatorios.");
        }
        
        // Obtener datos actuales
        $stmt = $conn->prepare("SELECT imagen FROM blogs_guias WHERE id = ?");
        $stmt->execute([$id]);
        $blog_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Procesar imagen si se ha subido
        $imagen = $blog_actual['imagen'];
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            // Crear directorio si no existe
            $directorio = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/blogs';
            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }
            
            $nueva_imagen = admin_subir_imagen($_FILES['imagen'], $directorio);
            
            if ($nueva_imagen) {
                // Eliminar imagen anterior si existe
                if (!empty($imagen)) {
                    $ruta_imagen = $directorio . '/' . $imagen;
                    if (file_exists($ruta_imagen)) {
                        unlink($ruta_imagen);
                    }
                }
                
                $imagen = $nueva_imagen;
            }
        }
        
        $stmt = $conn->prepare("
            UPDATE blogs_guias
            SET titulo = ?, contenido = ?, imagen = ?, fecha_publicacion = ?, 
                destacado = ?, activo = ?, orden = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$titulo, $contenido, $imagen, $fecha_publicacion, $destacado, $activo, $orden, $id]);
        
        $mensaje = "Blog/guía actualizado correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Eliminar blog/guía
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        // Obtener imagen para eliminarla
        $stmt = $conn->prepare("SELECT imagen FROM blogs_guias WHERE id = ?");
        $stmt->execute([$id]);
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($blog && !empty($blog['imagen'])) {
            $ruta_imagen = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/blogs/' . $blog['imagen'];
            if (file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM blogs_guias WHERE id = ?");
        $stmt->execute([$id]);
        
        $mensaje = "Blog/guía eliminado correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Cambiar estado (activar/desactivar)
if (isset($_GET['accion']) && $_GET['accion'] == 'toggle_activo' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        // Obtener estado actual
        $stmt = $conn->prepare("SELECT activo FROM blogs_guias WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            $nuevo_estado = $resultado['activo'] ? 0 : 1;
            
            $stmt = $conn->prepare("UPDATE blogs_guias SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            
            $mensaje = "Estado actualizado correctamente.";
            $tipo_mensaje = "success";
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Cambiar destacado
if (isset($_GET['accion']) && $_GET['accion'] == 'toggle_destacado' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        // Obtener estado actual
        $stmt = $conn->prepare("SELECT destacado FROM blogs_guias WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            $nuevo_estado = $resultado['destacado'] ? 0 : 1;
            
            $stmt = $conn->prepare("UPDATE blogs_guias SET destacado = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            
            $mensaje = "Destacado actualizado correctamente.";
            $tipo_mensaje = "success";
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener blog/guía para editar
$blog_editar = null;
if (isset($_GET['accion']) && $_GET['accion'] == 'editar' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("SELECT * FROM blogs_guias WHERE id = ?");
        $stmt->execute([$id]);
        $blog_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener lista de blogs/guías
try {
    $stmt = $conn->prepare("SELECT * FROM blogs_guias ORDER BY destacado DESC, orden ASC, fecha_publicacion DESC");
    $stmt->execute();
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "Error al cargar blogs/guías: " . $e->getMessage();
    $tipo_mensaje = "danger";
    $blogs = [];
}

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');

// Fecha actual para el campo de fecha
$fecha_actual = date('Y-m-d');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Gestión de Blogs y Guías</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; 
        <a href="<?php echo ADMIN_URL; ?>/editar-inicio.php">Editar Inicio</a> &gt; 
        Blogs y Guías
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Formulario para añadir/editar blog -->
<div class="admin-card">
    <div class="admin-card-title">
        <?php echo $blog_editar ? 'Editar Blog/Guía' : 'Añadir Nuevo Blog/Guía'; ?>
    </div>
    
    <form action="" method="post" enctype="multipart/form-data">
        <?php if ($blog_editar): ?>
            <input type="hidden" name="blog_id" value="<?php echo $blog_editar['id']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="titulo" class="form-label">Título *</label>
            <input type="text" name="titulo" id="titulo" class="form-control" value="<?php echo $blog_editar ? $blog_editar['titulo'] : ''; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="contenido" class="form-label">Contenido *</label>
            <textarea name="contenido" id="contenido" class="form-control" rows="10" required><?php echo $blog_editar ? $blog_editar['contenido'] : ''; ?></textarea>
            <small>Puede usar HTML básico para formatear el texto.</small>
        </div>
        
        <div class="form-group">
            <label for="imagen" class="form-label">Imagen</label>
            <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
            
            <?php if ($blog_editar && !empty($blog_editar['imagen'])): ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo BASE_URL; ?>/public/assets/images/blogs/<?php echo $blog_editar['imagen']; ?>" alt="Imagen actual" style="max-width: 200px; max-height: 150px;">
                    <p>Imagen actual. Sube una nueva imagen para reemplazarla.</p>
                </div>
            <?php endif; ?>
            
            <small>Tamaño recomendado: 800x450 píxeles.</small>
        </div>
        
        <div class="form-group">
            <label for="fecha_publicacion" class="form-label">Fecha de Publicación</label>
            <input type="date" name="fecha_publicacion" id="fecha_publicacion" class="form-control" value="<?php echo $blog_editar ? $blog_editar['fecha_publicacion'] : $fecha_actual; ?>">
        </div>
        
        <div class="form-group">
            <label for="orden" class="form-label">Orden</label>
            <input type="number" name="orden" id="orden" class="form-control" value="<?php echo $blog_editar ? $blog_editar['orden'] : '0'; ?>">
            <small>Los números más bajos aparecen primero.</small>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="destacado" class="form-check-input" <?php echo ($blog_editar && $blog_editar['destacado']) ? 'checked' : ''; ?>>
                <span class="form-check-label">Destacar en Inicio</span>
            </label>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" class="form-check-input" <?php echo (!$blog_editar || $blog_editar['activo']) ? 'checked' : ''; ?>>
                <span class="form-check-label">Publicar (Activo)</span>
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" name="<?php echo $blog_editar ? 'actualizar_blog' : 'guardar_blog'; ?>" class="btn btn-primary">
                <?php echo $blog_editar ? 'Actualizar Blog/Guía' : 'Guardar Blog/Guía'; ?>
            </button>
            
            <?php if ($blog_editar): ?>
                <a href="<?php echo ADMIN_URL; ?>/blogs.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Lista de blogs -->
<div class="admin-card">
    <div class="admin-card-title">Blogs y Guías Existentes</div>
    
    <?php if (empty($blogs)): ?>
        <p>No hay blogs o guías registrados. Utilice el formulario para crear nuevos.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Título</th>
                        <th>Fecha</th>
                        <th>Destacado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blogs as $blog): ?>
                        <tr>
                            <td>
                                <?php if (!empty($blog['imagen'])): ?>
                                    <img src="<?php echo BASE_URL; ?>/public/assets/images/blogs/<?php echo $blog['imagen']; ?>" alt="<?php echo $blog['titulo']; ?>" style="max-width: 80px; max-height: 60px;">
                                <?php else: ?>
                                    <div style="width: 80px; height: 60px; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #999;">
                                        Sin imagen
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $blog['titulo']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($blog['fecha_publicacion'])); ?></td>
                            <td>
                                <span style="color: <?php echo $blog['destacado'] ? '#FFC107' : '#999'; ?>;">
                                    <?php echo $blog['destacado'] ? '<i class="fas fa-star"></i> Destacado' : 'No destacado'; ?>
                                </span>
                            </td>
                            <td>
                                <span style="color: <?php echo $blog['activo'] ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $blog['activo'] ? 'Publicado' : 'Borrador'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/blogs.php?accion=editar&id=<?php echo $blog['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="<?php echo ADMIN_URL; ?>/blogs.php?accion=toggle_destacado&id=<?php echo $blog['id']; ?>" class="btn btn-<?php echo $blog['destacado'] ? 'secondary' : 'warning'; ?> btn-sm">
                                    <?php echo $blog['destacado'] ? 'Quitar destacado' : 'Destacar'; ?>
                                </a>
                                <a href="<?php echo ADMIN_URL; ?>/blogs.php?accion=toggle_activo&id=<?php echo $blog['id']; ?>" class="btn btn-<?php echo $blog['activo'] ? 'warning' : 'success'; ?> btn-sm">
                                    <?php echo $blog['activo'] ? 'Despublicar' : 'Publicar'; ?>
                                </a>
                                <a href="<?php echo ADMIN_URL; ?>/blogs.php?accion=eliminar&id=<?php echo $blog['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro que desea eliminar este blog/guía?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>