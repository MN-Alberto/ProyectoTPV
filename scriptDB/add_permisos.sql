-- Agregar columna permisos a la tabla usuarios
-- Permite almacenar permisos adicionales para empleados como 'crear_productos'

ALTER TABLE usuarios 
ADD COLUMN permisos SET('crear_productos') DEFAULT NULL;
