-- Tabla de tarifas prefijadas para el sistema de ventas
-- Estas tarifas se usan en el selector del cajero para aplicar descuentos

CREATE TABLE IF NOT EXISTS tarifas_prefijadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    descuento_porcentaje DECIMAL(5, 2) NOT NULL DEFAULT 0,
    requiere_cliente BOOLEAN NOT NULL DEFAULT FALSE,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    orden INT NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertar tarifas iniciales
INSERT INTO tarifas_prefijadas (nombre, descripcion, descuento_porcentaje, requiere_cliente, orden) VALUES
('Cliente', 'Tarifa estándar sin descuento', 0, FALSE, 1),
('Mayorista Nivel 1', 'Descuento para mayoristas de nivel 1', 12, FALSE, 2),
('Mayorista Nivel 2', 'Descuento para mayoristas de nivel 2', 16, FALSE, 3),
('Cliente Registrado', 'Descuento para clientes registrados (requiere búsqueda)', 0, TRUE, 4);
