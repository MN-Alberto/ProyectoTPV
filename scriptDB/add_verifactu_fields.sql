-- Script para añadir campos Verifactu
-- Autor: Alberto Méndez (via Antigravity)
-- Fecha: 21/04/2026

ALTER TABLE tickets 
ADD COLUMN hash VARCHAR(64) DEFAULT NULL,
ADD COLUMN hash_previo VARCHAR(64) DEFAULT NULL,
ADD COLUMN xml_datos LONGTEXT DEFAULT NULL,
ADD COLUMN estado_aeat ENUM('pendiente', 'enviado', 'rechazado', 'error') DEFAULT 'pendiente',
ADD COLUMN csv_aeat VARCHAR(100) DEFAULT NULL,
ADD COLUMN fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE facturas 
ADD COLUMN hash VARCHAR(64) DEFAULT NULL,
ADD COLUMN hash_previo VARCHAR(64) DEFAULT NULL,
ADD COLUMN xml_datos LONGTEXT DEFAULT NULL,
ADD COLUMN estado_aeat ENUM('pendiente', 'enviado', 'rechazado', 'error') DEFAULT 'pendiente',
ADD COLUMN csv_aeat VARCHAR(100) DEFAULT NULL,
ADD COLUMN fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP;

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
       idioma_ticket, hash, hash_previo, estado_aeat, csv_aeat, fecha_registro
FROM tickets 
UNION ALL 
SELECT id, idUsuario, fecha, total, descuentoTipo, descuentoValor, descuentoCupon, 
       descuentoTarifaTipo, descuentoTarifaValor, descuentoTarifaCupon, 
       descuentoManualTipo, descuentoManualValor, descuentoManualCupon, 
       metodoPago, estado, tipoDocumento, importeEntregado, cambioDevuelto, 
       cerrada, idSesionCaja, idTarifa, cliente_dni, cliente_nombre, 
       cliente_direccion, cliente_observaciones, mensaje_personalizado, 
       desglose_pago, puntos_ganados, puntos_canjeados, puntos_balance, 
       idioma_ticket, hash, hash_previo, estado_aeat, csv_aeat, fecha_registro
FROM facturas;
