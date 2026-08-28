<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Client.php';

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
  public function save(Client $client): bool
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
        ':name'        => $client->getName(),
        ':email'       => $client->getEmail(),
        ':password'    => password_hash($client->getPassword(), PASSWORD_DEFAULT),
        ':phoneNumber' => $client->getPhoneNumber(),
        ':isActive'    => $client->getIsActive()
      ]);

      // tbroleclientid comparte el mismo valor que tbroleid (PK compartida, no es FK)
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
        ':idClient'       => $idRole,
        ':isClientActive' => $client->getIsClientActive(),
        ':idRol'          => $client->getIdRol(),
        ':imageClient'    => $client->getImageClient()
      ]);

      $client->setIdClient($idRole);

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
  public function findByEmail(string $email): ?Client
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

            FROM tbrole r

            INNER JOIN tbroleclient c
                ON c.tbroleclientid = r.tbroleid

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
  // BUSCAR POR ID DE CLIENTE
  // =========================================================
  public function findByClientPk(int $clientPk): ?Client
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

            INNER JOIN tbrole r
                ON r.tbroleid = c.tbroleclientid

            WHERE c.tbroleclientid = :idClient

            LIMIT 1
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idClient' => $clientPk
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

                c.tbroleclientid,
                c.tbroleclientisactive,
                c.tbroleclientrolid,
                c.tbroleclientimage

            FROM tbroleclient c

            INNER JOIN tbrole r
                ON r.tbroleid = c.tbroleclientid

            ORDER BY c.tbroleclientid ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $clients = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $clients[] = $this->mapRow($row);
    }

    return $clients;
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Client
  {
    return new Client(
      id: (int) $row['tbroleid'],
      name: $row['tbrolename'],
      email: $row['tbroleemail'],
      password: $row['tbrolepassword'],
      isActive: (bool) $row['tbroleisactive'],
      idClient: (int) $row['tbroleclientid'],
      isClientActive: (bool) $row['tbroleclientisactive'],
      idRol: (int) $row['tbroleclientrolid'],
      imageClient: $row['tbroleclientimage'],
      phoneNumber: $row['tbrolephonenumber']
    );
  }
}
