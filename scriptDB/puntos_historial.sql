-- Tabla de historial de puntos de clientes
-- Registro de todos los movimientos de puntos (ganados, canjeados, ajustes)

CREATE TABLE IF NOT EXISTS puntos_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_dni VARCHAR(20) NOT NULL,
    venta_id INT DEFAULT NULL,
    puntos_ganados INT DEFAULT 0,
    puntos_canjeados INT DEFAULT 0,
    descuento_euros DECIMAL(10,2) DEFAULT 0,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario_id INT DEFAULT NULL,
    observacion VARCHAR(255) DEFAULT NULL,
    INDEX idx_cliente_dni (cliente_dni),
    INDEX idx_venta_id (venta_id),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir columna de puntos a clientes si no existe
-- (Nota: Esta tabla ya debería existir con la columna puntos)
