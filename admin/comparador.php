<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

// Guardar nuevo comparador
if (isset($_POST['guardar_comparador'])) {
    try {
        $titulo = $_POST['titulo'];
        $categoria1_id = !empty($_POST['categoria1_id']) ? (int)$_POST['categoria1_id'] : null;
        $categoria1_titulo = $_POST['categoria1_titulo'];
        $categoria1_descripcion = $_POST['categoria1_descripcion'];
        $categoria2_id = !empty($_POST['categoria2_id']) ? (int)$_POST['categoria2_id'] : null;
        $categoria2_titulo = $_POST['categoria2_titulo'];
        $categoria2_descripcion = $_POST['categoria2_descripcion'];
        $orden = (int)$_POST['orden'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($titulo) || empty($categoria1_titulo) || empty($categoria2_titulo)) {
            throw new Exception("Los campos Título, Título Categoría 1 y Título Categoría 2 son obligatorios.");
        }
        
        // Procesar imágenes
        $categoria1_imagen = '';
        if (isset($_FILES['categoria1_imagen']) && $_FILES['categoria1_imagen']['error'] == 0) {
            // Crear directorio si no existe
            $directorio = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/comparadores';
            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }
            
            $categoria1_imagen = admin_subir_imagen($_FILES['categoria1_imagen'], $directorio);
            
            if (!$categoria1_imagen) {
                throw new Exception("Error al subir la imagen de la categoría 1.");
            }
        }
        
        $categoria2_imagen = '';
        if (isset($_FILES['categoria2_imagen']) && $_FILES['categoria2_imagen']['error'] == 0) {
            // Usar el mismo directorio que ya creamos
            $directorio = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/comparadores';
            
            $categoria2_imagen = admin_subir_imagen($_FILES['categoria2_imagen'], $directorio);
            
            if (!$categoria2_imagen) {
                throw new Exception("Error al subir la imagen de la categoría 2.");
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO comparador_categorias (
                titulo, categoria1_id, categoria1_titulo, categoria1_descripcion, categoria1_imagen,
                categoria2_id, categoria2_titulo, categoria2_descripcion, categoria2_imagen,
                orden, activo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $titulo, $categoria1_id, $categoria1_titulo, $categoria1_descripcion, $categoria1_imagen,
            $categoria2_id, $categoria2_titulo, $categoria2_descripcion, $categoria2_imagen,
            $orden, $activo
        ]);
        
        $mensaje = "Comparador guardado correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Actualizar comparador existente
if (isset($_POST['actualizar_comparador']) && isset($_POST['comparador_id'])) {
    try {
        $id = (int)$_POST['comparador_id'];
        $titulo = $_POST['titulo'];
        $categoria1_id = !empty($_POST['categoria1_id']) ? (int)$_POST['categoria1_id'] : null;
        $categoria1_titulo = $_POST['categoria1_titulo'];
        $categoria1_descripcion = $_POST['categoria1_descripcion'];
        $categoria2_id = !empty($_POST['categoria2_id']) ? (int)$_POST['categoria2_id'] : null;
        $categoria2_titulo = $_POST['categoria2_titulo'];
        $categoria2_descripcion = $_POST['categoria2_descripcion'];
        $orden = (int)$_POST['orden'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($titulo) || empty($categoria1_titulo) || empty($categoria2_titulo)) {
            throw new Exception("Los campos Título, Título Categoría 1 y Título Categoría 2 son obligatorios.");
        }
        
        // Obtener datos actuales
        $stmt = $conn->prepare("
            SELECT categoria1_imagen, categoria2_imagen 
            FROM comparador_categorias 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $comparador_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Procesar imágenes
        $categoria1_imagen = $comparador_actual['categoria1_imagen'];
        if (isset($_FILES['categoria1_imagen']) && $_FILES['categoria1_imagen']['error'] == 0) {
            // Crear directorio si no existe
            $directorio = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/comparadores';
            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }
            
            $nueva_imagen = admin_subir_imagen($_FILES['categoria1_imagen'], $directorio);
            
            if ($nueva_imagen) {
                // Eliminar imagen anterior si existe
                if (!empty($categoria1_imagen)) {
                    $ruta_imagen = $directorio . '/' . $categoria1_imagen;
                    if (file_exists($ruta_imagen)) {
                        unlink($ruta_imagen);
                    }
                }
                
                $categoria1_imagen = $nueva_imagen;
            }
        }
        
        $categoria2_imagen = $comparador_actual['categoria2_imagen'];
        if (isset($_FILES['categoria2_imagen']) && $_FILES['categoria2_imagen']['error'] == 0) {
            // Usar el mismo directorio
            $directorio = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/comparadores';
            
            $nueva_imagen = admin_subir_imagen($_FILES['categoria2_imagen'], $directorio);
            
            if ($nueva_imagen) {
                // Eliminar imagen anterior si existe
                if (!empty($categoria2_imagen)) {
                    $ruta_imagen = $directorio . '/' . $categoria2_imagen;
                    if (file_exists($ruta_imagen)) {
                        unlink($ruta_imagen);
                    }
                }
                
                $categoria2_imagen = $nueva_imagen;
            }
        }
        
        $stmt = $conn->prepare("
            UPDATE comparador_categorias
            SET titulo = ?, 
                categoria1_id = ?, categoria1_titulo = ?, categoria1_descripcion = ?, categoria1_imagen = ?,
                categoria2_id = ?, categoria2_titulo = ?, categoria2_descripcion = ?, categoria2_imagen = ?,
                orden = ?, activo = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $titulo, 
            $categoria1_id, $categoria1_titulo, $categoria1_descripcion, $categoria1_imagen,
            $categoria2_id, $categoria2_titulo, $categoria2_descripcion, $categoria2_imagen,
            $orden, $activo, $id
        ]);
        
        $mensaje = "Comparador actualizado correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Eliminar comparador
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        // Obtener imágenes para eliminarlas
        $stmt = $conn->prepare("
            SELECT categoria1_imagen, categoria2_imagen 
            FROM comparador_categorias 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $comparador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($comparador) {
            $directorio = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/comparadores';
            
            // Eliminar imagen 1 si existe
            if (!empty($comparador['categoria1_imagen'])) {
                $ruta_imagen = $directorio . '/' . $comparador['categoria1_imagen'];
                if (file_exists($ruta_imagen)) {
                    unlink($ruta_imagen);
                }
            }
            
            // Eliminar imagen 2 si existe
            if (!empty($comparador['categoria2_imagen'])) {
                $ruta_imagen = $directorio . '/' . $comparador['categoria2_imagen'];
                if (file_exists($ruta_imagen)) {
                    unlink($ruta_imagen);
                }
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM comparador_categorias WHERE id = ?");
        $stmt->execute([$id]);
        
        $mensaje = "Comparador eliminado correctamente.";
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
        $stmt = $conn->prepare("SELECT activo FROM comparador_categorias WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            $nuevo_estado = $resultado['activo'] ? 0 : 1;
            
            $stmt = $conn->prepare("UPDATE comparador_categorias SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            
            $mensaje = "Estado actualizado correctamente.";
            $tipo_mensaje = "success";
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener comparador para editar
$comparador_editar = null;
if (isset($_GET['accion']) && $_GET['accion'] == 'editar' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("SELECT * FROM comparador_categorias WHERE id = ?");
        $stmt->execute([$id]);
        $comparador_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener lista de comparadores
try {
    $stmt = $conn->prepare("
        SELECT c.*, c1.nombre as categoria1_nombre, c2.nombre as categoria2_nombre
        FROM comparador_categorias c
        LEFT JOIN categorias c1 ON c.categoria1_id = c1.id
        LEFT JOIN categorias c2 ON c.categoria2_id = c2.id
        ORDER BY c.orden ASC
    ");
    $stmt->execute();
    $comparadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "Error al cargar comparadores: " . $e->getMessage();
    $tipo_mensaje = "danger";
    $comparadores = [];
}

// Obtener categorías para el selector
$categorias = admin_obtener_categorias_select();

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Gestión de Comparadores</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; 
        <a href="<?php echo ADMIN_URL; ?>/editar-inicio.php">Editar Inicio</a> &gt; 
        Comparadores
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Formulario para añadir/editar comparador -->
<div class="admin-card">
    <div class="admin-card-title">
        <?php echo $comparador_editar ? 'Editar Comparador' : 'Añadir Nuevo Comparador'; ?>
    </div>
    
    <form action="" method="post" enctype="multipart/form-data">
        <?php if ($comparador_editar): ?>
            <input type="hidden" name="comparador_id" value="<?php echo $comparador_editar['id']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="titulo" class="form-label">Título Principal *</label>
            <input type="text" name="titulo" id="titulo" class="form-control" value="<?php echo $comparador_editar ? $comparador_editar['titulo'] : ''; ?>" required>
            <small>Ejemplo: "¿Qué te conviene más?"</small>
        </div>
        
        <div class="form-group">
            <label for="orden" class="form-label">Orden</label>
            <input type="number" name="orden" id="orden" class="form-control" value="<?php echo $comparador_editar ? $comparador_editar['orden'] : '0'; ?>">
            <small>Los números más bajos aparecen primero.</small>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" class="form-check-input" <?php echo (!$comparador_editar || $comparador_editar['activo']) ? 'checked' : ''; ?>>
                <span class="form-check-label">Comparador Activo</span>
            </label>
        </div>
        
        <hr style="margin: 30px 0;">
        
        <div style="display: flex; flex-wrap: wrap; gap: 30px;">
            <!-- Categoría 1 -->
            <div style="flex: 1; min-width: 300px;">
                <h3 style="margin-bottom: 20px;">Categoría/Opción 1</h3>
                
                <div class="form-group">
                    <label for="categoria1_id" class="form-label">Categoría (opcional)</label>
                    <select name="categoria1_id" id="categoria1_id" class="form-select">
                        <option value="">Seleccionar categoría</option>
                        <?php foreach ($categorias as $id => $nombre): ?>
                            <option value="<?php echo $id; ?>" <?php echo ($comparador_editar && $comparador_editar['categoria1_id'] == $id) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Si selecciona una categoría, el enlace llevará a esa categoría.</small>
                </div>
                
                <div class="form-group">
                    <label for="categoria1_titulo" class="form-label">Título *</label>
                    <input type="text" name="categoria1_titulo" id="categoria1_titulo" class="form-control" value="<?php echo $comparador_editar ? $comparador_editar['categoria1_titulo'] : ''; ?>" required>
                    <small>Ejemplo: "PC para Gaming"</small>
                </div>
                
                <div class="form-group">
                    <label for="categoria1_descripcion" class="form-label">Descripción</label>
                    <textarea name="categoria1_descripcion" id="categoria1_descripcion" class="form-control" rows="3"><?php echo $comparador_editar ? $comparador_editar['categoria1_descripcion'] : ''; ?></textarea>
                    <small>Breve descripción que aparecerá debajo del título.</small>
                </div>
                
                <div class="form-group">
                    <label for="categoria1_imagen" class="form-label">Imagen</label>
                    <input type="file" name="categoria1_imagen" id="categoria1_imagen" class="form-control" accept="image/*">
                    
                    <?php if ($comparador_editar && !empty($comparador_editar['categoria1_imagen'])): ?>
                        <div style="margin-top: 10px;">
                            <img src="<?php echo BASE_URL; ?>/public/assets/images/comparadores/<?php echo $comparador_editar['categoria1_imagen']; ?>" alt="Imagen actual" style="max-width: 200px; max-height: 150px;">
                            <p>Imagen actual. Sube una nueva imagen para reemplazarla.</p>
                        </div>
                    <?php endif; ?>
                    
                    <small>Tamaño recomendado: 600x400 píxeles.</small>
                </div>
            </div>
            
            <!-- Categoría 2 -->
            <div style="flex: 1; min-width: 300px;">
                <h3 style="margin-bottom: 20px;">Categoría/Opción 2</h3>
                
                <div class="form-group">
                    <label for="categoria2_id" class="form-label">Categoría (opcional)</label>
                    <select name="categoria2_id" id="categoria2_id" class="form-select">
                        <option value="">Seleccionar categoría</option>
                        <?php foreach ($categorias as $id => $nombre): ?>
                            <option value="<?php echo $id; ?>" <?php echo ($comparador_editar && $comparador_editar['categoria2_id'] == $id) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Si selecciona una categoría, el enlace llevará a esa categoría.</small>
                </div>
                
                <div class="form-group">
                    <label for="categoria2_titulo" class="form-label">Título *</label>
                    <input type="text" name="categoria2_titulo" id="categoria2_titulo" class="form-control" value="<?php echo $comparador_editar ? $comparador_editar['categoria2_titulo'] : ''; ?>" required>
                    <small>Ejemplo: "PC para Trabajo"</small>
                </div>
                
                <div class="form-group">
                    <label for="categoria2_descripcion" class="form-label">Descripción</label>
                    <textarea name="categoria2_descripcion" id="categoria2_descripcion" class="form-control" rows="3"><?php echo $comparador_editar ? $comparador_editar['categoria2_descripcion'] : ''; ?></textarea>
                    <small>Breve descripción que aparecerá debajo del título.</small>
                </div>
                
                <div class="form-group">
                    <label for="categoria2_imagen" class="form-label">Imagen</label>
                    <input type="file" name="categoria2_imagen" id="categoria2_imagen" class="form-control" accept="image/*">
                    
                    <?php if ($comparador_editar && !empty($comparador_editar['categoria2_imagen'])): ?>
                        <div style="margin-top: 10px;">
                            <img src="<?php echo BASE_URL; ?>/public/assets/images/comparadores/<?php echo $comparador_editar['categoria2_imagen']; ?>" alt="Imagen actual" style="max-width: 200px; max-height: 150px;">
                            <p>Imagen actual. Sube una nueva imagen para reemplazarla.</p>
                        </div>
                    <?php endif; ?>
                    
                    <small>Tamaño recomendado: 600x400 píxeles.</small>
                </div>
            </div>
        </div>
        
        <div class="form-group" style="margin-top: 30px;">
            <button type="submit" name="<?php echo $comparador_editar ? 'actualizar_comparador' : 'guardar_comparador'; ?>" class="btn btn-primary">
                <?php echo $comparador_editar ? 'Actualizar Comparador' : 'Guardar Comparador'; ?>
            </button>
            
            <?php if ($comparador_editar): ?>
                <a href="<?php echo ADMIN_URL; ?>/comparador.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Lista de comparadores -->
<div class="admin-card">
    <div class="admin-card-title">Comparadores Existentes</div>
    
    <?php if (empty($comparadores)): ?>
        <p>No hay comparadores registrados. Utilice el formulario para crear nuevos comparadores.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Título</th>
                        <th>Opción 1</th>
                        <th>Opción 2</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comparadores as $comparador): ?>
                        <tr>
                            <td><?php echo $comparador['orden']; ?></td>
                            <td><?php echo $comparador['titulo']; ?></td>
                            <td>
                                <?php echo $comparador['categoria1_titulo']; ?>
                                <?php if (!empty($comparador['categoria1_nombre'])): ?>
                                    <br><small>Categoría: <?php echo $comparador['categoria1_nombre']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $comparador['categoria2_titulo']; ?>
                                <?php if (!empty($comparador['categoria2_nombre'])): ?>
                                    <br><small>Categoría: <?php echo $comparador['categoria2_nombre']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color: <?php echo $comparador['activo'] ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $comparador['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/comparador.php?accion=editar&id=<?php echo $comparador['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="<?php echo ADMIN_URL; ?>/comparador.php?accion=toggle_activo&id=<?php echo $comparador['id']; ?>" class="btn btn-<?php echo $comparador['activo'] ? 'warning' : 'success'; ?> btn-sm">
                                    <?php echo $comparador['activo'] ? 'Desactivar' : 'Activar'; ?>
                                </a>
                                <a href="<?php echo ADMIN_URL; ?>/comparador.php?accion=eliminar&id=<?php echo $comparador['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro que desea eliminar este comparador?')">Eliminar</a>
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