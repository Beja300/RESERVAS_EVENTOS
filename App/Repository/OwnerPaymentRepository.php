<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';

class OwnerPaymentRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // BUSCAR POR OWNER Y MÉTODO DE PAGO (ambos activos)
  // =========================================================
  public function findByOwnerAndMethod(int $idOwner, int $idPaymentMethod): ?array
  {
    $sql = "
            SELECT
                op.tbownerpaymentid,
                op.tbownerpaymentownerid,
                op.tbownerpaymentpaymentmethodid,
                op.tbownerpaymentholder,
                op.tbownerpaymentaccount,
                op.tbownerpaymentinstructions,
                pm.tbpaymentmethodname

            FROM tbownerpayment op

            INNER JOIN tbpaymentmethod pm
                ON pm.tbpaymentmethodid = op.tbownerpaymentpaymentmethodid

            WHERE op.tbownerpaymentownerid = :idOwner
              AND op.tbownerpaymentpaymentmethodid = :idPaymentMethod
              AND op.tbownerpaymentisactive = true
              AND pm.tbpaymentmethodisactive = true

            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner'         => $idOwner,
      ':idPaymentMethod' => $idPaymentMethod
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
  }
}
