-- ============================================================
-- Mejora: Gestión de Caja
-- ============================================================

-- Tabla para las sesiones de caja
CREATE TABLE IF NOT EXISTS caja_sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idUsuario INT NOT NULL,
    fechaApertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fechaCierre DATETIME NULL,
    importeInicial DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    importeActual DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    cambio DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Cambio guardado para el siguiente turno',
    estado ENUM('abierta', 'cerrada') NOT NULL DEFAULT 'abierta',
    FOREIGN KEY (idUsuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Añadir campo cambio si no existe (para bases de datos existentes)
ALTER TABLE caja_sesiones ADD COLUMN IF NOT EXISTS cambio DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Cambio guardado para el siguiente turno';

-- Tabla para registrar los retiros de dinero de caja
CREATE TABLE IF NOT EXISTS retiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    idCajaSesion INT NOT NULL,
    idUsuario INT NOT NULL,
    importe DECIMAL(10, 2) NOT NULL,
    motivo VARCHAR(255) NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idCajaSesion) REFERENCES caja_sesiones(id) ON DELETE CASCADE,
    FOREIGN KEY (idUsuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Asegurar que la tabla ventas tiene los campos necesarios para el seguimiento de caja
-- (dineroEntregado y cambioDevuelto son útiles para auditoría según la fórmula del usuario)
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS importeEntregado DECIMAL(10, 2) DEFAULT NULL;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS cambioDevuelto DECIMAL(10, 2) DEFAULT NULL;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS cerrada TINYINT(1) DEFAULT 0;

-- Añadir columna para relacionar ventas con sesiones de caja
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS idSesionCaja INT NULL;
ALTER TABLE ventas ADD FOREIGN KEY IF NOT EXISTS (idSesionCaja) REFERENCES caja_sesiones(id) ON DELETE SET NULL ON UPDATE CASCADE;
