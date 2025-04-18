-- Usar la base de datos
USE compuyatienda;

-- Insertar productos de ejemplo
INSERT INTO productos (sku, nombre, slug, precio, precio_oferta, descripcion, stock, categoria_id, marca, modelo, destacado, nuevo, activo, imagen_principal) VALUES 
-- PC Gamer
('PCG-01-00001', 'PC Gamer Extreme RTX 4070', 'pc-gamer-extreme-rtx-4070', 5999.99, 5499.99, 'PC Gamer con procesador Intel Core i9, 32GB RAM, SSD 1TB, RTX 4070', 10, 1, 'CompuYa', 'Extreme Gaming', 1, 1, 1, 'pc_gamer_1.jpg'),
('PCG-01-00002', 'PC Gamer Pro RTX 3060', 'pc-gamer-pro-rtx-3060', 3999.99, 3799.99, 'PC Gamer con procesador AMD Ryzen 7, 16GB RAM, SSD 512GB, RTX 3060', 15, 1, 'CompuYa', 'Pro Gaming', 0, 1, 1, 'pc_gamer_2.jpg'),

-- Laptops
('LAP-02-00001', 'Laptop Asus TUF Gaming', 'laptop-asus-tuf-gaming', 4299.99, 3999.99, 'Laptop gaming con Intel Core i7, 16GB RAM, SSD 512GB, RTX 3050Ti', 8, 2, 'Asus', 'TUF A15', 1, 0, 1, 'laptop_1.jpg'),
('LAP-02-00002', 'Laptop MSI Katana', 'laptop-msi-katana', 5299.99, 0, 'Laptop gaming con Intel Core i7, 16GB RAM, SSD 1TB, RTX 3060', 5, 2, 'MSI', 'Katana GF66', 0, 1, 1, 'laptop_2.jpg'),

-- Monitores
('MON-03-00001', 'Monitor Gaming ASUS 27"', 'monitor-gaming-asus-27', 1299.99, 1199.99, 'Monitor gaming 27 pulgadas, 165Hz, 1ms, IPS, Full HD', 12, 3, 'Asus', 'VG27AQ', 1, 0, 1, 'monitor_1.jpg'),
('MON-03-00002', 'Monitor Curvo Samsung 32"', 'monitor-curvo-samsung-32', 1899.99, 1799.99, 'Monitor curvo 32 pulgadas, 144Hz, 1ms, VA, QHD', 7, 3, 'Samsung', 'G5 Odyssey', 0, 1, 1, 'monitor_2.jpg'),

-- Periféricos
('PER-04-00001', 'Teclado Mecánico Logitech', 'teclado-mecanico-logitech', 399.99, 349.99, 'Teclado mecánico RGB, switches Blue, layout español', 20, 4, 'Logitech', 'G Pro X', 0, 0, 1, 'teclado_1.jpg'),
('PER-04-00002', 'Mouse Gaming Razer', 'mouse-gaming-razer', 259.99, 229.99, 'Mouse gaming inalámbrico, 20000 DPI, RGB, 8 botones programables', 25, 4, 'Razer', 'Viper Ultimate', 1, 0, 1, 'mouse_1.jpg'),

-- Tarjetas de Video
('TAR-09-00001', 'Tarjeta de Video RTX 4070', 'tarjeta-video-rtx-4070', 3499.99, 3299.99, 'Tarjeta de video NVIDIA GeForce RTX 4070, 12GB GDDR6X', 6, 9, 'ASUS', 'ROG Strix', 1, 0, 1, 'gpu_1.jpg'),
('TAR-09-00002', 'Tarjeta de Video RX 7700 XT', 'tarjeta-video-rx-7700-xt', 2799.99, 2599.99, 'Tarjeta de video AMD Radeon RX 7700 XT, 12GB GDDR6', 9, 9, 'MSI', 'Gaming X', 0, 1, 1, 'gpu_2.jpg'),

-- Procesadores
('PRO-10-00001', 'Procesador Intel Core i9-13900K', 'procesador-intel-core-i9-13900k', 2199.99, 1999.99, 'Procesador Intel Core i9-13900K, 24 núcleos, 5.8GHz', 10, 10, 'Intel', 'Core i9-13900K', 1, 0, 1, 'cpu_1.jpg'),
('PRO-10-00002', 'Procesador AMD Ryzen 9 7950X', 'procesador-amd-ryzen-9-7950x', 2099.99, 1949.99, 'Procesador AMD Ryzen 9 7950X, 16 núcleos, 5.7GHz', 8, 10, 'AMD', 'Ryzen 9 7950X', 0, 1, 1, 'cpu_2.jpg');

-- Insertar especificaciones para algunos productos
-- PC Gamer 1
INSERT INTO especificaciones (producto_id, nombre, valor) VALUES 
(1, 'Procesador', 'Intel Core i9-13900K'),
(1, 'Memoria RAM', '32GB DDR5 5200MHz'),
(1, 'Tarjeta Gráfica', 'NVIDIA GeForce RTX 4070 12GB'),
(1, 'Almacenamiento', 'SSD NVMe 1TB'),
(1, 'Sistema Operativo', 'Windows 11 Home');

-- Laptop 1
INSERT INTO especificaciones (producto_id, nombre, valor) VALUES 
(3, 'Procesador', 'Intel Core i7-13700H'),
(3, 'Memoria RAM', '16GB DDR5 4800MHz'),
(3, 'Tarjeta Gráfica', 'NVIDIA GeForce RTX 3050Ti 6GB'),
(3, 'Almacenamiento', 'SSD NVMe 512GB'),
(3, 'Pantalla', '15.6" Full HD 144Hz');

-- Monitor 1
INSERT INTO especificaciones (producto_id, nombre, valor) VALUES 
(5, 'Tamaño', '27 pulgadas'),
(5, 'Resolución', '2560 x 1440 (QHD)'),
(5, 'Tasa de refresco', '165Hz'),
(5, 'Tiempo de respuesta', '1ms'),
(5, 'Tecnología de panel', 'IPS');

-- Tarjeta de Video 1
INSERT INTO especificaciones (producto_id, nombre, valor) VALUES 
(9, 'Memoria', '12GB GDDR6X'),
(9, 'Bus de memoria', '192-bit'),
(9, 'Núcleos CUDA', '5888'),
(9, 'Boost Clock', '2.6 GHz'),
(9, 'Puertos', '3x DisplayPort 1.4, 1x HDMI 2.1');

-- Vincular productos a secciones
-- Ofertas
INSERT INTO productos_seccion (seccion_id, producto_id, orden) VALUES 
(1, 1, 1), -- PC Gamer Extreme
(1, 3, 2), -- Laptop Asus
(1, 5, 3), -- Monitor Asus
(1, 8, 4), -- Mouse Razer
(1, 9, 5), -- RTX 4070
(1, 11, 6); -- Intel Core i9

-- Nuevos
INSERT INTO productos_seccion (seccion_id, producto_id, orden) VALUES 
(2, 2, 1), -- PC Gamer Pro
(2, 4, 2), -- Laptop MSI
(2, 6, 3), -- Monitor Samsung
(2, 10, 4), -- RX 7700 XT
(2, 12, 5); -- Ryzen 9

-- Sección Tarjetas de Video
INSERT INTO productos_seccion (seccion_id, producto_id, orden) VALUES 
(4, 9, 1), -- RTX 4070
(4, 10, 2); -- RX 7700 XT

-- Sección Laptops Gaming
INSERT INTO productos_seccion (seccion_id, producto_id, orden) VALUES 
(5, 3, 1), -- Laptop Asus
(5, 4, 2); -- Laptop MSI