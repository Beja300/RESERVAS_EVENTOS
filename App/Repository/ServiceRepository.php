<?php

require_once __DIR__ . '/../models/Service.php';

class ServiceRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function saveService(Service $service): bool
  {
    try {
      $sql = "
                INSERT INTO tbservice (
                    tbservicelocalid,
                    tbservicename,
                    tbservicetype,
                    tbserviceprice,
                    tbservicestate,
                    tbserviceisactive
                )
                VALUES (
                    :idLocal,
                    :nameService,
                    :typeService,
                    :priceService,
                    :stateService,
                    :isActive
                )
            ";

      $stmt = $this->connection->prepare($sql);

      $stmt->execute([
        ':idLocal' => $service->getIdLocal(),
        ':nameService' => $service->getNameService(),
        ':typeService' => $service->getTypeService(),
        ':priceService' => $service->getPriceService(),
        ':stateService' => $service->getStateService(),
        ':isActive' => $service->getIsActive()
      ]);

      $service->setIdService((int) $this->connection->lastInsertId());

      return true;
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // OBTENER TODOS
  // =========================================================
  public function getAllService(): array
  {
    $sql = "
            SELECT
                tbserviceid,
                tbservicelocalid,
                tbservicename,
                tbservicetype,
                tbserviceprice,
                tbservicestate,
                tbserviceisactive

            FROM tbservice

            ORDER BY tbservicename ASC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $services = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $services[] = $this->mapRowToService($row);
    }

    return $services;
  }


  // =========================================================
  // OBTENER POR ID
  // =========================================================
  public function getByIdService(int $idService): ?Service
  {
    $sql = "
            SELECT
                tbserviceid,
                tbservicelocalid,
                tbservicename,
                tbservicetype,
                tbserviceprice,
                tbservicestate,
                tbserviceisactive

            FROM tbservice

            WHERE tbserviceid = :idService
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idService' => $idService
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      return null;
    }

    return $this->mapRowToService($row);
  }


  // =========================================================
  // OBTENER POR LOCAL (servicios de un local, solo activos)
  // =========================================================
  public function getByLocalService(int $idLocal): array
  {
    $sql = "
            SELECT
                tbserviceid,
                tbservicelocalid,
                tbservicename,
                tbservicetype,
                tbserviceprice,
                tbservicestate,
                tbserviceisactive

            FROM tbservice

            WHERE tbservicelocalid = :idLocal
              AND tbserviceisactive = true

            ORDER BY tbservicename ASC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idLocal' => $idLocal
    ]);

    $services = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $services[] = $this->mapRowToService($row);
    }

    return $services;
  }


  // =========================================================
  // OBTENER POR ESTADO (solicitado / aprobado / rechazado)
  // =========================================================
  public function getByStateService(string $stateService): array
  {
    $sql = "
            SELECT
                tbserviceid,
                tbservicelocalid,
                tbservicename,
                tbservicetype,
                tbserviceprice,
                tbservicestate,
                tbserviceisactive

            FROM tbservice

            WHERE tbservicestate = :stateService
              AND tbserviceisactive = true

            ORDER BY tbservicename ASC
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':stateService' => $stateService
    ]);

    $services = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $services[] = $this->mapRowToService($row);
    }

    return $services;
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function updateService(Service $service): bool
  {
    try {
      $sql = "
                UPDATE tbservice
                SET
                    tbservicelocalid = :idLocal,
                    tbservicename = :nameService,
                    tbservicetype = :typeService,
                    tbserviceprice = :priceService,
                    tbservicestate = :stateService,
                    tbserviceisactive = :isActive
                WHERE tbserviceid = :idService
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idLocal' => $service->getIdLocal(),
        ':nameService' => $service->getNameService(),
        ':typeService' => $service->getTypeService(),
        ':priceService' => $service->getPriceService(),
        ':stateService' => $service->getStateService(),
        ':isActive' => $service->getIsActive(),
        ':idService' => $service->getIdService()
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // CAMBIAR ESTADO (aprobar / rechazar)
  // =========================================================
  public function updateStateService(int $idService, string $stateService): bool
  {
    $sql = "
            UPDATE tbservice
            SET tbservicestate = :stateService
            WHERE tbserviceid = :idService
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':stateService' => $stateService,
      ':idService' => $idService
    ]);
  }


  // =========================================================
  // DESACTIVAR
  // =========================================================
  public function deactivateService(int $idService): bool
  {
    $sql = "
            UPDATE tbservice
            SET tbserviceisactive = false
            WHERE tbserviceid = :idService
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idService' => $idService
    ]);
  }


  // =========================================================
  // ELIMINAR
  // =========================================================
  public function deleteService(int $idService): bool
  {
    try {
      $sql = "
                DELETE FROM tbservice
                WHERE tbserviceid = :idService
            ";

      $stmt = $this->connection->prepare($sql);

      return $stmt->execute([
        ':idService' => $idService
      ]);
    } catch (PDOException $e) {

      return false;
    }
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRowToService(array $row): Service
  {
    return new Service(
      (int) $row['tbserviceid'],
      (int) $row['tbservicelocalid'],
      $row['tbservicename'],
      $row['tbservicetype'],
      (float) $row['tbserviceprice'],
      $row['tbservicestate'],
      (bool) $row['tbserviceisactive']
    );
  }
}
