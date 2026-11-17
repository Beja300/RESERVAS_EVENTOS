<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/ServiceGroup.php';

class ServiceGroupRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function save(ServiceGroup $group): int
  {
    $sql = 'INSERT INTO tbservicegroup (tbownerid, tbservicegroupname, tbservicegroupactive)
            VALUES (:ownerId, :name, :active)';
    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':ownerId' => $group->getIdOwner(),
      ':name' => $group->getName(),
      ':active' => $group->getIsActive() ? 1 : 0,
    ]);

    return (int) $this->connection->lastInsertId();
  }

  public function findById(int $idGroup): ?ServiceGroup
  {
    $stmt = $this->connection->prepare(
      'SELECT * FROM tbservicegroup WHERE tbservicegroupid = :id LIMIT 1'
    );
    $stmt->execute([':id' => $idGroup]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  public function findByOwner(int $idOwner): array
  {
    $stmt = $this->connection->prepare(
      'SELECT * FROM tbservicegroup
       WHERE tbownerid = :ownerId AND tbservicegroupactive = true
       ORDER BY tbservicegroupname ASC, tbservicegroupid ASC'
    );
    $stmt->execute([':ownerId' => $idOwner]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function findByNameForOwner(int $idOwner, string $name, ?int $excludeId = null): ?ServiceGroup
  {
    $sql = 'SELECT * FROM tbservicegroup
            WHERE tbownerid = :ownerId
              AND LOWER(TRIM(tbservicegroupname)) = LOWER(:name)
              AND tbservicegroupactive = true';
    $params = [':ownerId' => $idOwner, ':name' => trim($name)];

    if ($excludeId !== null) {
      $sql .= ' AND tbservicegroupid <> :excludeId';
      $params[':excludeId'] = $excludeId;
    }

    $sql .= ' LIMIT 1';
    $stmt = $this->connection->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  public function updateName(int $idGroup, string $name): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbservicegroup SET tbservicegroupname = :name WHERE tbservicegroupid = :id'
    );

    return $stmt->execute([':name' => trim($name), ':id' => $idGroup]);
  }

  private function mapRow(array $row): ServiceGroup
  {
    return new ServiceGroup(
      idGroup: (int) $row['tbservicegroupid'],
      idOwner: (int) $row['tbownerid'],
      name: (string) $row['tbservicegroupname'],
      createdAt: (string) ($row['tbservicegroupcreatedat'] ?? ''),
      isActive: $this->toBool($row['tbservicegroupactive'])
    );
  }

  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }
}
