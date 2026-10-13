<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/VenueImage.php';

/**
 * Repositorio de la galería de imágenes de un local (tbvenueimage).
 * La portada se guarda en tbvenue.tbvenueimage y NO vive aquí.
 */
class VenueImageRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // IMÁGENES DE UN LOCAL (ordenadas)
  // =========================================================
  public function findByVenue(int $idVenue): array
  {
    $sql = "
      SELECT
        tbvenueimageid,
        tbvenueid,
        tbvenueimagepath,
        tbvenueimagesort,
        tbvenueimageactive
      FROM tbvenueimage
      WHERE tbvenueid = :idVenue
        AND tbvenueimageactive = true
      ORDER BY tbvenueimagesort ASC, tbvenueimageid ASC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idVenue' => $idVenue]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // GUARDAR VARIAS IMÁGENES EN ORDEN
  // =========================================================
  public function saveBatch(int $idVenue, array $paths): void
  {
    $stmt = $this->connection->prepare(
      "INSERT INTO tbvenueimage (tbvenueid, tbvenueimagepath, tbvenueimagesort, tbvenueimageactive)
       VALUES (:idVenue, :path, :sort, TRUE)"
    );

    $this->connection->beginTransaction();

    try {
      foreach (array_values($paths) as $index => $path) {
        $stmt->execute([
          ':idVenue' => $idVenue,
          ':path'    => $path,
          ':sort'    => $index,
        ]);
      }

      $this->connection->commit();
    } catch (\Throwable $e) {
      $this->connection->rollBack();
      throw $e;
    }
  }

  // =========================================================
  // ELIMINAR TODAS LAS IMÁGENES DE LA GALERÍA DE UN LOCAL
  // =========================================================
  public function deleteByVenue(int $idVenue): void
  {
    $stmt = $this->connection->prepare(
      "DELETE FROM tbvenueimage WHERE tbvenueid = :idVenue"
    );
    $stmt->execute([':idVenue' => $idVenue]);
  }

  private function mapRow(array $row): VenueImage
  {
    return new VenueImage(
      idVenueImage: (int) $row['tbvenueimageid'],
      idVenue: (int) $row['tbvenueid'],
      imagePath: (string) $row['tbvenueimagepath'],
      sort: (int) $row['tbvenueimagesort'],
      isActive: $row['tbvenueimageactive'] === 1
          || $row['tbvenueimageactive'] === '1'
          || $row['tbvenueimageactive'] === true
    );
  }
}