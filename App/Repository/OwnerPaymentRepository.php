<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/OwnerPayment.php';
require_once __DIR__ . '/../Model/PaymentMethod.php';

class OwnerPaymentRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR (inserta un nuevo método de cobro del owner)
  // =========================================================
  public function save(OwnerPayment $op): int
  {
    $sql = "
      INSERT INTO tbownerpayment (
        tbownerpaymentownerid,
        tbownerpaymentpaymentmethodid,
        tbownerpaymentholder,
        tbownerpaymentaccount,
        tbownerpaymentinstructions,
        tbownerpaymentactive
      )
      VALUES (
        :idOwner,
        :idPaymentMethod,
        :holder,
        :account,
        :instructions,
        :isActive
      )
    ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner'         => $op->getIdOwner(),
      ':idPaymentMethod' => $op->getIdPaymentMethod(),
      ':holder'          => $op->getHolder() !== '' ? $op->getHolder() : null,
      ':account'         => $op->getAccount() !== '' ? $op->getAccount() : null,
      ':instructions'    => $op->getInstructions() !== '' ? $op->getInstructions() : null,
      ':isActive'        => $op->getIsActive() ? 1 : 0,
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // ACTUALIZAR
  // =========================================================
  public function update(OwnerPayment $op): bool
  {
    $sql = "
      UPDATE tbownerpayment
      SET
        tbownerpaymentholder = :holder,
        tbownerpaymentaccount = :account,
        tbownerpaymentinstructions = :instructions,
        tbownerpaymentactive = :isActive
      WHERE tbownerpaymentid = :idOwnerPayment
    ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':holder'          => $op->getHolder() !== '' ? $op->getHolder() : null,
      ':account'         => $op->getAccount() !== '' ? $op->getAccount() : null,
      ':instructions'    => $op->getInstructions() !== '' ? $op->getInstructions() : null,
      ':isActive'        => $op->getIsActive() ? 1 : 0,
      ':idOwnerPayment'  => $op->getIdOwnerPayment(),
    ]);
  }

  // =========================================================
  // BUSCAR POR ID
  // =========================================================
  public function findById(int $idOwnerPayment): ?OwnerPayment
  {
    $sql = "
      SELECT
        op.tbownerpaymentid,
        op.tbownerpaymentownerid,
        op.tbownerpaymentpaymentmethodid,
        pm.tbpaymentmethodtype,
        op.tbownerpaymentholder,
        op.tbownerpaymentaccount,
        op.tbownerpaymentinstructions,
        op.tbownerpaymentactive
      FROM tbownerpayment op
      INNER JOIN tbpaymentmethod pm
        ON pm.tbpaymentmethodid = op.tbownerpaymentpaymentmethodid
      WHERE op.tbownerpaymentid = :idOwnerPayment
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idOwnerPayment' => $idOwnerPayment]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // BUSCAR POR OWNER (todos sus métodos de cobro configurados)
  // =========================================================
  public function findByOwner(int $idOwner): array
  {
    $sql = "
      SELECT
        op.tbownerpaymentid,
        op.tbownerpaymentownerid,
        op.tbownerpaymentpaymentmethodid,
        pm.tbpaymentmethodtype,
        op.tbownerpaymentholder,
        op.tbownerpaymentaccount,
        op.tbownerpaymentinstructions,
        op.tbownerpaymentactive
      FROM tbownerpayment op
      INNER JOIN tbpaymentmethod pm
        ON pm.tbpaymentmethodid = op.tbownerpaymentpaymentmethodid
      WHERE op.tbownerpaymentownerid = :idOwner
      ORDER BY pm.tbpaymentmethodtype ASC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idOwner' => $idOwner]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // BUSCAR POR OWNER Y MÉTODO DE PAGO (ambos activos)
  // =========================================================
  public function findByOwnerAndMethod(int $idOwner, int $idPaymentMethod): ?array
  {
    $rows = $this->findByOwner($idOwner);

    foreach ($rows as $op) {
      if ($op->getIdPaymentMethod() === $idPaymentMethod && $op->getIsActive()) {
        return [
          'tbownerpaymentid'            => $op->getIdOwnerPayment(),
          'tbownerpaymentholder'        => $op->getHolder(),
          'tbownerpaymentaccount'       => $op->getAccount(),
          'tbownerpaymentinstructions'  => $op->getInstructions(),
          'tbpaymentmethodtype'         => $op->getPaymentMethod(),
        ];
      }
    }

    return null;
  }

  // =========================================================
  // ELIMINAR (borrado físico del método de cobro del owner)
  // =========================================================
  public function delete(int $idOwnerPayment): bool
  {
    $sql = "DELETE FROM tbownerpayment WHERE tbownerpaymentid = :idOwnerPayment";
    $stmt = $this->connection->prepare($sql);
    return $stmt->execute([':idOwnerPayment' => $idOwnerPayment]);
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): OwnerPayment
  {
    return new OwnerPayment(
      idOwnerPayment: (int) $row['tbownerpaymentid'],
      idOwner: (int) $row['tbownerpaymentownerid'],
      idPaymentMethod: (int) $row['tbownerpaymentpaymentmethodid'],
      paymentMethod: $row['tbpaymentmethodtype'] ?? '',
      holder: $row['tbownerpaymentholder'] ?? '',
      account: $row['tbownerpaymentaccount'] ?? '',
      instructions: $row['tbownerpaymentinstructions'] ?? '',
      isActive: $this->toBool($row['tbownerpaymentactive'])
    );
  }

  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }
}
