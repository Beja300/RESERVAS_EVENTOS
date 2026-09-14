-- =========================================================
-- MIGRACIÓN — Sistema de recomendación híbrido (base inicial)
-- ---------------------------------------------------------
-- 1) Agrega latitud/longitud a tblocation para el cálculo de
--    distancia geográfica con la fórmula de Haversine.
-- 2) La tabla tbuserhistory ya almacena las interacciones de los
--    usuarios (búsquedas, vistas, favoritos, reservas, compras,
--    cancelaciones y calificaciones); es la materia prima para el
--    futuro modelo de Machine Learning.
-- =========================================================

ALTER TABLE tblocation
    ADD COLUMN tblocationlatitude  DECIMAL(10,7) NULL,
    ADD COLUMN tblocationlongitude DECIMAL(10,7) NULL;