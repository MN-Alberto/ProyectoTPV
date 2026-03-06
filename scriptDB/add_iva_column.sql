-- Add iva column to productos table if it doesn't exist
-- This script adds the iva column to store the VAT percentage for each product

-- Check if column exists before adding
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'ProyectoTPV' 
    AND TABLE_NAME = 'productos' 
    AND COLUMN_NAME = 'iva'
);

-- Add column if it doesn't exist
SET @sql = IF(@column_exists = 0, 
    'ALTER TABLE productos ADD COLUMN iva INT NOT NULL DEFAULT 21 AFTER activo', 
    'SELECT ''Column iva already exists''');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update existing products to have default IVA of 21%
UPDATE productos SET iva = 21 WHERE iva IS NULL OR iva = 0;
