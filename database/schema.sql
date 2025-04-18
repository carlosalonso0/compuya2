-- Crear base de datos
CREATE DATABASE IF NOT EXISTS compuyatienda CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE compuyatienda;

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    categoria_padre_id INT NULL,
    imagen VARCHAR(255) NULL,
    activo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_padre_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabla de productos
CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    precio DECIMAL(10, 2) NOT NULL,
    precio_oferta DECIMAL(10, 2) DEFAULT 0,
    descripcion TEXT,
    stock INT DEFAULT 0,
    categoria_id INT NOT NULL,
    marca VARCHAR(100),
    modelo VARCHAR(100),
    destacado BOOLEAN DEFAULT 0,
    nuevo BOOLEAN DEFAULT 0,
    activo BOOLEAN DEFAULT 1,
    imagen_principal VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Tabla de especificaciones
CREATE TABLE IF NOT EXISTS especificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    valor VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Tabla de imágenes de productos
CREATE TABLE IF NOT EXISTS imagenes_producto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    ruta_imagen VARCHAR(255) NOT NULL,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Tabla de banners (carrusel principal)
CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100),
    descripcion VARCHAR(255),
    imagen VARCHAR(255) NOT NULL,
    url VARCHAR(255),
    activo BOOLEAN DEFAULT 1,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de secciones de inicio
CREATE TABLE IF NOT EXISTS secciones_inicio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    titulo_mostrar VARCHAR(100) NOT NULL,
    tipo ENUM('carrusel', 'banner_doble', 'categoria') NOT NULL,
    categoria_id INT NULL,
    activo BOOLEAN DEFAULT 1,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabla de productos en secciones
CREATE TABLE IF NOT EXISTS productos_seccion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seccion_id INT NOT NULL,
    producto_id INT NOT NULL,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seccion_id) REFERENCES secciones_inicio(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Tabla de banners dobles
CREATE TABLE IF NOT EXISTS banners_dobles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seccion_id INT NOT NULL,
    titulo VARCHAR(100),
    descripcion VARCHAR(255),
    imagen VARCHAR(255) NOT NULL,
    url VARCHAR(255),
    posicion ENUM('izquierda', 'derecha') NOT NULL,
    activo BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seccion_id) REFERENCES secciones_inicio(id) ON DELETE CASCADE
);

-- Insertar categorías básicas
INSERT INTO categorias (nombre, slug) VALUES 
('PC Gamer', 'pc-gamer'),
('Laptops', 'laptops'),
('Monitores', 'monitores'),
('Periféricos', 'perifericos'),
('Impresoras', 'impresoras'),
('Componentes', 'componentes');

-- Insertar subcategorías de componentes
INSERT INTO categorias (nombre, slug, categoria_padre_id) VALUES 
('Cases', 'cases', 6),
('Placas Madre', 'placas-madre', 6),
('Tarjetas de Video', 'tarjetas-de-video', 6),
('Procesadores', 'procesadores', 6);

-- Insertar secciones iniciales
INSERT INTO secciones_inicio (nombre, titulo_mostrar, tipo, orden) VALUES 
('ofertas', 'Ofertas Especiales', 'carrusel', 1),
('nuevos', 'Productos Nuevos', 'carrusel', 2),
('banner_doble', 'Promociones', 'banner_doble', 3);

-- Insertar categorías destacadas
INSERT INTO secciones_inicio (nombre, titulo_mostrar, tipo, categoria_id, orden) VALUES 
('categoria_gpu', 'Tarjetas de Video', 'categoria', 9, 4),
('categoria_laptops', 'Laptops Gaming', 'categoria', 2, 5);

-- Insertar banners de ejemplo
INSERT INTO banners (titulo, descripcion, imagen, url, orden) VALUES 
('Ofertas de Verano', 'Hasta 40% de descuento en laptops gaming', 'banner1.jpg', '#', 1),
('Nuevos Procesadores', 'Descubre la nueva generación de procesadores', 'banner2.jpg', '#', 2);

-- Insertar banners dobles de ejemplo
INSERT INTO banners_dobles (seccion_id, titulo, descripcion, imagen, url, posicion) VALUES 
(3, 'Gaming Extremo', 'Arma tu PC Gamer', 'double_banner1.jpg', '#', 'izquierda'),
(3, 'Tarjetas Gráficas', 'Las mejores marcas', 'double_banner2.jpg', '#', 'derecha');