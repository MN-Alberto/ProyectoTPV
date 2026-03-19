-- ============================================================
-- Índices de rendimiento para la tabla clientes
-- Optimiza paginación server-side y búsquedas por DNI
-- ============================================================

-- Índice para listado paginado (WHERE activo = 1 ORDER BY id DESC LIMIT/OFFSET)
ALTER TABLE clientes ADD INDEX idx_activo_id (activo, id);

-- Índice para búsqueda por DNI parcial (WHERE dni LIKE 'x%' AND activo = 1)
ALTER TABLE clientes ADD INDEX idx_activo_dni (activo, dni);
