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
  // GUARDAR
  // =========================================================
  public function save(Owner $owner): bool
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
        ':name'        => $owner->getName(),
        ':email'       => $owner->getEmail(),
        ':password'    => password_hash($owner->getPassword(), PASSWORD_DEFAULT),
        ':phoneNumber' => $owner->getPhoneNumber(),
        ':isActive'    => $owner->getIsActive()
      ]);

      // tbroleownerid comparte el mismo valor que tbroleid (PK compartida, no es FK)
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
        ':idOwner'                   => $idRole,
        ':firstNameOwner'            => $owner->getFirstNameOwner(),
        ':lastNameOwner'             => $owner->getLastNameOwner(),
        ':aliasOwner'                => $owner->getAliasOwner(),
        ':identificationNumberOwner' => $owner->getIdentificationNumberOwner(),
        ':isOwnerActive'             => $owner->getIsOwnerActive(),
        ':idRol'                     => $owner->getIdRol(),
        ':imageOwner'                => $owner->getImageOwner()
      ]);

      $owner->setIdOwner($idRole);

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

            FROM tbrole r

            INNER JOIN tbroleowner o
                ON o.tbroleownerid = r.tbroleid

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

            INNER JOIN tbrole r
                ON r.tbroleid = o.tbroleownerid

            WHERE o.tbroleownerid = :idOwner

            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idOwner' => $ownerPk
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

                o.tbroleownerid,
                o.tbroleownerfirstname,
                o.tbroleownerlastname,
                o.tbroleowneralias,
                o.tbroleowneridentificationnumber,
                o.tbroleownerisactive,
                o.tbroleownerrolid,
                o.tbroleownerimage

            FROM tbroleowner o

            INNER JOIN tbrole r
                ON r.tbroleid = o.tbroleownerid

            ORDER BY o.tbroleownerid ASC
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
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Owner
  {
    return new Owner(
      id: (int) $row['tbroleid'],
      name: $row['tbrolename'],
      email: $row['tbroleemail'],
      password: $row['tbrolepassword'],
      isActive: (bool) $row['tbroleisactive'],
      idOwner: (int) $row['tbroleownerid'],
      firstName: $row['tbroleownerfirstname'],
      lastName: $row['tbroleownerlastname'],
      alias: $row['tbroleowneralias'],
      identificationNumber: $row['tbroleowneridentificationnumber'],
      isOwnerActive: (bool) $row['tbroleownerisactive'],
      idRol: (int) $row['tbroleownerrolid'],
      imageOwner: $row['tbroleownerimage'],
      phoneNumber: $row['tbrolephonenumber']
    );
  }
}
