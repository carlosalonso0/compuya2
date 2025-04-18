<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Procesar formularios
$mensaje = '';
$tipo_mensaje = '';

// Guardar nueva estadística
if (isset($_POST['guardar_estadistica'])) {
    try {
        $titulo = $_POST['titulo'];
        $valor = $_POST['valor'];
        $icono = $_POST['icono'];
        $orden = (int)$_POST['orden'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($titulo) || empty($valor)) {
            throw new Exception("Los campos Título y Valor son obligatorios.");
        }
        
        $stmt = $conn->prepare("
            INSERT INTO estadisticas_inicio (titulo, valor, icono, orden, activo)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$titulo, $valor, $icono, $orden, $activo]);
        
        $mensaje = "Estadística guardada correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Actualizar estadística existente
if (isset($_POST['actualizar_estadistica']) && isset($_POST['estadistica_id'])) {
    try {
        $id = (int)$_POST['estadistica_id'];
        $titulo = $_POST['titulo'];
        $valor = $_POST['valor'];
        $icono = $_POST['icono'];
        $orden = (int)$_POST['orden'];
        $activo = isset($_POST['activo']) ? 1 : 0;
        
        if (empty($titulo) || empty($valor)) {
            throw new Exception("Los campos Título y Valor son obligatorios.");
        }
        
        $stmt = $conn->prepare("
            UPDATE estadisticas_inicio
            SET titulo = ?, valor = ?, icono = ?, orden = ?, activo = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$titulo, $valor, $icono, $orden, $activo, $id]);
        
        $mensaje = "Estadística actualizada correctamente.";
        $tipo_mensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Eliminar estadística
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("DELETE FROM estadisticas_inicio WHERE id = ?");
        $stmt->execute([$id]);
        
        $mensaje = "Estadística eliminada correctamente.";
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
        $stmt = $conn->prepare("SELECT activo FROM estadisticas_inicio WHERE id = ?");
        $stmt->execute([$id]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado) {
            $nuevo_estado = $resultado['activo'] ? 0 : 1;
            
            $stmt = $conn->prepare("UPDATE estadisticas_inicio SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            
            $mensaje = "Estado actualizado correctamente.";
            $tipo_mensaje = "success";
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener estadística para editar
$estadistica_editar = null;
if (isset($_GET['accion']) && $_GET['accion'] == 'editar' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        $stmt = $conn->prepare("SELECT * FROM estadisticas_inicio WHERE id = ?");
        $stmt->execute([$id]);
        $estadistica_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener lista de estadísticas
try {
    $stmt = $conn->prepare("SELECT * FROM estadisticas_inicio ORDER BY orden ASC");
    $stmt->execute();
    $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mensaje = "Error al cargar estadísticas: " . $e->getMessage();
    $tipo_mensaje = "danger";
    $estadisticas = [];
}

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Gestión de Estadísticas</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; 
        <a href="<?php echo ADMIN_URL; ?>/editar-inicio.php">Editar Inicio</a> &gt; 
        Estadísticas
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Formulario para añadir/editar estadística -->
<div class="admin-card">
    <div class="admin-card-title">
        <?php echo $estadistica_editar ? 'Editar Estadística' : 'Añadir Nueva Estadística'; ?>
    </div>
    
    <form action="" method="post">
        <?php if ($estadistica_editar): ?>
            <input type="hidden" name="estadistica_id" value="<?php echo $estadistica_editar['id']; ?>">
        <?php endif; ?>
        
        <div class="form-group">
            <label for="titulo" class="form-label">Título *</label>
            <input type="text" name="titulo" id="titulo" class="form-control" value="<?php echo $estadistica_editar ? $estadistica_editar['titulo'] : ''; ?>" required>
            <small>Ejemplo: Clientes Satisfechos, Productos en Catálogo, etc.</small>
        </div>
        
        <div class="form-group">
            <label for="valor" class="form-label">Valor *</label>
            <input type="text" name="valor" id="valor" class="form-control" value="<?php echo $estadistica_editar ? $estadistica_editar['valor'] : ''; ?>" required>
            <small>Ejemplo: +10,000, 500, 8, etc. Puede incluir símbolos como + o %.</small>
        </div>
        
        <div class="form-group">
            <label for="icono" class="form-label">Icono (FontAwesome)</label>
            <input type="text" name="icono" id="icono" class="form-control" value="<?php echo $estadistica_editar ? $estadistica_editar['icono'] : ''; ?>">
            <small>Nombre del icono de FontAwesome sin el prefijo "fa-". Ejemplos: users, box, calendar, truck, etc.</small>
        </div>
        
        <div class="form-group">
            <label for="orden" class="form-label">Orden</label>
            <input type="number" name="orden" id="orden" class="form-control" value="<?php echo $estadistica_editar ? $estadistica_editar['orden'] : '0'; ?>">
            <small>Los números más bajos aparecen primero.</small>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" class="form-check-input" <?php echo (!$estadistica_editar || $estadistica_editar['activo']) ? 'checked' : ''; ?>>
                <span class="form-check-label">Estadística Activa</span>
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" name="<?php echo $estadistica_editar ? 'actualizar_estadistica' : 'guardar_estadistica'; ?>" class="btn btn-primary">
                <?php echo $estadistica_editar ? 'Actualizar Estadística' : 'Guardar Estadística'; ?>
            </button>
            
            <?php if ($estadistica_editar): ?>
                <a href="<?php echo ADMIN_URL; ?>/estadisticas.php" class="btn btn-secondary">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Lista de estadísticas -->
<div class="admin-card">
    <div class="admin-card-title">Estadísticas Existentes</div>
    
    <?php if (empty($estadisticas)): ?>
        <p>No hay estadísticas registradas. Utilice el formulario para crear nuevas estadísticas.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Título</th>
                        <th>Valor</th>
                        <th>Icono</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estadisticas as $estadistica): ?>
                        <tr>
                            <td><?php echo $estadistica['orden']; ?></td>
                            <td><?php echo $estadistica['titulo']; ?></td>
                            <td><?php echo $estadistica['valor']; ?></td>
                            <td>
                                <?php if (!empty($estadistica['icono'])): ?>
                                    <i class="fas fa-<?php echo $estadistica['icono']; ?>"></i>
                                    (<?php echo $estadistica['icono']; ?>)
                                <?php else: ?>
                                    Sin icono
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="color: <?php echo $estadistica['activo'] ? '#28a745' : '#dc3545'; ?>;">
                                    <?php echo $estadistica['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo ADMIN_URL; ?>/estadisticas.php?accion=editar&id=<?php echo $estadistica['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="<?php echo ADMIN_URL; ?>/estadisticas.php?accion=toggle_activo&id=<?php echo $estadistica['id']; ?>" class="btn btn-<?php echo $estadistica['activo'] ? 'warning' : 'success'; ?> btn-sm">
                                    <?php echo $estadistica['activo'] ? 'Desactivar' : 'Activar'; ?>
                                </a>
                                <a href="<?php echo ADMIN_URL; ?>/estadisticas.php?accion=eliminar&id=<?php echo $estadistica['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro que desea eliminar esta estadística?')">Eliminar</a>
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