ALTER TABLE lineasVenta ADD COLUMN decimales INT DEFAULT 2;
ALTER TABLE devoluciones ADD COLUMN decimales INT DEFAULT 2;

-- Opcional: actualizar registros antiguos si es necesario (por defecto ya es 2)
UPDATE lineasVenta SET decimales = 2 WHERE decimales IS NULL OR decimales = 0;
UPDATE devoluciones SET decimales = 2 WHERE decimales IS NULL OR decimales = 0;
