-- ============================================================
-- Índices optimizados para la tabla de usuarios
-- Mejora el rendimiento de búsquedas y paginación
-- con grandes volúmenes de datos (500k+ registros)
-- ============================================================

-- Índice para búsquedas por nombre (ORDER BY y WHERE)
CREATE INDEX idx_usuarios_nombre ON usuarios(nombre);

-- Índice para búsquedas por email (búsqueda exacta - ya es UNIQUE)
-- El índice UNIQUE ya existe implícitamente, pero lo verificamos

-- Índice compuesto para paginación eficiente + ordenamiento
-- Útil para OFFSET-based pagination
CREATE INDEX idx_usuarios_activo_nombre ON usuarios(activo, nombre);

-- Índice para paginación cursor-based (keyset pagination)
-- Permite paginar sin OFFSET usando WHERE id < last_id
CREATE INDEX idx_usuarios_id_nombre ON usuarios(id, nombre);

-- Nota: Para millones de registros, considerar cursor-based pagination
-- en lugar de OFFSET:
-- SELECT * FROM usuarios WHERE id < :last_id ORDER BY id DESC LIMIT :limit
