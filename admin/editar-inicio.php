<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Manejar acciones
$mensaje = '';
$tipo_mensaje = '';

// Acción: Actualizar orden de secciones
if (isset($_POST['accion']) && $_POST['accion'] == 'actualizar_orden') {
    if (isset($_POST['orden']) && is_array($_POST['orden'])) {
        if (admin_actualizar_orden_secciones($_POST['orden'])) {
            $mensaje = 'Orden de secciones actualizado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al actualizar el orden de secciones.';
            $tipo_mensaje = 'danger';
        }
    }
}

// Acción: Activar/Desactivar sección
if (isset($_GET['accion']) && $_GET['accion'] == 'toggle_activo' && isset($_GET['id'])) {
    $seccion = admin_obtener_seccion($_GET['id']);
    if ($seccion) {
        $nuevo_estado = $seccion['activo'] ? 0 : 1;
        if (admin_actualizar_seccion($_GET['id'], ['activo' => $nuevo_estado])) {
            $mensaje = 'Estado de la sección actualizado correctamente.';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al actualizar el estado de la sección.';
            $tipo_mensaje = 'danger';
        }
    }
}

// Acción: Editar sección
if (isset($_POST['accion']) && $_POST['accion'] == 'editar_seccion' && isset($_POST['seccion_id'])) {
    $datos = [
        'titulo_mostrar' => $_POST['titulo_mostrar'],
        'activo' => isset($_POST['activo']) ? 1 : 0
    ];
    
    if ($_POST['tipo'] == 'categoria' && isset($_POST['categoria_id'])) {
        $datos['categoria_id'] = $_POST['categoria_id'];
    }
    
    if (admin_actualizar_seccion($_POST['seccion_id'], $datos)) {
        $mensaje = 'Sección actualizada correctamente.';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al actualizar la sección.';
        $tipo_mensaje = 'danger';
    }
}

// Acción: Crear sección
if (isset($_POST['accion']) && $_POST['accion'] == 'crear_seccion') {
    $datos = [
        'nombre' => strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['nombre'])),
        'titulo_mostrar' => $_POST['titulo_mostrar'],
        'tipo' => $_POST['tipo'],
        'activo' => isset($_POST['activo']) ? 1 : 0,
        'orden' => 999 // Se pondrá al final
    ];
    
    if ($_POST['tipo'] == 'categoria' && isset($_POST['categoria_id'])) {
        $datos['categoria_id'] = $_POST['categoria_id'];
    }
    
    if (admin_crear_seccion($datos)) {
        $mensaje = 'Sección creada correctamente.';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al crear la sección.';
        $tipo_mensaje = 'danger';
    }
}

// Obtener secciones para mostrar
$secciones = admin_obtener_secciones();

// Obtener categorías para el selector
$categorias = admin_obtener_categorias_select();

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Editar Página de Inicio</h1>
    <div class="admin-breadcrumb">
        <a href="<?php echo ADMIN_URL; ?>">Dashboard</a> &gt; Editar Inicio
    </div>
</div>

<?php if (!empty($mensaje)): ?>
<div class="alert alert-<?php echo $tipo_mensaje; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Secciones de la página de inicio -->
<div class="admin-card">
    <div class="admin-card-title d-flex justify-between align-center">
        <div>Secciones de la Página de Inicio</div>
        <button class="btn btn-primary" onclick="mostrarFormularioCrear()">Agregar Sección</button>
    </div>
    
    <p class="mb-10">Ordena las secciones arrastrándolas a la posición deseada. Puedes activar o desactivar cada sección con el botón correspondiente.</p>
    
    <form id="formOrden" method="post" action="">
        <input type="hidden" name="accion" value="actualizar_orden">
        <ul id="listaSecciones" class="sortable-list">
            <?php foreach ($secciones as $seccion): ?>
                <li class="sortable-item" data-id="<?php echo $seccion['id']; ?>">
                    <div>
                        <input type="hidden" name="orden[]" value="<?php echo $seccion['id']; ?>">
                        <span style="font-weight: bold;"><?php echo $seccion['titulo_mostrar']; ?></span>
                        <span style="color: #777; margin-left: 10px;">(<?php echo ucfirst($seccion['tipo']); ?>)</span>
                        <span style="color: <?php echo $seccion['activo'] ? '#28a745' : '#dc3545'; ?>; margin-left: 10px;">
                            [<?php echo $seccion['activo'] ? 'Activo' : 'Inactivo'; ?>]
                        </span>
                    </div>
                    <div>
    <a href="<?php echo ADMIN_URL; ?>/editar-inicio.php?accion=toggle_activo&id=<?php echo $seccion['id']; ?>" class="btn btn-<?php echo $seccion['activo'] ? 'warning' : 'success'; ?> btn-sm">
        <?php echo $seccion['activo'] ? 'Desactivar' : 'Activar'; ?>
    </a>
    <button type="button" class="btn btn-primary btn-sm" onclick="editarSeccion(<?php echo $seccion['id']; ?>, '<?php echo $seccion['titulo_mostrar']; ?>', '<?php echo $seccion['tipo']; ?>', <?php echo $seccion['categoria_id'] ? $seccion['categoria_id'] : 'null'; ?>, <?php echo $seccion['activo']; ?>)">
        Editar
    </button>
    <?php
// Determinar la URL correcta para gestionar cada tipo de sección
$gestion_url = '';

if ($seccion['tipo'] == 'carrusel' || $seccion['tipo'] == 'banner_doble' || $seccion['tipo'] == 'categoria') {
    // Usar editar-seccion.php para tipos estándar
    $gestion_url = ADMIN_URL . '/editar-seccion.php?id=' . $seccion['id'];
} else {
    // Usar páginas específicas para tipos especiales basadas en el tipo, no en el nombre
    switch ($seccion['tipo']) {
        case 'estadisticas':
            $gestion_url = ADMIN_URL . '/estadisticas.php';
            break;
        case 'blogs_guias':
            $gestion_url = ADMIN_URL . '/blogs.php';
            break;
        case 'comparador':
            $gestion_url = ADMIN_URL . '/comparador.php';
            break;
        case 'ofertas_contador':
            $gestion_url = ADMIN_URL . '/ofertas-contador.php';
            break;
        default:
            // También verificar por nombre para casos especiales
            if ($seccion['nombre'] == 'estadistica') {
                $gestion_url = ADMIN_URL . '/estadisticas.php';
            } elseif ($seccion['nombre'] == 'contador') {
                $gestion_url = ADMIN_URL . '/ofertas-contador.php';
            } elseif ($seccion['nombre'] == 'blog') {
                $gestion_url = ADMIN_URL . '/blogs.php';
            } else {
                // Si no coincide con ningún caso específico, usar la URL genérica
                $gestion_url = ADMIN_URL . '/editar-seccion.php?id=' . $seccion['id'];
            }
    }
}
?>
    <a href="<?php echo $gestion_url; ?>" class="btn btn-success btn-sm">
        Gestionar
    </a>
    <a href="<?php echo ADMIN_URL; ?>/editar-inicio.php?accion=eliminar&id=<?php echo $seccion['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmarEliminacion('¿Está seguro que desea eliminar esta sección?');">
        Eliminar
    </a>
</div>
                </li>
            <?php endforeach; ?>
        </ul>
        
        <div class="mt-10">
            <button type="submit" class="btn btn-primary">Guardar Orden</button>
        </div>
    </form>
</div>

<!-- Formulario para editar sección (oculto por defecto) -->
<div id="formEditarSeccion" class="admin-card" style="display: none;">
    <div class="admin-card-title">Editar Sección</div>
    
    <form method="post" action="">
        <input type="hidden" name="accion" value="editar_seccion">
        <input type="hidden" name="seccion_id" id="editSeccionId">
        
        <div class="form-group">
            <label for="editTituloMostrar" class="form-label">Título a Mostrar</label>
            <input type="text" name="titulo_mostrar" id="editTituloMostrar" class="form-control" required>
        </div>
        
        <div id="editCategoriaGroup" class="form-group" style="display: none;">
            <label for="editCategoriaId" class="form-label">Categoría</label>
            <select name="categoria_id" id="editCategoriaId" class="form-select">
                <option value="">Seleccione una categoría</option>
                <?php foreach ($categorias as $id => $nombre): ?>
                    <option value="<?php echo $id; ?>"><?php echo $nombre; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" id="editActivo" class="form-check-input">
                <span class="form-check-label">Sección Activa</span>
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <button type="button" class="btn btn-danger" onclick="ocultarFormularioEditar()">Cancelar</button>
        </div>
    </form>
</div>

<!-- Formulario para crear sección (oculto por defecto) -->
<div id="formCrearSeccion" class="admin-card" style="display: none;">
    <div class="admin-card-title">Agregar Nueva Sección</div>
    
    <form method="post" action="">
        <input type="hidden" name="accion" value="crear_seccion">
        
        <div class="form-group">
            <label for="nombre" class="form-label">Nombre (sin espacios ni caracteres especiales)</label>
            <input type="text" name="nombre" id="nombre" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="titulo_mostrar" class="form-label">Título a Mostrar</label>
            <input type="text" name="titulo_mostrar" id="titulo_mostrar" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="tipo" class="form-label">Tipo de Sección</label>
            <select name="tipo" id="tipo" class="form-select" onchange="mostrarCampoCategoria()">
                <option value="carrusel">Carrusel de Productos</option>
                <option value="banner_doble">Banners Dobles</option>
                <option value="categoria">Productos de Categoría</option>
            </select>
        </div>
        
        <div id="categoriaGroup" class="form-group" style="display: none;">
            <label for="categoria_id" class="form-label">Categoría</label>
            <select name="categoria_id" id="categoria_id" class="form-select">
                <option value="">Seleccione una categoría</option>
                <?php foreach ($categorias as $id => $nombre): ?>
                    <option value="<?php echo $id; ?>"><?php echo $nombre; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="activo" id="activo" class="form-check-input" checked>
                <span class="form-check-label">Sección Activa</span>
            </label>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Crear Sección</button>
            <button type="button" class="btn btn-danger" onclick="ocultarFormularioCrear()">Cancelar</button>
        </div>
    </form>
</div>

<script>
    // Hacer que la lista sea ordenable
    document.addEventListener('DOMContentLoaded', function() {
        const lista = document.getElementById('listaSecciones');
        let draggedItem = null;
        
        // Funcionalidad básica de drag and drop
        lista.querySelectorAll('li').forEach(function(item) {
            item.addEventListener('dragstart', function() {
                draggedItem = this;
                setTimeout(() => this.style.opacity = '0.5', 0);
            });
            
            item.addEventListener('dragend', function() {
                draggedItem = null;
                this.style.opacity = '1';
                
                // Actualizar los inputs ocultos con el nuevo orden
                const ordenInputs = lista.querySelectorAll('input[name="orden[]"]');
                ordenInputs.forEach(function(input, index) {
                    input.value = input.closest('li').dataset.id;
                });
            });
            
            item.addEventListener('dragover', function(e) {
                e.preventDefault();
            });
            
            item.addEventListener('dragenter', function(e) {
                e.preventDefault();
                this.style.background = '#f5f5f5';
            });
            
            item.addEventListener('dragleave', function() {
                this.style.background = '';
            });
            
            item.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.background = '';
                if (draggedItem !== this) {
                    const items = Array.from(lista.querySelectorAll('li'));
                    const indexDragged = items.indexOf(draggedItem);
                    const indexTarget = items.indexOf(this);
                    
                    if (indexDragged < indexTarget) {
                        lista.insertBefore(draggedItem, this.nextElementSibling);
                    } else {
                        lista.insertBefore(draggedItem, this);
                    }
                }
            });
            
            // Hacer el elemento arrastrable
            item.setAttribute('draggable', 'true');
        });
    });
    
    // Mostrar/ocultar el campo de categoría según el tipo de sección
    function mostrarCampoCategoria() {
        const tipoSelect = document.getElementById('tipo');
        const categoriaGroup = document.getElementById('categoriaGroup');
        
        if (tipoSelect.value === 'categoria') {
            categoriaGroup.style.display = 'block';
        } else {
            categoriaGroup.style.display = 'none';
        }
    }
    
    // Mostrar formulario para crear sección
    function mostrarFormularioCrear() {
        document.getElementById('formCrearSeccion').style.display = 'block';
        document.getElementById('formEditarSeccion').style.display = 'none';
    }
    
    // Ocultar formulario para crear sección
    function ocultarFormularioCrear() {
        document.getElementById('formCrearSeccion').style.display = 'none';
    }
    
    // Mostrar formulario para editar sección
    function editarSeccion(id, titulo, tipo, categoriaId, activo) {
        document.getElementById('editSeccionId').value = id;
        document.getElementById('editTituloMostrar').value = titulo;
        document.getElementById('editActivo').checked = activo === 1;
        
        const categoriaGroup = document.getElementById('editCategoriaGroup');
        
        if (tipo === 'categoria') {
            categoriaGroup.style.display = 'block';
            if (categoriaId) {
                document.getElementById('editCategoriaId').value = categoriaId;
            }
        } else {
            categoriaGroup.style.display = 'none';
        }
        
        document.getElementById('formEditarSeccion').style.display = 'block';
        document.getElementById('formCrearSeccion').style.display = 'none';
    }
    
    // Ocultar formulario para editar sección
    function ocultarFormularioEditar() {
        document.getElementById('formEditarSeccion').style.display = 'none';
    }
</script>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>