-- Agregar columna cliente_dni a la tabla ventas para almacenar el DNI del cliente
ALTER TABLE ventas ADD COLUMN cliente_dni VARCHAR(20) NULL;
