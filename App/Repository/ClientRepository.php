<?php

require_once __DIR__ . '/../models/Client.php';

class ClientRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function saveClient(Client $client): bool
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
        ':name' => $client->getName(),
        ':email' => $client->getEmail(),
        ':password' => $client->getPassword(),
        ':phoneNumber' => $client->getPhoneNumber(),
        ':isActive' => $client->getIsActive()
      ]);

      $idRole = (int) $this->connection->lastInsertId();

      $sqlClient = "
                INSERT INTO tbroleclient (
                    tbroleclientid,
                    tbroleclientisactive,
                    tbroleclientrolid,
                    tbroleclientimage
                )
                VALUES (
                    :idClient,
                    :isClientActive,
                    :idRol,
                    :imageClient
                )
            ";

      $stmtClient = $this->connection->prepare($sqlClient);

      $stmtClient->execute([
        ':idClient' => $idRole,
        ':isClientActive' => $client->getIsClientActive(),
        ':idRol' => $client->getIdRol(),
        ':imageClient' => $client->getImageClient()
      ]);

      $client->setIdRole($idRole);
      $client->setIdClient($idRole);

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
  public function getAllClient(): array
  {
    $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephonenumber,
                r.tbroleisactive,

                c.tbroleclientid,
                c.tbroleclientisactive,
                c.tbroleclientrolid,
                c.tbroleclientimage

            FROM tbroleclient c

            INNER JOIN role r
                ON c.tbroleclientid = r.tbroleid

            ORDER BY c.tbroleclientid ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $clients = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $clients[] = $this->mapRowToClient($row);
    }

    return $clients;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdClient(int $idClient): ?Client
  {
    $sql = "
            SELECT
                r.tbroleid,
                r.tbrolename,
                r.tbroleemail,
                r.tbrolepassword,
                r.tbrolephonenumber,
                r.tbroleisactive,

                c.tbroleclientid,
                c.tbroleclientisactive,
                c.tbroleclientrolid,
                c.tbroleclientimage

            FROM tbroleclient c

            INNER JOIN role r
                ON c.tbroleclientid = r.tbroleid

            WHERE c.tbroleclientid = :idClient
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClient' => $idClient
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToClient($row);
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updateClient(Client $client): bool
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
        ':name' => $client->getName(),
        ':email' => $client->getEmail(),
        ':password' => $client->getPassword(),
        ':phoneNumber' => $client->getPhoneNumber(),
        ':isActive' => $client->getIsActive(),
        ':id' => $client->getIdRole()
      ]);

      $sqlClient = "
                UPDATE tbroleclient
                SET
                    tbroleclientisactive = :isClientActive,
                    tbroleclientrolid = :idRol,
                    tbroleclientimage = :imageClient
                WHERE tbroleclientid = :idClient
            ";

      $stmtClient = $this->connection->prepare($sqlClient);

      $stmtClient->execute([
        ':isClientActive' => $client->getIsClientActive(),
        ':idRol' => $client->getIdRol(),
        ':imageClient' => $client->getImageClient(),
        ':idClient' => $client->getIdClient()
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
  public function deactivateClient(int $idClient): bool
  {
    try {
      $this->connection->beginTransaction();

      $sqlClient = "
                UPDATE tbroleclient
                SET tbroleclientisactive = false
                WHERE tbroleclientid = :idClient
            ";

      $stmtClient = $this->connection->prepare($sqlClient);
      $stmtClient->execute([
        ':idClient' => $idClient
      ]);

      $sqlRole = "
                UPDATE role
                SET tbroleisactive = false
                WHERE tbroleid = :id
            ";

      $stmtRole = $this->connection->prepare($sqlRole);
      $stmtRole->execute([
        ':id' => $idClient
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
  public function deleteClient(int $idClient): bool
  {
    try {

      $this->connection->beginTransaction();

      $sqlClient = "
                DELETE FROM tbroleclient
                WHERE tbroleclientid = :idClient
            ";

      $stmtClient = $this->connection->prepare($sqlClient);

      $stmtClient->execute([
        ':idClient' => $idClient
      ]);

      $sqlRole = "
                DELETE FROM role
                WHERE tbroleid = :id
            ";

      $stmtRole = $this->connection->prepare($sqlRole);

      $stmtRole->execute([
        ':id' => $idClient
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
  private function mapRowToClient(array $row): Client
  {
    return new Client(
      (int) $row['tbroleid'],
      $row['tbrolename'],
      $row['tbroleemail'],
      $row['tbrolepassword'],
      (bool) $row['tbroleisactive'],
      (int) $row['tbroleclientid'],
      (bool) $row['tbroleclientisactive'],
      (int) $row['tbroleclientrolid'],
      $row['tbroleclientimage'],
      $row['tbrolephonenumber']
    );
  }
}
