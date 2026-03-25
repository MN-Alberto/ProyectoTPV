-- ============================================================
-- Add correlative numbering with series for tickets and invoices
-- ============================================================

-- 1. Add columns for correlative numbering
ALTER TABLE ventas_ids ADD COLUMN numero INT NOT NULL DEFAULT 0 AFTER tipo;
ALTER TABLE ventas_ids ADD COLUMN serie VARCHAR(10) NOT NULL DEFAULT '' AFTER numero;

-- 2. Update existing records with correlative numbers based on their type
-- For tickets, assign correlative numbers starting from 1
UPDATE ventas_ids SET numero = (
    SELECT COUNT(*) FROM ventas_ids v2 
    WHERE v2.tipo = 'ticket' AND v2.id <= ventas_ids.id
), serie = 'T' 
WHERE tipo = 'ticket';

-- For invoices, assign correlative numbers starting from 1  
UPDATE ventas_ids SET numero = (
    SELECT COUNT(*) FROM ventas_ids v2 
    WHERE v2.tipo = 'factura' AND v2.id <= ventas_ids.id
), serie = 'F'
WHERE tipo = 'factura';

-- 3. Create stored procedure to get next correlative number
DELIMITER //
CREATE PROCEDURE spObtenerSiguienteNumero(IN p_tipo VARCHAR(20))
BEGIN
    DECLARE v_serie VARCHAR(10);
    DECLARE v_max_numero INT;
    
    -- Determine series based on type
    IF p_tipo = 'factura' THEN
        SET v_serie = 'F';
    ELSE
        SET v_serie = 'T';
    END IF;
    
    -- Get current maximum number for this type
    SELECT COALESCE(MAX(numero), 0) INTO v_max_numero 
    FROM ventas_ids 
    WHERE tipo = p_tipo;
    
    -- Return the next number
    SELECT v_serie AS serie, (v_max_numero + 1) AS siguiente;
END //
DELIMITER ;
