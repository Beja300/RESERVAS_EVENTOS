<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Service.php';

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
  public function save(Service $service): int
  {
    $sql = "
            INSERT INTO tbservice (
                tbservicelocalid,
                tbservicename,
                tbservicetype,
                tbserviceprice,
                tbservicestate,
                tbserviceactive
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
      ':idLocal'      => $service->getIdLocal(),
      ':nameService'  => $service->getNameService(),
      ':typeService'  => $service->getTypeService(),
      ':priceService' => $service->getPriceService(),
      ':stateService' => $service->getStateService(),
      ':isActive'     => $service->getIsActive()
    ]);

    return (int) $this->connection->lastInsertId();
  }


  // =========================================================
  // BUSCAR POR ID
  // =========================================================
  public function findById(int $idService): ?Service
  {
    $sql = "
            SELECT
                tbserviceid,
                tbservicelocalid,
                tbservicename,
                tbservicetype,
                tbserviceprice,
                tbservicestate,
                tbserviceactive

            FROM tbservice

            WHERE tbserviceid = :idService
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idService' => $idService
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }


  // =========================================================
  // OBTENER DISPONIBLES DE UN LOCAL (lo que ve el Cliente:
  // solo aprobados y activos)
  // =========================================================
  public function findAvailableByLocal(int $idLocal): array
  {
    $sql = "
            SELECT
                tbserviceid,
                tbservicelocalid,
                tbservicename,
                tbservicetype,
                tbserviceprice,
                tbservicestate,
                tbserviceactive

            FROM tbservice

            WHERE tbservicelocalid = :idLocal
              AND tbservicestate = 'aprobado'
              AND tbserviceactive = true
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idLocal' => $idLocal
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // OBTENER PENDIENTES (lo que ve el Admin: todos los servicios
  // en estado 'solicitado')
  // =========================================================
  public function findPending(): array
  {
    $sql = "
            SELECT
                tbserviceid,
                tbservicelocalid,
                tbservicename,
                tbservicetype,
                tbserviceprice,
                tbservicestate,
                tbserviceactive

            FROM tbservice

            WHERE tbservicestate = 'solicitado'
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // OBTENER POR LOCAL (lo que ve el Owner en su panel: TODOS
  // sus servicios, cualquier estado)
  // =========================================================
  public function findByLocal(int $idLocal): array
  {
    $sql = "
            SELECT
                tbserviceid,
                tbservicelocalid,
                tbservicename,
                tbservicetype,
                tbserviceprice,
                tbservicestate,
                tbserviceactive

            FROM tbservice

            WHERE tbservicelocalid = :idLocal
        ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idLocal' => $idLocal
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // CAMBIAR ESTADO
  // =========================================================
  public function updateState(int $idService, string $stateService): bool
  {
    $sql = "
            UPDATE tbservice
            SET tbservicestate = :stateService
            WHERE tbserviceid = :idService
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':stateService' => $stateService,
      ':idService'    => $idService
    ]);
  }


  // =========================================================
  // EDITAR
  // =========================================================
  public function update(Service $service): bool
  {
    $sql = "
            UPDATE tbservice
            SET
                tbservicename = :nameService,
                tbservicetype = :typeService,
                tbserviceprice = :priceService,
                tbserviceactive = :isActive
            WHERE tbserviceid = :idService
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':nameService'  => $service->getNameService(),
      ':typeService'  => $service->getTypeService(),
      ':priceService' => $service->getPriceService(),
      ':isActive'     => $service->getIsActive(),
      ':idService'    => $service->getIdService()
    ]);
  }


  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): Service
  {
    return new Service(
      idService: (int) $row['tbserviceid'],
      idLocal: (int) $row['tbservicelocalid'],
      nameService: $row['tbservicename'],
      typeService: $row['tbservicetype'],
      priceService: (float) $row['tbserviceprice'],
      stateService: $row['tbservicestate'],
      isActive: $this->toBool($row['tbserviceactive'])
    );
  }

  private function toBool(mixed $value): bool
  {
    return $value === 1 || $value === '1' || $value === true;
  }
}
