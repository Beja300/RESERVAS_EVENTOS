<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Owner.php';

class OwnerRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    // =========================================================
    // GUARDAR (tbrole + tbroleowner + tbowner)
    // =========================================================
    public function save(Owner $owner): bool
    {
        try {
            $this->connection->beginTransaction();

            $idRole = $this->insertRole($owner);

            $sqlLink = "
                INSERT INTO tbroleowner (
                    tbroleownerrolid,
                    tbroleowneractive
                )
                VALUES (
                    :idRole,
                    :isActive
                )
            ";
            $stmtLink = $this->connection->prepare($sqlLink);
            $stmtLink->execute([
                ':idRole'   => $idRole,
                ':isActive' => $this->toDb($owner->getIsOwnerActive()),
            ]);

            $sqlProfile = "
                INSERT INTO tbowner (
                    tbownerroleid,
                    tbownerfirstname,
                    tbownerlastname,
                    tbowneralias,
                    tbowneridentificationnumber,
                    tbownerimage,
                    tbowneractive
                )
                VALUES (
                    :idRole,
                    :firstName,
                    :lastName,
                    :alias,
                    :identificationNumber,
                    :image,
                    :isActive
                )
            ";
            $stmtProfile = $this->connection->prepare($sqlProfile);
            $stmtProfile->execute([
                ':idRole'                => $idRole,
                ':firstName'             => $owner->getFirstNameOwner(),
                ':lastName'              => $owner->getLastNameOwner() !== '' ? $owner->getLastNameOwner() : null,
                ':alias'                 => $owner->getAliasOwner() !== '' ? $owner->getAliasOwner() : null,
                ':identificationNumber'  => $owner->getIdentificationNumberOwner() !== '' ? $owner->getIdentificationNumberOwner() : null,
                ':image'                 => $owner->getImageOwner() ?: null,
                ':isActive'              => $this->toDb($owner->getIsOwnerActive()),
            ]);

            $owner->setIdOwner($idRole);
            $owner->setIdRol($idRole);

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
    public function findByEmail(string $email): ?Owner
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephone,
                r.tbroleactive,
                p.tbownerid,
                p.tbownerfirstname,
                p.tbownerlastname,
                p.tbowneralias,
                p.tbowneridentificationnumber,
                p.tbownerimage,
                p.tbowneractive
            FROM tbrole r
            INNER JOIN tbroleowner o ON o.tbroleownerrolid = r.tbroleid
            LEFT JOIN tbowner p ON p.tbownerroleid = r.tbroleid
            WHERE r.tbroleemail = :email
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    // =========================================================
    // BUSCAR POR ID DE OWNER
    // =========================================================
    public function findByOwnerPk(int $ownerPk): ?Owner
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephone,
                r.tbroleactive,
                p.tbownerid,
                p.tbownerfirstname,
                p.tbownerlastname,
                p.tbowneralias,
                p.tbowneridentificationnumber,
                p.tbownerimage,
                p.tbowneractive
            FROM tbrole r
            INNER JOIN tbroleowner o ON o.tbroleownerrolid = r.tbroleid
            LEFT JOIN tbowner p ON p.tbownerroleid = r.tbroleid
            WHERE o.tbroleownerid = :idOwner
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':idOwner' => $ownerPk]);

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
                p.tbownerid,
                p.tbownerfirstname,
                p.tbownerlastname,
                p.tbowneralias,
                p.tbowneridentificationnumber,
                p.tbownerimage,
                p.tbowneractive
            FROM tbrole r
            INNER JOIN tbroleowner o ON o.tbroleownerrolid = r.tbroleid
            LEFT JOIN tbowner p ON p.tbownerroleid = r.tbroleid
            ORDER BY p.tbownerid ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        $owners = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $owners[] = $this->mapRow($row);
        }
        return $owners;
    }

    // =========================================================
    // BUSCAR POR NÚMERO DE IDENTIFICACIÓN (único en tbowner)
    // =========================================================
    public function findByIdentificationNumber(string $identificationNumber): ?Owner
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephone,
                r.tbroleactive,
                p.tbownerid,
                p.tbownerfirstname,
                p.tbownerlastname,
                p.tbowneralias,
                p.tbowneridentificationnumber,
                p.tbownerimage,
                p.tbowneractive
            FROM tbrole r
            INNER JOIN tbroleowner o ON o.tbroleownerrolid = r.tbroleid
            LEFT JOIN tbowner p ON p.tbownerroleid = r.tbroleid
            WHERE p.tbowneridentificationnumber = :identificationNumber
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':identificationNumber' => $identificationNumber]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    // =========================================================
    // COMPARTIDO CON ADMIN/CLIENT: insertar registro base tbrole
    // =========================================================
    private function insertRole(Owner $owner): int
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
            ':name'        => $owner->getName(),
            ':email'       => $owner->getEmail(),
            ':password'    => password_hash($owner->getPassword(), PASSWORD_DEFAULT),
            ':phoneNumber' => $owner->getPhoneNumber(),
            ':isActive'    => $this->toDb($owner->getIsActive()),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    // =========================================================
    // MAPEO FILA -> OBJETO
    // =========================================================
    private function mapRow(array $row): Owner
    {
        return new Owner(
            id: (int) $row['tbroleid'],
            name: $row['tbrolename'],
            email: $row['tbroleemail'],
            password: $row['tbrolepassword'],
            isActive: $this->toBool($row['tbroleactive']),
            idOwner: (int) ($row['tbownerid'] ?? 0),
            lastName: $row['tbownerlastname'] ?? '',
            alias: $row['tbowneralias'] ?? '',
            identificationNumber: $row['tbowneridentificationnumber'] ?? '',
            isOwnerActive: $this->toBool($row['tbowneractive'] ?? $row['tbroleactive']),
            idRol: (int) $row['tbroleid'],
            imageOwner: $row['tbownerimage'] ?? '',
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
