<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/admin-functions.php');

// Incluir el header
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-header.php');
?>

<!-- Cabecera de página -->
<div class="admin-header">
    <h1 class="admin-title">Panel de Administración</h1>
    <div class="admin-breadcrumb">
        Dashboard
    </div>
</div>

<!-- Resumen general -->
<div class="admin-card">
    <div class="admin-card-title">Resumen de la Tienda</div>
    
    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <?php
        // Contar productos
        $stmt = $conn->query("SELECT COUNT(*) FROM productos WHERE activo = 1");
        $total_productos = $stmt->fetchColumn();
        
        // Contar categorías
        $stmt = $conn->query("SELECT COUNT(*) FROM categorias WHERE activo = 1");
        $total_categorias = $stmt->fetchColumn();
        
        // Contar secciones activas
        $stmt = $conn->query("SELECT COUNT(*) FROM secciones_inicio WHERE activo = 1");
        $total_secciones = $stmt->fetchColumn();
        
        // Contar banners activos
        $stmt = $conn->query("SELECT COUNT(*) FROM banners WHERE activo = 1");
        $total_banners = $stmt->fetchColumn();
        ?>
        
        <div style="flex: 1; min-width: 200px; background-color: #007bff; color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_productos; ?></div>
            <div style="margin-top: 10px;">Productos Activos</div>
        </div>
        
        <div style="flex: 1; min-width: 200px; background-color: #28a745; color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_categorias; ?></div>
            <div style="margin-top: 10px;">Categorías</div>
        </div>
        
        <div style="flex: 1; min-width: 200px; background-color: #ffc107; color: #333; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_secciones; ?></div>
            <div style="margin-top: 10px;">Secciones de Inicio</div>
        </div>
        
        <div style="flex: 1; min-width: 200px; background-color: #dc3545; color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_banners; ?></div>
            <div style="margin-top: 10px;">Banners Activos</div>
        </div>
    </div>
</div>

<!-- Accesos rápidos -->
<div class="admin-card">
    <div class="admin-card-title">Accesos Rápidos</div>
    
    <div style="display: flex; flex-wrap: wrap; gap: 15px;">
        <a href="<?php echo ADMIN_URL; ?>/editar-inicio.php" style="flex: 1; min-width: 200px; text-decoration: none;">
            <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; transition: transform 0.3s;">
                <div style="font-size: 18px; color: #333; margin-bottom: 10px;">Editar Página de Inicio</div>
                <div style="color: #777; font-size: 14px;">Gestionar secciones, banners y productos destacados</div>
            </div>
        </a>
        
        <a href="<?php echo ADMIN_URL; ?>/productos.php" style="flex: 1; min-width: 200px; text-decoration: none;">
            <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; transition: transform 0.3s;">
                <div style="font-size: 18px; color: #333; margin-bottom: 10px;">Gestionar Productos</div>
                <div style="color: #777; font-size: 14px;">Agregar, editar y eliminar productos</div>
            </div>
        </a>
        
        <a href="<?php echo ADMIN_URL; ?>/categorias.php" style="flex: 1; min-width: 200px; text-decoration: none;">
            <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; transition: transform 0.3s;">
                <div style="font-size: 18px; color: #333; margin-bottom: 10px;">Gestionar Categorías</div>
                <div style="color: #777; font-size: 14px;">Organizar categorías y subcategorías</div>
            </div>
        </a>
        
        <a href="<?php echo ADMIN_URL; ?>/importar-productos.php" style="flex: 1; min-width: 200px; text-decoration: none;">
            <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; transition: transform 0.3s;">
                <div style="font-size: 18px; color: #333; margin-bottom: 10px;">Importar Productos</div>
                <div style="color: #777; font-size: 14px;">Cargar productos masivamente con CSV</div>
            </div>
        </a>
    </div>
</div>

<!-- Vista previa de la tienda -->
<div class="admin-card">
    <div class="admin-card-title">Vista Previa de la Tienda</div>
    
    <div style="text-align: center; padding: 20px 0;">
        <p style="margin-bottom: 20px;">Revisa cómo se ve tu tienda actualmente:</p>
        <a href="<?php echo BASE_URL; ?>/public/index.php" target="_blank" class="btn btn-primary">Ver Tienda</a>
    </div>
</div>

<?php
// Incluir el footer
include($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/admin/includes/admin-footer.php');
?>