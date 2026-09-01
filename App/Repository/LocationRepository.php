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
                tblocationtown,
                tblocationdescription
            )
            VALUES (
                :province,
                :canton,
                :district,
                :town,
                :description
            )
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':province'     => $location->getProvinceLocation(),
            ':canton'       => $location->getCantonLocation(),
            ':district'     => $location->getDistrictLocation(),
            ':town'         => $location->getTownLocation(),
            ':description'  => $location->getDescriptionLocation(),
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
                tblocationtown,
                tblocationdescription
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
                tblocationtown,
                tblocationdescription
            FROM tblocation
            ORDER BY tblocationid ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        return array_map([$this, 'mapRow'], $stmt->fetchAll());
    }

    // =========================================================
    // BUSCAR ID POR PROVINCIA/CANTÓN/DISTRITO (para historial)
    // =========================================================
    public function findIdByParts(string $province, string $canton, string $district): ?int
    {
        $sql = "
            SELECT tblocationid
            FROM tblocation
            WHERE tblocationprovince = :province
              AND tblocationcanton = :canton
              AND tblocationdistrict = :district
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':province' => $province,
            ':canton'   => $canton,
            ':district' => $district,
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    // =========================================================
    // BUSCAR ID SOLO POR PROVINCIA/CANTÓN (para ubicación detectada)
    // =========================================================
    public function findIdByCanton(string $province, string $canton): ?int
    {
        $sql = "
            SELECT tblocationid
            FROM tblocation
            WHERE tblocationprovince = :province
              AND tblocationcanton = :canton
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            ':province' => $province,
            ':canton'   => $canton,
        ]);

        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    private function mapRow(array $row): Location
    {
        return new Location(
            idLocation: (int) $row['tblocationid'],
            provinceLocation: $row['tblocationprovince'],
            cantonLocation: $row['tblocationcanton'],
            districtLocation: $row['tblocationdistrict'],
            townLocation: $row['tblocationtown'],
            descriptionLocation: $row['tblocationdescription']
        );
    }
}
