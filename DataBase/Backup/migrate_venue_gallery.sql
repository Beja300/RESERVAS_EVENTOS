-- =========================================================
-- MIGRACIÓN: galería de imágenes de locales (varias fotos por local)
-- ---------------------------------------------------------
-- Crea la tabla tbvenueimage para guardar las fotos adicionales del
-- local. La primera foto sigue guardándose en tbvenue.tbvenueimage
-- como portada para no romper catálogo/listados.
--
-- Ejecutar una sola vez sobre bases existentes. En instalaciones
-- nuevas NO es necesario (dbeventhall.sql ya la incluye).
-- =========================================================

USE dbeventhall;

CREATE TABLE IF NOT EXISTS tbvenueimage (
    tbvenueimageid INT AUTO_INCREMENT PRIMARY KEY,
    tbvenueid INT NOT NULL,
    tbvenueimagepath VARCHAR(255) NOT NULL,
    tbvenueimagesort INT NOT NULL DEFAULT 0,
    tbvenueimageactive TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;