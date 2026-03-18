-- ============================================================
-- Script para añadir columna de puntos a la tabla clientes
-- Sistema de puntos: 1000 puntos por cada 20€ de compra
-- ============================================================

ALTER TABLE clientes ADD COLUMN puntos INT NOT NULL DEFAULT 0 AFTER compras_realizadas;
