<?php

require_once __DIR__ . '/../Model/VenueRentalBlock.php';

class VenueRentalBlockRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function save(VenueRentalBlock $block): int
  {
    $stmt = $this->connection->prepare(
      'INSERT INTO tbvenuerentalblock
       (tbvenueid, tbvenuerentalblockname, tbvenuerentalblockstart,
        tbvenuerentalblockend, tbvenuerentalblockprice,
        tbvenuerentalblocksort, tbvenuerentalblockactive)
       VALUES
       (:venueId, :name, :start, :end, :price, :sort, :active)'
    );
    $stmt->execute([
      ':venueId' => $block->getIdVenue(),
      ':name' => $block->getName(),
      ':start' => $block->getStartTime(),
      ':end' => $block->getEndTime(),
      ':price' => $block->getPrice(),
      ':sort' => $block->getSort(),
      ':active' => $block->getIsActive() ? 1 : 0,
    ]);

    return (int) $this->connection->lastInsertId();
  }

  public function findByVenue(int $idVenue, bool $onlyActive = false): array
  {
    $sql = 'SELECT * FROM tbvenuerentalblock WHERE tbvenueid = :venueId';
    if ($onlyActive) {
      $sql .= ' AND tbvenuerentalblockactive = true';
    }
    $sql .= ' ORDER BY tbvenuerentalblocksort ASC, tbvenuerentalblockstart ASC';

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':venueId' => $idVenue]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function findById(int $idBlock): ?VenueRentalBlock
  {
    $stmt = $this->connection->prepare(
      'SELECT * FROM tbvenuerentalblock WHERE tbvenuerentalblockid = :id LIMIT 1'
    );
    $stmt->execute([':id' => $idBlock]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  public function replaceForVenue(int $idVenue, array $blocks): void
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbvenuerentalblock SET tbvenuerentalblockactive = false WHERE tbvenueid = :venueId'
    );
    $stmt->execute([':venueId' => $idVenue]);

    $sort = 0;
    foreach ($blocks as $block) {
      if (!$block instanceof VenueRentalBlock) {
        continue;
      }
      $block->setIdVenue($idVenue);
      $block->setSort($sort++);
      $block->setIsActive(true);

      $existing = $block->getIdBlock() > 0 ? $this->findById($block->getIdBlock()) : null;

      if ($existing !== null && $existing->getIdVenue() === $idVenue) {
        $stmt = $this->connection->prepare(
          'UPDATE tbvenuerentalblock
           SET tbvenuerentalblockname = :name,
               tbvenuerentalblockstart = :start,
               tbvenuerentalblockend = :end,
               tbvenuerentalblockprice = :price,
               tbvenuerentalblocksort = :sort,
               tbvenuerentalblockactive = true
           WHERE tbvenuerentalblockid = :id AND tbvenueid = :venueId'
        );
        $stmt->execute([
          ':name' => $block->getName(),
          ':start' => $block->getStartTime(),
          ':end' => $block->getEndTime(),
          ':price' => $block->getPrice(),
          ':sort' => $block->getSort(),
          ':id' => $block->getIdBlock(),
          ':venueId' => $idVenue,
        ]);
        continue;
      }

      $this->save($block);
    }
  }

  private function mapRow(array $row): VenueRentalBlock
  {
    return new VenueRentalBlock(
      idBlock: (int) $row['tbvenuerentalblockid'],
      idVenue: (int) $row['tbvenueid'],
      name: (string) $row['tbvenuerentalblockname'],
      startTime: substr((string) $row['tbvenuerentalblockstart'], 0, 5),
      endTime: substr((string) $row['tbvenuerentalblockend'], 0, 5),
      price: (float) $row['tbvenuerentalblockprice'],
      sort: (int) $row['tbvenuerentalblocksort'],
      isActive: $this->toBool($row['tbvenuerentalblockactive'])
    );
  }

  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }
}
