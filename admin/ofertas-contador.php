<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

// Guardar nueva oferta con contador
if (isset($_POST['guardar_oferta'])) {
    try {
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $url = $_POST['url'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($titulo) || empty($fecha_inicio) || empty($fecha_fin)) {
            throw new Exception("Los campos Título, Fecha de inicio y Fecha de fin son obligatorios.");
        }
        
        // Validar fechas
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);
        
        if ($fin <= $inicio) {
            throw new Exception("La fecha de fin debe ser posterior a la fecha de inicio.");
        }
        
        // Procesar imagen si se ha subido
        $imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            // Crear directorio si no existe
            $directorio = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/ofertas';
            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }
            
            $imagen = admin_subir_imagen($_FILES['imagen'], $directorio);
            
            if (!$imagen) {
                throw new Exception("Error al subir la imagen.");
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO ofertas_contador (titulo, descripcion, imagen, fecha_inicio, fecha_fin, url, activo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$titulo, $descripcion, $imagen, $fecha_inicio, $fecha_fin, $url, $activo]);
        
        $mensaje = "Oferta con contador guardada correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Actualizar oferta existente
if (isset($_POST['actualizar_oferta']) && isset($_POST['oferta_id'])) {
    try {
        $id = (int)$_POST['oferta_id'];
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $url = $_POST['url'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($titulo) || empty($fecha_inicio) || empty($fecha_fin)) {
            throw new Exception("Los campos Título, Fecha de inicio y Fecha de fin son obligatorios.");
        }
        
        // Validar fechas
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);
        
        if ($fin <= $inicio) {
            throw new Exception("La fecha de fin debe ser posterior a la fecha de inicio.");
        }
        
        // Obtener datos actuales
        $stmt = $conn->prepare("SELECT imagen FROM ofertas_contador WHERE id = ?");
        $stmt->execute([$id]);
        $oferta_actual = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Procesar imagen si se ha subido
        $imagen = $oferta_actual['imagen'];
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            // Crear directorio si no existe
            $directorio = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/ofertas';
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
            UPDATE ofertas_contador
            SET titulo = ?, descripcion = ?, imagen = ?, fecha_inicio = ?, 
                fecha_fin = ?, url = ?, activo = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$titulo, $descripcion, $imagen, $fecha_inicio, $fecha_fin, $url, $activo, $id]);
        
        $mensaje = "Oferta con contador actualizada correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Eliminar oferta
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        // Obtener imagen para eliminarla
        $stmt = $conn->prepare("SELECT imagen FROM ofertas_contador WHERE id = ?");
        $stmt->execute([$id]);
        $oferta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($oferta && !empty($oferta['imagen'])) {
            $ruta_imagen = $_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/public/assets/images/ofertas/' . $oferta['imagen'];
            if (file_exists($ruta_imagen)) {
                unlink($ruta_imagen);
            }
        }
        
        $stmt = $conn->prepare("DELETE FROM ofertas_contador WHERE id = ?");
        $stmt->execute([$id]);
        
        $mensaje = "Oferta con contador eliminada correctamente.";
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
        $stmt = $conn->prepare("SELECT activo FROM ofertas_contador WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            $nuevo_estado = $resultado['activo'] ? 0 : 1;
            
            $stmt = $conn->prepare("UPDATE ofertas_contador SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            
            $mensaje = "Estado actualizado correctamente.";
            $tipo_mensaje = "success";
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener oferta para editar
$oferta_editar = null;
if (isset($_GET['accion']) && $_GET['accion'] == 'editar' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("SELECT * FROM ofertas_contador WHERE id = ?");
        $stmt->execute([$id]);
        $oferta_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener lista de ofertas
try {
    $stmt = $conn->prepare("
        SELECT * FROM ofertas_contador 
        ORDER BY fecha_fin DESC
    ");
    $stmt->execute();
    $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "Error al cargar ofertas: " . $e->getMessage();
    $tipo_mensaje = "danger";
    $ofertas = [];
}

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');

// Fechas para el formulario
$fecha_actual = date('Y-m-d\TH:i');
$fecha_fin_default = date('Y-m-d\TH:i', strtotime('+7 days'));
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Gestión de Ofertas con Contador</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; 
        <a href="<?php echo ADMIN_URL; ?>/editar-inicio.php">Editar Inicio</a> &gt; 
        Ofertas con Contador
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Formulario para añadir/editar oferta -->
<div class="admin-card">
    <div class="admin-card-title">
        <?php echo $oferta_editar ? 'Editar Oferta con Contador' : 'Añadir Nueva Oferta con Contador'; ?>
    </div>
    
    <form action="" method="post" enctype="multipart/form-data">
        <?php if ($oferta_editar): ?>
            <input type="hidden" name="oferta_id" value="<?php echo $oferta_editar['id']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="titulo" class="form-label">Título *</label>
            <input type="text" name="titulo" id="titulo" class="form-control" value="<?php echo $oferta_editar ? $oferta_editar['titulo'] : ''; ?>" required>
            <small>Ejemplo: "¡CYBER WEEK! Descuentos de hasta 40%"</small>
        </div>
        
        <div class="form-group">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="3"><?php echo $oferta_editar ? $oferta_editar['descripcion'] : ''; ?></textarea>
            <small>Breve descripción de la oferta.</small>
        </div>
        
        <div class="form-group">
            <label for="imagen" class="form-label">Imagen de Fondo</label>
            <input type="file" name="imagen" id="imagen" class="form-control" accept="image/*">
            
            <?php if ($oferta_editar && !empty($oferta_editar['imagen'])): ?>
                <div style="margin-top: 10px;">
                    <img src="<?php echo BASE_URL; ?>/public/assets/images/ofertas/<?php echo $oferta_editar['imagen']; ?>" alt="Imagen actual" style="max-width: 200px; max-height: 100px;">
                    <p>Imagen actual. Sube una nueva imagen para reemplazarla.</p>
                </div>
            <?php endif; ?>
            
            <small>Tamaño recomendado: 1200x300 píxeles.</small>
        </div>
        
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" class="form-control" 
                       value="<?php echo $oferta_editar ? date('Y-m-d\TH:i', strtotime($oferta_editar['fecha_inicio'])) : $fecha_actual; ?>" required>
            </div>
            
            <div class="form-group" style="flex: 1; min-width: 200px;">
                <label for="fecha_fin" class="form-label">Fecha de Fin *</label>
                <input type="datetime-local" name="fecha_fin" id="fecha_fin" class="form-control" 
                       value="<?php echo $oferta_editar ? date('Y-m-d\TH:i', strtotime($oferta_editar['fecha_fin'])) : $fecha_fin_default; ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="url" class="form-label">URL de destino</label>
            <input type="text" name="url" id="url" class="form-control" value="<?php echo $oferta_editar ? $oferta_editar['url'] : '/public/busqueda.php?q=oferta'; ?>">
            <small>Página a la que dirigirá al hacer clic en la oferta.</small>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" class="form-check-input" <?php echo (!$oferta_editar || $oferta_editar['activo']) ? 'checked' : ''; ?>>
                <span class="form-check-label">Oferta Activa</span>
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" name="<?php echo $oferta_editar ? 'actualizar_oferta' : 'guardar_oferta'; ?>" class="btn btn-primary">
                <?php echo $oferta_editar ? 'Actualizar Oferta' : 'Guardar Oferta'; ?>
            </button>
            
            <?php if ($oferta_editar): ?>
                <a href="<?php echo ADMIN_URL; ?>/ofertas-contador.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Lista de ofertas -->
<div class="admin-card">
    <div class="admin-card-title">Ofertas con Contador</div>
    
    <?php if (empty($ofertas)): ?>
        <p>No hay ofertas con contador registradas. Utilice el formulario para crear nuevas ofertas.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Imagen</th>
                        <th>Título</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Estado</th>
                        <th>Tiempo Restante</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ofertas as $oferta): 
                        $fecha_actual = new DateTime();
                        $fecha_fin = new DateTime($oferta['fecha_fin']);
                        $fecha_inicio = new DateTime($oferta['fecha_inicio']);
                        $diferencia = $fecha_fin->diff($fecha_actual);
                        $esta_activa = $fecha_actual >= $fecha_inicio && $fecha_actual <= $fecha_fin && $oferta['activo'];
                        $ha_terminado = $fecha_actual > $fecha_fin;
                        $no_ha_comenzado = $fecha_actual < $fecha_inicio;
                    ?>
                        <tr>
                            <td>
                                <?php if (!empty($oferta['imagen'])): ?>
                                    <img src="<?php echo BASE_URL; ?>/public/assets/images/ofertas/<?php echo $oferta['imagen']; ?>" alt="<?php echo $oferta['titulo']; ?>" style="max-width: 80px; max-height: 40px;">
                                <?php else: ?>
                                    <div style="width: 80px; height: 40px; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #999;">
                                        Sin imagen
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $oferta['titulo']; ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($oferta['fecha_inicio'])); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($oferta['fecha_fin'])); ?></td>
                            <td>
                                <?php if ($ha_terminado): ?>
                                    <span style="color: #dc3545;">Finalizada</span>
                                <?php elseif ($no_ha_comenzado): ?>
                                    <span style="color: #FFC107;">Programada</span>
                                <?php elseif ($esta_activa): ?>
                                    <span style="color: #28a745;">Activa</span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ha_terminado): ?>
                                    <span>Finalizada</span>
                                <?php elseif ($no_ha_comenzado): ?>
                                    <span>Inicia en: <?php echo $diferencia->days; ?> días</span>
                                <?php else: ?>
                                    <span><?php echo $diferencia->days; ?>d <?php echo $diferencia->h; ?>h <?php echo $diferencia->i; ?>m</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/ofertas-contador.php?accion=editar&id=<?php echo $oferta['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="<?php echo ADMIN_URL; ?>/ofertas-contador.php?accion=toggle_activo&id=<?php echo $oferta['id']; ?>" class="btn btn-<?php echo $oferta['activo'] ? 'warning' : 'success'; ?> btn-sm">
                                    <?php echo $oferta['activo'] ? 'Desactivar' : 'Activar'; ?>
                                </a>
                                <a href="<?php echo ADMIN_URL; ?>/ofertas-contador.php?accion=eliminar&id=<?php echo $oferta['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro que desea eliminar esta oferta?')">Eliminar</a>
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