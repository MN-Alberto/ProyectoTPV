-- Remove UNIQUE constraint from email column in usuarios table
-- This allows multiple users to have the same email address

ALTER TABLE usuarios MODIFY COLUMN email VARCHAR(150) NOT NULL;