<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');

/**
 * Obtiene las especificaciones predefinidas para una categoría
 * 
 * @param int $categoria_id ID de la categoría
 * @return array Arreglo de especificaciones predefinidas
 * 
 */
function obtener_especificaciones_predefinidas($categoria_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT id, nombre, orden
            FROM categoria_especificaciones
            WHERE categoria_id = ? AND activo = 1
            ORDER BY orden ASC
        ");
        
        $stmt->execute([$categoria_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en obtener_especificaciones_predefinidas: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene las especificaciones de un producto
 * 
 * @param int $producto_id ID del producto
 * @return array Arreglo de especificaciones del producto
 */
function obtener_especificaciones_producto($producto_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT id, nombre, valor
            FROM especificaciones
            WHERE producto_id = ?
            ORDER BY id ASC
        ");
        
        $stmt->execute([$producto_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en obtener_especificaciones_producto: " . $e->getMessage());
        return [];
    }
}

/**
 * Guarda o actualiza las especificaciones de un producto
 * 
/**
 * Guarda o actualiza las especificaciones de un producto
 * 
 * @param int $producto_id ID del producto
 * @param array $especificaciones Arreglo de especificaciones (nombre, valor)
 * @return bool Resultado de la operación
 */
function guardar_especificaciones_producto($producto_id, $especificaciones) {
    global $conn;
    
    try {
        // Iniciar transacción solo si no hay una activa ya
        $transaccion_iniciada = false;
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
            $transaccion_iniciada = true;
        }
        
        // Eliminar especificaciones existentes
        $stmt = $conn->prepare("DELETE FROM especificaciones WHERE producto_id = ?");
        $stmt->execute([$producto_id]);
        
        // Insertar nuevas especificaciones
        $stmt = $conn->prepare("INSERT INTO especificaciones (producto_id, nombre, valor) VALUES (?, ?, ?)");
        
        foreach ($especificaciones as $spec) {
            if (!empty($spec['nombre']) && !empty($spec['valor'])) {
                $stmt->execute([$producto_id, $spec['nombre'], $spec['valor']]);
            }
        }
        
        // Confirmar transacción solo si nosotros la iniciamos
        if ($transaccion_iniciada) {
            $conn->commit();
        }
        
        return true;
        
    } catch(PDOException $e) {
        // Hacer rollback solo si nosotros iniciamos la transacción
        if (isset($transaccion_iniciada) && $transaccion_iniciada && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Error en guardar_especificaciones_producto: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene las especificaciones de un producto organizadas como un mapa nombre => valor
 * 
 * @param int $producto_id ID del producto
 * @return array Mapa de especificaciones
 */
function obtener_especificaciones_mapa($producto_id) {
    $especificaciones = obtener_especificaciones_producto($producto_id);
    $mapa = [];
    
    foreach ($especificaciones as $spec) {
        $mapa[$spec['nombre']] = $spec['valor'];
    }
    
    return $mapa;
}

/**
 * Agrega una especificación predefinida a una categoría
 * 
 * @param int $categoria_id ID de la categoría
 * @param string $nombre Nombre de la especificación
 * @param int $orden Orden de la especificación
 * @return bool|int ID de la especificación creada o false en caso de error
 */
function agregar_especificacion_predefinida($categoria_id, $nombre, $orden = 0) {
    global $conn;
    
    try {
        // Verificar si ya existe
        $stmt = $conn->prepare("
            SELECT id FROM categoria_especificaciones
            WHERE categoria_id = ? AND nombre = ?
        ");
        
        $stmt->execute([$categoria_id, $nombre]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existente) {
            // Si existe y estaba inactiva, activarla
            $stmt = $conn->prepare("
                UPDATE categoria_especificaciones
                SET activo = 1, orden = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$orden, $existente['id']]);
            return $existente['id'];
        }
        
        // Si no existe, crear nueva
        $stmt = $conn->prepare("
            INSERT INTO categoria_especificaciones (categoria_id, nombre, orden, activo)
            VALUES (?, ?, ?, 1)
        ");
        
        $stmt->execute([$categoria_id, $nombre, $orden]);
        return $conn->lastInsertId();
        
    } catch(PDOException $e) {
        error_log("Error en agregar_especificacion_predefinida: " . $e->getMessage());
        return false;
    }
}

/**
 * Elimina una especificación predefinida de una categoría
 * 
 * @param int $especificacion_id ID de la especificación
 * @return bool Resultado de la operación
 */
function eliminar_especificacion_predefinida($especificacion_id) {
    global $conn;
    
    try {
        // En lugar de eliminar, marcar como inactiva
        $stmt = $conn->prepare("
            UPDATE categoria_especificaciones
            SET activo = 0
            WHERE id = ?
        ");
        
        $stmt->execute([$especificacion_id]);
        return true;
        
    } catch(PDOException $e) {
        error_log("Error en eliminar_especificacion_predefinida: " . $e->getMessage());
        return false;
    }
}