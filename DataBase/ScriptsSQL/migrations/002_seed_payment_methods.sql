-- =========================================================
-- 002_seed_payment_methods.sql
-- Garantiza que el catálogo base de métodos de pago exista sin
-- duplicar filas si la migración se ejecuta más de una vez.
-- =========================================================

INSERT INTO tbpaymentmethod (tbpaymentmethodtype, tbpaymentmethodactive)
SELECT 'Efectivo', TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM tbpaymentmethod WHERE LOWER(tbpaymentmethodtype) = LOWER('Efectivo')
);

INSERT INTO tbpaymentmethod (tbpaymentmethodtype, tbpaymentmethodactive)
SELECT 'Transferencia Bancaria', TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM tbpaymentmethod WHERE LOWER(tbpaymentmethodtype) = LOWER('Transferencia Bancaria')
);

INSERT INTO tbpaymentmethod (tbpaymentmethodtype, tbpaymentmethodactive)
SELECT 'SINPE Móvil', TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM tbpaymentmethod WHERE LOWER(tbpaymentmethodtype) = LOWER('SINPE Móvil')
);
