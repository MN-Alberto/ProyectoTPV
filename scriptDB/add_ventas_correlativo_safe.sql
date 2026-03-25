-- ============================================================
-- Fix: Add correlative numbering to ventas_ids (safe version)
-- ============================================================

-- 1. Check if columns exist first
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ventas_ids' AND COLUMN_NAME = 'tipo');

-- 2. Add columns only if they don't exist (safe mode)
-- Note: If tipo column doesn't exist, this script won't work properly

-- First, let's add the columns
ALTER TABLE ventas_ids ADD COLUMN IF NOT EXISTS numero INT NOT NULL DEFAULT 0 AFTER tipo;
ALTER TABLE ventas_ids ADD COLUMN IF NOT EXISTS serie VARCHAR(10) NOT NULL DEFAULT '' AFTER numero;

-- 3. Update existing records based on current IDs
-- Since we can't use the old ID approach, we'll just set basic values
UPDATE ventas_ids SET serie = 'T', numero = id WHERE serie = '' OR serie IS NULL;

-- 4. If there's a separate way to identify tickets vs invoices, use that
-- Otherwise all will be T series. For invoices, you'd need to run separate update

-- Example: If there's another way to identify invoices:
-- UPDATE ventas_ids SET serie = 'F' WHERE /* some condition identifying invoices */;
