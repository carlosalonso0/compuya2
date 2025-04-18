<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/compuyatienda/config/db.php');

/**
 * Obtiene todas las secciones de inicio
 * 
 * @param bool $solo_activas Obtener solo secciones activas
 * @return array Arreglo de secciones
 */
function admin_obtener_secciones($solo_activas = false) {
    global $conn;
    
    try {
        $sql = "SELECT * FROM secciones_inicio";
        if ($solo_activas) {
            $sql .= " WHERE activo = 1";
        }
        $sql .= " ORDER BY orden ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene una sección por ID
 * 
 * @param int $seccion_id ID de la sección
 * @return array Datos de la sección
 */
function admin_obtener_seccion($seccion_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM secciones_inicio WHERE id = ?");
        $stmt->execute([$seccion_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Actualiza el orden de las secciones
 * 
 * @param array $orden_ids IDs de secciones en orden
 * @return bool Resultado de la operación
 */
function admin_actualizar_orden_secciones($orden_ids) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        foreach ($orden_ids as $orden => $id) {
            $stmt = $conn->prepare("UPDATE secciones_inicio SET orden = ? WHERE id = ?");
            $stmt->execute([$orden, $id]);
        }
        
        $conn->commit();
        return true;
        
    } catch(PDOException $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Actualiza una sección
 * 
 * @param int $seccion_id ID de la sección
 * @param array $datos Datos a actualizar
 * @return bool Resultado de la operación
 */
function admin_actualizar_seccion($seccion_id, $datos) {
    global $conn;
    
    try {
        $campos = [];
        $valores = [];
        
        foreach ($datos as $campo => $valor) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
        
        $valores[] = $seccion_id;
        
        $sql = "UPDATE secciones_inicio SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($valores);
        
        return true;
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Crea una nueva sección
 * 
 * @param array $datos Datos de la sección
 * @return int|bool ID de la sección creada o false en caso de error
 */
function admin_crear_seccion($datos) {
    global $conn;
    
    try {
        $campos = array_keys($datos);
        $placeholders = array_fill(0, count($campos), '?');
        
        $sql = "INSERT INTO secciones_inicio (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($datos));
        
        return $conn->lastInsertId();
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Elimina una sección
 * 
 * @param int $seccion_id ID de la sección
 * @return bool Resultado de la operación
 */
function admin_eliminar_seccion($seccion_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM secciones_inicio WHERE id = ?");
        $stmt->execute([$seccion_id]);
        
        return true;
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Obtiene todos los banners principales
 * 
 * @param bool $solo_activos Obtener solo banners activos
 * @return array Arreglo de banners
 */
function admin_obtener_banners($solo_activos = false) {
    global $conn;
    
    try {
        $sql = "SELECT * FROM banners";
        if ($solo_activos) {
            $sql .= " WHERE activo = 1";
        }
        $sql .= " ORDER BY orden ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene un banner por ID
 * 
 * @param int $banner_id ID del banner
 * @return array Datos del banner
 */
function admin_obtener_banner($banner_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM banners WHERE id = ?");
        $stmt->execute([$banner_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Actualiza un banner
 * 
 * @param int $banner_id ID del banner
 * @param array $datos Datos a actualizar
 * @return bool Resultado de la operación
 */
function admin_actualizar_banner($banner_id, $datos) {
    global $conn;
    
    try {
        $campos = [];
        $valores = [];
        
        foreach ($datos as $campo => $valor) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
        
        $valores[] = $banner_id;
        
        $sql = "UPDATE banners SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($valores);
        
        return true;
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Crea un nuevo banner
 * 
 * @param array $datos Datos del banner
 * @return int|bool ID del banner creado o false en caso de error
 */
function admin_crear_banner($datos) {
    global $conn;
    
    try {
        $campos = array_keys($datos);
        $placeholders = array_fill(0, count($campos), '?');
        
        $sql = "INSERT INTO banners (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($datos));
        
        return $conn->lastInsertId();
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Elimina un banner
 * 
 * @param int $banner_id ID del banner
 * @return bool Resultado de la operación
 */
function admin_eliminar_banner($banner_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->execute([$banner_id]);
        
        return true;
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Obtiene los banners dobles de una sección
 * 
 * @param int $seccion_id ID de la sección
 * @param bool $solo_activos Obtener solo banners activos
 * @return array Arreglo de banners dobles
 */
function admin_obtener_banners_dobles($seccion_id, $solo_activos = false) {
    global $conn;
    
    try {
        $sql = "SELECT * FROM banners_dobles WHERE seccion_id = ?";
        if ($solo_activos) {
            $sql .= " AND activo = 1";
        }
        $sql .= " ORDER BY posicion ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $seccion_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en admin_obtener_banners_dobles: " . $e->getMessage());
        return [];
    }
}


/**
 * Obtiene un banner doble por ID
 * 
 * @param int $banner_id ID del banner doble
 * @return array Datos del banner doble
 */
function admin_obtener_banner_doble($banner_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM banners_dobles WHERE id = ?");
        $stmt->bindParam(1, $banner_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en admin_obtener_banner_doble: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza un banner doble
 * 
 * @param int $banner_id ID del banner doble
 * @param array $datos Datos a actualizar
 * @return bool Resultado de la operación
 */
function admin_actualizar_banner_doble($banner_id, $datos) {
    global $conn;
    
    try {
        $campos = [];
        $valores = [];
        
        foreach ($datos as $campo => $valor) {
            $campos[] = "$campo = ?";
            $valores[] = $valor;
        }
        
        $valores[] = $banner_id;
        
        $sql = "UPDATE banners_dobles SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($valores);
        
        return true;
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Crea un nuevo banner doble
 * 
 * @param array $datos Datos del banner doble
 * @return int|bool ID del banner doble creado o false en caso de error
 */
function admin_crear_banner_doble($datos) {
    global $conn;
    
    try {
        $campos = array_keys($datos);
        $placeholders = array_fill(0, count($campos), '?');
        
        $sql = "INSERT INTO banners_dobles (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array_values($datos));
        
        return $conn->lastInsertId();
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Elimina un banner doble
 * 
 * @param int $banner_id ID del banner doble
 * @return bool Resultado de la operación
 */
function admin_eliminar_banner_doble($banner_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("DELETE FROM banners_dobles WHERE id = ?");
        $stmt->execute([$banner_id]);
        
        return true;
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Obtiene las categorías para un select
 * 
 * @param bool $incluir_subcategorias Incluir subcategorías
 * @return array Arreglo de categorías
 */
function admin_obtener_categorias_select($incluir_subcategorias = true) {
    global $conn;
    
    try {
        // Obtener categorías principales
        $stmt = $conn->prepare("
            SELECT id, nombre FROM categorias 
            WHERE categoria_padre_id IS NULL AND activo = 1
            ORDER BY nombre ASC
        ");
        $stmt->execute();
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $resultado = [];
        
        foreach ($categorias as $categoria) {
            $resultado[$categoria['id']] = $categoria['nombre'];
            
            // Si se incluyen subcategorías, obtenerlas
            if ($incluir_subcategorias) {
                $stmt_sub = $conn->prepare("
                    SELECT id, nombre FROM categorias 
                    WHERE categoria_padre_id = ? AND activo = 1
                    ORDER BY nombre ASC
                ");
                $stmt_sub->execute([$categoria['id']]);
                $subcategorias = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($subcategorias as $subcategoria) {
                    $resultado[$subcategoria['id']] = '-- ' . $subcategoria['nombre'];
                }
            }
        }
        
        return $resultado;
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * Obtiene los productos para una sección
 * 
 * @param int $seccion_id ID de la sección
 * @return array Arreglo de productos
 */
function admin_obtener_productos_seccion($seccion_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT p.*, ps.orden 
            FROM productos p
            INNER JOIN productos_seccion ps ON p.id = ps.producto_id
            WHERE ps.seccion_id = ?
            ORDER BY ps.orden ASC
        ");
        
        $stmt->execute([$seccion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        echo "Error: " . $e->getMessage();
        return [];
    }
}

/**
 * Actualiza los productos de una sección
 * 
 * @param int $seccion_id ID de la sección
 * @param array $producto_ids IDs de productos
 * @return bool Resultado de la operación
 */
function admin_actualizar_productos_seccion($seccion_id, $producto_ids) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        // Eliminar productos actuales de la sección
        $stmt = $conn->prepare("DELETE FROM productos_seccion WHERE seccion_id = ?");
        $stmt->execute([$seccion_id]);
        
        // Insertar nuevos productos
        foreach ($producto_ids as $orden => $producto_id) {
            $stmt = $conn->prepare("INSERT INTO productos_seccion (seccion_id, producto_id, orden) VALUES (?, ?, ?)");
            $stmt->execute([$seccion_id, $producto_id, $orden]);
        }
        
        $conn->commit();
        return true;
        
    } catch(PDOException $e) {
        $conn->rollBack();
        echo "Error: " . $e->getMessage();
        return false;
    }
}

/**
 * Busca productos para selector
 * 
 * @param string $termino Término de búsqueda
 * @param int $categoria_id ID de categoría (opcional)
 * @param int $limit Límite de resultados
 * @return array Arreglo de productos
 */
function admin_buscar_productos($termino = '', $categoria_id = null, $limit = 10) {
    global $conn;
    
    try {
        $sql = "SELECT id, sku, nombre, precio, imagen_principal FROM productos WHERE activo = 1";
        $params = [];
        
        if (!empty($termino)) {
            $sql .= " AND (nombre LIKE ? OR sku LIKE ?)";
            $params[] = "%$termino%";
            $params[] = "%$termino%";
        }
        
        if ($categoria_id) {
            // Convertir a entero para evitar inyección SQL
            $categoria_id = (int)$categoria_id;
            $sql .= " AND categoria_id = $categoria_id";
            // No añadimos el parámetro al array porque ya lo pusimos directamente en la consulta
        }
        
        // El límite también debe convertirse a entero
        $limit = (int)$limit;
        $sql .= " ORDER BY id DESC LIMIT $limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch(PDOException $e) {
        error_log("Error en admin_buscar_productos: " . $e->getMessage());
        return [];
    }
}

/**
 * Sube una imagen y devuelve su nombre
 * 
 * @param array $archivo Datos del archivo subido
 * @param string $directorio Directorio de destino
 * @return string|bool Nombre del archivo subido o false en caso de error
 */
function admin_subir_imagen($archivo, $directorio) {
    // Validar archivo
    if ($archivo['error'] != 0) {
        return false;
    }
    
    // Verificar que sea una imagen
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($archivo['type'], $tipos_permitidos)) {
        return false;
    }
    
    // Generar nombre único
    $nombre_archivo = uniqid() . '_' . basename($archivo['name']);
    $ruta_destino = $directorio . '/' . $nombre_archivo;
    
    // Mover archivo
    if (move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        return $nombre_archivo;
    }
    
    return false;
}
?>