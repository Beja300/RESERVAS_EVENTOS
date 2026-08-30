<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Earning.php';

class EarningRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(Earning $earning): int
  {
    $sql = "
      INSERT INTO tbeearning (
        tbeearningbookingid,
        tbeearningtotal,
        tbeearningcommission,
        tbeearningtax,
        tbeearningowneramount,
        tbeearningreviewedbyrole
      )
      VALUES (
        :idBooking,
        :total,
        :commission,
        :tax,
        :ownerAmount,
        :reviewedByRole
      )
    ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idBooking'      => $earning->getIdBooking(),
      ':total'          => $earning->getTotal(),
      ':commission'     => $earning->getCommission(),
      ':tax'            => $earning->getTax(),
      ':ownerAmount'    => $earning->getOwnerAmount(),
      ':reviewedByRole' => $earning->getReviewedByRole(),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // BUSCAR POR RESERVA
  // =========================================================
  public function findByBooking(int $idBooking): ?Earning
  {
    $sql = "
      SELECT
        tbeearningid,
        tbeearningbookingid,
        tbeearningtotal,
        tbeearningcommission,
        tbeearningtax,
        tbeearningowneramount,
        tbeearningreviewedbyrole,
        tbeearningdate
      FROM tbeearning
      WHERE tbeearningbookingid = :idBooking
        AND tbeearningactive = true
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idBooking' => $idBooking]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // DESACTIVAR LA GANANCIA DE UNA RESERVA (reembolso)
  // =========================================================
  public function deactivateByBooking(int $idBooking): bool
  {
    $sql = "
      UPDATE tbeearning
      SET tbeearningactive = false
      WHERE tbeearningbookingid = :idBooking
        AND tbeearningactive = true
    ";

    $stmt = $this->connection->prepare($sql);
    return $stmt->execute([':idBooking' => $idBooking]);
  }

  // =========================================================
  // OBTENER TODOS
  // =========================================================
  public function findAll(): array
  {
    $sql = "
      SELECT
        tbeearningid,
        tbeearningbookingid,
        tbeearningtotal,
        tbeearningcommission,
        tbeearningtax,
        tbeearningowneramount,
        tbeearningreviewedbyrole,
        tbeearningdate
      FROM tbeearning
      WHERE tbeearningactive = true
      ORDER BY tbeearningid ASC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // RESUMEN ECONÓMICO DE UN MES (YYYY-MM)
  // Suma directamente de las ganancias registradas (por la fecha
  // en que se aprobó el pago), sin depender del estado de reservas.
  // =========================================================
  public function summarizeByMonth(string $yearMonth): array
  {
    $sql = "
      SELECT
        COALESCE(SUM(tbeearningtotal), 0)       AS ingreso_bruto,
        COALESCE(SUM(tbeearningcommission), 0)  AS comision,
        COALESCE(SUM(tbeearningtax), 0)         AS iva,
        COALESCE(SUM(tbeearningowneramount), 0) AS propietarios
      FROM tbeearning
      WHERE tbeearningdate LIKE :yearMonth
        AND tbeearningactive = true
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':yearMonth' => $yearMonth . '%']);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
      'ingreso_bruto' => (float) $row['ingreso_bruto'],
      'comision'      => (float) $row['comision'],
      'iva'           => (float) $row['iva'],
      'propietarios'  => (float) $row['propietarios'],
    ];
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Earning
  {
    return new Earning(
      idEarning: (int) $row['tbeearningid'],
      idBooking: (int) $row['tbeearningbookingid'],
      total: (float) $row['tbeearningtotal'],
      commission: (float) $row['tbeearningcommission'],
      tax: (float) $row['tbeearningtax'],
      ownerAmount: (float) $row['tbeearningowneramount'],
      reviewedByRole: $row['tbeearningreviewedbyrole'] !== null ? (int) $row['tbeearningreviewedbyrole'] : null,
      earningDate: $row['tbeearningdate']
    );
  }
}
