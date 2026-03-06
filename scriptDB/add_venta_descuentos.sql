-- Añadir columnas de descuento a la tabla ventas si no existen
-- Este script añade campos para almacenar los descuentos de tarifa y manuales

ALTER TABLE ventas 
ADD COLUMN IF NOT EXISTS descuentoTipo ENUM('ninguno', 'porcentaje', 'fijo') DEFAULT 'ninguno' AFTER total,
ADD COLUMN IF NOT EXISTS descuentoValor DECIMAL(10, 2) DEFAULT 0 AFTER descuentoTipo,
ADD COLUMN IF NOT EXISTS descuentoCupon VARCHAR(50) DEFAULT '' AFTER descuentoValor,
ADD COLUMN IF NOT EXISTS descuentoTarifaTipo ENUM('ninguno', 'porcentaje', 'fijo') DEFAULT 'ninguno' AFTER descuentoCupon,
ADD COLUMN IF NOT EXISTS descuentoTarifaValor DECIMAL(10, 2) DEFAULT 0 AFTER descuentoTarifaTipo,
ADD COLUMN IF NOT EXISTS descuentoTarifaCupon VARCHAR(50) DEFAULT '' AFTER descuentoTarifaValor,
ADD COLUMN IF NOT EXISTS descuentoManualTipo ENUM('ninguno', 'porcentaje', 'fijo') DEFAULT 'ninguno' AFTER descuentoTarifaCupon,
ADD COLUMN IF NOT EXISTS descuentoManualValor DECIMAL(10, 2) DEFAULT 0 AFTER descuentoManualTipo,
ADD COLUMN IF NOT EXISTS descuentoManualCupon VARCHAR(50) DEFAULT '' AFTER descuentoManualValor;
