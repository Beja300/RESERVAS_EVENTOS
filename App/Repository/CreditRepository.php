<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Credit.php';

class CreditRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(Credit $credit): int
  {
    $sql = "
      INSERT INTO tbcredit (
        tbbookingid,
        tbclientid,
        tbownerid,
        tbcredittotal,
        tbcreditbalance,
        tbcreditduedate,
        tbcreditstate,
        tbcreditactive
      )
      VALUES (
        :idBooking,
        :idClient,
        :idOwner,
        :total,
        :balance,
        :dueDate,
        :state,
        :isActive
      )
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idBooking' => $credit->getIdBooking(),
      ':idClient'  => $credit->getIdClient(),
      ':idOwner'   => $credit->getIdOwner(),
      ':total'     => $credit->getTotal(),
      ':balance'   => $credit->getBalance(),
      ':dueDate'   => $credit->getDueDate(),
      ':state'     => $credit->getState(),
      ':isActive'  => $this->toDb($credit->getIsActive()),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // BUSCAR POR PK
  // =========================================================
  public function findById(int $idCredit): ?Credit
  {
    $sql = "
      SELECT
        tbcreditid,
        tbbookingid,
        tbclientid,
        tbownerid,
        tbcredittotal,
        tbcreditbalance,
        tbcreditduedate,
        tbcreditstate,
        tbcreditdate,
        tbcreditactive
      FROM tbcredit
      WHERE tbcreditid = :idCredit
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idCredit' => $idCredit]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // BUSCAR POR RESERVA (1:1)
  // =========================================================
  public function findByBooking(int $idBooking): ?Credit
  {
    $sql = "
      SELECT
        tbcreditid,
        tbbookingid,
        tbclientid,
        tbownerid,
        tbcredittotal,
        tbcreditbalance,
        tbcreditduedate,
        tbcreditstate,
        tbcreditdate,
        tbcreditactive
      FROM tbcredit
      WHERE tbbookingid = :idBooking
        AND tbcreditactive = true
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idBooking' => $idBooking]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // CUENTAS POR COBRAR DE UN CLIENTE (con datos para mostrar)
  // =========================================================
  public function findByClient(int $idClient): array
  {
    $sql = "
      SELECT
        c.tbcreditid,
        c.tbbookingid,
        c.tbcredittotal,
        c.tbcreditbalance,
        c.tbcreditduedate,
        c.tbcreditstate,
        c.tbcreditdate,
        v.tbvenuename
      FROM tbcredit c
      INNER JOIN tbbooking b ON b.tbbookingid = c.tbbookingid
      INNER JOIN tbvenue v ON v.tbvenueid = b.tbvenueid
      WHERE c.tbclientid = :idClient
        AND c.tbcreditactive = true
      ORDER BY c.tbcreditid DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idClient' => $idClient]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // =========================================================
  // CUENTAS POR PAGAR (créditos) DE UN PROPIETARIO
  // =========================================================
  public function findByOwner(int $idOwner): array
  {
    $sql = "
      SELECT
        c.tbcreditid,
        c.tbbookingid,
        c.tbcredittotal,
        c.tbcreditbalance,
        c.tbcreditduedate,
        c.tbcreditstate,
        c.tbcreditdate,
        v.tbvenuename,
        r.tbrolename AS clientname
      FROM tbcredit c
      INNER JOIN tbbooking b ON b.tbbookingid = c.tbbookingid
      INNER JOIN tbvenue v ON v.tbvenueid = b.tbvenueid
      INNER JOIN tbroleclient rc ON rc.tbclientid = c.tbclientid
      INNER JOIN tbrole r ON r.tbroleid = rc.tbroleid
      WHERE c.tbownerid = :idOwner
        AND c.tbcreditactive = true
      ORDER BY c.tbcreditid DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idOwner' => $idOwner]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // =========================================================
  // TODAS LAS CUENTAS (panel del Admin)
  // =========================================================
  public function findAll(): array
  {
    $sql = "
      SELECT
        c.tbcreditid,
        c.tbbookingid,
        c.tbcredittotal,
        c.tbcreditbalance,
        c.tbcreditduedate,
        c.tbcreditstate,
        c.tbcreditdate,
        v.tbvenuename,
        r.tbrolename AS clientname
      FROM tbcredit c
      INNER JOIN tbbooking b ON b.tbbookingid = c.tbbookingid
      INNER JOIN tbvenue v ON v.tbvenueid = b.tbvenueid
      INNER JOIN tbroleclient rc ON rc.tbclientid = c.tbclientid
      INNER JOIN tbrole r ON r.tbroleid = rc.tbroleid
      WHERE c.tbcreditactive = true
      ORDER BY c.tbcreditid DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  // =========================================================
  // ACTUALIZAR SALDO / ESTADO
  // =========================================================
  public function updateBalanceAndState(int $idCredit, float $balance, string $state): bool
  {
    $sql = "
      UPDATE tbcredit
      SET tbcreditbalance = :balance,
          tbcreditstate = :state
      WHERE tbcreditid = :idCredit
    ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':balance'  => $balance,
      ':state'    => $state,
      ':idCredit' => $idCredit,
    ]);
  }

  // =========================================================
  // SALDO PENDIENTE TOTAL DE UN PROPIETARIO
  // =========================================================
  public function sumOutstandingByOwner(int $idOwner): float
  {
    $sql = "
      SELECT COALESCE(SUM(tbcreditbalance), 0) AS total
      FROM tbcredit
      WHERE tbownerid = :idOwner
        AND tbcreditactive = true
        AND tbcreditstate <> 'pagado'
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idOwner' => $idOwner]);

    return (float) $stmt->fetchColumn();
  }

  // =========================================================
  // SALDO PENDIENTE TOTAL DE UN CLIENTE
  // =========================================================
  public function sumOutstandingByClient(int $idClient): float
  {
    $sql = "
      SELECT COALESCE(SUM(tbcreditbalance), 0) AS total
      FROM tbcredit
      WHERE tbclientid = :idClient
        AND tbcreditactive = true
        AND tbcreditstate <> 'pagado'
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idClient' => $idClient]);

    return (float) $stmt->fetchColumn();
  }

  private function mapRow(array $row): Credit
  {
    return new Credit(
      idCredit: (int) $row['tbcreditid'],
      idBooking: (int) $row['tbbookingid'],
      idClient: (int) $row['tbclientid'],
      idOwner: (int) $row['tbownerid'],
      total: (float) $row['tbcredittotal'],
      balance: (float) $row['tbcreditbalance'],
      dueDate: (string) $row['tbcreditduedate'],
      state: (string) $row['tbcreditstate'],
      creditDate: (string) ($row['tbcreditdate'] ?? ''),
      isActive: $this->toBool($row['tbcreditactive'])
    );
  }

  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }

  private function toDb(bool $value): int
  {
    return $value ? 1 : 0;
  }
}
