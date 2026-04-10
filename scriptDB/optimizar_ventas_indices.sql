-- =========================================================
-- OPTIMIZACIÓN DE ÍNDICES PARA VISTA VENTAS
-- IMPORTANTE: ventas es una VISTA, los indices se crean en las tablas base
-- Tiempo de ejecución esperado: ~10seg por cada millón de registros
-- =========================================================

-- 🔹 ÍNDICES PRINCIPALES COMPUESTOS para tablas base
-- Estos índices permiten a MySQL optimizar la vista y hacer MERGE
CREATE INDEX idx_tickets_filtros_orden 
ON tickets (fecha DESC, id DESC, metodoPago, tipoDocumento, idUsuario, total);

CREATE INDEX idx_facturas_filtros_orden 
ON facturas (fecha DESC, id DESC, metodoPago, tipoDocumento, idUsuario, total);

-- 🔹 Índice para busqueda rápida por ID en ambas tablas
CREATE INDEX idx_tickets_id ON tickets (id);
CREATE INDEX idx_facturas_id ON facturas (id);

-- 🔹 Índice para busqueda por numero correlativo
CREATE UNIQUE INDEX idx_ventas_ids_serie_numero 
ON ventas_ids (serie, numero, id);

-- 🔹 Índice para lineas de venta (evita subconsultas)
CREATE INDEX idx_lineasventa_idventa_cantidad 
ON lineasVenta (idVenta, cantidad);

-- 🔹 Optimizaciones para COUNT(*) (opcional, requiere privilegios SUPER)
-- Ejecutar manualmente solo si tienes permisos de administrador:
-- SET GLOBAL innodb_stats_persistent = ON;
-- SET GLOBAL innodb_stats_auto_recalc = ON;

-- =========================================================
-- VERIFICACIÓN
-- =========================================================
SHOW INDEXES FROM tickets;
SHOW INDEXES FROM facturas;
SHOW INDEXES FROM ventas_ids;
SHOW INDEXES FROM lineasVenta;