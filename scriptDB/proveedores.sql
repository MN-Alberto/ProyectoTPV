-- ============================================================
-- Script de creación de la tabla proveedores
-- Autor: Alberto Méndez
-- Fecha: 04/03/2026
-- ============================================================

USE ProyectoTPV;

CREATE TABLE IF NOT EXISTS proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    contacto VARCHAR(100),
    email VARCHAR(150),
    direccion VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- Datos de ejemplo
INSERT INTO proveedores (nombre, contacto, email, direccion, activo) VALUES
('Logitech España S.L.', '912345678', 'ventas@logitech.es', 'Av. de la Industria 4, 28108 Madrid', 1),
('Kingston Technology', '934567890', 'distribucion@kingston.es', 'C/ Tecnología 15, 08001 Barcelona', 1),
('TP-Link Iberia', '916789012', 'comercial@tp-link.es', 'Parque Empresarial 7, 28023 Madrid', 1),
('HP España', '913456789', 'pedidos@hp.es', 'C/ Ribera del Loira 2, 28042 Madrid', 1),
('Samsung Electronics Iberia', '917890123', 'b2b@samsung.es', 'Av. de Europa 4, 28108 Alcobendas', 1);
