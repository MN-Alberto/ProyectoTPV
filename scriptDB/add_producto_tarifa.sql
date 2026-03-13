-- Agregar columna tarifa a la tabla productos para almacenar la tarifa/precio especial del producto
-- La columna almacenará el ID de la tarifa prefijada seleccionada
ALTER TABLE productos ADD COLUMN tarifa INT NULL DEFAULT NULL;

-- Actualizar los productos existentes para que no tengan tarifa asignada (valor null)
-- UPDATE productos SET tarifa = NULL WHERE tarifa IS NULL;
