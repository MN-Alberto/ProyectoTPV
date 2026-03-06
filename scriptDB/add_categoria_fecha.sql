-- Add fecha_creacion column to categorias table if it doesn't exist
ALTER TABLE categorias ADD COLUMN fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
