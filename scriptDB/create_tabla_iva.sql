-- ============================================================
-- Script de creación de la tabla IVA y migración de productos
-- Autor: Alberto Méndez
-- Fecha: 11/03/2026
-- ============================================================

-- 1. Crear la tabla IVA con los tipos de IVA
CREATE TABLE IF NOT EXISTS iva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    porcentaje DECIMAL(5, 2) NOT NULL
) ENGINE=InnoDB;

-- 2. Insertar los tipos de IVA estándar españoles
INSERT IGNORE INTO iva (nombre, porcentaje) VALUES
('General', 21.00),
('Reducido', 10.00),
('Superreducido', 4.00),
('Exento', 0.00);

-- 3. Añadir columna idIva a productos (FK a iva.id)
ALTER TABLE productos ADD COLUMN IF NOT EXISTS idIva INT NULL AFTER activo;

-- 4. Popular idIva según el valor actual de productos.iva
UPDATE productos p
SET p.idIva = (SELECT i.id FROM iva i WHERE i.porcentaje = p.iva)
WHERE p.idIva IS NULL;

-- 5. Asignar IVA General (21%) a los que no tengan coincidencia
UPDATE productos
SET idIva = (SELECT id FROM iva WHERE porcentaje = 21.00)
WHERE idIva IS NULL;

-- 6. Hacer la columna NOT NULL y añadir la FK
ALTER TABLE productos MODIFY COLUMN idIva INT NOT NULL DEFAULT 1;
ALTER TABLE productos ADD CONSTRAINT fk_productos_iva 
    FOREIGN KEY (idIva) REFERENCES iva(id) ON UPDATE CASCADE ON DELETE RESTRICT;

-- 7. Eliminar la columna antigua iva de productos
ALTER TABLE productos DROP COLUMN IF EXISTS iva;
