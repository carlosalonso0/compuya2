<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

// Editar banner existente
if (isset($_POST['editar_banner']) && isset($_POST['banner_id'])) {
    $datos = [
        'titulo' => $_POST['titulo'],
        'descripcion' => $_POST['descripcion'],
        'url' => $_POST['url'],
        'activo' => isset($_POST['activo']) ? 1 : 0,
        'orden' => (int)$_POST['orden']
    ];
    
    // Si se ha subido una nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        $imagen_nombre = admin_subir_imagen($_FILES['imagen'], BANNERS_IMG_PATH);
        if ($imagen_nombre) {
            $datos['imagen'] = $imagen_nombre;
            
            // Eliminar imagen anterior
            $banner = admin_obtener_banner($_POST['banner_id']);
            if ($banner && !empty($banner['imagen'])) {
                $ruta_imagen = BANNERS_IMG_PATH . '/' . $banner['imagen'];
                if (file_exists($ruta_imagen)) {
                    unlink($ruta_imagen);
                }
            }
        }
    }
    
    // Actualizar en la base de datos
    if (admin_actualizar_banner($_POST['banner_id'], $datos)) {
        $mensaje = "Banner actualizado correctamente.";
        $tipo_mensaje = "success";
    } else {
        $mensaje = "Error al actualizar los datos del banner.";
        $tipo_mensaje = "danger";
    }
}

// Crear nuevo banner
if (isset($_POST['guardar_banner'])) {
    // Verificar si se ha subido una imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
        // Procesar imagen
        $imagen_nombre = admin_subir_imagen($_FILES['imagen'], BANNERS_IMG_PATH);
        
        if ($imagen_nombre) {
            // Datos para guardar en la base de datos
            $datos = [
                'titulo' => $_POST['titulo'],
                'descripcion' => $_POST['descripcion'],
                'imagen' => $imagen_nombre,
                'url' => $_POST['url'],
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'orden' => (int)$_POST['orden']
            ];
            
            // Guardar en la base de datos
            if (admin_crear_banner($datos)) {
                $mensaje = "Banner creado correctamente.";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al guardar los datos del banner.";
                $tipo_mensaje = "danger";
            }
        } else {
            $mensaje = "Error al subir la imagen.";
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = "Debe seleccionar una imagen.";
        $tipo_mensaje = "danger";
    }
}

// Eliminar banner
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    $banner = admin_obtener_banner($_GET['id']);
    if ($banner) {
        // Eliminar imagen
        if (!empty($banner['imagen'])) {
            $ruta_imagen = BANNERS_IMG_PATH . '/' . $banner['imagen'];
            if (file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }
        }
        
        // Eliminar de la base de datos
        if (admin_eliminar_banner($_GET['id'])) {
            $mensaje = "Banner eliminado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar el banner.";
            $tipo_mensaje = "danger";
        }
    }
}

// Activar/Desactivar banner
if (isset($_GET['accion']) && $_GET['accion'] == 'toggle_activo' && isset($_GET['id'])) {
    $banner = admin_obtener_banner($_GET['id']);
    if ($banner) {
        $nuevo_estado = $banner['activo'] ? 0 : 1;
        if (admin_actualizar_banner($_GET['id'], ['activo' => $nuevo_estado])) {
            $mensaje = "Estado del banner actualizado correctamente.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el estado del banner.";
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener todos los banners del carrusel principal
$banners = admin_obtener_banners();

// Banner a editar (si existe)
$banner_editar = null;
if (isset($_GET['accion']) && $_GET['accion'] == 'editar' && isset($_GET['id'])) {
    $banner_editar = admin_obtener_banner($_GET['id']);
}

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Gestión del Banner Principal</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; Gestionar Banner Principal
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Información sobre el banner principal -->
<div class="admin-card">
    <div class="admin-card-title">Información</div>
    
    <p>Esta sección te permite gestionar las imágenes del carrusel principal que aparece en la parte superior de la página de inicio, justo debajo de la barra de navegación.</p>
    <p>Puedes añadir, editar o eliminar imágenes del carrusel según tus necesidades.</p>
</div>

<?php if ($banner_editar): ?>
<!-- Formulario para editar banner -->
<div class="admin-card">
    <div class="admin-card-title">
        Editar Banner
    </div>
    
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="banner_id" value="<?php echo $banner_editar['id']; ?>">
        
        <div class="form-group">
            <label for="imagen" class="form-label">Imagen del Banner</label>
            <input type="file" name="imagen" id="imagen" class="form-control">
            <?php if (!empty($banner_editar['imagen'])): ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo BANNERS_IMG_URL . '/' . $banner_editar['imagen']; ?>" alt="Banner actual" style="max-width: 200px;">
                    <p>Imagen actual. Sube una nueva imagen para reemplazarla.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" name="titulo" id="titulo" class="form-control" value="<?php echo $banner_editar['titulo']; ?>">
        </div>
        
        <div class="form-group">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="3"><?php echo $banner_editar['descripcion']; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="url" class="form-label">URL de destino</label>
            <input type="text" name="url" id="url" class="form-control" value="<?php echo $banner_editar['url']; ?>">
        </div>
        
        <div class="form-group">
            <label for="orden" class="form-label">Orden</label>
            <input type="number" name="orden" id="orden" class="form-control" value="<?php echo $banner_editar['orden']; ?>">
            <small>Los banners se muestran en orden ascendente (primero los números más bajos).</small>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" class="form-check-input" <?php echo $banner_editar['activo'] ? 'checked' : ''; ?>>
                <span class="form-check-label">Banner Activo</span>
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" name="editar_banner" class="btn btn-primary">
                Actualizar Banner
            </button>
            <a href="<?php echo ADMIN_URL; ?>/banners.php" class="btn btn-danger">Cancelar</a>
        </div>
    </form>
</div>
<?php else: ?>
<!-- Formulario para crear nuevo banner -->
<div class="admin-card">
    <div class="admin-card-title">Agregar Nuevo Banner al Carrusel</div>
    
    <form action="" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="imagen" class="form-label">Imagen del Banner</label>
            <input type="file" name="imagen" id="imagen" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" name="titulo" id="titulo" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label for="url" class="form-label">URL de destino</label>
            <input type="text" name="url" id="url" class="form-control" value="#">
        </div>
        
        <div class="form-group">
            <label for="orden" class="form-label">Orden</label>
            <input type="number" name="orden" id="orden" class="form-control" value="0">
            <small>Los banners se muestran en orden ascendente (primero los números más bajos).</small>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" value="1" class="form-check-input" checked>
                <span class="form-check-label">Banner Activo</span>
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" name="guardar_banner" class="btn btn-primary">
                Guardar Banner
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Lista de banners existentes -->
<div class="admin-card">
    <div class="admin-card-title">Banners del Carrusel Principal</div>
    
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Orden</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($banners)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay banners configurados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($banners as $banner): ?>
                        <tr>
                            <td>
                                <?php if (!empty($banner['imagen'])): ?>
                                    <img src="<?php echo BANNERS_IMG_URL . '/' . $banner['imagen']; ?>" alt="<?php echo $banner['titulo']; ?>" style="max-width: 100px;">
                                <?php else: ?>
                                    Sin imagen
                                <?php endif; ?>
                            </td>
                            <td><?php echo $banner['titulo']; ?></td>
                            <td><?php echo substr($banner['descripcion'], 0, 50) . (strlen($banner['descripcion']) > 50 ? '...' : ''); ?></td>
                            <td><?php echo $banner['orden']; ?></td>
                            <td>
                                <span style="color: <?php echo $banner['activo'] ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $banner['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/banners.php?accion=editar&id=<?php echo $banner['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="<?php echo ADMIN_URL; ?>/banners.php?accion=toggle_activo&id=<?php echo $banner['id']; ?>" class="btn btn-<?php echo $banner['activo'] ? 'warning' : 'success'; ?> btn-sm">
                                    <?php echo $banner['activo'] ? 'Desactivar' : 'Activar'; ?>
                                </a>
                                <a href="<?php echo ADMIN_URL; ?>/banners.php?accion=eliminar&id=<?php echo $banner['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmarEliminacion('¿Está seguro que desea eliminar este banner?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>