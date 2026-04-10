-- ======================================================
-- OPTIMIZACIÓN DE ÍNDICES PARA 2.000.000 DE VENTAS
-- NO MODIFICA NINGUNA CONSULTA, SOLO AÑADE ÍNDICES
-- ======================================================

-- Índice para obtenerResumenCajaAbierta() - EL MÁS CRÍTICO
CREATE INDEX idx_tickets_cerrada_estado_metodo ON tickets (cerrada, estado, metodoPago, total);
CREATE INDEX idx_facturas_cerrada_estado_metodo ON facturas (cerrada, estado, metodoPago, total);

-- Índice para consultas por fechas y cerrada
CREATE INDEX idx_tickets_cerrada_estado_fecha ON tickets (cerrada, estado, fecha DESC);
CREATE INDEX idx_facturas_cerrada_estado_fecha ON facturas (cerrada, estado, fecha DESC);

-- Índice para consultas por usuario
CREATE INDEX idx_tickets_idusuario_fecha ON tickets (idUsuario, fecha DESC);
CREATE INDEX idx_facturas_idusuario_fecha ON facturas (idUsuario, fecha DESC);

-- Índice para consultas por sesión de caja
CREATE INDEX idx_tickets_idsesioncaja ON tickets (idSesionCaja);
CREATE INDEX idx_facturas_idsesioncaja ON facturas (idSesionCaja);

-- Índice para lineas de venta
CREATE INDEX idx_lineasventa_idventa ON lineas_venta (idVenta);

-- Índice para búsquedas por cliente
CREATE INDEX idx_tickets_cliente_dni ON tickets (cliente_dni);
CREATE INDEX idx_facturas_cliente_dni ON facturas (cliente_dni);

-- ======================================================
-- OPTIMIZACIÓN ADICIONAL PARA INFORMES
-- ======================================================
CREATE INDEX idx_tickets_fecha ON tickets (fecha DESC);
CREATE INDEX idx_facturas_fecha ON facturas (fecha DESC);