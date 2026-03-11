-- Agregar columnas de precios por tarifa a la tabla productos
-- Estos campos almacenan los precios calculados para cada tipo de cliente

ALTER TABLE productos 
ADD COLUMN precio_cliente DECIMAL(10, 2) DEFAULT NULL AFTER precio,
ADD COLUMN precio_mayorista1 DECIMAL(10, 2) DEFAULT NULL AFTER precio_cliente,
ADD COLUMN precio_mayorista2 DECIMAL(10, 2) DEFAULT NULL AFTER precio_mayorista1;

-- Actualizar los productos existentes con los precios calculados según las tarifas actuales
-- Esto se ejecutará desde PHP cuando se guarde una tarifa
