<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Service.php';

class ServiceRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
    $this->ensureApprovalColumns();
  }

  // =========================================================
  // AUTO-MIGRACIÓN: garantiza que tbservice tenga las columnas de
  // aprobación que usa la app. Si faltan (BD creada antes de esta
  // funcionalidad), las agrega de forma idempotente, para que el
  // panel del Admin y del Owner funcionen sin pasos manuales.
  // Se ejecuta en bloque try/catch: NUNCA debe romper una página
  // si por cualquier motivo (permisos, BD, etc.) no puede alterar.
  // =========================================================
  private function ensureApprovalColumns(): void
  {
    try {
      $stmt = $this->connection->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tbservice' AND COLUMN_NAME = :col"
      );

      foreach (['tbserviceapprovedby', 'tbserviceapprovedon'] as $column) {
        $stmt->execute([':col' => $column]);
        if ((int) $stmt->fetchColumn() === 0) {
          $type = $column === 'tbserviceapprovedby' ? 'INT NULL' : 'DATETIME NULL';
          $this->connection->exec("ALTER TABLE tbservice ADD COLUMN {$column} {$type}");
        }
      }
    } catch (\Throwable $e) {
      // Si la auto-migración falla, lo dejamos pasar: la consulta real
      // podrá fallar si faltan columnas, pero al menos no rompe la app
      // en el constructor. Se loguea para diagnóstico.
      error_log('[ServiceRepository] auto-migración no aplicada: ' . $e->getMessage());
    }
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
      ':isActive'     => $this->toDb($service->getIsActive())
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
                tbserviceapprovedby,
                tbserviceapprovedon,
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
                tbserviceapprovedby,
                tbserviceapprovedon,
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
                tbserviceapprovedby,
                tbserviceapprovedon,
                tbserviceactive

            FROM tbservice

            WHERE tbservicestate = 'solicitado'
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }


  // =========================================================
  // HISTORIAL DE APROBACIÓN (lo que ve el Admin): servicios que
  // ya fueron aprobados o rechazados, con el nombre del local y
  // del administrador que los aprobó. Devuelve filas asociativas
  // enriquecidas (no objetos Service).
  // =========================================================
  public function findHistory(): array
  {
    $sql = "
            SELECT
                s.tbserviceid,
                s.tbservicename,
                s.tbservicetype,
                s.tbserviceprice,
                s.tbservicestate,
                v.tbvenuename   AS venueName,
                r.tbrolename    AS approvedByName,
                s.tbserviceapprovedon
            FROM tbservice s
            LEFT JOIN tbvenue v ON v.tbvenueid = s.tbservicelocalid
            LEFT JOIN tbrole r ON r.tbroleid = s.tbserviceapprovedby
            WHERE s.tbservicestate IN ('aprobado', 'rechazado')
            ORDER BY s.tbserviceapprovedon DESC, s.tbserviceid DESC
        ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                tbserviceapprovedby,
                tbserviceapprovedon,
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
  // APROBAR: cambia el estado y registra quién lo aprobó y cuándo
  // =========================================================
  public function approve(int $idService, int $approvedByRoleId): bool
  {
    $sql = "
            UPDATE tbservice
            SET tbservicestate = 'aprobado',
                tbserviceapprovedby = :approvedByRoleId,
                tbserviceapprovedon = NOW()
            WHERE tbserviceid = :idService
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':approvedByRoleId' => $approvedByRoleId,
      ':idService'        => $idService
    ]);
  }

  // =========================================================
  // RECHAZAR: cambia el estado y registra quién lo rechazó y cuándo
  // =========================================================
  public function reject(int $idService, int $approvedByRoleId): bool
  {
    $sql = "
            UPDATE tbservice
            SET tbservicestate = 'rechazado',
                tbserviceapprovedby = :approvedByRoleId,
                tbserviceapprovedon = NOW()
            WHERE tbserviceid = :idService
        ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':approvedByRoleId' => $approvedByRoleId,
      ':idService'        => $idService
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
      ':isActive'     => $this->toDb($service->getIsActive()),
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
      isActive: $this->toBool($row['tbserviceactive']),
      approvedBy: ($row['tbserviceapprovedby'] ?? null) !== null ? (int) $row['tbserviceapprovedby'] : null,
      approvedOn: $row['tbserviceapprovedon'] ?? null
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
