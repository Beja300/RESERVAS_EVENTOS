<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/CreditPayment.php';

class CreditPaymentRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR ABONO
  // =========================================================
  public function save(CreditPayment $payment): int
  {
    $sql = "
      INSERT INTO tbcreditpayment (
        tbcreditid,
        tbcreditpaymentamount,
        tbcreditpaymentnote,
        tbroleid,
        tbcreditpaymentactive
      )
      VALUES (
        :idCredit,
        :amount,
        :note,
        :idRole,
        :isActive
      )
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idCredit' => $payment->getIdCredit(),
      ':amount'   => $payment->getAmount(),
      ':note'     => $payment->getNote(),
      ':idRole'   => $payment->getIdRole(),
      ':isActive' => $this->toDb($payment->getIsActive()),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // ABONOS DE UN CRÉDITO
  // =========================================================
  public function findByCredit(int $idCredit): array
  {
    $sql = "
      SELECT
        p.tbcreditpaymentid,
        p.tbcreditid,
        p.tbcreditpaymentamount,
        p.tbcreditpaymentnote,
        p.tbroleid,
        p.tbcreditpaymentdate,
        r.tbrolename
      FROM tbcreditpayment p
      LEFT JOIN tbrole r ON r.tbroleid = p.tbroleid
      WHERE p.tbcreditid = :idCredit
        AND p.tbcreditpaymentactive = true
      ORDER BY p.tbcreditpaymentid ASC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idCredit' => $idCredit]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function toDb(bool $value): int
  {
    return $value ? 1 : 0;
  }
}
