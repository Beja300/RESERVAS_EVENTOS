<?php

require_once __DIR__ . '/../Model/BlockedClient.php';

class BlockedClientRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function save(BlockedClient $blocked): int
  {
    $stmt = $this->connection->prepare(
      'INSERT INTO tbownerblockedclient
       (tbownerid, tbclientid, tbownerblockedclientreason, tbownerblockedclientactive)
       VALUES (:ownerId, :clientId, :reason, :active)'
    );
    $stmt->execute([
      ':ownerId' => $blocked->getIdOwner(),
      ':clientId' => $blocked->getIdClient(),
      ':reason' => $blocked->getReason(),
      ':active' => $blocked->getIsActive() ? 1 : 0,
    ]);

    return (int) $this->connection->lastInsertId();
  }

  public function findActiveByOwner(int $idOwner): array
  {
    $stmt = $this->connection->prepare(
      'SELECT bc.*, r.tbrolename AS clientname, r.tbroleemail AS clientemail
       FROM tbownerblockedclient bc
       INNER JOIN tbroleclient rc ON rc.tbclientid = bc.tbclientid AND rc.tbroleclientactive = true
       INNER JOIN tbrole r ON r.tbroleid = rc.tbroleid
       WHERE bc.tbownerid = :ownerId AND bc.tbownerblockedclientactive = true
       ORDER BY bc.tbownerblockedclientdate DESC'
    );
    $stmt->execute([':ownerId' => $idOwner]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function isBlocked(int $idOwner, int $idClient): bool
  {
    $stmt = $this->connection->prepare(
      'SELECT COUNT(*) FROM tbownerblockedclient
       WHERE tbownerid = :ownerId AND tbclientid = :clientId
         AND tbownerblockedclientactive = true'
    );
    $stmt->execute([':ownerId' => $idOwner, ':clientId' => $idClient]);

    return (int) $stmt->fetchColumn() > 0;
  }

  public function deactivate(int $idBlock, int $idOwner): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbownerblockedclient SET tbownerblockedclientactive = false
       WHERE tbownerblockedclientid = :id AND tbownerid = :ownerId'
    );

    $stmt->execute([':id' => $idBlock, ':ownerId' => $idOwner]);

    return $stmt->rowCount() > 0;
  }

  public function overdueClientsByOwner(int $idOwner): array
  {
    $stmt = $this->connection->prepare(
      'SELECT cl.tbclientid, r.tbrolename AS clientname, r.tbroleemail AS clientemail,
              COUNT(*) AS overduecount, COALESCE(SUM(i.tbcreditinstallmentamount), 0) AS overdueamount
       FROM tbcreditinstallment i
       INNER JOIN tbcredit c ON c.tbcreditid = i.tbcreditid
       INNER JOIN tbclient cl ON cl.tbclientid = c.tbclientid
       INNER JOIN tbroleclient rc ON rc.tbclientid = cl.tbclientid AND rc.tbroleclientactive = true
       INNER JOIN tbrole r ON r.tbroleid = rc.tbroleid
       WHERE c.tbownerid = :ownerId
         AND c.tbcreditactive = true
         AND c.tbcreditstate <> "pagado"
         AND i.tbcreditinstallmentactive = true
         AND i.tbcreditinstallmentstate <> "pagado"
         AND i.tbcreditinstallmentduedate < :today
       GROUP BY cl.tbclientid, r.tbrolename, r.tbroleemail
       ORDER BY overdueamount DESC'
    );
    $stmt->execute([':ownerId' => $idOwner, ':today' => date('Y-m-d')]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function findClientNames(int $idOwner): array
  {
    $stmt = $this->connection->prepare(
      'SELECT r.tbrolename AS name, r.tbroleemail AS email, c.tbclientid
       FROM tbownerblockedclient bc
       INNER JOIN tbclient c ON c.tbclientid = bc.tbclientid
       INNER JOIN tbroleclient rc ON rc.tbclientid = c.tbclientid AND rc.tbroleclientactive = true
       INNER JOIN tbrole r ON r.tbroleid = rc.tbroleid
       WHERE bc.tbownerid = :ownerId AND bc.tbownerblockedclientactive = true
       ORDER BY r.tbrolename ASC'
    );
    $stmt->execute([':ownerId' => $idOwner]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
