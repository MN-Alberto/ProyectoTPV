-- Añadir columna precio_clienteregistrado a la tabla productos
-- y popul con los precios calculados según el descuento de la tarifa Cliente Registrado

ALTER TABLE productos ADD COLUMN `precio_clienteregistrado` DECIMAL(10, 2) NULL AFTER activo;

-- Actualizar los precios basados en el descuento de la tarifa
UPDATE productos p
SET precio_clienteregistrado = ROUND(p.precio * (1 - t.descuento_porcentaje / 100), 2)
FROM productos p
CROSS JOIN (SELECT descuento_porcentaje FROM tarifas_prefijadas WHERE nombre = 'Cliente Registrado') t
WHERE p.activo = 1;
