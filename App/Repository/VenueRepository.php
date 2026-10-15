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
                tbownerid,
                tblocationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueprice,
                tbvenueimage,
                tbvenueactive
            )
            VALUES (
                :idOwner,
                :idLocation,
                :nameVenue,
                :typeVenue,
                :capacityVenue,
                :priceVenue,
                :imageVenue,
                :isActive
            )
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->bindValue(':idOwner', $venue->getIdOwner(), PDO::PARAM_INT);
    $stmt->bindValue(
      ':idLocation',
      $venue->getIdLocation(),
      $venue->getIdLocation() === null ? PDO::PARAM_NULL : PDO::PARAM_INT
    );
    $stmt->bindValue(':nameVenue', $venue->getNameVenue());
    $stmt->bindValue(':typeVenue', $venue->getTypeVenue());
    $stmt->bindValue(':capacityVenue', $venue->getCapacityVenue(), PDO::PARAM_INT);
    $stmt->bindValue(':priceVenue', $venue->getPriceVenue());
    $stmt->bindValue(':imageVenue', $venue->getImageVenue());
    $stmt->bindValue(':isActive', $this->toDb($venue->getIsActive()), PDO::PARAM_INT);

    $stmt->execute();

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
                tbownerid,
                tblocationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueprice,
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
                tbownerid,
                tblocationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueprice,
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
  public function findActiveByIds(array $ids): array
  {
    return $this->findByIds($ids, true);
  }

  // =========================================================
  // BUSCAR LOCALES POR IDS (opcionalmente solo activos)
  // =========================================================
  public function findByIds(array $ids, bool $onlyActive = false): array
  {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($i) => $i > 0)));

    if (empty($ids)) {
      return [];
    }

    $placeholders = [];
    $params = [];
    foreach ($ids as $i => $id) {
      $key = ":id{$i}";
      $placeholders[] = $key;
      $params[$key] = $id;
    }

    $sql = "
            SELECT
                tbvenueid,
                tbownerid,
                tblocationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueprice,
                tbvenueimage,
                tbvenueactive
            FROM tbvenue
            WHERE tbvenueid IN (" . implode(', ', $placeholders) . ")
        ";

    if ($onlyActive) {
      $sql .= ' AND tbvenueactive = true';
    }

    $stmt = $this->connection->prepare($sql);
    $stmt->execute($params);

    $byId = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $byId[(int) $row['tbvenueid']] = $this->mapRow($row);
    }

    // Conserva el orden pedido por el llamador (ej. el de la lista de favoritos).
    $ordered = [];
    foreach ($ids as $id) {
      if (isset($byId[$id])) {
        $ordered[] = $byId[$id];
      }
    }

    return $ordered;
  }

  // =========================================================
  // BUSCAR LOCALES ACTIVOS POR FILTROS (ubicación, tipo, texto)
  // =========================================================
  public function findByFilters(array $filters = []): array
  {
    $sql = "
            SELECT
                v.tbvenueid,
                v.tbownerid,
                v.tblocationid,
                v.tbvenuename,
                v.tbvenuetype,
                v.tbvenuecapacity,
                v.tbvenueprice,
                v.tbvenueimage,
                v.tbvenueactive,
                l.tblocationprovince
            FROM tbvenue v
            LEFT JOIN tblocation l ON l.tblocationid = v.tblocationid
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

    // Categorización por TIPO DE SERVICIO: locales que ofrecen al menos un
    // servicio aprobado de esa categoría (reutiliza tbservicetype).
    if (!empty($filters['serviceType'])) {
      $conditions[] = "EXISTS (
        SELECT 1 FROM tbservice s
        WHERE s.tbvenueid = v.tbvenueid
          AND s.tbserviceactive = true
          AND s.tbservicestate = 'aprobado'
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


  // =========================================================
  // PANEL DEL OWNER (todos sus locales, activos o no)
  // =========================================================
  public function findByOwner(int $idOwner): array
  {
    $sql = "
            SELECT
                tbvenueid,
                tbownerid,
                tblocationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueprice,
                tbvenueimage,
                tbvenueactive

            FROM tbvenue

            WHERE tbownerid = :idOwner
            ORDER BY tbvenuename ASC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner' => $idOwner
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // ¿YA EXISTE UN LOCAL CON ESE NOMBRE PARA EL MISMO DUEÑO?
  // (control de duplicados desde PHP, case-insensitive)
  // =========================================================
  public function existsByNameForOwner(int $idOwner, string $name, ?int $excludeId = null): bool
  {
    $sql = "
      SELECT COUNT(*) FROM tbvenue
      WHERE tbownerid = :idOwner
        AND LOWER(TRIM(tbvenuename)) = LOWER(:name)
    ";

    $params = [':idOwner' => $idOwner, ':name' => trim($name)];

    if ($excludeId !== null) {
      $sql .= " AND tbvenueid <> :excludeId";
      $params[':excludeId'] = $excludeId;
    }

    $stmt = $this->connection->prepare($sql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn() > 0;
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
                tbvenueprice = :priceVenue,
                tbvenueimage = :imageVenue,
                tblocationid = :idLocation,
                tbvenueactive = :isActive
            WHERE tbvenueid = :idVenue
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->bindValue(':nameVenue', $venue->getNameVenue());
    $stmt->bindValue(':typeVenue', $venue->getTypeVenue());
    $stmt->bindValue(':capacityVenue', $venue->getCapacityVenue(), PDO::PARAM_INT);
    $stmt->bindValue(':priceVenue', $venue->getPriceVenue());
    $stmt->bindValue(':imageVenue', $venue->getImageVenue());
    $stmt->bindValue(
      ':idLocation',
      $venue->getIdLocation(),
      $venue->getIdLocation() === null ? PDO::PARAM_NULL : PDO::PARAM_INT
    );
    $stmt->bindValue(':isActive', $this->toDb($venue->getIsActive()), PDO::PARAM_INT);
    $stmt->bindValue(':idVenue', $venue->getIdVenue(), PDO::PARAM_INT);

    return $stmt->execute();
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Venue
  {
    return new Venue(
      idVenue: (int) $row['tbvenueid'],
      idOwner: (int) $row['tbownerid'],
      idLocation: $row['tblocationid'] === null ? null : (int) $row['tblocationid'],
      nameVenue: $row['tbvenuename'],
      typeVenue: $row['tbvenuetype'] ?? '',
      capacityVenue: (int) $row['tbvenuecapacity'],
      priceVenue: (float) $row['tbvenueprice'],
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
