<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Role.php';

/**
 * REPOSITORIO: RoleRepository
 *
 * Traduce entre objetos Role y filas de la tabla tbrole.
 * Es la base que usan los repositorios de los subtipos
 * (AdminRepository, ClientRepository, OwnerRepository)
 * cuando necesitan crear primero el registro base en tbrole.
 */
class RoleRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // Devuelve el id generado (no bool) porque los subtipos
  // lo necesitan para insertar su propia fila con el mismo id.
  // =========================================================
  public function save(Role $role): int
  {
    $sql = "
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

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':name'        => $role->getName(),
      ':email'       => $role->getEmail(),
      ':password'    => password_hash($role->getPassword(), PASSWORD_DEFAULT),
      ':phoneNumber' => $role->getPhoneNumber(),
      ':isActive'    => $this->toDb($role->getIsActive())
    ]);

    // lastInsertId() devuelve el AUTO_INCREMENT que la base
    // acaba de generar -- es el tbroleid del nuevo registro.
    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // BUSCAR POR EMAIL
  // =========================================================
  public function findByEmail(string $email): ?Role
  {
    $sql = "
            SELECT
                tbroleid,
                tbrolename,
                tbroleemail,
                tbrolepassword,
                tbrolephone,
                tbroleactive

            FROM tbrole

            WHERE tbroleemail = :email

            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':email' => $email
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }


  // =========================================================
  // BUSCAR POR ID
  // =========================================================
  public function findById(int $idRole): ?Role
  {
    $sql = "
            SELECT
                tbroleid,
                tbrolename,
                tbroleemail,
                tbrolepassword,
                tbrolephone,
                tbroleactive

            FROM tbrole

            WHERE tbroleid = :idRole

            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idRole' => $idRole
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }


  // =========================================================
  // ACTUALIZAR CAMPOS BASE (nombre, correo, teléfono)
  // NO toca la contraseña -- eso se maneja aparte, con su propio
  // flujo de verificación, para no exponerlo en un formulario
  // de perfil normal.
  // =========================================================
  public function update(Role $role): bool
  {
    $sql = "
            UPDATE tbrole
            SET
                tbrolename = :name,
                tbroleemail = :email,
                tbrolephone = :phoneNumber
            WHERE tbroleid = :idRole
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':name'        => $role->getName(),
      ':email'       => $role->getEmail(),
      ':phoneNumber' => $role->getPhoneNumber(),
      ':idRole'      => $role->getIdRole()
    ]);
  }


  // =========================================================
  // ACTUALIZAR CONTRASEÑA (solo si se pide explícitamente)
  // =========================================================
  public function updatePassword(int $idRole, string $password): bool
  {
    $sql = "
            UPDATE tbrole
            SET tbrolepassword = :password
            WHERE tbroleid = :idRole
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':password' => password_hash($password, PASSWORD_DEFAULT),
      ':idRole'   => $idRole
    ]);
  }


  // =========================================================
  // ACTIVAR / DESACTIVAR CUENTA (controla el login)
  // La usan AdminService/ClientService/OwnerService antes de
  // tocar sus propias tablas de subtipo.
  // =========================================================
  public function setActive(int $idRole, bool $isActive): bool
  {
    $sql = "
            UPDATE tbrole
            SET tbroleactive = :isActive
            WHERE tbroleid = :idRole
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':isActive' => $this->toDb($isActive),
      ':idRole'   => $idRole
    ]);
  }


  // =========================================================
  // CONTAR CUENTAS ACTIVAS
  // =========================================================
  public function countActive(): int
  {
    $sql = "
            SELECT COUNT(*)
            FROM tbrole
            WHERE tbroleactive = true
        ";

    $stmt = $this->connection->query($sql);

    return (int) $stmt->fetchColumn();
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function delete(int $idRole): bool
  {
    $sql = "
            DELETE FROM tbrole
            WHERE tbroleid = :idRole
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idRole' => $idRole
    ]);
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Role
  {
    return new Role(
      idRole: (int) $row['tbroleid'],
      name: $row['tbrolename'],
      email: $row['tbroleemail'],
      password: $row['tbrolepassword'],
      phoneNumber: $row['tbrolephone'],
      isActive: $this->toBool($row['tbroleactive'])
    );
  }

  // Convierte un valor de BBDD (0/1/'0'/'1') a boolean correcto.
  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }

  private function toDb(bool $value): int
  {
    return $value ? 1 : 0;
  }
}
