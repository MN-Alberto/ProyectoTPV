-- Tabla para almacenar precios específicos por producto y tarifa
-- Esta tabla permite tener precios personalizados para cada combinación producto-tarifa

CREATE TABLE IF NOT EXISTS producto_tarifa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    id_tarifa INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_tarifa) REFERENCES tarifas_prefijadas(id) ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY unique_producto_tarifa (id_producto, id_tarifa)
) ENGINE=InnoDB;
