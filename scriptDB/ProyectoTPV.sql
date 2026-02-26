-- ============================================================
-- Script de creación de la base de datos ProyectoTPV
-- Autor: Alberto Méndez
-- Fecha: 24/02/2026
-- Bazar de productos informáticos - Terminal Punto de Venta
-- ============================================================

DROP DATABASE IF EXISTS ProyectoTPV;
CREATE DATABASE ProyectoTPV CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE ProyectoTPV;

-- ============================================================
-- Tabla: usuarios
-- ============================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'empleado') NOT NULL DEFAULT 'empleado',
    fechaAlta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- Tabla: categorias
-- ============================================================
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT
) ENGINE=InnoDB;

-- ============================================================
-- Tabla: productos
-- ============================================================
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    idCategoria INT NOT NULL,
    imagen VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (idCategoria) REFERENCES categorias(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- Tabla: ventas
-- ============================================================
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idUsuario INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    metodoPago ENUM('efectivo', 'tarjeta', 'bizum') NOT NULL DEFAULT 'efectivo',
    estado ENUM('completada', 'anulada') NOT NULL DEFAULT 'completada',
    tipoDocumento ENUM('ticket', 'factura') NOT NULL DEFAULT 'ticket',
    FOREIGN KEY (idUsuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- Tabla: lineasVenta
-- ============================================================
CREATE TABLE lineasVenta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idVenta INT NOT NULL,
    idProducto INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precioUnitario DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (idVenta) REFERENCES ventas(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (idProducto) REFERENCES productos(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- Datos iniciales
-- ============================================================

-- Usuarios del sistema (password: admin123)
INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES
('admin', 'admin@tpvbazar.com', '$2y$10$INSFpYHGFBsBcb.rsQx/K.8Hp/2aINaqJ0dtbjd7mmO0poCONX/bK', 'admin', 1),
('empleado', 'empleado@tpvbazar.com', '$2y$10$INSFpYHGFBsBcb.rsQx/K.8Hp/2aINaqJ0dtbjd7mmO0poCONX/bK', 'empleado', 1);

-- Categorías de productos informáticos
INSERT INTO categorias (nombre, descripcion) VALUES
('Periféricos', 'Teclados, ratones, auriculares y otros periféricos'),
('Componentes', 'Procesadores, tarjetas gráficas, memorias RAM, discos duros'),
('Accesorios', 'Cables, fundas, soportes y otros accesorios'),
('Redes', 'Routers, switches, adaptadores de red'),
('Almacenamiento', 'Pendrives, discos externos, tarjetas de memoria'),
('Impresión', 'Impresoras, cartuchos de tinta, papel');

-- Productos de ejemplo
INSERT INTO productos (nombre, descripcion, precio, stock, idCategoria, activo) VALUES
('Ratón inalámbrico Logitech M185', 'Ratón inalámbrico compacto con receptor USB', 12.99, 50, 1, 1),
('Teclado mecánico RGB', 'Teclado mecánico con retroiluminación RGB, switches blue', 34.99, 25, 1, 1),
('Auriculares gaming HyperX', 'Auriculares con micrófono para gaming, sonido 7.1', 49.99, 15, 1, 1),
('Memoria RAM DDR4 8GB', 'Módulo de memoria RAM DDR4 3200MHz 8GB', 29.99, 40, 2, 1),
('SSD 500GB NVMe', 'Disco sólido NVMe M.2 500GB, lectura 3500MB/s', 54.99, 30, 2, 1),
('Cable HDMI 2.1 2m', 'Cable HDMI 2.1 de 2 metros, 4K 120Hz', 8.99, 100, 3, 1),
('Funda portátil 15.6"', 'Funda protectora para portátil de hasta 15.6 pulgadas', 14.99, 35, 3, 1),
('Router WiFi 6 TP-Link', 'Router WiFi 6 AX1500, doble banda', 39.99, 20, 4, 1),
('Pendrive 64GB USB 3.0', 'Pendrive USB 3.0 de 64GB, velocidad de lectura 150MB/s', 9.99, 80, 5, 1),
('Pack cartuchos HP 305', 'Pack de cartuchos originales HP 305 negro y color', 24.99, 45, 6, 1),

-- Nuevos 20 productos:
('Webcam 1080p', 'Cámara web con micrófono integrado, resolución 1080p', 25.50, 30, 1, 1),
('Alfombrilla XL', 'Alfombrilla de ratón extendida 90x40cm', 15.99, 60, 1, 1),
('Micrófono de condensador USB', 'Micrófono para streaming y podcast con trípode', 45.00, 20, 1, 1),
('Procesador AMD Ryzen 5', 'Procesador de 6 núcleos y 12 hilos, 3.6 GHz', 159.90, 15, 2, 1),
('Tarjeta Gráfica RTX 3060', 'Tarjeta gráfica de 12GB GDDR6', 329.99, 8, 2, 1),
('Placa Base B550', 'Placa base ATX compatible con procesadores AMD Ryzen', 119.50, 20, 2, 1),
('Fuente de Alimentación 650W', 'Fuente de alimentación 80 Plus Bronze', 59.99, 25, 2, 1),
('Cable DisplayPort 1.4', 'Cable DisplayPort de 1.8 metros, soporta 8K', 12.50, 50, 3, 1),
('Soporte para Monitor Doble', 'Brazo articulado para dos monitores de hasta 27"', 39.99, 15, 3, 1),
('Hub USB-C 7 en 1', 'Adaptador multipuerto con HDMI, USB 3.0 y lector SD', 22.90, 40, 3, 1),
('Switch Gigabit de 5 puertos', 'Switch de red no gestionable de 5 puertos 10/100/1000', 16.99, 35, 4, 1),
('Adaptador WiFi USB', 'Antena WiFi dual band AC1200', 18.50, 50, 4, 1),
('Cable de red RJ45 Cat 6 (5m)', 'Cable Ethernet de 5 metros de longitud', 6.99, 100, 4, 1),
('Disco Duro Externo 1TB', 'Disco duro portátil USB 3.0 de 1TB', 49.99, 40, 5, 1),
('Tarjeta MicroSD 128GB', 'Tarjeta de memoria Clase 10 con adaptador SD', 19.99, 80, 5, 1),
('SSD SATA 1TB', 'Disco de estado sólido interno SATA III de 1TB', 85.00, 25, 5, 1),
('Impresora Láser Monocromo', 'Impresora láser básica WiFi', 110.00, 10, 6, 1),
('Tóner Compatible TN2410', 'Cartucho de tóner negro compatible', 15.50, 60, 6, 1),
('Papel DIN A4 500 hojas', 'Paquete de 500 hojas de papel 80g para impresión', 4.99, 150, 6, 1),
('Escáner de Documentos Portátil', 'Escáner compacto alimentado por USB', 89.90, 12, 6, 1),

-- Periféricos (1)
('Ratón gaming inalámbrico RGB', 'Ratón óptico 16000 DPI con batería recargable', 29.99, 35, 1, 1),
('Teclado inalámbrico compacto', 'Teclado slim con conexión Bluetooth', 21.99, 40, 1, 1),
('Auriculares Bluetooth deportivos', 'Auriculares resistentes al agua IPX5', 19.99, 50, 1, 1),
('Webcam 4K Ultra HD', 'Cámara web 4K con enfoque automático', 69.99, 15, 1, 1),
('Altavoces estéreo USB', 'Altavoces compactos 2.0 con alimentación USB', 17.50, 45, 1, 1),
('Base refrigeradora portátil', 'Base con 5 ventiladores LED para portátil', 24.99, 30, 1, 1),
('Gamepad USB para PC', 'Mando compatible con Windows plug & play', 18.99, 40, 1, 1),
('Lector de tarjetas USB 3.0', 'Lector multitarjeta SD y microSD', 9.50, 60, 1, 1),

-- Componentes (2)
('Procesador Intel i5 12400F', 'CPU 6 núcleos 12 hilos hasta 4.4GHz', 179.90, 12, 2, 1),
('Tarjeta gráfica RX 6600', 'GPU 8GB GDDR6 rendimiento gaming 1080p', 249.99, 10, 2, 1),
('Memoria RAM DDR4 16GB', 'Kit 2x8GB 3200MHz CL16', 59.99, 30, 2, 1),
('Refrigeración líquida 240mm', 'Sistema AIO con iluminación RGB', 89.99, 20, 2, 1),
('Caja ATX con ventana', 'Torre ATX con panel lateral de cristal templado', 64.99, 18, 2, 1),
('Disipador CPU Cooler 212', 'Disipador por aire con ventilador 120mm', 34.99, 25, 2, 1),
('Fuente 750W 80 Plus Gold', 'Fuente modular certificación Gold', 89.90, 15, 2, 1),
('Placa Base Z690', 'Placa base ATX compatible con Intel 12ª Gen', 189.99, 10, 2, 1),

-- Accesorios (3)
('Cable USB-C a USB-C 1m', 'Cable de carga rápida 100W', 7.99, 100, 3, 1),
('Soporte portátil aluminio', 'Soporte ajustable plegable', 19.99, 40, 3, 1),
('Mochila para portátil 17"', 'Mochila acolchada con múltiples compartimentos', 29.99, 30, 3, 1),
('Adaptador HDMI a VGA', 'Conversor HDMI macho a VGA hembra', 8.50, 60, 3, 1),
('Regleta 6 enchufes con USB', 'Base múltiple con protección contra sobretensiones', 16.99, 50, 3, 1),
('Cable extensor USB 3.0 3m', 'Cable alargador alta velocidad', 6.50, 80, 3, 1),
('Funda tablet 10"', 'Funda universal con soporte plegable', 12.99, 45, 3, 1),
('Kit limpieza pantalla', 'Spray y paño microfibra antiestático', 5.99, 90, 3, 1),

-- Redes (4)
('Router Mesh WiFi 6', 'Sistema Mesh doble banda cobertura total hogar', 129.99, 12, 4, 1),
('Switch 8 puertos Gigabit', 'Switch metálico no gestionable', 24.99, 30, 4, 1),
('Repetidor WiFi AC750', 'Extensor de señal compacto', 22.99, 35, 4, 1),
('Tarjeta de red PCIe WiFi 6', 'Adaptador interno con Bluetooth 5.2', 34.99, 20, 4, 1),
('Cable fibra óptica 2m', 'Latiguillo LC-LC multimodo', 9.99, 40, 4, 1),
('Rack mural 9U', 'Armario rack metálico para redes', 89.99, 8, 4, 1),
('Crimpadora RJ45', 'Herramienta para cables de red', 14.99, 25, 4, 1),
('Tester de red RJ45', 'Probador de cables Ethernet', 19.99, 20, 4, 1),

-- Almacenamiento (5)
('Pendrive 128GB USB 3.1', 'Memoria flash alta velocidad', 14.99, 70, 5, 1),
('Disco duro interno 2TB', 'HDD 3.5" 7200RPM SATA III', 59.99, 25, 5, 1),
('SSD NVMe 1TB Gen4', 'SSD M.2 PCIe 4.0 alta velocidad', 119.99, 18, 5, 1),
('Caja externa 2.5" USB 3.0', 'Carcasa para discos SATA 2.5 pulgadas', 12.99, 50, 5, 1),
('NAS 2 Bahías', 'Servidor de almacenamiento doméstico', 219.99, 6, 5, 1),
('Tarjeta SD 64GB', 'Memoria SD Clase 10 UHS-I', 11.99, 60, 5, 1),
('Grabadora DVD externa', 'Unidad óptica USB portátil', 24.99, 20, 5, 1),
('SSD externo 2TB USB-C', 'Unidad portátil alta velocidad', 149.99, 10, 5, 1),

-- Impresión y oficina (6)
('Impresora multifunción tinta', 'Impresora con escáner y copiadora WiFi', 79.99, 15, 6, 1),
('Cartucho tinta negro XL', 'Cartucho compatible alto rendimiento', 18.99, 50, 6, 1),
('Cartucho tinta color XL', 'Cartucho compatible tricolor', 21.99, 45, 6, 1),
('Trituradora papel 8 hojas', 'Destructora de documentos corte en tiras', 39.99, 20, 6, 1),
('Etiquetas adhesivas A4', 'Paquete 100 hojas etiquetas blancas', 12.50, 40, 6, 1),
('Calculadora científica', 'Calculadora 240 funciones pantalla LCD', 14.99, 35, 6, 1),
('Silla oficina ergonómica', 'Silla ajustable con soporte lumbar', 129.99, 10, 6, 1),
('Reposapiés ergonómico', 'Base ajustable antideslizante', 19.99, 25, 6, 1),
('Pizarra blanca magnética', 'Pizarra 90x60cm con marco aluminio', 34.99, 15, 6, 1),
('Archivador metálico 4 cajones', 'Archivador vertical con cerradura', 149.99, 5, 6, 1);
