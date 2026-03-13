-- Tabla para almacenar precios específicos por producto y tarifa
-- id_producto: Referencia al producto
-- id_tarifa: Referencia a la tarifa prefijada
-- precio: Precio específico (sin IVA) para ese producto en esa tarifa
-- es_manual: Indica si el precio fue fijado manualmente (1) o calculado automáticamente (0)

CREATE TABLE IF NOT EXISTS productos_tarifas (
    id_producto INT NOT NULL,
    id_tarifa INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    es_manual BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_producto, id_tarifa),
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_tarifa) REFERENCES tarifas_prefijadas(id) ON DELETE CASCADE
);

-- Migración inicial: Popular la tabla con los precios calculados basados en los descuentos actuales
-- Nota: Esto asume que el precio base de 'productos' no tiene IVA.
INSERT IGNORE INTO productos_tarifas (id_producto, id_tarifa, precio, es_manual)
SELECT 
    p.id as id_producto,
    t.id as id_tarifa,
    CASE 
        WHEN t.nombre = 'Cliente Registrado' THEN p.precio -- Ya se manejaba así
        ELSE ROUND(p.precio * (1 - t.descuento_porcentaje / 100), 2)
    END as precio,
    0 as es_manual
FROM productos p
JOIN tarifas_prefijadas t ON t.activo = 1;
