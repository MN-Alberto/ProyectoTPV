-- =============================================================
-- Tabla de configuración del tema visual
-- Almacena pares clave-valor para personalizar la apariencia
-- =============================================================

CREATE TABLE IF NOT EXISTS configuracion_tema (
    clave VARCHAR(50) PRIMARY KEY,
    valor VARCHAR(255) NOT NULL
);

-- Valores predeterminados
INSERT INTO configuracion_tema (clave, valor) VALUES
    ('header_bg', '#1a1a2e'),
    ('header_color', '#ffffff'),
    ('header_font', 'Inter'),
    ('footer_bg', '#1a1a2e'),
    ('footer_color', '#e5e7eb'),
    ('footer_font', 'Inter'),
    ('primary_bg', '#2563eb'),
    ('primary_color', '#ffffff'),
    ('primary_font', 'Inter'),
    ('sidebar_bg', '#ffffff'),
    ('sidebar_color', '#1a1a2e'),
    ('sidebar_font', 'Inter'),
    ('btn_bg', '#1a1a2e'),
    ('btn_color', '#ffffff'),
    ('btn_font', 'Inter'),
    ('btn_white_bg', '#ffffff'),
    ('btn_white_color', '#1a1a2e'),
    ('btn_white_font', 'Inter')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
