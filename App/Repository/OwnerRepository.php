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
    // GUARDAR (tbrole + tbowner + tbroleowner)
    // =========================================================
    public function save(Owner $owner): bool
    {
        try {
            $this->connection->beginTransaction();

            $idRole = $this->insertRole($owner);

            $sqlProfile = "
                INSERT INTO tbowner (
                    tbownerfirstname,
                    tbownerlastname,
                    tbowneralias,
                    tbowneridentificationnumber,
                    tbownerimage,
                    tbowneractive
                )
                VALUES (
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
                ':firstName'             => $owner->getFirstNameOwner(),
                ':lastName'              => $owner->getLastNameOwner() !== '' ? $owner->getLastNameOwner() : null,
                ':alias'                 => $owner->getAliasOwner() !== '' ? $owner->getAliasOwner() : null,
                ':identificationNumber'  => $owner->getIdentificationNumberOwner() !== '' ? $owner->getIdentificationNumberOwner() : null,
                ':image'                 => $owner->getImageOwner() ?: null,
                ':isActive'              => $this->toDb($owner->getIsOwnerActive()),
            ]);

            $idOwner = (int) $this->connection->lastInsertId();

            $sqlLink = "
                INSERT INTO tbroleowner (
                    tbroleownerrolid,
                    tbroleownerownerid,
                    tbroleowneractive
                )
                VALUES (
                    :idRole,
                    :idOwner,
                    :isActive
                )
            ";
            $stmtLink = $this->connection->prepare($sqlLink);
            $stmtLink->execute([
                ':idRole'   => $idRole,
                ':idOwner'  => $idOwner,
                ':isActive' => $this->toDb($owner->getIsOwnerActive()),
            ]);

            $owner->setIdOwner($idOwner);
            $owner->setIdRol($idRole);

            $this->connection->commit();
            return true;
        } catch (PDOException $e) {
            $this->connection->rollBack();
            return false;
        }
    }

    // =========================================================
    // ACTUALIZAR PERFIL DEL PROPIETARIO (tbowner)
    // Recibe un Owner ya actualizado; sincroniza tbownerfirstname
    // con el nombre base (tbrole) y actualiza sus datos propios.
    // =========================================================
    public function updateProfile(Owner $owner): bool
    {
        $sql = "
            UPDATE tbowner
            SET
                tbownerfirstname = :firstName,
                tbownerlastname = :lastName,
                tbowneralias = :alias,
                tbowneridentificationnumber = :identificationNumber,
                tbownerimage = :image
            WHERE tbownerid = :idOwner
        ";

        $stmt = $this->connection->prepare($sql);

        $ownerImage = $owner->getImageOwner();

        return $stmt->execute([
            ':firstName'            => $owner->getFirstNameOwner(),
            ':lastName'             => $owner->getLastNameOwner() !== '' ? $owner->getLastNameOwner() : null,
            ':alias'                => $owner->getAliasOwner() !== '' ? $owner->getAliasOwner() : null,
            ':identificationNumber' => $owner->getIdentificationNumberOwner() !== '' ? $owner->getIdentificationNumberOwner() : null,
            ':image'                => $ownerImage !== '' ? $ownerImage : null,
            ':idOwner'              => $owner->getIdOwner(),
        ]);
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
            INNER JOIN tbowner p ON p.tbownerid = o.tbroleownerownerid
            WHERE r.tbroleemail = :email
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    // =========================================================
    // BUSCAR POR ID DE OWNER (id real del perfil tbowner)
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
            INNER JOIN tbowner p ON p.tbownerid = o.tbroleownerownerid
            WHERE p.tbownerid = :idOwner
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':idOwner' => $ownerPk]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    // =========================================================
    // BUSCAR POR ID DE ROL
    // =========================================================
    public function findByRoleId(int $roleId): ?Owner
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
            INNER JOIN tbowner p ON p.tbownerid = o.tbroleownerownerid
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
                p.tbownerid,
                p.tbownerfirstname,
                p.tbownerlastname,
                p.tbowneralias,
                p.tbowneridentificationnumber,
                p.tbownerimage,
                p.tbowneractive
            FROM tbrole r
            INNER JOIN tbroleowner o ON o.tbroleownerrolid = r.tbroleid
            INNER JOIN tbowner p ON p.tbownerid = o.tbroleownerownerid
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
            INNER JOIN tbowner p ON p.tbownerid = o.tbroleownerownerid
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
