<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/PaymentMethod.php';

class PaymentMethodRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(PaymentMethod $paymentMethod): int
  {
    $sql = "
            INSERT INTO tbpaymentmethod (
                tbpaymentmethodname,
                tbpaymentmethodisactive
            )
            VALUES (
                :paymentMethod,
                :isActive
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':paymentMethod' => $paymentMethod->getPaymentMethod(),
      ':isActive'      => $paymentMethod->getIsActive()
    ]);

    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // OBTENER ACTIVOS
  // =========================================================
  public function findActive(): array
  {
    $sql = "
            SELECT
                tbpaymentmethodid,
                tbpaymentmethodname,
                tbpaymentmethodisactive

            FROM tbpaymentmethod

            WHERE tbpaymentmethodisactive = true

            ORDER BY tbpaymentmethodname ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // BUSCAR POR ID
  // =========================================================
  public function findById(int $idPaymentMethod): ?PaymentMethod
  {
    $sql = "
            SELECT
                tbpaymentmethodid,
                tbpaymentmethodname,
                tbpaymentmethodisactive

            FROM tbpaymentmethod

            WHERE tbpaymentmethodid = :idPaymentMethod
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idPaymentMethod' => $idPaymentMethod
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): PaymentMethod
  {
    return new PaymentMethod(
      idPaymentMethod: (int) $row['tbpaymentmethodid'],
      paymentMethod: $row['tbpaymentmethodname'],
      isActive: (bool) $row['tbpaymentmethodisactive']
    );
  }
}
