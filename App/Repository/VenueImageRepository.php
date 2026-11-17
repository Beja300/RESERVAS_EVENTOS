<?php

require_once __DIR__ . '/../Model/VenueImage.php';

class VenueImageRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function save(VenueImage $image): int
  {
    $stmt = $this->connection->prepare(
      'INSERT INTO tbvenueimage
       (tbvenueid, tbvenueimagepath, tbvenueimagesort, tbvenueimageactive)
       VALUES (:venueId, :path, :sort, :active)'
    );
    $stmt->execute([
      ':venueId' => $image->getIdVenue(),
      ':path' => $image->getPath(),
      ':sort' => $image->getSort(),
      ':active' => $image->getIsActive() ? 1 : 0,
    ]);

    return (int) $this->connection->lastInsertId();
  }

  public function findByVenue(int $idVenue): array
  {
    $stmt = $this->connection->prepare(
      'SELECT * FROM tbvenueimage
       WHERE tbvenueid = :venueId AND tbvenueimageactive = true
       ORDER BY tbvenueimagesort ASC, tbvenueimageid ASC'
    );
    $stmt->execute([':venueId' => $idVenue]);

    return array_map(static fn(array $row): VenueImage => new VenueImage(
      idImage: (int) $row['tbvenueimageid'],
      idVenue: (int) $row['tbvenueid'],
      path: (string) $row['tbvenueimagepath'],
      sort: (int) $row['tbvenueimagesort'],
      isActive: (int) $row['tbvenueimageactive'] === 1
    ), $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function deactivate(int $idImage): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbvenueimage SET tbvenueimageactive = false WHERE tbvenueimageid = :id'
    );

    return $stmt->execute([':id' => $idImage]);
  }
}
