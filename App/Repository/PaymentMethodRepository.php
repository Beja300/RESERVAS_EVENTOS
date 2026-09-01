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
                tbpaymentmethodtype,
                tbpaymentmethodactive
            )
            VALUES (
                :paymentMethod,
                :isActive
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':paymentMethod' => $paymentMethod->getPaymentMethod(),
      ':isActive'      => (int) $paymentMethod->getIsActive()
    ]);

    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // OBTENER TODOS (admin: muestra activos e inactivos)
  // =========================================================
  public function findAll(): array
  {
    $sql = "
            SELECT
                tbpaymentmethodid,
                tbpaymentmethodtype,
                tbpaymentmethodactive

            FROM tbpaymentmethod

            ORDER BY tbpaymentmethodactive DESC, tbpaymentmethodtype ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // OBTENER ACTIVOS
  // =========================================================
  public function findActive(): array
  {
    $sql = "
            SELECT
                tbpaymentmethodid,
                tbpaymentmethodtype,
                tbpaymentmethodactive

            FROM tbpaymentmethod

            WHERE tbpaymentmethodactive = true

            ORDER BY tbpaymentmethodtype ASC
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
                tbpaymentmethodtype,
                tbpaymentmethodactive

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
  // ACTUALIZAR
  // =========================================================
  public function update(PaymentMethod $paymentMethod): void
  {
    $sql = "
            UPDATE tbpaymentmethod
            SET    tbpaymentmethodtype   = :type,
                   tbpaymentmethodactive = :isActive
            WHERE  tbpaymentmethodid = :idPaymentMethod
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':type'            => $paymentMethod->getPaymentMethod(),
      ':isActive'        => (int) $paymentMethod->getIsActive(),
      ':idPaymentMethod' => $paymentMethod->getIdPaymentMethod()
    ]);
  }

  // =========================================================
  // DESACTIVAR (soft delete: se oculta de los catálogos activos)
  // =========================================================
  public function deactivate(int $idPaymentMethod): void
  {
    $sql = "
            UPDATE tbpaymentmethod
            SET    tbpaymentmethodactive = 0
            WHERE  tbpaymentmethodid = :idPaymentMethod
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idPaymentMethod' => $idPaymentMethod
    ]);
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): PaymentMethod
  {
    return new PaymentMethod(
      idPaymentMethod: (int) $row['tbpaymentmethodid'],
      paymentMethod: $row['tbpaymentmethodtype'],
      isActive: $this->toBool($row['tbpaymentmethodactive'])
    );
  }

  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }
}
