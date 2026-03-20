-- Script para añadir idSesionCaja a la tabla de devoluciones
-- Autor: Antigravity
-- Fecha: 20/03/2026

USE ProyectoTPV;

-- Añadir columna idSesionCaja si no existe
ALTER TABLE devoluciones ADD COLUMN IF NOT EXISTS idSesionCaja INT NULL AFTER idVenta;

-- Añadir clave foránea
ALTER TABLE devoluciones 
ADD CONSTRAINT fk_devolucion_sesion_caja 
FOREIGN KEY (idSesionCaja) REFERENCES caja_sesiones(id) 
ON DELETE SET NULL ON UPDATE CASCADE;
