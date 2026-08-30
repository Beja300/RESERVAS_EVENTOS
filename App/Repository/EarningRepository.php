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
  // SUMAS DEL MES PARA UN OWNER (ganancias, comisión, IVA, total)
  // Filtra por 'YYYY-MM' usando la fecha en que se registró la
  // ganancia (cuando el comprobante fue aprobado).
  // =========================================================
  public function totalsByOwnerForMonth(int $idOwner, string $yearMonth): array
  {
    $sql = "
      SELECT
        COALESCE(SUM(e.tbeearningtotal), 0)          AS total,
        COALESCE(SUM(e.tbeearningcommission), 0)     AS commission,
        COALESCE(SUM(e.tbeearningtax), 0)            AS tax,
        COALESCE(SUM(e.tbeearningowneramount), 0)    AS ownerAmount
      FROM tbeearning e
      INNER JOIN tbbooking b
        ON b.tbbookingid = e.tbeearningbookingid
      INNER JOIN tbvenue v
        ON v.tbvenueid = b.tbbookinglocalid
      WHERE v.tbvenueownerid = :idOwner
        AND e.tbeearningactive = true
        AND e.tbeearningdate LIKE :yearMonth
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idOwner'   => $idOwner,
      ':yearMonth' => $yearMonth . '%'
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
      'total'       => (float) ($row['total'] ?? 0),
      'commission'  => (float) ($row['commission'] ?? 0),
      'tax'         => (float) ($row['tax'] ?? 0),
      'ownerAmount' => (float) ($row['ownerAmount'] ?? 0),
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
