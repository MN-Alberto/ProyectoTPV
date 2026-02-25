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
('Pack cartuchos HP 305', 'Pack de cartuchos originales HP 305 negro y color', 24.99, 45, 6, 1);
