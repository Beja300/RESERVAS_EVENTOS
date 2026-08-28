<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/ServiceHistory.php';

class ServiceHistoryRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(ServiceHistory $history): int
  {
    $sql = "
      INSERT INTO tbservicehistory (
        tbservicehistoryserviceid,
        tbservicehistoryprice,
        tbservicehistoryvalidfrom
      )
      VALUES (
        :idService,
        :price,
        :validFrom
      )
    ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idService' => $history->getIdService(),
      ':price'     => $history->getPrice(),
      ':validFrom' => $history->getValidFrom(),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // BUSCAR POR SERVICIO (historial completo)
  // =========================================================
  public function findByService(int $idService): array
  {
    $sql = "
      SELECT
        tbservicehistoryid,
        tbservicehistoryserviceid,
        tbservicehistoryprice,
        tbservicehistoryvalidfrom
      FROM tbservicehistory
      WHERE tbservicehistoryserviceid = :idService
        AND tbservicehistoryactive = true
      ORDER BY tbservicehistoryvalidfrom ASC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idService' => $idService]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // PRECIO VIGENTE EN UNA FECHA (congelado)
  // =========================================================
  public function findPriceOnDate(int $idService, string $date): ?float
  {
    $sql = "
      SELECT tbservicehistoryprice
      FROM tbservicehistory
      WHERE tbservicehistoryserviceid = :idService
        AND tbservicehistoryvalidfrom <= :date
        AND tbservicehistoryactive = true
      ORDER BY tbservicehistoryvalidfrom DESC
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idService' => $idService,
      ':date'      => $date,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (float) $row['tbservicehistoryprice'] : null;
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): ServiceHistory
  {
    return new ServiceHistory(
      idServiceHistory: (int) $row['tbservicehistoryid'],
      idService: (int) $row['tbservicehistoryserviceid'],
      price: (float) $row['tbservicehistoryprice'],
      validFrom: $row['tbservicehistoryvalidfrom']
    );
  }
}
