<?php

require_once __DIR__ . '/../models/Admin.php';

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

            // Primero guardamos los datos que vienen de Role
            $sqlRole = "
                INSERT INTO role (
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
                ':name' => $admin->getName(),
                ':email' => $admin->getEmail(),
                ':password' => password_hash($admin->getPassword(), PASSWORD_DEFAULT),
                ':phoneNumber' => $admin->getPhoneNumber(),
                ':isActive' => $admin->getIsActive()
            ]);

            // El id que genera `role` es el mismo que usamos como tbroleadminid
            // (no hay FK real, la relación es 1:1 por PK compartida)
            $idRole = (int) $this->connection->lastInsertId();

            // Después guardamos los datos propios de Admin
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
                ':idAdmin' => $idRole,
                ':isAdminActive' => $admin->getIsAdminActive(),
                ':idRol' => $admin->getIdRol(),
                ':imageAdmin' => $admin->getImageAdmin()
            ]);

            // Reflejamos el id generado en el objeto para que el caller lo tenga
            $admin->setIdRole($idRole);
            $admin->setIdAdmin($idRole);

            $this->connection->commit();

            return true;
        } catch (PDOException $e) {

            $this->connection->rollBack();

            return false;
        }
    }


    // =========================================================
    // OBTENER TODOS
    // =========================================================
    public function getAll(): array
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

            INNER JOIN role r
                ON a.tbroleadminid = r.tbroleid

            ORDER BY a.tbroleadminid ASC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        $admins = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $admins[] = $this->mapRowToAdmin($row);
        }

        return $admins;
    }


    // =========================================================
    // OBTENER POR ID
    // =========================================================
    public function getById(int $idAdmin): ?Admin
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

            INNER JOIN role r
                ON a.tbroleadminid = r.tbroleid

            WHERE a.tbroleadminid = :idAdmin
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->execute([
            ':idAdmin' => $idAdmin
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->mapRowToAdmin($row);
    }


    // =========================================================
    // EDITAR
    // =========================================================
    public function update(Admin $admin): bool
    {
        try {
            $this->connection->beginTransaction();

            // Actualizar datos de Role
            $sqlRole = "
                UPDATE role
                SET
                    tbrolename = :name,
                    tbroleemail = :email,
                    tbrolepassword = :password,
                    tbrolephonenumber = :phoneNumber,
                    tbroleisactive = :isActive
                WHERE tbroleid = :id
            ";

            $stmtRole = $this->connection->prepare($sqlRole);

            $stmtRole->execute([
                ':name' => $admin->getName(),
                ':email' => $admin->getEmail(),
                ':password' => $admin->getPassword(),
                ':phoneNumber' => $admin->getPhoneNumber(),
                ':isActive' => $admin->getIsActive(),
                ':id' => $admin->getIdRole()
            ]);

            // Actualizar datos propios de Admin
            $sqlAdmin = "
                UPDATE tbroleadmin
                SET
                    tbroleadminisactive = :isAdminActive,
                    tbroleadminrolid = :idRol,
                    tbroleadminimage = :imageAdmin
                WHERE tbroleadminid = :idAdmin
            ";

            $stmtAdmin = $this->connection->prepare($sqlAdmin);

            $stmtAdmin->execute([
                ':isAdminActive' => $admin->getIsAdminActive(),
                ':idRol' => $admin->getIdRol(),
                ':imageAdmin' => $admin->getImageAdmin(),
                ':idAdmin' => $admin->getIdAdmin()
            ]);

            $this->connection->commit();

            return true;
        } catch (PDOException $e) {

            $this->connection->rollBack();

            return false;
        }
    }


    // =========================================================
    // DESACTIVAR
    // =========================================================
    public function deactivate(int $idAdmin): bool
    {
        try {
            $this->connection->beginTransaction();

            $sqlAdmin = "
                UPDATE tbroleadmin
                SET tbroleadminisactive = false
                WHERE tbroleadminid = :idAdmin
            ";

            $stmtAdmin = $this->connection->prepare($sqlAdmin);
            $stmtAdmin->execute([
                ':idAdmin' => $idAdmin
            ]);

            // Reflejamos el mismo estado en role para que ambos lados queden alineados
            $sqlRole = "
                UPDATE role
                SET tbroleisactive = false
                WHERE tbroleid = :id
            ";

            $stmtRole = $this->connection->prepare($sqlRole);
            $stmtRole->execute([
                ':id' => $idAdmin
            ]);

            $this->connection->commit();

            return true;
        } catch (PDOException $e) {

            $this->connection->rollBack();

            return false;
        }
    }


    // =========================================================
    // ELIMINAR
    // =========================================================
    public function delete(int $idAdmin): bool
    {
        try {

            $this->connection->beginTransaction();

            // Primero eliminamos de tbroleadmin
            $sqlAdmin = "
                DELETE FROM tbroleadmin
                WHERE tbroleadminid = :idAdmin
            ";

            $stmtAdmin = $this->connection->prepare($sqlAdmin);

            $stmtAdmin->execute([
                ':idAdmin' => $idAdmin
            ]);

            // Después eliminamos de Role
            $sqlRole = "
                DELETE FROM role
                WHERE tbroleid = :id
            ";

            $stmtRole = $this->connection->prepare($sqlRole);

            $stmtRole->execute([
                ':id' => $idAdmin
            ]);

            $this->connection->commit();

            return true;
        } catch (PDOException $e) {

            $this->connection->rollBack();

            return false;
        }
    }


    // =========================================================
    // MAPEO FILA -> OBJETO
    // =========================================================
    private function mapRowToAdmin(array $row): Admin
    {
        return new Admin(
            (int) $row['tbroleid'],
            $row['tbrolename'],
            $row['tbroleemail'],
            $row['tbrolepassword'],
            (bool) $row['tbroleisactive'],
            (int) $row['tbroleadminid'],
            (bool) $row['tbroleadminisactive'],
            (int) $row['tbroleadminrolid'],
            $row['tbroleadminimage'],
            $row['tbrolephonenumber']
        );
    }
}
