-- ============================================================
-- Índices optimizados para la tabla de clientes
-- Mejora el rendimiento de búsquedas y paginación
-- ============================================================

-- Índice para búsquedas por DNI (búsqueda exacta y parcial)
CREATE INDEX idx_clientes_dni ON clientes(dni);

-- Índice compuesto para paginación eficiente + ordenamiento
-- Útil para paginación con WHERE activo = 1 ORDER BY id DESC
CREATE INDEX idx_clientes_activo_id ON clientes(activo, id DESC);

-- Índice para búsquedas por nombre (búsqueda parcial)
CREATE INDEX idx_clientes_nombre ON clientes(nombre);

-- Índice para búsquedas por apellido (búsqueda parcial)
CREATE INDEX idx_clientes_apellidos ON clientes(apellidos);

-- Verificar índices existentes
-- SHOW INDEX FROM clientes;
