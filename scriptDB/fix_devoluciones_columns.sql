-- Script para añadir columnas faltantes a la tabla de devoluciones
-- Autor: Antigravity
-- Fecha: 20/03/2026

USE ProyectoTPV;

-- Añadir idVenta si no existe
ALTER TABLE devoluciones ADD COLUMN IF NOT EXISTS idVenta INT NULL AFTER idUsuario;

-- Añadir idSesionCaja si no existe
ALTER TABLE devoluciones ADD COLUMN IF NOT EXISTS idSesionCaja INT NULL AFTER idVenta;

-- Añadir precioUnitario si no existe
ALTER TABLE devoluciones ADD COLUMN IF NOT EXISTS precioUnitario DECIMAL(10, 2) NULL AFTER cantidad;

-- Añadir iva si no existe
ALTER TABLE devoluciones ADD COLUMN IF NOT EXISTS iva INT NULL DEFAULT 21 AFTER precioUnitario;

-- Añadir metodoPago si no existe
ALTER TABLE devoluciones ADD COLUMN IF NOT EXISTS metodoPago VARCHAR(50) NULL AFTER idUsuario;

-- Añadir motivo si no existe
ALTER TABLE devoluciones ADD COLUMN IF NOT EXISTS motivo VARCHAR(255) NULL AFTER metodoPago;

-- Añadir importeTotal si no existe (por si acaso)
ALTER TABLE devoluciones ADD COLUMN IF NOT EXISTS importeTotal DECIMAL(10, 2) NULL AFTER iva;

-- Asegurar claves foráneas (Si falla por duplicado, es que ya existe y puedes ignorar este error)
-- Intentamos con un nombre ligeramente distinto por si acaso o simplemente ignoramos si falla.

ALTER TABLE devoluciones 
ADD CONSTRAINT fk_devolucion_sesion_caja_v2 
FOREIGN KEY (idSesionCaja) REFERENCES caja_sesiones(id) 
ON DELETE SET NULL ON UPDATE CASCADE;
