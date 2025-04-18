<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');

// Verificar si hay sesión de administrador (en una versión real)
// if (!isset($_SESSION['admin_id'])) {
//     redirect(ADMIN_URL . '/login.php');
// }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo SITE_NAME; ?></title>
    <style>
        /* Estilos básicos para el panel de administración */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            display: flex;
            min-height: 100vh;
        }
        
        /* Menú lateral */
        .admin-sidebar {
            width: 250px;
            background-color: #333;
            color: white;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            z-index: 10;
        }
        
        .admin-sidebar-header {
            padding: 20px;
            text-align: center;
            background-color: #222;
            border-bottom: 1px solid #444;
        }
        
        .admin-sidebar-menu {
            padding: 20px 0;
        }
        
        .admin-sidebar-menu h3 {
            padding: 10px 20px;
            font-size: 14px;
            color: #aaa;
            text-transform: uppercase;
        }
        
        .admin-sidebar-menu ul {
            list-style: none;
        }
        
        .admin-sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .admin-sidebar-menu a {
            display: block;
            padding: 10px 20px;
            color: #eee;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .admin-sidebar-menu a:hover, .admin-sidebar-menu a.active {
            background-color: #444;
        }
        
        /* Contenido principal */
        .admin-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .admin-header {
            background-color: white;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .admin-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }
        
        .admin-breadcrumb {
            font-size: 14px;
            color: #777;
            margin-top: 5px;
        }
        
        /* Tarjetas y secciones */
        .admin-card {
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .admin-card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        /* Formularios */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            font-size: 14px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .form-check-input {
            margin-right: 10px;
        }
        
        /* Botones */
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: #007bff;
        }
        
        .btn-success {
            background-color: #28a745;
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #333;
        }
        
        /* Tablas */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .admin-table tr:hover {
            background-color: #f5f5f5;
        }
        
        /* Utilidades */
        .text-center {
            text-align: center;
        }
        
        .mt-10 {
            margin-top: 10px;
        }
        
        .mb-10 {
            margin-bottom: 10px;
        }
        
        .d-flex {
            display: flex;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        .align-center {
            align-items: center;
        }
        
        /* Mensajes de alerta */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        /* Drag and drop para ordenar */
        .sortable-list {
            list-style: none;
        }
        
        .sortable-item {
            padding: 10px 15px;
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            cursor: move;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sortable-item:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <!-- Menú lateral -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <h1 style="font-size: 18px;">Admin Panel</h1>
            <div style="font-size: 14px; margin-top: 5px;"><?php echo SITE_NAME; ?></div>
        </div>
        
        <div class="admin-sidebar-menu">
            <h3>Tienda</h3>
            <ul>
                <li><a href="<?php echo ADMIN_URL; ?>/index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="<?php echo ADMIN_URL; ?>/editar-inicio.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'editar-inicio.php' ? 'active' : ''; ?>">Editar Inicio</a></li>
                <li><a href="<?php echo ADMIN_URL; ?>/productos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'productos.php' ? 'active' : ''; ?>">Productos</a></li>
                <li><a href="<?php echo ADMIN_URL; ?>/categorias.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>">Categorías</a></li>
            </ul>
            
            <h3>Configuración</h3>
            <ul>
                <li><a href="<?php echo ADMIN_URL; ?>/importar-productos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'importar-productos.php' ? 'active' : ''; ?>">Importar Productos</a></li>
                <li><a href="<?php echo ADMIN_URL; ?>/importar-specs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'importar-specs.php' ? 'active' : ''; ?>">Importar Especificaciones</a></li>
                <li><a href="<?php echo ADMIN_URL; ?>/banners.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'banners.php' ? 'active' : ''; ?>">Gestionar Banners</a></li>
            </ul>
            
            <h3>Usuario</h3>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>/public/index.php" target="_blank">Ver Tienda</a></li>
                <li><a href="<?php echo ADMIN_URL; ?>/logout.php">Cerrar Sesión</a></li>
            </ul>
        </div>
    </aside>
    
    <!-- Contenido principal -->
    <main class="admin-content">