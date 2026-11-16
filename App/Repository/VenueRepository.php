<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Venue.php';

class VenueRepository
{
  private PDO $connection;

  private const COLUMNS = "
    tbvenueid, tbownerid, tblocationid, tbvenuename, tbvenuetype,
    tbvenuecapacity, tbvenueprice, tbvenueimage, tbvenueactive,
    tbvenuedescription, tbvenuesize, tbvenuebathrooms, tbvenueparking,
    tbvenueparkingcapacity, tbvenuemincapacity, tbvenuecleaningfee,
    tbvenuecheckintime, tbvenuecheckouttime
  ";

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function save(Venue $venue): int
  {
    $sql = 'INSERT INTO tbvenue (
      tbownerid, tblocationid, tbvenuename, tbvenuetype, tbvenuecapacity,
      tbvenueprice, tbvenueimage, tbvenueactive, tbvenuedescription,
      tbvenuesize, tbvenuebathrooms, tbvenueparking, tbvenueparkingcapacity,
      tbvenuemincapacity, tbvenuecleaningfee, tbvenuecheckintime,
      tbvenuecheckouttime
    ) VALUES (
      :idOwner, :idLocation, :nameVenue, :typeVenue, :capacityVenue,
      :priceVenue, :imageVenue, :isActive, :descriptionVenue,
      :sizeVenue, :bathroomsVenue, :hasParking, :parkingCapacity,
      :minCapacity, :cleaningFee, :checkInTime, :checkOutTime
    )';

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idOwner' => $venue->getIdOwner(),
      ':idLocation' => $venue->getIdLocation(),
      ':nameVenue' => $venue->getNameVenue(),
      ':typeVenue' => $venue->getTypeVenue(),
      ':capacityVenue' => $venue->getCapacityVenue(),
      ':priceVenue' => $venue->getPriceVenue(),
      ':imageVenue' => $venue->getImageVenue(),
      ':isActive' => $this->toDb($venue->getIsActive()),
      ':descriptionVenue' => $venue->getDescriptionVenue(),
      ':sizeVenue' => $venue->getSizeVenue(),
      ':bathroomsVenue' => $venue->getBathroomsVenue(),
      ':hasParking' => $venue->getHasParking() ? 1 : 0,
      ':parkingCapacity' => $venue->getParkingCapacity(),
      ':minCapacity' => $venue->getMinCapacity(),
      ':cleaningFee' => $venue->getCleaningFee(),
      ':checkInTime' => $venue->getCheckInTime(),
      ':checkOutTime' => $venue->getCheckOutTime(),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  public function findById(int $idVenue): ?Venue
  {
    $stmt = $this->connection->prepare(
      'SELECT ' . self::COLUMNS . ' FROM tbvenue WHERE tbvenueid = :idVenue'
    );
    $stmt->execute([':idVenue' => $idVenue]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  public function findActive(): array
  {
    $stmt = $this->connection->prepare(
      'SELECT ' . self::COLUMNS . ' FROM tbvenue
       WHERE tbvenueactive = true ORDER BY tbvenuename ASC'
    );
    $stmt->execute();

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function findActiveByIds(array $ids): array
  {
    return $this->findByIds($ids, true);
  }

  public function findByIds(array $ids, bool $onlyActive = false): array
  {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));

    if (empty($ids)) {
      return [];
    }

    $placeholders = [];
    $params = [];
    foreach ($ids as $i => $id) {
      $key = ':id' . $i;
      $placeholders[] = $key;
      $params[$key] = $id;
    }

    $sql = 'SELECT ' . self::COLUMNS . ' FROM tbvenue
            WHERE tbvenueid IN (' . implode(', ', $placeholders) . ')';
    if ($onlyActive) {
      $sql .= ' AND tbvenueactive = true';
    }

    $stmt = $this->connection->prepare($sql);
    $stmt->execute($params);

    $byId = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $byId[(int) $row['tbvenueid']] = $this->mapRow($row);
    }

    $ordered = [];
    foreach ($ids as $id) {
      if (isset($byId[$id])) {
        $ordered[] = $byId[$id];
      }
    }

    return $ordered;
  }

  public function findByFilters(array $filters = []): array
  {
    $sql = 'SELECT v.tbvenueid, v.tbownerid, v.tblocationid, v.tbvenuename,
                   v.tbvenuetype, v.tbvenuecapacity, v.tbvenueprice, v.tbvenueimage,
                   v.tbvenueactive, v.tbvenuedescription, v.tbvenuesize,
                   v.tbvenuebathrooms, v.tbvenueparking, v.tbvenueparkingcapacity,
                   v.tbvenuemincapacity, v.tbvenuecleaningfee,
                   v.tbvenuecheckintime, v.tbvenuecheckouttime,
                   l.tblocationprovince
            FROM tbvenue v
            LEFT JOIN tblocation l ON l.tblocationid = v.tblocationid
            WHERE v.tbvenueactive = true';

    $conditions = [];
    $params = [];

    if (!empty($filters['province'])) {
      $conditions[] = 'l.tblocationprovince = :province';
      $params[':province'] = $filters['province'];
    }

    if (!empty($filters['canton'])) {
      $conditions[] = 'l.tblocationcanton = :canton';
      $params[':canton'] = $filters['canton'];
    }

    if (!empty($filters['district'])) {
      $conditions[] = 'l.tblocationdistrict = :district';
      $params[':district'] = $filters['district'];
    }

    if (!empty($filters['type'])) {
      $conditions[] = 'v.tbvenuetype = :type';
      $params[':type'] = $filters['type'];
    }

    if (!empty($filters['serviceType'])) {
      $conditions[] = "EXISTS (
        SELECT 1 FROM tbservice s
        WHERE s.tbvenueid = v.tbvenueid
          AND s.tbserviceactive = true
          AND s.tbservicetype = :serviceType
      )";
      $params[':serviceType'] = $filters['serviceType'];
    }

    if (!empty($filters['q'])) {
      $conditions[] = '(v.tbvenuename LIKE :q OR v.tbvenuetype LIKE :q)';
      $params[':q'] = '%' . $filters['q'] . '%';
    }

    if (!empty($conditions)) {
      $sql .= ' AND ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY v.tbvenuename ASC';

    $stmt = $this->connection->prepare($sql);
    $stmt->execute($params);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function findByOwner(int $idOwner): array
  {
    $stmt = $this->connection->prepare(
      'SELECT ' . self::COLUMNS . ' FROM tbvenue
       WHERE tbownerid = :idOwner
       ORDER BY tbvenueactive DESC, tbvenuename ASC'
    );
    $stmt->execute([':idOwner' => $idOwner]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function findActiveByOwner(int $idOwner): array
  {
    $stmt = $this->connection->prepare(
      'SELECT ' . self::COLUMNS . ' FROM tbvenue
       WHERE tbownerid = :idOwner AND tbvenueactive = true
       ORDER BY tbvenuename ASC'
    );
    $stmt->execute([':idOwner' => $idOwner]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function existsByNameForOwner(int $idOwner, string $name, ?int $excludeId = null): bool
  {
    $sql = 'SELECT COUNT(*) FROM tbvenue
            WHERE tbownerid = :idOwner
              AND LOWER(TRIM(tbvenuename)) = LOWER(:name)';
    $params = [':idOwner' => $idOwner, ':name' => trim($name)];

    if ($excludeId !== null) {
      $sql .= ' AND tbvenueid <> :excludeId';
      $params[':excludeId'] = $excludeId;
    }

    $stmt = $this->connection->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
  }

  public function update(Venue $venue): bool
  {
    $sql = 'UPDATE tbvenue SET
      tbvenuename = :nameVenue, tbvenuetype = :typeVenue,
      tbvenuecapacity = :capacityVenue, tbvenueprice = :priceVenue,
      tbvenueimage = :imageVenue, tblocationid = :idLocation,
      tbvenueactive = :isActive, tbvenuedescription = :descriptionVenue,
      tbvenuesize = :sizeVenue, tbvenuebathrooms = :bathroomsVenue,
      tbvenueparking = :hasParking, tbvenueparkingcapacity = :parkingCapacity,
      tbvenuemincapacity = :minCapacity, tbvenuecleaningfee = :cleaningFee,
      tbvenuecheckintime = :checkInTime, tbvenuecheckouttime = :checkOutTime
      WHERE tbvenueid = :idVenue';

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':nameVenue' => $venue->getNameVenue(),
      ':typeVenue' => $venue->getTypeVenue(),
      ':capacityVenue' => $venue->getCapacityVenue(),
      ':priceVenue' => $venue->getPriceVenue(),
      ':imageVenue' => $venue->getImageVenue(),
      ':idLocation' => $venue->getIdLocation(),
      ':isActive' => $this->toDb($venue->getIsActive()),
      ':descriptionVenue' => $venue->getDescriptionVenue(),
      ':sizeVenue' => $venue->getSizeVenue(),
      ':bathroomsVenue' => $venue->getBathroomsVenue(),
      ':hasParking' => $venue->getHasParking() ? 1 : 0,
      ':parkingCapacity' => $venue->getParkingCapacity(),
      ':minCapacity' => $venue->getMinCapacity(),
      ':cleaningFee' => $venue->getCleaningFee(),
      ':checkInTime' => $venue->getCheckInTime(),
      ':checkOutTime' => $venue->getCheckOutTime(),
      ':idVenue' => $venue->getIdVenue(),
    ]);
  }

  public function setActive(int $idVenue, bool $active): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbvenue SET tbvenueactive = :active WHERE tbvenueid = :id'
    );

    return $stmt->execute([':active' => $this->toDb($active), ':id' => $idVenue]);
  }

  private function mapRow(array $row): Venue
  {
    return new Venue(
      idVenue: (int) $row['tbvenueid'],
      idOwner: (int) $row['tbownerid'],
      idLocation: (int) ($row['tblocationid'] ?? 0),
      nameVenue: $row['tbvenuename'],
      typeVenue: $row['tbvenuetype'] ?? '',
      capacityVenue: (int) $row['tbvenuecapacity'],
      priceVenue: (float) $row['tbvenueprice'],
      imageVenue: $row['tbvenueimage'] ?? '',
      isActive: $this->toBool($row['tbvenueactive']),
      descriptionVenue: $row['tbvenuedescription'] ?? null,
      sizeVenue: $row['tbvenuesize'] ?? null,
      bathroomsVenue: ($row['tbvenuebathrooms'] ?? null) !== null ? (int) $row['tbvenuebathrooms'] : null,
      hasParking: $this->toBool($row['tbvenueparking'] ?? false),
      parkingCapacity: ($row['tbvenueparkingcapacity'] ?? null) !== null ? (int) $row['tbvenueparkingcapacity'] : null,
      minCapacity: ($row['tbvenuemincapacity'] ?? null) !== null ? (int) $row['tbvenuemincapacity'] : null,
      cleaningFee: (float) ($row['tbvenuecleaningfee'] ?? 0),
      checkInTime: isset($row['tbvenuecheckintime']) && $row['tbvenuecheckintime'] !== null ? substr((string) $row['tbvenuecheckintime'], 0, 5) : null,
      checkOutTime: isset($row['tbvenuecheckouttime']) && $row['tbvenuecheckouttime'] !== null ? substr((string) $row['tbvenuecheckouttime'], 0, 5) : null
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
