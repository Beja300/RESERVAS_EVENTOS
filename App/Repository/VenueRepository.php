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
                tbvenueubicationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueimage,
                tbvenueisactive
            )
            VALUES (
                :idOwner,
                :idUbication,
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
      ':idUbication'   => $venue->getIdUbication(),
      ':nameVenue'     => $venue->getNameVenue(),
      ':typeVenue'     => $venue->getTypeVenue(),
      ':capacityVenue' => $venue->getCapacityVenue(),
      ':imageVenue'    => $venue->getImageVenue(),
      ':isActive'      => $venue->getIsActive()
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
                tbvenueubicationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueimage,
                tbvenueisactive

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
                tbvenueubicationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueimage,
                tbvenueisactive

            FROM tbvenue

            WHERE tbvenueisactive = true

            ORDER BY tbvenuename ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

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
                tbvenueubicationid,
                tbvenuename,
                tbvenuetype,
                tbvenuecapacity,
                tbvenueimage,
                tbvenueisactive

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
                tbvenueisactive = :isActive
            WHERE tbvenueid = :idVenue
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':nameVenue'     => $venue->getNameVenue(),
      ':typeVenue'     => $venue->getTypeVenue(),
      ':capacityVenue' => $venue->getCapacityVenue(),
      ':imageVenue'    => $venue->getImageVenue(),
      ':isActive'      => $venue->getIsActive(),
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
      idUbication: (int) $row['tbvenueubicationid'],
      nameVenue: $row['tbvenuename'],
      typeVenue: $row['tbvenuetype'],
      capacityVenue: (int) $row['tbvenuecapacity'],
      imageVenue: $row['tbvenueimage'],
      isActive: (bool) $row['tbvenueisactive']
    );
  }
}
