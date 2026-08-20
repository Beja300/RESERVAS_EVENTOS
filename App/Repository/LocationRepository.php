<?php

require_once __DIR__ . '/../models/Location.php';

class LocationRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function saveLocation(Location $location): bool
  {
    try {
      $sql = "
                INSERT INTO tblocation (
                    tblocationprovince,
                    tblocationcanton,
                    tblocationdistrict,
                    tblocationaddress
                )
                VALUES (
                    :province,
                    :canton,
                    :district,
                    :address
                )
            ";

      $stmt = $this->connection->prepare($sql);

      $stmt->execute([
        ':province' => $location->getProvinceLocation(),
        ':canton' => $location->getCantonLocation(),
        ':district' => $location->getDistrictLocation(),
        ':address' => $location->getAddressLocation()
      ]);

      $location->setIdLocation((int) $this->connection->lastInsertId());

      return true;
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // OBTENER TODOS
  // =========================================================
  public function getAllLocation(): array
  {
    $sql = "
            SELECT
                tblocationid,
                tblocationprovince,
                tblocationcanton,
                tblocationdistrict,
                tblocationaddress

            FROM tblocation

            ORDER BY tblocationid ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $locations = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $locations[] = $this->mapRowToLocation($row);
    }

    return $locations;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdLocation(int $idLocation): ?Location
  {
    $sql = "
            SELECT
                tblocationid,
                tblocationprovince,
                tblocationcanton,
                tblocationdistrict,
                tblocationaddress

            FROM tblocation

            WHERE tblocationid = :idLocation
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idLocation' => $idLocation
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToLocation($row);
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updateLocation(Location $location): bool
  {
    try {
      $sql = "
                UPDATE tblocation
                SET
                    tblocationprovince = :province,
                    tblocationcanton = :canton,
                    tblocationdistrict = :district,
                    tblocationaddress = :address
                WHERE tblocationid = :idLocation
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':province' => $location->getProvinceLocation(),
        ':canton' => $location->getCantonLocation(),
        ':district' => $location->getDistrictLocation(),
        ':address' => $location->getAddressLocation(),
        ':idLocation' => $location->getIdLocation()
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function deleteLocation(int $idLocation): bool
  {
    try {
      $sql = "
                DELETE FROM tblocation
                WHERE tblocationid = :idLocation
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idLocation' => $idLocation
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRowToLocation(array $row): Location
  {
    return new Location(
      (int) $row['tblocationid'],
      $row['tblocationprovince'],
      $row['tblocationcanton'],
      $row['tblocationdistrict'],
      $row['tblocationaddress']
    );
  }
}
