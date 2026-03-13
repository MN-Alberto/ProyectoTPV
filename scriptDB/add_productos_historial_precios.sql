-- Tabla para almacenar el historial de precios de productos
CREATE TABLE IF NOT EXISTS productos_historial_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    precio DECIMAL(10, 4) NOT NULL,
    id_tarifa INT DEFAULT NULL, -- NULL means base price, otherwise tariff-specific price
    fecha_cambio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT DEFAULT NULL,
    INDEX idx_producto (id_producto),
    INDEX idx_fecha (fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
