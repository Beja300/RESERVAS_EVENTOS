<?php

require_once __DIR__ . '/../Model/CreditInstallment.php';

class CreditInstallmentRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function replaceForCredit(int $idCredit, array $installments): void
  {
    $del = $this->connection->prepare('DELETE FROM tbcreditinstallment WHERE tbcreditid = :creditId');
    $del->execute([':creditId' => $idCredit]);

    $stmt = $this->connection->prepare(
      'INSERT INTO tbcreditinstallment
       (tbcreditid, tbcreditinstallmentnumber, tbcreditinstallmentamount,
        tbcreditinstallmentduedate, tbcreditinstallmentstate, tbcreditinstallmentactive)
       VALUES
       (:creditId, :number, :amount, :dueDate, :state, 1)'
    );

    foreach ($installments as $installment) {
      if (!$installment instanceof CreditInstallment) {
        continue;
      }
      $stmt->execute([
        ':creditId' => $idCredit,
        ':number' => $installment->getNumber(),
        ':amount' => $installment->getAmount(),
        ':dueDate' => $installment->getDueDate(),
        ':state' => $installment->getState(),
      ]);
    }
  }

  public function findByCredit(int $idCredit): array
  {
    $stmt = $this->connection->prepare(
      'SELECT * FROM tbcreditinstallment
       WHERE tbcreditid = :creditId AND tbcreditinstallmentactive = true
       ORDER BY tbcreditinstallmentnumber ASC'
    );
    $stmt->execute([':creditId' => $idCredit]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function totalPaid(int $idCredit): float
  {
    $stmt = $this->connection->prepare(
      'SELECT COALESCE(SUM(tbcreditpaymentamount), 0) FROM tbcreditpayment
       WHERE tbcreditid = :creditId AND tbcreditpaymentactive = true
         AND tbcreditpaymentstate = \'aprobado\''
    );
    $stmt->execute([':creditId' => $idCredit]);

    return (float) $stmt->fetchColumn();
  }

  public function syncPaidStates(int $idCredit, float $paidTotal, string $paidDate): void
  {
    $installments = $this->findByCredit($idCredit);
    $accumulated = 0.0;

    $stmt = $this->connection->prepare(
      'UPDATE tbcreditinstallment
       SET tbcreditinstallmentstate = :state, tbcreditinstallmentpaiddate = :paidDate
       WHERE tbcreditinstallmentid = :id'
    );

    foreach ($installments as $installment) {
      $accumulated = round($accumulated + $installment->getAmount(), 2);
      $isPaid = $paidTotal + 0.001 >= $accumulated;
      $state = $isPaid ? 'pagado' : 'pendiente';

      $stmt->execute([
        ':state' => $state,
        ':paidDate' => $isPaid ? $paidDate : null,
        ':id' => $installment->getIdInstallment(),
      ]);
    }
  }

  public function overdueCountByOwner(int $idOwner): int
  {
    $stmt = $this->connection->prepare(
      'SELECT COUNT(*) FROM tbcreditinstallment i
       INNER JOIN tbcredit c ON c.tbcreditid = i.tbcreditid
       WHERE c.tbownerid = :ownerId AND c.tbcreditactive = true
         AND c.tbcreditstate <> "pagado"
         AND i.tbcreditinstallmentstate <> "pagado"
         AND i.tbcreditinstallmentduedate < :today'
    );
    $stmt->execute([':ownerId' => $idOwner, ':today' => date('Y-m-d')]);

    return (int) $stmt->fetchColumn();
  }

  public function overdueCountByClient(int $idClient, ?int $idOwner = null): int
  {
    $sql = 'SELECT COUNT(*) FROM tbcreditinstallment i
       INNER JOIN tbcredit c ON c.tbcreditid = i.tbcreditid
       WHERE c.tbclientid = :clientId AND c.tbcreditactive = true
         AND c.tbcreditstate <> "pagado"
         AND i.tbcreditinstallmentstate <> "pagado"
         AND i.tbcreditinstallmentduedate < :today';

    $params = [':clientId' => $idClient, ':today' => date('Y-m-d')];

    if ($idOwner !== null && $idOwner > 0) {
      $sql .= ' AND c.tbownerid = :ownerId';
      $params[':ownerId'] = $idOwner;
    }

    $stmt = $this->connection->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
  }

  private function mapRow(array $row): CreditInstallment
  {
    return new CreditInstallment(
      idInstallment: (int) $row['tbcreditinstallmentid'],
      idCredit: (int) $row['tbcreditid'],
      number: (int) $row['tbcreditinstallmentnumber'],
      amount: (float) $row['tbcreditinstallmentamount'],
      dueDate: (string) $row['tbcreditinstallmentduedate'],
      paidDate: $row['tbcreditinstallmentpaiddate'] !== null ? (string) $row['tbcreditinstallmentpaiddate'] : null,
      state: (string) $row['tbcreditinstallmentstate'],
      isActive: $this->toBool($row['tbcreditinstallmentactive'])
    );
  }

  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }
}
