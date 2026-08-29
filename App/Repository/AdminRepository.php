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
    // GUARDAR (tbrole + tbroleadmin + tbadmin)
    // =========================================================
    public function save(Admin $admin): bool
    {
        try {
            $this->connection->beginTransaction();

            $idRole = $this->insertRole($admin);

            // Tabla intermedia rol <-> admin
            $sqlLink = "
                INSERT INTO tbroleadmin (
                    tbroleadminrolid,
                    tbroleadminactive
                )
                VALUES (
                    :idRole,
                    :isActive
                )
            ";
            $stmtLink = $this->connection->prepare($sqlLink);
            $stmtLink->execute([
                ':idRole'   => $idRole,
                ':isActive' => $this->toDb($admin->getIsAdminActive()),
            ]);

            // Perfil propio del administrador
            $sqlProfile = "
                INSERT INTO tbadmin (
                    tbadminroleid,
                    tbadminname,
                    tbadminimage,
                    tbadminactive
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
                ':name'     => $admin->getName(),
                ':image'    => $admin->getImageAdmin() ?: null,
                ':isActive' => $this->toDb($admin->getIsAdminActive()),
            ]);

            $admin->setIdAdmin($idRole);
            $admin->setIdRol($idRole);

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
                r.tbrolephone,
                r.tbroleactive,
                p.tbadminid,
                p.tbadminimage,
                p.tbadminactive
            FROM tbrole r
            INNER JOIN tbroleadmin a ON a.tbroleadminrolid = r.tbroleid
            LEFT JOIN tbadmin p ON p.tbadminroleid = r.tbroleid
            WHERE r.tbroleemail = :email
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
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
                r.tbrolephone,
                r.tbroleactive,
                p.tbadminid,
                p.tbadminimage,
                p.tbadminactive
            FROM tbrole r
            INNER JOIN tbroleadmin a ON a.tbroleadminrolid = r.tbroleid
            LEFT JOIN tbadmin p ON p.tbadminroleid = r.tbroleid
            WHERE a.tbroleadminid = :idAdmin
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':idAdmin' => $adminPk]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

    // =========================================================
    // BUSCAR POR ID DE ROL
    // =========================================================
    public function findByRoleId(int $roleId): ?Admin
    {
        $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephone,
                r.tbroleactive,
                p.tbadminid,
                p.tbadminimage,
                p.tbadminactive
            FROM tbrole r
            INNER JOIN tbroleadmin a ON a.tbroleadminrolid = r.tbroleid
            LEFT JOIN tbadmin p ON p.tbadminroleid = r.tbroleid
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
                p.tbadminid,
                p.tbadminimage,
                p.tbadminactive
            FROM tbrole r
            INNER JOIN tbroleadmin a ON a.tbroleadminrolid = r.tbroleid
            LEFT JOIN tbadmin p ON p.tbadminroleid = r.tbroleid
            ORDER BY p.tbadminid ASC
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
    // COMPARTIDO CON CLIENT/OWNER: insertar registro base tbrole
    // =========================================================
    private function insertRole(Admin $admin): int
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
            ':name'        => $admin->getName(),
            ':email'       => $admin->getEmail(),
            ':password'    => password_hash($admin->getPassword(), PASSWORD_DEFAULT),
            ':phoneNumber' => $admin->getPhoneNumber(),
            ':isActive'    => $this->toDb($admin->getIsActive()),
        ]);

        return (int) $this->connection->lastInsertId();
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
            isActive: $this->toBool($row['tbroleactive']),
            idAdmin: (int) ($row['tbadminid'] ?? 0),
            isAdminActive: $this->toBool($row['tbadminactive'] ?? $row['tbroleactive']),
            idRol: (int) $row['tbroleid'],
            imageAdmin: $row['tbadminimage'] ?? '',
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
