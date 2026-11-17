<?php

require_once __DIR__ . '/../Model/ServiceImage.php';

class ServiceImageRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  public function save(ServiceImage $image): int
  {
    $stmt = $this->connection->prepare(
      'INSERT INTO tbserviceimage
       (tbserviceid, tbserviceimagepath, tbserviceimagesort, tbserviceimageactive)
       VALUES (:serviceId, :path, :sort, :active)'
    );
    $stmt->execute([
      ':serviceId' => $image->getIdService(),
      ':path' => $image->getPath(),
      ':sort' => $image->getSort(),
      ':active' => $image->getIsActive() ? 1 : 0,
    ]);

    return (int) $this->connection->lastInsertId();
  }

  public function findByService(int $idService): array
  {
    $stmt = $this->connection->prepare(
      'SELECT * FROM tbserviceimage
       WHERE tbserviceid = :serviceId AND tbserviceimageactive = true
       ORDER BY tbserviceimagesort ASC, tbserviceimageid ASC'
    );
    $stmt->execute([':serviceId' => $idService]);

    return array_map(static fn(array $row): ServiceImage => new ServiceImage(
      idImage: (int) $row['tbserviceimageid'],
      idService: (int) $row['tbserviceid'],
      path: (string) $row['tbserviceimagepath'],
      sort: (int) $row['tbserviceimagesort'],
      isActive: (int) $row['tbserviceimageactive'] === 1
    ), $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  public function deactivate(int $idImage): bool
  {
    $stmt = $this->connection->prepare(
      'UPDATE tbserviceimage SET tbserviceimageactive = false WHERE tbserviceimageid = :id'
    );

    return $stmt->execute([':id' => $idImage]);
  }
}
