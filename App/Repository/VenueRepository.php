<?php

require_once __DIR__ . '/../models/Venue.php';

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
  public function saveVenue(Venue $venue): bool
  {
    try {
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
        ':idOwner' => $venue->getIdOwner(),
        ':idUbication' => $venue->getIdUbication(),
        ':nameVenue' => $venue->getNameVenue(),
        ':typeVenue' => $venue->getTypeVenue(),
        ':capacityVenue' => $venue->getCapacityVenue(),
        ':imageVenue' => $venue->getImageVenue(),
        ':isActive' => $venue->getIsActive()
      ]);

      $venue->setIdVenue((int) $this->connection->lastInsertId());

      return true;
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // OBTENER TODOS
  // =========================================================
  public function getAllVenue(): array
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

            ORDER BY tbvenuename ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $venues = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $venues[] = $this->mapRowToVenue($row);
    }

    return $venues;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdVenue(int $idVenue): ?Venue
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

    if (!$row) {
      return null;
    }

    return $this->mapRowToVenue($row);
  }


  // =========================================================
  // OBTENER POR OWNER (locales de un propietario, solo activos)
  // =========================================================
  public function getByOwnerVenue(int $idOwner): array
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
              AND tbvenueisactive = true

            ORDER BY tbvenuename ASC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner' => $idOwner
    ]);

    $venues = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $venues[] = $this->mapRowToVenue($row);
    }

    return $venues;
  }


  // =========================================================
  // OBTENER POR UBICACIÓN
  // =========================================================
  public function getByUbicationVenue(int $idUbication): array
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

            WHERE tbvenueubicationid = :idUbication
              AND tbvenueisactive = true

            ORDER BY tbvenuename ASC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idUbication' => $idUbication
    ]);

    $venues = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $venues[] = $this->mapRowToVenue($row);
    }

    return $venues;
  }


  // =========================================================
  // OBTENER POR TIPO
  // =========================================================
  public function getByTypeVenue(string $typeVenue): array
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

            WHERE tbvenuetype = :typeVenue
              AND tbvenueisactive = true

            ORDER BY tbvenuename ASC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':typeVenue' => $typeVenue
    ]);

    $venues = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $venues[] = $this->mapRowToVenue($row);
    }

    return $venues;
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updateVenue(Venue $venue): bool
  {
    try {
      $sql = "
                UPDATE tbvenue
                SET
                    tbvenueownerid = :idOwner,
                    tbvenueubicationid = :idUbication,
                    tbvenuename = :nameVenue,
                    tbvenuetype = :typeVenue,
                    tbvenuecapacity = :capacityVenue,
                    tbvenueimage = :imageVenue,
                    tbvenueisactive = :isActive
                WHERE tbvenueid = :idVenue
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idOwner' => $venue->getIdOwner(),
        ':idUbication' => $venue->getIdUbication(),
        ':nameVenue' => $venue->getNameVenue(),
        ':typeVenue' => $venue->getTypeVenue(),
        ':capacityVenue' => $venue->getCapacityVenue(),
        ':imageVenue' => $venue->getImageVenue(),
        ':isActive' => $venue->getIsActive(),
        ':idVenue' => $venue->getIdVenue()
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // ACTUALIZAR SOLO IMAGEN
  // =========================================================
  public function updateImageVenue(int $idVenue, string $imageVenue): bool
  {
    $sql = "
            UPDATE tbvenue
            SET tbvenueimage = :imageVenue
            WHERE tbvenueid = :idVenue
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':imageVenue' => $imageVenue,
      ':idVenue' => $idVenue
    ]);
  }


  // =========================================================
  // DESACTIVAR
  // =========================================================
  public function deactivateVenue(int $idVenue): bool
  {
    $sql = "
            UPDATE tbvenue
            SET tbvenueisactive = false
            WHERE tbvenueid = :idVenue
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idVenue' => $idVenue
    ]);
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function deleteVenue(int $idVenue): bool
  {
    try {
      $sql = "
                DELETE FROM tbvenue
                WHERE tbvenueid = :idVenue
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idVenue' => $idVenue
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRowToVenue(array $row): Venue
  {
    return new Venue(
      (int) $row['tbvenueid'],
      (int) $row['tbvenueownerid'],
      (int) $row['tbvenueubicationid'],
      $row['tbvenuename'],
      $row['tbvenuetype'],
      (int) $row['tbvenuecapacity'],
      $row['tbvenueimage'],
      (bool) $row['tbvenueisactive']
    );
  }
}
