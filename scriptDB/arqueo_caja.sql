-- Tabla para arqueos de caja (cash count)
CREATE TABLE IF NOT EXISTS arqueos_caja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idCajaSesion INT NOT NULL,
    idUsuario INT NOT NULL,
    fechaArqueo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Datos del sistema
    fondoInicial DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Fondo inicial de la sesión',
    ventasEfectivo DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Ventas en efectivo del día',
    devolucionesEfectivo DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Devoluciones en efectivo del día',
    retiros DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Retiros de caja del día',
    efectivoEsperado DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Efectivo esperado (calculado automáticamente)',
    
    -- Conteo manual
    efectivoContado DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Efectivo contado manualmente',
    diferencia DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Diferencia (contado - esperado)',
    
    -- Detalles del conteo (JSON con billets y monedas)
    detalleConteo TEXT COMMENT 'JSON con el detalle de billetes y monedas contados',
    
    -- Observaciones
    observaciones TEXT COMMENT 'Observaciones opcionales del cajero',
    
    -- Estado
    tipoArqueo ENUM('cierre', 'intermedio') NOT NULL DEFAULT 'cierre' COMMENT 'Tipo de arqueo',
    
    FOREIGN KEY (idCajaSesion) REFERENCES caja_sesiones(id) ON DELETE CASCADE,
    FOREIGN KEY (idUsuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices para mejorar el rendimiento
CREATE INDEX idx_arqueo_sesion ON arqueos_caja(idCajaSesion);
CREATE INDEX idx_arqueo_fecha ON arqueos_caja(fechaArqueo);
