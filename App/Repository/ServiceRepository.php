<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Service.php';

class ServiceRepository
{
  private PDO $connection;

  private const COLUMNS = "
    tbserviceid,
    tbvenueid,
    tbservicename,
    tbservicetype,
    tbserviceprice,
    tbservicedescription,
    tbserviceunit,
    tbserviceminquantity,
    tbservicemaxquantity,
    tbserviceincludedquantity,
    tbservicestate,
    tbroleid,
    tbserviceapprovedon,
    tbservicegroupid,
    tbserviceactive
  ";

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function save(Service $service): int
  {
    $sql = "INSERT INTO tbservice (
      tbvenueid, tbservicename, tbservicetype, tbserviceprice,
      tbservicedescription, tbserviceunit, tbserviceminquantity,
      tbservicemaxquantity, tbserviceincludedquantity, tbservicestate,
      tbservicegroupid, tbserviceactive
    ) VALUES (
      :idLocal, :nameService, :typeService, :priceService,
      :descriptionService, :unitService, :minQuantity,
      :maxQuantity, :includedQuantity, :stateService,
      :idGroup, :isActive
    )";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idLocal' => $service->getIdLocal(),
      ':nameService' => $service->getNameService(),
      ':typeService' => $service->getTypeService(),
      ':priceService' => $service->getPriceService(),
      ':descriptionService' => $service->getDescriptionService(),
      ':unitService' => $service->getUnitService(),
      ':minQuantity' => $service->getMinQuantity(),
      ':maxQuantity' => $service->getMaxQuantity(),
      ':includedQuantity' => $service->getIncludedQuantity(),
      ':stateService' => $service->getStateService(),
      ':idGroup' => $service->getIdGroup(),
      ':isActive' => $this->toDb($service->getIsActive()),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  public function findById(int $idService): ?Service
  {
    $stmt = $this->connection->prepare(
      'SELECT ' . self::COLUMNS . ' FROM tbservice WHERE tbserviceid = :idService'
    );
    $stmt->execute([':idService' => $idService]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  public function findAvailableByLocal(int $idLocal): array
  {
    $stmt = $this->connection->prepare(
      'SELECT ' . self::COLUMNS . ' FROM tbservice
       WHERE tbvenueid = :idLocal AND tbserviceactive = true
       ORDER BY tbservicename ASC'
    );
    $stmt->execute([':idLocal' => $idLocal]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function findByLocal(int $idLocal): array
  {
    $stmt = $this->connection->prepare(
      'SELECT ' . self::COLUMNS . ' FROM tbservice
       WHERE tbvenueid = :idLocal
       ORDER BY tbserviceactive DESC, tbservicename ASC'
    );
    $stmt->execute([':idLocal' => $idLocal]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function findByGroup(int $idGroup): array
  {
    $stmt = $this->connection->prepare(
      'SELECT ' . self::COLUMNS . ' FROM tbservice
       WHERE tbservicegroupid = :idGroup
       ORDER BY tbserviceactive DESC, tbserviceid ASC'
    );
    $stmt->execute([':idGroup' => $idGroup]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function findByGroupAndVenue(int $idGroup, int $idLocal): ?Service
  {
    $stmt = $this->connection->prepare(
      'SELECT ' . self::COLUMNS . ' FROM tbservice
       WHERE tbservicegroupid = :idGroup AND tbvenueid = :idLocal
       LIMIT 1'
    );
    $stmt->execute([':idGroup' => $idGroup, ':idLocal' => $idLocal]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  public function existsByNameForLocal(int $idLocal, string $name, ?int $excludeId = null): bool
  {
    $sql = 'SELECT COUNT(*) FROM tbservice
            WHERE tbvenueid = :idLocal
              AND LOWER(TRIM(tbservicename)) = LOWER(:name)';
    $params = [':idLocal' => $idLocal, ':name' => trim($name)];

    if ($excludeId !== null) {
      $sql .= ' AND tbserviceid <> :excludeId';
      $params[':excludeId'] = $excludeId;
    }

    $stmt = $this->connection->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
  }

  public function updateState(int $idService, string $stateService): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbservice SET tbservicestate = :stateService WHERE tbserviceid = :idService'
    );

    return $stmt->execute([':stateService' => $stateService, ':idService' => $idService]);
  }

  public function update(Service $service): bool
  {
    $sql = 'UPDATE tbservice SET
      tbservicename = :nameService, tbservicetype = :typeService,
      tbserviceprice = :priceService, tbservicedescription = :descriptionService,
      tbserviceunit = :unitService, tbserviceminquantity = :minQuantity,
      tbservicemaxquantity = :maxQuantity, tbserviceincludedquantity = :includedQuantity,
      tbserviceactive = :isActive
      WHERE tbserviceid = :idService';

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':nameService' => $service->getNameService(),
      ':typeService' => $service->getTypeService(),
      ':priceService' => $service->getPriceService(),
      ':descriptionService' => $service->getDescriptionService(),
      ':unitService' => $service->getUnitService(),
      ':minQuantity' => $service->getMinQuantity(),
      ':maxQuantity' => $service->getMaxQuantity(),
      ':includedQuantity' => $service->getIncludedQuantity(),
      ':isActive' => $this->toDb($service->getIsActive()),
      ':idService' => $service->getIdService(),
    ]);
  }

  public function renameByGroup(int $idGroup, string $name): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbservice SET tbservicename = :name WHERE tbservicegroupid = :idGroup'
    );

    return $stmt->execute([':name' => trim($name), ':idGroup' => $idGroup]);
  }

  public function setActive(int $idService, bool $active): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbservice SET tbserviceactive = :active WHERE tbserviceid = :id'
    );

    return $stmt->execute([':active' => $this->toDb($active), ':id' => $idService]);
  }

  public function setGroup(int $idService, int $idGroup): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbservice SET tbservicegroupid = :idGroup WHERE tbserviceid = :id'
    );

    return $stmt->execute([':idGroup' => $idGroup, ':id' => $idService]);
  }

  public function setVenue(int $idService, int $idVenue): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbservice SET tbvenueid = :idVenue WHERE tbserviceid = :id'
    );

    return $stmt->execute([':idVenue' => $idVenue, ':id' => $idService]);
  }

  public function distinctTypes(): array
  {
    $stmt = $this->connection->prepare(
      "SELECT DISTINCT tbservicetype FROM tbservice
       WHERE tbserviceactive = true
         AND tbservicetype IS NOT NULL AND TRIM(tbservicetype) <> ''
       ORDER BY tbservicetype ASC"
    );
    $stmt->execute();

    return array_map(
      static fn(array $row): string => (string) $row['tbservicetype'],
      $stmt->fetchAll(PDO::FETCH_ASSOC)
    );
  }

  public function countActiveByGroup(int $idGroup): int
  {
    $stmt = $this->connection->prepare(
      'SELECT COUNT(*) FROM tbservice
       WHERE tbservicegroupid = :idGroup AND tbserviceactive = true'
    );
    $stmt->execute([':idGroup' => $idGroup]);

    return (int) $stmt->fetchColumn();
  }

  private function mapRow(array $row): Service
  {
    return new Service(
      idService: (int) $row['tbserviceid'],
      idLocal: (int) $row['tbvenueid'],
      nameService: $row['tbservicename'],
      typeService: $row['tbservicetype'],
      priceService: (float) $row['tbserviceprice'],
      stateService: $row['tbservicestate'],
      isActive: $this->toBool($row['tbserviceactive']),
      approvedBy: ($row['tbroleid'] ?? null) !== null ? (int) $row['tbroleid'] : null,
      approvedOn: $row['tbserviceapprovedon'] ?? null,
      descriptionService: $row['tbservicedescription'] ?? null,
      unitService: (string) ($row['tbserviceunit'] ?? 'unidad'),
      minQuantity: (int) ($row['tbserviceminquantity'] ?? 1),
      maxQuantity: ($row['tbservicemaxquantity'] ?? null) !== null ? (int) $row['tbservicemaxquantity'] : null,
      includedQuantity: (int) ($row['tbserviceincludedquantity'] ?? 0),
      idGroup: ($row['tbservicegroupid'] ?? null) !== null ? (int) $row['tbservicegroupid'] : null
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
