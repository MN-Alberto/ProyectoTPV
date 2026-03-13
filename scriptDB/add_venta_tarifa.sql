-- Agregar columna idTarifa a la tabla ventas para almacenar la tarifa aplicada
ALTER TABLE ventas ADD COLUMN idTarifa INT NULL;
ALTER TABLE ventas ADD FOREIGN KEY (idTarifa) REFERENCES tarifas_prefijadas(id) ON UPDATE CASCADE ON DELETE SET NULL;
