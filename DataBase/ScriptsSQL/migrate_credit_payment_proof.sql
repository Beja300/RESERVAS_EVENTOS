-- Comprobante asociado a cada abono de crédito.
ALTER TABLE tbcreditpayment
  ADD COLUMN tbcreditpaymentproof VARCHAR(255) NULL;
