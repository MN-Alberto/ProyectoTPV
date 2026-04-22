CREATE TABLE IF NOT EXISTS configuracion_fiscal (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NOT NULL,
    descripcion VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert initial values from confDB.php constants if not exists
INSERT INTO configuracion_fiscal (clave, valor, descripcion) VALUES 
('tpv_nif', '99999910G', 'NIF del obligado tributario'),
('tpv_razon_social', 'CERTIFICADO FISICA PRUEBAS', 'Razón Social / Nombre fiscal'),
('aeat_url_verifactu', 'https://prewww1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP', 'URL del Servicio Web AEAT'),
('cert_path', 'C:/Users/PracticasSoftware5/Documents/ProyectoTPV/certs/99999910G_prueba.pfx', 'Ruta local absoluta al certificado .pfx'),
('cert_pass', '1234', 'Contraseña del certificado digital')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
