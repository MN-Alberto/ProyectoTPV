-- Añadir columna IVA a lineasVenta si no existe
ALTER TABLE lineasVenta 
ADD COLUMN IF NOT EXISTS iva INT NOT NULL DEFAULT 21 AFTER precioUnitario;
