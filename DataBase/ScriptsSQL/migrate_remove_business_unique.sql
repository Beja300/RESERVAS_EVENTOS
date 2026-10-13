-- =========================================================
-- MIGRACIÓN: quitar restricciones UNIQUE de negocio de MySQL
-- ---------------------------------------------------------
-- La rúbrica exige que las reglas de unicidad/duplicados se
-- controlen desde PHP (Service/Controller), NO mediante
-- restricciones de negocio de MySQL. Este script elimina los
-- índices UNIQUE que imponían esas reglas a nivel de motor.
--
-- La unicidad sigue garantizada en la capa de código:
--   - correo    -> AuthService::validateEmailIsUnique
--   - cédula    -> AuthService::validateIdentificationIsUnique
--   - 1:1 rol   -> validaciones de los Services al asignar subtipo
--   - factura   -> InvoiceService (una factura por reserva)
--
-- Ejecutar una sola vez sobre bases existentes.
-- Importar DESPUÉS de dbeventhall.sql en instalaciones nuevas ya
-- no es necesario (el esquema ya no declara los UNIQUE).
-- =========================================================

USE dbeventhall;

-- Índices inline: MySQL los nombra igual que la columna.
ALTER TABLE tbrole            DROP INDEX tbroleemail;
ALTER TABLE tbroleadmin       DROP INDEX tbroleid;
ALTER TABLE tbroleclient      DROP INDEX tbroleid;
ALTER TABLE tbroleowner       DROP INDEX tbroleid;
ALTER TABLE tbowner           DROP INDEX tbowneridentificationnumber;
ALTER TABLE tbinvoice         DROP INDEX tbbookingid;
