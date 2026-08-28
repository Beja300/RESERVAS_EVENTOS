<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Admin.php';

class AdminRepository
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    // =========================================================
    // GUARDAR
    // =========================================================
    public function save(Admin $admin): bool
    {
        try {
            $this->connection->beginTransaction();

            $sqlRole = "
                INSERT INTO tbrole (
                    tbrolename,
                    tbroleemail,
                    tbrolepassword,
                    tbrolephonenumber,
                    tbroleisactive
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
                ':name'        => $admin->getName(),
                ':email'       => $admin->getEmail(),
                ':password'    => password_hash($admin->getPassword(), PASSWORD_DEFAULT),
                ':phoneNumber' => $admin->getPhoneNumber(),
                ':isActive'    => $admin->getIsActive()
            ]);

            // tbroleadminid comparte el mismo valor que tbroleid (PK compartida, no es FK)
            $idRole = (int) $this->connection->lastInsertId();

            $sqlAdmin = "
                INSERT INTO tbroleadmin (
                    tbroleadminid,
                    tbroleadminisactive,
                    tbroleadminrolid,
                    tbroleadminimage
                )
                VALUES (
                    :idAdmin,
                    :isAdminActive,
                    :idRol,
                    :imageAdmin
                )
            ";

            $stmtAdmin = $this->connection->prepare($sqlAdmin);

            $stmtAdmin->execute([
                ':idAdmin'       => $idRole,
                ':isAdminActive' => $admin->getIsAdminActive(),
                ':idRol'         => $admin->getIdRol(),
                ':imageAdmin'    => $admin->getImageAdmin()
            ]);

            $admin->setIdAdmin($idRole);

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
    public function findByEmail(string $email): ?Admin
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephonenumber,
                r.tbroleisactive,

                a.tbroleadminid,
                a.tbroleadminisactive,
                a.tbroleadminrolid,
                a.tbroleadminimage

            FROM tbrole r

            INNER JOIN tbroleadmin a
                ON a.tbroleadminid = r.tbroleid

            WHERE r.tbroleemail = :email

            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRow($row);
    }


    // =========================================================
    // BUSCAR POR ID DE ADMIN
    // =========================================================
    public function findByAdminPk(int $adminPk): ?Admin
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephonenumber,
                r.tbroleisactive,

                a.tbroleadminid,
                a.tbroleadminisactive,
                a.tbroleadminrolid,
                a.tbroleadminimage

            FROM tbroleadmin a

            INNER JOIN tbrole r
                ON r.tbroleid = a.tbroleadminid

            WHERE a.tbroleadminid = :idAdmin

            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':idAdmin' => $adminPk
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRow($row);
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
                r.tbrolephonenumber,
                r.tbroleisactive,

                a.tbroleadminid,
                a.tbroleadminisactive,
                a.tbroleadminrolid,
                a.tbroleadminimage

            FROM tbroleadmin a

            INNER JOIN tbrole r
                ON r.tbroleid = a.tbroleadminid

            ORDER BY a.tbroleadminid ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        $admins = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $admins[] = $this->mapRow($row);
        }

        return $admins;
    }


    // =========================================================
    // MAPEO FILA -> OBJETO
    // =========================================================
    private function mapRow(array $row): Admin
    {
        return new Admin(
            id: (int) $row['tbroleid'],
            name: $row['tbrolename'],
            email: $row['tbroleemail'],
            password: $row['tbrolepassword'],
            isActive: (bool) $row['tbroleisactive'],
            idAdmin: (int) $row['tbroleadminid'],
            isAdminActive: (bool) $row['tbroleadminisactive'],
            idRol: (int) $row['tbroleadminrolid'],
            imageAdmin: $row['tbroleadminimage'],
            phoneNumber: $row['tbrolephonenumber']
        );
    }
}
