<?php

require_once __DIR__ . '/../models/Owner.php';

class OwnerRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function saveOwner(Owner $owner): bool
  {
    try {
      $this->connection->beginTransaction();

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
        ':name' => $owner->getName(),
        ':email' => $owner->getEmail(),
        ':password' => $owner->getPassword(),
        ':phoneNumber' => $owner->getPhoneNumber(),
        ':isActive' => $owner->getIsActive()
      ]);

      $idRole = (int) $this->connection->lastInsertId();

      $sqlOwner = "
                INSERT INTO tbroleowner (
                    tbroleownerid,
                    tbroleownerfirstname,
                    tbroleownerlastname,
                    tbroleowneralias,
                    tbroleowneridentificationnumber,
                    tbroleownerisactive,
                    tbroleownerrolid,
                    tbroleownerimage
                )
                VALUES (
                    :idOwner,
                    :firstNameOwner,
                    :lastNameOwner,
                    :aliasOwner,
                    :identificationNumberOwner,
                    :isOwnerActive,
                    :idRol,
                    :imageOwner
                )
            ";

      $stmtOwner = $this->connection->prepare($sqlOwner);

      $stmtOwner->execute([
        ':idOwner' => $idRole,
        ':firstNameOwner' => $owner->getFirstNameOwner(),
        ':lastNameOwner' => $owner->getLastNameOwner(),
        ':aliasOwner' => $owner->getAliasOwner(),
        ':identificationNumberOwner' => $owner->getIdentificationNumberOwner(),
        ':isOwnerActive' => $owner->getIsOwnerActive(),
        ':idRol' => $owner->getIdRol(),
        ':imageOwner' => $owner->getImageOwner()
      ]);

      $owner->setIdRole($idRole);
      $owner->setIdOwner($idRole);

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
  public function getAllOwner(): array
  {
    $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephonenumber,
                r.tbroleisactive,

                o.tbroleownerid,
                o.tbroleownerfirstname,
                o.tbroleownerlastname,
                o.tbroleowneralias,
                o.tbroleowneridentificationnumber,
                o.tbroleownerisactive,
                o.tbroleownerrolid,
                o.tbroleownerimage

            FROM tbroleowner o

            INNER JOIN role r
                ON o.tbroleownerid = r.tbroleid

            ORDER BY o.tbroleownerid ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $owners = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $owners[] = $this->mapRowToOwner($row);
    }

    return $owners;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdOwner(int $idOwner): ?Owner
  {
    $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephonenumber,
                r.tbroleisactive,

                o.tbroleownerid,
                o.tbroleownerfirstname,
                o.tbroleownerlastname,
                o.tbroleowneralias,
                o.tbroleowneridentificationnumber,
                o.tbroleownerisactive,
                o.tbroleownerrolid,
                o.tbroleownerimage

            FROM tbroleowner o

            INNER JOIN role r
                ON o.tbroleownerid = r.tbroleid

            WHERE o.tbroleownerid = :idOwner
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner' => $idOwner
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToOwner($row);
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updateOwner(Owner $owner): bool
  {
    try {
      $this->connection->beginTransaction();

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
        ':name' => $owner->getName(),
        ':email' => $owner->getEmail(),
        ':password' => $owner->getPassword(),
        ':phoneNumber' => $owner->getPhoneNumber(),
        ':isActive' => $owner->getIsActive(),
        ':id' => $owner->getIdRole()
      ]);

      $sqlOwner = "
                UPDATE tbroleowner
                SET
                    tbroleownerfirstname = :firstNameOwner,
                    tbroleownerlastname = :lastNameOwner,
                    tbroleowneralias = :aliasOwner,
                    tbroleowneridentificationnumber = :identificationNumberOwner,
                    tbroleownerisactive = :isOwnerActive,
                    tbroleownerrolid = :idRol,
                    tbroleownerimage = :imageOwner
                WHERE tbroleownerid = :idOwner
            ";

      $stmtOwner = $this->connection->prepare($sqlOwner);

      $stmtOwner->execute([
        ':firstNameOwner' => $owner->getFirstNameOwner(),
        ':lastNameOwner' => $owner->getLastNameOwner(),
        ':aliasOwner' => $owner->getAliasOwner(),
        ':identificationNumberOwner' => $owner->getIdentificationNumberOwner(),
        ':isOwnerActive' => $owner->getIsOwnerActive(),
        ':idRol' => $owner->getIdRol(),
        ':imageOwner' => $owner->getImageOwner(),
        ':idOwner' => $owner->getIdOwner()
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
  public function deactivateOwner(int $idOwner): bool
  {
    try {
      $this->connection->beginTransaction();

      $sqlOwner = "
                UPDATE tbroleowner
                SET tbroleownerisactive = false
                WHERE tbroleownerid = :idOwner
            ";

      $stmtOwner = $this->connection->prepare($sqlOwner);
      $stmtOwner->execute([
        ':idOwner' => $idOwner
      ]);

      $sqlRole = "
                UPDATE role
                SET tbroleisactive = false
                WHERE tbroleid = :id
            ";

      $stmtRole = $this->connection->prepare($sqlRole);
      $stmtRole->execute([
        ':id' => $idOwner
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
  public function deleteOwner(int $idOwner): bool
  {
    try {

      $this->connection->beginTransaction();

      $sqlOwner = "
                DELETE FROM tbroleowner
                WHERE tbroleownerid = :idOwner
            ";

      $stmtOwner = $this->connection->prepare($sqlOwner);

      $stmtOwner->execute([
        ':idOwner' => $idOwner
      ]);

      $sqlRole = "
                DELETE FROM role
                WHERE tbroleid = :id
            ";

      $stmtRole = $this->connection->prepare($sqlRole);

      $stmtRole->execute([
        ':id' => $idOwner
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
  private function mapRowToOwner(array $row): Owner
  {
    return new Owner(
      (int) $row['tbroleid'],
      $row['tbrolename'],
      $row['tbroleemail'],
      $row['tbrolepassword'],
      (bool) $row['tbroleisactive'],
      (int) $row['tbroleownerid'],
      $row['tbroleownerfirstname'],
      $row['tbroleownerlastname'],
      $row['tbroleowneralias'],
      $row['tbroleowneridentificationnumber'],
      (bool) $row['tbroleownerisactive'],
      (int) $row['tbroleownerrolid'],
      $row['tbroleownerimage'],
      $row['tbrolephonenumber']
    );
  }
}
