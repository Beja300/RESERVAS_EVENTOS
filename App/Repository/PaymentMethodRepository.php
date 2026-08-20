<?php

require_once __DIR__ . '/../models/PaymentMethod.php';

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
  public function savePaymentMethod(PaymentMethod $paymentMethod): bool
  {
    try {
      $sql = "
                INSERT INTO tbpaymentmethod (
                    tbpaymentmethodid,
                    tbpaymentmethodname,
                    tbpaymentmethodisactive
                )
                VALUES (
                    :id
                    :name,
                    :isActive
                )
            ";

      $stmt = $this->connection->prepare($sql);

      $stmt->execute([
        ':id' => $paymentMethod->getIdPaymentMethod(),
        ':name' => $paymentMethod->getPaymentMethod(),
        ':isActive' => $paymentMethod->getIsActive()
      ]);

      $paymentMethod->setIdPaymentMethod((int) $this->connection->lastInsertId());

      return true;
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // OBTENER TODOS
  // =========================================================
  public function getAllPaymentMethod(): array
  {
    $sql = "
            SELECT
                tbpaymentmethodid,
                tbpaymentmethodname,
                tbpaymentmethodisactive

            FROM tbpaymentmethod

            ORDER BY tbpaymentmethodname ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $paymentMethods = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $paymentMethods[] = $this->mapRowToPaymentMethod($row);
    }

    return $paymentMethods;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdPaymentMethod(int $idPaymentMethod): ?PaymentMethod
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

    if (!$row) {
      return null;
    }

    return $this->mapRowToPaymentMethod($row);
  }


  // =========================================================
  // OBTENER SOLO ACTIVOS (para selects/formularios)
  // =========================================================
  public function getActivePaymentMethod(): array
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

    $paymentMethods = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $paymentMethods[] = $this->mapRowToPaymentMethod($row);
    }

    return $paymentMethods;
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updatePaymentMethod(PaymentMethod $paymentMethod): bool
  {
    try {
      $sql = "
                UPDATE tbpaymentmethod
                SET
                    tbpaymentmethodname = :name,
                    tbpaymentmethodisactive = :isActive
                WHERE tbpaymentmethodid = :idPaymentMethod
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':name' => $paymentMethod->getPaymentMethod(),
        ':isActive' => $paymentMethod->getIsActive(),
        ':idPaymentMethod' => $paymentMethod->getIdPaymentMethod()
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // DESACTIVAR
  // =========================================================
  public function deactivatePaymentMethod(int $idPaymentMethod): bool
  {
    $sql = "
            UPDATE tbpaymentmethod
            SET tbpaymentmethodisactive = false
            WHERE tbpaymentmethodid = :idPaymentMethod
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idPaymentMethod' => $idPaymentMethod
    ]);
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function deletePaymentMethod(int $idPaymentMethod): bool
  {
    try {
      $sql = "
                DELETE FROM tbpaymentmethod
                WHERE tbpaymentmethodid = :idPaymentMethod
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idPaymentMethod' => $idPaymentMethod
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRowToPaymentMethod(array $row): PaymentMethod
  {
    return new PaymentMethod(
      (int) $row['tbpaymentmethodid'],
      $row['tbpaymentmethodname'],
      (bool) $row['tbpaymentmethodisactive']
    );
  }
}
