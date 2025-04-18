<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');

/**
 * Obtiene productos para una sección específica (ofertas, nuevos, categoría)
 * 
 * @param string $seccion_nombre Nombre de la sección
 * @param int $limit Límite de productos a obtener
 * @return array Arreglo de productos
 */
function obtener_productos_seccion($seccion_nombre, $limit = 10) {
    global $conn;
    
    try {
        // Asegurarnos que el límite sea un entero
        $limit = (int)$limit;
        
        // Obtener el ID de la sección
        $stmt = $conn->prepare("SELECT id FROM secciones_inicio WHERE nombre = ? AND activo = 1");
        $stmt->execute([$seccion_nombre]);
        $seccion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$seccion) {
            return [];
        }
        
        // Obtener productos de la sección - incluir el límite directamente en la consulta
        $sql = "
            SELECT p.* 
            FROM productos p
            INNER JOIN productos_seccion ps ON p.id = ps.producto_id
            WHERE ps.seccion_id = ? AND p.activo = 1
            ORDER BY ps.orden ASC
            LIMIT {$limit}
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$seccion['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en obtener_productos_seccion: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene productos por categoría
 * 
 * @param int $categoria_id ID de la categoría
 * @param int $limit Límite de productos a obtener
 * @return array Arreglo de productos
 */
function obtener_productos_categoria($categoria_id, $limit = 10) {
    global $conn;
    
    try {
        // Asegurarnos que el límite sea un entero
        $limit = (int)$limit;
        
        $sql = "
            SELECT * FROM productos 
            WHERE categoria_id = ? AND activo = 1
            ORDER BY id DESC
            LIMIT {$limit}
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$categoria_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en obtener_productos_categoria: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene productos en oferta
 * 
 * @param int $limit Límite de productos a obtener
 * @return array Arreglo de productos
 */
function obtener_productos_oferta($limit = 10) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM productos 
            WHERE precio_oferta > 0 AND precio_oferta < precio AND activo = 1
            ORDER BY id DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene productos nuevos
 * 
 * @param int $limit Límite de productos a obtener
 * @return array Arreglo de productos
 */
function obtener_productos_nuevos($limit = 10) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM productos 
            WHERE nuevo = 1 AND activo = 1
            ORDER BY id DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene producto por ID
 * 
 * @param int $producto_id ID del producto
 * @return array Datos del producto
 */
function obtener_producto($producto_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM productos WHERE id = ? AND activo = 1");
        $stmt->execute([$producto_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Calcula el porcentaje de descuento
 * 
 * @param float $precio Precio original
 * @param float $precio_oferta Precio de oferta
 * @return int Porcentaje de descuento
 */
function calcular_descuento($precio, $precio_oferta) {
    if ($precio_oferta <= 0 || $precio_oferta >= $precio) {
        return 0;
    }
    
    $descuento = (($precio - $precio_oferta) / $precio) * 100;
    return round($descuento);
}

/**
 * Formatea el precio para mostrar
 * 
 * @param float $precio Precio a formatear
 * @return string Precio formateado
 */
function formatear_precio($precio) {
    return 'S/ ' . number_format($precio, 2, '.', ',');
}
?>