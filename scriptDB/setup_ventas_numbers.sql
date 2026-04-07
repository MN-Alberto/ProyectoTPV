-- ============================================================
-- Create and populate ventas_ids with correlative numbering
-- Run this SQL to set up ticket/invoice numbering
-- ============================================================

-- 1. Check if ventas_ids table exists and show its structure
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas_ids';

-- 2. Create the table if it doesn't exist
CREATE TABLE IF NOT EXISTS ventas_ids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(20) DEFAULT 'ticket',
    numero INT DEFAULT 0,
    serie VARCHAR(10) DEFAULT ''
) ENGINE=InnoDB;

-- 3. Add columns if they don't exist
-- (MySQL 8.0+ syntax)
ALTER TABLE ventas_ids ADD COLUMN IF NOT EXISTS numero INT DEFAULT 0;
ALTER TABLE ventas_ids ADD COLUMN IF NOT EXISTS serie VARCHAR(10) DEFAULT '';

-- 4. Update all records to have correlative numbers
-- For tickets: serie = 'T'
UPDATE ventas_ids SET serie = 'T', numero = id WHERE (serie IS NULL OR serie = '') AND (tipo = 'ticket' OR tipo IS NULL OR tipo = '');

-- For invoices: serie = 'F' (if you have any invoices in the table)
-- This would need to be run manually if you have invoices
-- UPDATE ventas_ids SET serie = 'F' WHERE tipo = 'factura';

-- 5. To check the results:
-- SELECT * FROM ventas_ids ORDER BY id DESC LIMIT 10;

-- 6. To manually set the next number for tickets:
-- UPDATE ventas_ids SET numero = (SELECT MAX(id) FROM ventas_ids) WHERE serie = 'T';
