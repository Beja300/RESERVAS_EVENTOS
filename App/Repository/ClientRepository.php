<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Client.php';

class ClientRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    // =========================================================
    // GUARDAR (tbrole + tbclient + tbroleclient)
    // =========================================================
    public function save(Client $client): bool
    {
        try {
            $this->connection->beginTransaction();

            $idRole = $this->insertRole($client);

            $sqlProfile = "
                INSERT INTO tbclient (
                    tbclientname,
                    tbclientimage,
                    tbclientactive
                )
                VALUES (
                    :name,
                    :image,
                    :isActive
                )
            ";
            $stmtProfile = $this->connection->prepare($sqlProfile);
            $stmtProfile->execute([
                ':name'     => $client->getName(),
                ':image'    => $client->getImageClient() ?: null,
                ':isActive' => $this->toDb($client->getIsClientActive()),
            ]);

            $idClient = (int) $this->connection->lastInsertId();

            $sqlLink = "
                INSERT INTO tbroleclient (
                    tbroleclientrolid,
                    tbroleclientclientid,
                    tbroleclientactive
                )
                VALUES (
                    :idRole,
                    :idClient,
                    :isActive
                )
            ";
            $stmtLink = $this->connection->prepare($sqlLink);
            $stmtLink->execute([
                ':idRole'   => $idRole,
                ':idClient' => $idClient,
                ':isActive' => $this->toDb($client->getIsClientActive()),
            ]);

            $client->setIdClient($idClient);
            $client->setIdRol($idRole);

            $this->connection->commit();
            return true;
        } catch (PDOException $e) {
            $this->connection->rollBack();
            return false;
        }
    }

    // =========================================================
    // BUSCAR POR EMAIL
    // =========================================================
    public function findByEmail(string $email): ?Client
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephone,
                r.tbroleactive,
                p.tbclientid,
                p.tbclientimage,
                p.tbclientlocationid,
                p.tbclientactive
            FROM tbrole r
            INNER JOIN tbroleclient c ON c.tbroleclientrolid = r.tbroleid
            INNER JOIN tbclient p ON p.tbclientid = c.tbroleclientclientid
            WHERE r.tbroleemail = :email
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    // =========================================================
    // BUSCAR POR ID DE CLIENTE (id real del perfil tbclient)
    // =========================================================
    public function findByClientPk(int $clientPk): ?Client
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephone,
                r.tbroleactive,
                p.tbclientid,
                p.tbclientimage,
                p.tbclientlocationid,
                p.tbclientactive
            FROM tbrole r
            INNER JOIN tbroleclient c ON c.tbroleclientrolid = r.tbroleid
            INNER JOIN tbclient p ON p.tbclientid = c.tbroleclientclientid
            WHERE p.tbclientid = :idClient
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':idClient' => $clientPk]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    // =========================================================
    // BUSCAR POR ID DE ROL
    // =========================================================
    public function findByRoleId(int $roleId): ?Client
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephone,
                r.tbroleactive,
                p.tbclientid,
                p.tbclientimage,
                p.tbclientlocationid,
                p.tbclientactive
            FROM tbrole r
            INNER JOIN tbroleclient c ON c.tbroleclientrolid = r.tbroleid
            INNER JOIN tbclient p ON p.tbclientid = c.tbroleclientclientid
            WHERE r.tbroleid = :idRole
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':idRole' => $roleId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    // =========================================================
    // OBTENER TODOS
    // =========================================================
    public function findAll(): array
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephone,
                r.tbroleactive,
                p.tbclientid,
                p.tbclientimage,
                p.tbclientlocationid,
                p.tbclientactive
            FROM tbrole r
            INNER JOIN tbroleclient c ON c.tbroleclientrolid = r.tbroleid
            INNER JOIN tbclient p ON p.tbclientid = c.tbroleclientclientid
            ORDER BY p.tbclientid ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        $clients = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $clients[] = $this->mapRow($row);
        }
        return $clients;
    }

    // =========================================================
    // CLIENTES NUEVOS DEL MES: clientes cuya primera reserva
    // (la más antigua) cae dentro del mes seleccionado.
    // =========================================================
    public function countNewThisMonth(string $yearMonth): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM (
                SELECT tbbookingclientid
                FROM tbbooking
                GROUP BY tbbookingclientid
                HAVING MIN(tbbookingdate) LIKE :yearMonth
            ) AS first_bookings
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':yearMonth' => $yearMonth . '%']);

        return (int) $stmt->fetchColumn();
    }

    // =========================================================
    // CLIENTES RECURRENTES DEL MES: clientes con más de una
    // reserva dentro del mes seleccionado.
    // =========================================================
    public function countRecurrentThisMonth(string $yearMonth): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM (
                SELECT tbbookingclientid
                FROM tbbooking
                WHERE tbbookingdate LIKE :yearMonth
                GROUP BY tbbookingclientid
                HAVING COUNT(*) > 1
            ) AS recurring
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':yearMonth' => $yearMonth . '%']);

        return (int) $stmt->fetchColumn();
    }

    // =========================================================
    // CLIENTES CON MÁS RESERVAS DEL MES (top por nombre)
    // =========================================================
    public function topByBookings(string $yearMonth, int $limit = 5): array
    {
        $sql = "
            SELECT
                r.tbroleid AS idRol,
                r.tbrolename AS name,
                COUNT(b.tbbookingid) AS bookingCount
            FROM tbbooking b
            INNER JOIN tbroleclient c ON c.tbroleclientclientid = b.tbbookingclientid
            INNER JOIN tbrole r ON r.tbroleid = c.tbroleclientrolid
            WHERE b.tbbookingdate LIKE :yearMonth
            GROUP BY b.tbbookingclientid, r.tbroleid, r.tbrolename
            ORDER BY bookingCount DESC
            LIMIT :limit
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':yearMonth', $yearMonth . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================
    // COMPARTIDO CON ADMIN/OWNER: insertar registro base tbrole
    // =========================================================
    private function insertRole(Client $client): int
    {
        $sqlRole = "
            INSERT INTO tbrole (
                tbrolename,
                tbroleemail,
                tbrolepassword,
                tbrolephone,
                tbroleactive
            )
            VALUES (
                :name,
                :email,
                :password,
                :phoneNumber,
                :isActive
            )
        ";

        $stmtRole = $this->connection->prepare($sqlRole);
        $stmtRole->execute([
            ':name'        => $client->getName(),
            ':email'       => $client->getEmail(),
            ':password'    => password_hash($client->getPassword(), PASSWORD_DEFAULT),
            ':phoneNumber' => $client->getPhoneNumber(),
            ':isActive'    => $this->toDb($client->getIsActive()),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    // =========================================================
    // ACTUALIZAR PERFIL (foto de perfil y ubicación)
    // =========================================================
    public function updateProfile(int $idClient, string $image, ?int $locationId): bool
    {
        $sql = "
            UPDATE tbclient
            SET
                tbclientimage = :image,
                tbclientlocationid = :locationId
            WHERE tbclientid = :idClient
        ";

        $stmt = $this->connection->prepare($sql);

        return $stmt->execute([
            ':image'      => $image !== '' ? $image : null,
            ':locationId' => $locationId,
            ':idClient'   => $idClient,
        ]);
    }

    // =========================================================
    // MAPEO FILA -> OBJETO
    // =========================================================
    private function mapRow(array $row): Client
    {
        return new Client(
            id: (int) $row['tbroleid'],
            name: $row['tbrolename'],
            email: $row['tbroleemail'],
            password: $row['tbrolepassword'],
            isActive: $this->toBool($row['tbroleactive']),
            idClient: (int) ($row['tbclientid'] ?? 0),
            isClientActive: $this->toBool($row['tbclientactive'] ?? $row['tbroleactive']),
            idRol: (int) $row['tbroleid'],
            imageClient: $row['tbclientimage'] ?? '',
            phoneNumber: $row['tbrolephone'],
            locationId: ($row['tbclientlocationid'] ?? null) !== null ? (int) $row['tbclientlocationid'] : null
        );
    }

    private function toBool(mixed $value): bool
    {
        return $value === 1 || $value === '1' || $value === true;
    }

    private function toDb(bool $value): int
    {
        return $value ? 1 : 0;
    }
}
