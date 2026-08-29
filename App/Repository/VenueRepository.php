<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Venue.php';

class VenueRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(Venue $venue): int
  {
    $sql = "
            INSERT INTO tbvenue (
                tbvenueownerid,
                tbvenuelocationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueimage,
                tbvenueactive
            )
            VALUES (
                :idOwner,
                :idLocation,
                :nameVenue,
                :typeVenue,
                :capacityVenue,
                :imageVenue,
                :isActive
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner'       => $venue->getIdOwner(),
      ':idLocation'   => $venue->getIdLocation(),
      ':nameVenue'     => $venue->getNameVenue(),
      ':typeVenue'     => $venue->getTypeVenue(),
      ':capacityVenue' => $venue->getCapacityVenue(),
      ':imageVenue'    => $venue->getImageVenue(),
      ':isActive'      => $this->toDb($venue->getIsActive())
    ]);

    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // BUSCAR POR ID
  // =========================================================
  public function findById(int $idVenue): ?Venue
  {
    $sql = "
            SELECT
                tbvenueid,
                tbvenueownerid,
                tbvenuelocationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueimage,
                tbvenueactive

            FROM tbvenue

            WHERE tbvenueid = :idVenue
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idVenue' => $idVenue
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }


  // =========================================================
  // CATÁLOGO PÚBLICO DEL CLIENTE (solo locales activos)
  // =========================================================
  public function findActive(): array
  {
    $sql = "
            SELECT
                tbvenueid,
                tbvenueownerid,
                tbvenuelocationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueimage,
                tbvenueactive

            FROM tbvenue

            WHERE tbvenueactive = true

            ORDER BY tbvenuename ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // BUSCAR LOCALES ACTIVOS POR FILTROS (ubicación, tipo, texto)
  // =========================================================
  public function findByFilters(array $filters = []): array
  {
    $sql = "
            SELECT
                v.tbvenueid,
                v.tbvenueownerid,
                v.tbvenuelocationid,
                v.tbvenuename,
                v.tbvenuetype,
                v.tbvenuecapacity,
                v.tbvenueimage,
                v.tbvenueactive,
                l.tblocationprovince
            FROM tbvenue v
            LEFT JOIN tblocation l ON l.tblocationid = v.tbvenuelocationid
            WHERE v.tbvenueactive = true
        ";

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


  // =========================================================
  // PANEL DEL OWNER (todos sus locales, activos o no)
  // =========================================================
  public function findByOwner(int $idOwner): array
  {
    $sql = "
            SELECT
                tbvenueid,
                tbvenueownerid,
                tbvenuelocationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueimage,
                tbvenueactive

            FROM tbvenue

            WHERE tbvenueownerid = :idOwner
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner' => $idOwner
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function update(Venue $venue): bool
  {
    $sql = "
            UPDATE tbvenue
            SET
                tbvenuename = :nameVenue,
                tbvenuetype = :typeVenue,
                tbvenuecapacity = :capacityVenue,
                tbvenueimage = :imageVenue,
                tbvenueactive = :isActive
            WHERE tbvenueid = :idVenue
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':nameVenue'     => $venue->getNameVenue(),
      ':typeVenue'     => $venue->getTypeVenue(),
      ':capacityVenue' => $venue->getCapacityVenue(),
      ':imageVenue'    => $venue->getImageVenue(),
      ':isActive'      => $this->toDb($venue->getIsActive()),
      ':idVenue'       => $venue->getIdVenue()
    ]);
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Venue
  {
    return new Venue(
      idVenue: (int) $row['tbvenueid'],
      idOwner: (int) $row['tbvenueownerid'],
      idLocation: (int) $row['tbvenuelocationid'],
      nameVenue: $row['tbvenuename'],
      typeVenue: $row['tbvenuetype'] ?? '',
      capacityVenue: (int) $row['tbvenuecapacity'],
      imageVenue: $row['tbvenueimage'] ?? '',
      isActive: $this->toBool($row['tbvenueactive'])
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
