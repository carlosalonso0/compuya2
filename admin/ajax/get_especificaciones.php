<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/functions/especificaciones-functions.php');

// Verificar que se ha enviado el ID de categoría
if (!isset($_GET['categoria_id']) || empty($_GET['categoria_id'])) {
    echo json_encode([]);
    exit;
}

$categoria_id = (int)$_GET['categoria_id'];

// Obtener las especificaciones predefinidas para la categoría
$especificaciones = obtener_especificaciones_predefinidas($categoria_id);

// Devolver las especificaciones en formato JSON
header('Content-Type: application/json');
echo json_encode($especificaciones);