-- Tabla de logs del sistema
CREATE TABLE IF NOT EXISTS logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('login', 'login_fallido', 'logout', 'venta', 'apertura_caja', 'cierre_caja', 'retiro_caja', 'acceso_admin', 'acceso_cajero', 'acceso_login', 'creacion_usuario', 'modificacion_usuario', 'eliminacion_usuario', 'creacion_producto', 'modificacion_producto', 'eliminacion_producto', 'creacion_categoria', 'modificacion_categoria', 'eliminacion_categoria') NOT NULL,
    usuario_id INT,
    usuario_nombre VARCHAR(100),
    descripcion TEXT,
    detalles JSON,
    INDEX idx_fecha (fecha),
    INDEX idx_tipo (tipo),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
