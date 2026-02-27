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
    estado ENUM('abierta', 'cerrada') NOT NULL DEFAULT 'abierta',
    FOREIGN KEY (idUsuario) REFERENCES usuarios(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Asegurar que la tabla ventas tiene los campos necesarios para el seguimiento de caja
-- (dineroEntregado y cambioDevuelto son útiles para auditoría según la fórmula del usuario)
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS importeEntregado DECIMAL(10, 2) DEFAULT NULL;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS cambioDevuelto DECIMAL(10, 2) DEFAULT NULL;
ALTER TABLE ventas ADD COLUMN IF NOT EXISTS cerrada TINYINT(1) DEFAULT 0;
