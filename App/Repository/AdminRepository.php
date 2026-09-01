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
    // GUARDAR (tbrole + tbadmin + tbroleadmin)
    // =========================================================
    public function save(Admin $admin): bool
    {
        try {
            $this->connection->beginTransaction();

            $idRole = $this->insertRole($admin);

            // Perfil propio del administrador
            $sqlProfile = "
                INSERT INTO tbadmin (
                    tbadminname,
                    tbadminimage,
                    tbadminactive
                )
                VALUES (
                    :name,
                    :image,
                    :isActive
                )
            ";
            $stmtProfile = $this->connection->prepare($sqlProfile);
            $stmtProfile->execute([
                ':name'     => $admin->getName(),
                ':image'    => $admin->getImageAdmin() ?: null,
                ':isActive' => $this->toDb($admin->getIsAdminActive()),
            ]);

            $idAdmin = (int) $this->connection->lastInsertId();

            // Tabla intermedia rol <-> admin
            $sqlLink = "
                INSERT INTO tbroleadmin (
                    tbroleadminrolid,
                    tbroleadminadminid,
                    tbroleadminactive
                )
                VALUES (
                    :idRole,
                    :idAdmin,
                    :isActive
                )
            ";
            $stmtLink = $this->connection->prepare($sqlLink);
            $stmtLink->execute([
                ':idRole'   => $idRole,
                ':idAdmin'  => $idAdmin,
                ':isActive' => $this->toDb($admin->getIsAdminActive()),
            ]);

            $admin->setIdAdmin($idAdmin);
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
            INNER JOIN tbadmin p ON p.tbadminid = a.tbroleadminadminid
            WHERE r.tbroleemail = :email
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([':email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->mapRow($row) : null;
    }

// =========================================================
    // BUSCAR POR ID DE ADMIN (id real del perfil tbadmin)
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
            INNER JOIN tbadmin p ON p.tbadminid = a.tbroleadminadminid
            WHERE p.tbadminid = :idAdmin
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
            INNER JOIN tbadmin p ON p.tbadminid = a.tbroleadminadminid
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
            INNER JOIN tbadmin p ON p.tbadminid = a.tbroleadminadminid
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
    // ACTUALIZAR PERFIL DEL ADMINISTRADOR (tbadmin)
    // Recibe un Admin ya actualizado; sincroniza tbadminname
    // con el nombre base (tbrole) y actualiza su imagen propia.
    // =========================================================
    public function updateProfile(Admin $admin): bool
    {
        $sql = "
            UPDATE tbadmin
            SET
                tbadminname = :name,
                tbadminimage = :image
            WHERE tbadminid = :idAdmin
        ";

        $stmt = $this->connection->prepare($sql);

        $adminImage = $admin->getImageAdmin();

        return $stmt->execute([
            ':name'     => $admin->getName(),
            ':image'    => $adminImage !== '' ? $adminImage : null,
            ':idAdmin'  => $admin->getIdAdmin(),
        ]);
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
