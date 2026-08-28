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
    // GUARDAR (tbrole + tbroleclient + tbclient)
    // =========================================================
    public function save(Client $client): bool
    {
        try {
            $this->connection->beginTransaction();

            $idRole = $this->insertRole($client);

            $sqlLink = "
                INSERT INTO tbroleclient (
                    tbroleclientrolid,
                    tbroleclientactive
                )
                VALUES (
                    :idRole,
                    :isActive
                )
            ";
            $stmtLink = $this->connection->prepare($sqlLink);
            $stmtLink->execute([
                ':idRole'   => $idRole,
                ':isActive' => $this->toDb($client->getIsClientActive()),
            ]);

            $sqlProfile = "
                INSERT INTO tbclient (
                    tbclientroleid,
                    tbclientname,
                    tbclientimage,
                    tbclientactive
                )
                VALUES (
                    :idRole,
                    :name,
                    :image,
                    :isActive
                )
            ";
            $stmtProfile = $this->connection->prepare($sqlProfile);
            $stmtProfile->execute([
                ':idRole'   => $idRole,
                ':name'     => $client->getName(),
                ':image'    => $client->getImageClient() ?: null,
                ':isActive' => $this->toDb($client->getIsClientActive()),
            ]);

            $client->setIdClient($idRole);
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
                p.tbclientactive
            FROM tbrole r
            INNER JOIN tbroleclient c ON c.tbroleclientrolid = r.tbroleid
            LEFT JOIN tbclient p ON p.tbclientroleid = r.tbroleid
            WHERE r.tbroleemail = :email
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    // =========================================================
    // BUSCAR POR ID DE CLIENTE
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
                p.tbclientactive
            FROM tbrole r
            INNER JOIN tbroleclient c ON c.tbroleclientrolid = r.tbroleid
            LEFT JOIN tbclient p ON p.tbclientroleid = r.tbroleid
            WHERE c.tbroleclientid = :idClient
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':idClient' => $clientPk]);

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
                p.tbclientactive
            FROM tbrole r
            INNER JOIN tbroleclient c ON c.tbroleclientrolid = r.tbroleid
            LEFT JOIN tbclient p ON p.tbclientroleid = r.tbroleid
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
            phoneNumber: $row['tbrolephone']
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
