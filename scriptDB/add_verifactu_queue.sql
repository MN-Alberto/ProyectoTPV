-- ============================================================
-- Script: Cola de Envíos Verifactu + Libro de Eventos
-- Autor: Alberto Méndez (via Antigravity)
-- Fecha: 28/04/2026
-- ============================================================

-- Cola centralizada de envíos pendientes a la AEAT
CREATE TABLE IF NOT EXISTS verifactu_cola_envios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_documento INT NOT NULL,
    tabla_origen ENUM('tickets','facturas') NOT NULL,
    tipo_envio ENUM('alta','anulacion','subsanacion') NOT NULL DEFAULT 'alta',
    xml_contenido LONGTEXT NOT NULL,
    estado ENUM('pendiente','enviando','enviado','error_temporal','error_permanente','descartado') NOT NULL DEFAULT 'pendiente',
    intentos INT NOT NULL DEFAULT 0,
    max_intentos INT NOT NULL DEFAULT 10,
    ultimo_error TEXT DEFAULT NULL,
    codigo_error_aeat VARCHAR(10) DEFAULT NULL,
    es_error_conexion TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_ultimo_intento DATETIME DEFAULT NULL,
    fecha_envio_exitoso DATETIME DEFAULT NULL,
    proximo_reintento DATETIME DEFAULT NULL,
    INDEX idx_estado (estado),
    INDEX idx_proximo (proximo_reintento),
    INDEX idx_documento (id_documento, tabla_origen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Libro de Eventos (obligatorio por RD 1007/2023)
-- Registra todas las incidencias de comunicación con la AEAT
CREATE TABLE IF NOT EXISTS verifactu_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM(
        'envio_ok',
        'envio_error',
        'reintento',
        'reintento_ok',
        'subsanacion',
        'conexion_perdida',
        'conexion_recuperada',
        'validacion_fallida',
        'cola_procesada',
        'descartado'
    ) NOT NULL,
    id_documento INT DEFAULT NULL,
    tabla_origen VARCHAR(20) DEFAULT NULL,
    descripcion TEXT NOT NULL,
    datos_extra JSON DEFAULT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha),
    INDEX idx_documento (id_documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir intervalo de reintento configurable a la configuración fiscal
INSERT INTO configuracion_fiscal (clave, valor) 
VALUES ('verifactu_intervalo_reintento', '15')
ON DUPLICATE KEY UPDATE valor = valor;

-- Añadir max registros por lote
INSERT INTO configuracion_fiscal (clave, valor) 
VALUES ('verifactu_max_lote', '100')
ON DUPLICATE KEY UPDATE valor = valor;
