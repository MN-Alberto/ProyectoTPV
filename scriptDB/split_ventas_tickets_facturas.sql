-- ============================================================
-- Script de migración: Separar ventas en tickets y facturas (v2)
-- Autor: Alberto Méndez
-- Fecha: 12/03/2026
-- ============================================================

-- Paso 0: Limpieza previa para permitir re-ejecución en caso de error parcial
DROP VIEW IF EXISTS ventas;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS facturas;

-- Paso 1: Crear tabla tickets
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idUsuario INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    descuentoTipo ENUM('ninguno', 'porcentaje', 'fijo') DEFAULT 'ninguno',
    descuentoValor DECIMAL(10, 2) DEFAULT 0,
    descuentoCupon VARCHAR(50) DEFAULT '',
    descuentoTarifaTipo ENUM('ninguno', 'porcentaje', 'fijo') DEFAULT 'ninguno',
    descuentoTarifaValor DECIMAL(10, 2) DEFAULT 0,
    descuentoTarifaCupon VARCHAR(50) DEFAULT '',
    descuentoManualTipo ENUM('ninguno', 'porcentaje', 'fijo') DEFAULT 'ninguno',
    descuentoManualValor DECIMAL(10, 2) DEFAULT 0,
    descuentoManualCupon VARCHAR(50) DEFAULT '',
    metodoPago ENUM('efectivo', 'tarjeta', 'bizum') NOT NULL DEFAULT 'efectivo',
    estado ENUM('completada', 'anulada') NOT NULL DEFAULT 'completada',
    tipoDocumento ENUM('ticket', 'factura') NOT NULL DEFAULT 'ticket',
    importeEntregado DECIMAL(10, 2) DEFAULT NULL,
    cambioDevuelto DECIMAL(10, 2) DEFAULT NULL,
    cerrada TINYINT(1) DEFAULT 0,
    idSesionCaja INT NULL,
    idTarifa INT NULL,
    cliente_dni VARCHAR(20) NULL,
    FOREIGN KEY (idUsuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Paso 2: Crear tabla facturas
CREATE TABLE facturas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idUsuario INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    descuentoTipo ENUM('ninguno', 'porcentaje', 'fijo') DEFAULT 'ninguno',
    descuentoValor DECIMAL(10, 2) DEFAULT 0,
    descuentoCupon VARCHAR(50) DEFAULT '',
    descuentoTarifaTipo ENUM('ninguno', 'porcentaje', 'fijo') DEFAULT 'ninguno',
    descuentoTarifaValor DECIMAL(10, 2) DEFAULT 0,
    descuentoTarifaCupon VARCHAR(50) DEFAULT '',
    descuentoManualTipo ENUM('ninguno', 'porcentaje', 'fijo') DEFAULT 'ninguno',
    descuentoManualValor DECIMAL(10, 2) DEFAULT 0,
    descuentoManualCupon VARCHAR(50) DEFAULT '',
    metodoPago ENUM('efectivo', 'tarjeta', 'bizum') NOT NULL DEFAULT 'efectivo',
    estado ENUM('completada', 'anulada') NOT NULL DEFAULT 'completada',
    tipoDocumento ENUM('ticket', 'factura') NOT NULL DEFAULT 'factura',
    importeEntregado DECIMAL(10, 2) DEFAULT NULL,
    cambioDevuelto DECIMAL(10, 2) DEFAULT NULL,
    cerrada TINYINT(1) DEFAULT 0,
    idSesionCaja INT NULL,
    idTarifa INT NULL,
    cliente_dni VARCHAR(20) NULL,
    FOREIGN KEY (idUsuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Paso 3: Migrar datos existentes (ASUMIENDO QUE 'ventas' ES TODAVÍA LA TABLA ORIGINAL)
INSERT INTO tickets SELECT * FROM ventas WHERE tipoDocumento = 'ticket' OR tipoDocumento IS NULL;
INSERT INTO facturas SELECT * FROM ventas WHERE tipoDocumento = 'factura';

-- Paso 4: Eliminar FKs que apuntan a ventas (IMPORTANTE para poder borrar la tabla)
-- Intentar borrar FKs conocidas
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
     WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'lineasventa' AND CONSTRAINT_NAME = 'lineasventa_ibfk_1') > 0,
    'ALTER TABLE lineasventa DROP FOREIGN KEY lineasventa_ibfk_1',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
     WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'devoluciones' AND CONSTRAINT_NAME = 'fk_devolucion_venta') > 0,
    'ALTER TABLE devoluciones DROP FOREIGN KEY fk_devolucion_venta',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Paso 5: Eliminar la tabla ventas original
DROP TABLE IF EXISTS ventas;

-- Paso 6: Crear la vista 'ventas' como UNION ALL
CREATE VIEW ventas AS
SELECT * FROM tickets
UNION ALL
SELECT * FROM facturas;
