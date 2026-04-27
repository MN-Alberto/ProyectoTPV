-- ============================================================
-- Script: Campos Verifactu para Rectificativas y Anulaciones
-- Autor: Alberto Méndez (via Antigravity)
-- Fecha: 22/04/2026
-- ============================================================

-- Campos para anulación mejorada (tickets)
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS xml_datos_anu LONGTEXT DEFAULT NULL;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS hash_anulacion VARCHAR(64) DEFAULT NULL;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS csv_anulacion VARCHAR(100) DEFAULT NULL;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS fecha_anulacion DATETIME DEFAULT NULL;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS error_aeat TEXT DEFAULT NULL;

-- Campos para rectificativas (tickets)
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS es_rectificativa TINYINT(1) DEFAULT 0;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS id_documento_original INT DEFAULT NULL;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS tipo_factura_verifactu VARCHAR(2) DEFAULT NULL;

-- Campos para anulación mejorada (facturas)
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS xml_datos_anu LONGTEXT DEFAULT NULL;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS hash_anulacion VARCHAR(64) DEFAULT NULL;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS csv_anulacion VARCHAR(100) DEFAULT NULL;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS fecha_anulacion DATETIME DEFAULT NULL;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS error_aeat TEXT DEFAULT NULL;

-- Campos para rectificativas (facturas)
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS es_rectificativa TINYINT(1) DEFAULT 0;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS id_documento_original INT DEFAULT NULL;
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS tipo_factura_verifactu VARCHAR(2) DEFAULT NULL;

-- Series para rectificativas en ventas_ids
-- RT = Rectificativa Ticket, RF = Rectificativa Factura
ALTER TABLE ventas_ids MODIFY COLUMN serie VARCHAR(5) NOT NULL DEFAULT 'T';

-- Actualizar vista ventas para incluir nuevos campos
DROP VIEW IF EXISTS ventas;
CREATE VIEW ventas AS
SELECT id, idUsuario, fecha, total, descuentoTipo, descuentoValor, descuentoCupon,
       descuentoTarifaTipo, descuentoTarifaValor, descuentoTarifaCupon,
       descuentoManualTipo, descuentoManualValor, descuentoManualCupon,
       metodoPago, estado, tipoDocumento, importeEntregado, cambioDevuelto,
       cerrada, idSesionCaja, idTarifa, cliente_dni, cliente_nombre,
       cliente_direccion, cliente_observaciones, mensaje_personalizado,
       desglose_pago, puntos_ganados, puntos_canjeados, puntos_balance,
       idioma_ticket, hash, hash_previo, estado_aeat, csv_aeat, fecha_registro,
       cantidad_productos,
       es_rectificativa, id_documento_original, tipo_factura_verifactu,
       hash_anulacion, csv_anulacion, fecha_anulacion, error_aeat
FROM tickets
UNION ALL
SELECT id, idUsuario, fecha, total, descuentoTipo, descuentoValor, descuentoCupon,
       descuentoTarifaTipo, descuentoTarifaValor, descuentoTarifaCupon,
       descuentoManualTipo, descuentoManualValor, descuentoManualCupon,
       metodoPago, estado, tipoDocumento, importeEntregado, cambioDevuelto,
       cerrada, idSesionCaja, idTarifa, cliente_dni, cliente_nombre,
       cliente_direccion, cliente_observaciones, mensaje_personalizado,
       desglose_pago, puntos_ganados, puntos_canjeados, puntos_balance,
       idioma_ticket, hash, hash_previo, estado_aeat, csv_aeat, fecha_registro,
       cantidad_productos,
       es_rectificativa, id_documento_original, tipo_factura_verifactu,
       hash_anulacion, csv_anulacion, fecha_anulacion, error_aeat
FROM facturas;
