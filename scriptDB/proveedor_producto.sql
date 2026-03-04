-- ============================================================
-- Script de creación de la tabla intermedia proveedor_producto
-- Autor: Alberto Méndez
-- Fecha: 04/03/2026
-- ============================================================

USE ProyectoTPV;

DROP TABLE IF EXISTS proveedor_producto;

CREATE TABLE proveedor_producto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idProveedor INT NOT NULL,
    idProducto INT NOT NULL,
    recargoEquivalencia DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    precioProveedor DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (idProveedor) REFERENCES proveedores(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (idProducto) REFERENCES productos(id) ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY (idProveedor, idProducto)
) ENGINE=InnoDB;

-- Distribuir los ~70 productos entre los 5 proveedores.
-- Insertamos asociaciones indicando el idProveedor, idProducto, recargo de equivalencia y precio del proveedor.
-- 1: Logitech (Periféricos)
-- 2: Kingston (Memoria/Almacenamiento)
-- 3: TP-Link (Redes)
-- 4: HP (Impresión y varios)
-- 5: Samsung / Otros (Resto de componentes)

INSERT IGNORE INTO proveedor_producto (idProveedor, idProducto, recargoEquivalencia, precioProveedor) VALUES
-- Proveedor 1: Logitech España S.L. 
(1, 1, 5.20, 15.00), (1, 2, 5.20, 20.00), (1, 3, 5.20, 35.00), (1, 11, 5.20, 40.00), (1, 12, 1.40, 10.00), 
(1, 13, 5.20, 25.00), (1, 31, 5.20, 12.00), (1, 32, 5.20, 18.00), (1, 33, 5.20, 22.00), (1, 34, 5.20, 30.00), 
(1, 35, 5.20, 45.00), (1, 36, 1.40, 8.00), (1, 37, 5.20, 50.00), (1, 38, 5.20, 60.00),

-- Proveedor 2: Kingston Technology 
(2, 4, 1.40, 25.00), (2, 5, 1.40, 45.00), (2, 9, 1.40, 80.00), (2, 24, 1.40, 30.00), (2, 25, 1.40, 55.00), 
(2, 26, 1.40, 100.00), (2, 43, 1.40, 150.00), (2, 71, 1.40, 10.00), (2, 72, 1.40, 15.00), (2, 73, 1.40, 25.00), 
(2, 74, 1.40, 40.00), (2, 75, 1.40, 12.00), (2, 76, 1.40, 22.00), (2, 77, 1.40, 35.00), (2, 78, 1.40, 60.00),

-- Proveedor 3: TP-Link Iberia 
(3, 8, 5.20, 30.00), (3, 21, 5.20, 20.00), (3, 22, 5.20, 45.00), (3, 23, 1.40, 15.00), (3, 61, 5.20, 25.00), 
(3, 62, 5.20, 50.00), (3, 63, 5.20, 80.00), (3, 64, 5.20, 120.00), (3, 65, 1.40, 10.00), (3, 66, 5.20, 40.00), 
(3, 67, 1.40, 15.00), (3, 68, 5.20, 70.00),

-- Proveedor 4: HP España 
(4, 10, 5.20, 150.00), (4, 27, 5.20, 400.00), (4, 28, 5.20, 600.00), (4, 29, 0.50, 800.00), (4, 30, 5.20, 250.00), 
(4, 81, 5.20, 45.00), (4, 82, 5.20, 55.00), (4, 83, 5.20, 65.00), (4, 84, 5.20, 75.00), (4, 85, 0.50, 5.00), 
(4, 86, 5.20, 90.00), (4, 87, 5.20, 110.00), (4, 88, 5.20, 130.00), (4, 89, 5.20, 150.00), (4, 90, 5.20, 180.00),

-- Proveedor 5: Samsung Electronics Iberia / Otros 
(5, 6, 1.40, 120.00), (5, 7, 5.20, 200.00), (5, 14, 5.20, 250.00), (5, 15, 5.20, 400.00), (5, 16, 5.20, 150.00), 
(5, 17, 5.20, 300.00), (5, 18, 1.40, 80.00), (5, 19, 5.20, 60.00), (5, 20, 5.20, 90.00), (5, 41, 5.20, 40.00), 
(5, 42, 5.20, 70.00), (5, 44, 5.20, 110.00), (5, 45, 5.20, 180.00), (5, 46, 5.20, 220.00), (5, 47, 5.20, 350.00), 
(5, 48, 5.20, 50.00), (5, 51, 1.40, 25.00), (5, 52, 5.20, 15.00), (5, 53, 5.20, 35.00), (5, 54, 5.20, 45.00), 
(5, 55, 5.20, 85.00), (5, 56, 1.40, 120.00), (5, 57, 5.20, 160.00), (5, 58, 5.20, 250.00);
