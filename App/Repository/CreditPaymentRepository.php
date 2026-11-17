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
        tbcreditpaymentproof,
        tbroleid,
        tbcreditpaymentactive,
        tbcreditpaymentstate
      )
      VALUES (
        :idCredit,
        :amount,
        :note,
        :proof,
        :idRole,
        :isActive,
        'pendiente'
      )
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idCredit' => $payment->getIdCredit(),
      ':amount'   => $payment->getAmount(),
      ':note'     => $payment->getNote(),
      ':proof'    => $payment->getProofFile(),
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
        p.tbcreditpaymentproof,
        p.tbroleid,
        p.tbcreditpaymentdate,
        p.tbcreditpaymentstate,
        p.tbcreditpaymentreviewedat,
        p.tbcreditpaymentreviewedby,
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

  public function findById(int $idPayment): ?array
  {
    $stmt = $this->connection->prepare(
      'SELECT * FROM tbcreditpayment WHERE tbcreditpaymentid = :id LIMIT 1'
    );
    $stmt->execute([':id' => $idPayment]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function updateState(int $idPayment, string $state, int $reviewedBy): bool
  {
    if (!in_array($state, ['aprobado', 'rechazado'], true)) {
      return false;
    }
    $stmt = $this->connection->prepare(
      "UPDATE tbcreditpayment SET tbcreditpaymentstate = :state,
       tbcreditpaymentreviewedat = NOW(), tbcreditpaymentreviewedby = :reviewer
       WHERE tbcreditpaymentid = :id AND tbcreditpaymentstate = 'pendiente'"
    );
    $stmt->execute([':state' => $state, ':reviewer' => $reviewedBy, ':id' => $idPayment]);
    return $stmt->rowCount() === 1;
  }

  public function hasPendingByCredit(int $idCredit): bool
  {
    $stmt = $this->connection->prepare(
      "SELECT 1 FROM tbcreditpayment WHERE tbcreditid = :id AND tbcreditpaymentactive = true
       AND tbcreditpaymentstate = 'pendiente' LIMIT 1"
    );
    $stmt->execute([':id' => $idCredit]);
    return (bool) $stmt->fetchColumn();
  }

  private function toDb(bool $value): int
  {
    return $value ? 1 : 0;
  }
}
