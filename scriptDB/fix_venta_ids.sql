-- ============================================================
-- Fix: Generación unificada de IDs para tickets y facturas
-- ============================================================

-- 1. Crear tabla maestra de IDs
CREATE TABLE IF NOT EXISTS ventas_ids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ticket', 'factura') NOT NULL
) ENGINE=InnoDB;

-- 2. Poblar con IDs existentes de tickets
INSERT INTO ventas_ids (id, tipo) SELECT id, 'ticket' FROM tickets;

-- 3. Poblar con IDs existentes de facturas
INSERT INTO ventas_ids (id, tipo) SELECT id, 'factura' FROM facturas;

-- 4. Ajustar el AUTO_INCREMENT de ventas_ids al máximo actual + 1
SET @max_id = (SELECT MAX(id) FROM (SELECT id FROM tickets UNION SELECT id FROM facturas) as t);
SET @sql = CONCAT('ALTER TABLE ventas_ids AUTO_INCREMENT = ', @max_id + 1);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Quitar AUTO_INCREMENT de tickets y facturas (el ID vendrá de ventas_ids)
ALTER TABLE tickets MODIFY id INT NOT NULL;
ALTER TABLE facturas MODIFY id INT NOT NULL;
