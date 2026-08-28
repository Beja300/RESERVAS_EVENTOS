<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Location.php';

class LocationRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function save(Location $location): int
    {
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
            ':canton'   => $location->getCantonLocation(),
            ':district' => $location->getDistrictLocation(),
            ':address'  => $location->getAddressLocation()
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findById(int $idLocation): ?Location
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
        $stmt->execute([':idLocation' => $idLocation]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    public function findAll(): array
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

        return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function mapRow(array $row): Location
    {
        return new Location(
            idLocation: (int) $row['tblocationid'],
            provinceLocation: $row['tblocationprovince'],
            cantonLocation: $row['tblocationcanton'],
            districtLocation: $row['tblocationdistrict'],
            addressLocation: $row['tblocationaddress']
        );
    }
}
