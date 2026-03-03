-- Script para añadir idVenta a la tabla de devoluciones
-- Autor: Antigravity
-- Fecha: 03/03/2026

USE ProyectoTPV;

-- Añadir columna idVenta si no existe
ALTER TABLE devoluciones ADD COLUMN IF NOT EXISTS idVenta INT NULL AFTER idUsuario;

-- Añadir clave foránea si no existe
-- Nota: En MariaDB/MySQL ALTER TABLE ADD FOREIGN KEY no tiene IF NOT EXISTS, 
-- pero solemos gestionar los errores o ignorar si ya existe en scripts de migración.
ALTER TABLE devoluciones 
ADD CONSTRAINT fk_devolucion_venta 
FOREIGN KEY (idVenta) REFERENCES ventas(id) 
ON DELETE SET NULL;
