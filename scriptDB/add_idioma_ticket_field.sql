-- =================================================
-- MIGRACIÓN: Añadir campo idioma_ticket en ventas
-- FECHA: 20/04/2026
-- DESCRIPCIÓN: Permite guardar el idioma seleccionado para cada ticket de venta
-- =================================================

-- 1. Añadir campo en tabla tickets
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS idioma_ticket VARCHAR(5) DEFAULT 'es';

-- 2. Añadir campo en tabla facturas
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS idioma_ticket VARCHAR(5) DEFAULT 'es';

-- 3. Actualizar índices si es necesario
ALTER TABLE tickets ADD INDEX IF NOT EXISTS idx_idioma_ticket (idioma_ticket);
ALTER TABLE facturas ADD INDEX IF NOT EXISTS idx_idioma_ticket (idioma_ticket);

-- 4. ACTUALIZAR VISTA UNION 'ventas' PARA INCLUIR EL NUEVO CAMPO
DROP VIEW IF EXISTS ventas;

CREATE VIEW ventas AS 
SELECT 
    id,idUsuario,fecha,total,descuentoTipo,descuentoValor,descuentoCupon,descuentoTarifaTipo,descuentoTarifaValor,descuentoTarifaCupon,descuentoManualTipo,descuentoManualValor,descuentoManualCupon,metodoPago,estado,tipoDocumento,importeEntregado,cambioDevuelto,cerrada,idSesionCaja,idTarifa,cliente_dni,cliente_nombre,cliente_direccion,cliente_observaciones,mensaje_personalizado,desglose_pago,puntos_ganados,puntos_canjeados,puntos_balance,idioma_ticket
FROM tickets 
UNION ALL 
SELECT 
    id,idUsuario,fecha,total,descuentoTipo,descuentoValor,descuentoCupon,descuentoTarifaTipo,descuentoTarifaValor,descuentoTarifaCupon,descuentoManualTipo,descuentoManualValor,descuentoManualCupon,metodoPago,estado,tipoDocumento,importeEntregado,cambioDevuelto,cerrada,idSesionCaja,idTarifa,cliente_dni,cliente_nombre,cliente_direccion,cliente_observaciones,mensaje_personalizado,desglose_pago,puntos_ganados,puntos_canjeados,puntos_balance,idioma_ticket
FROM facturas;

-- 5. Actualizar registros existentes por defecto a español
UPDATE tickets SET idioma_ticket = 'es' WHERE idioma_ticket IS NULL;
UPDATE facturas SET idioma_ticket = 'es' WHERE idioma_ticket IS NULL;

-- =================================================
-- MIGRACIÓN COMPLETADA CON ÉXITO
-- =================================================