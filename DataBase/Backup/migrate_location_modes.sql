-- =========================================================
-- MIGRACIÓN: modos de ubicación de locales (exacta/aproximada)
-- ---------------------------------------------------------
-- Agrega el modo de ubicación y el texto de referencia para las
-- ubicaciones aproximadas. Las filas existentes quedan como exactas.
--
-- Ejecutar una sola vez sobre bases existentes. En instalaciones
-- nuevas NO es necesario (dbeventhall.sql ya las incluye).
-- =========================================================

USE dbeventhall;

ALTER TABLE tblocation
    ADD COLUMN tblocationtype VARCHAR(20) NOT NULL DEFAULT 'exacta' AFTER tblocationdistrict,
    ADD COLUMN tblocationapproximate VARCHAR(300) NULL AFTER tblocationtown;
