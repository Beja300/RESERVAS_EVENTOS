<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/RolePhoneHistorical.php';

class RolePhoneHistoricalRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR (append-only)
  // =========================================================
  public function save(RolePhoneHistorical $history): int
  {
    $sql = "
      INSERT INTO tbrolphonehistorical (
        tbroleid,
        tbrolphonehistoricalactualphone,
        tbrolphonehistoricalnewphone
      )
      VALUES (
        :idRole,
        :actualPhone,
        :newPhone
      )
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idRole'     => $history->getIdRole(),
      ':actualPhone'=> $history->getActualPhone(),
      ':newPhone'   => $history->getNewPhone(),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // HISTORIAL DE UN ROL
  // =========================================================
  public function findByRole(int $idRole): array
  {
    $sql = "
      SELECT
        tbrolphonehistoricalid,
        tbroleid,
        tbrolphonehistoricalactualphone,
        tbrolphonehistoricalnewphone,
        tbrolphonehistoricaldate
      FROM tbrolphonehistorical
      WHERE tbroleid = :idRole
      ORDER BY tbrolphonehistoricaldate DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idRole' => $idRole]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // ÚLTIMO CAMBIO DE TELÉFONO (fecha)
  // =========================================================
  public function findLastChangeDate(int $idRole): ?string
  {
    $sql = "
      SELECT tbrolphonehistoricaldate
      FROM tbrolphonehistorical
      WHERE tbroleid = :idRole
      ORDER BY tbrolphonehistoricaldate DESC
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idRole' => $idRole]);

    $value = $stmt->fetchColumn();

    return $value !== false ? (string) $value : null;
  }

  // =========================================================
  // CUÁNTOS CAMBIOS HUBO EN LOS ÚLTIMOS $days DÍAS
  // =========================================================
  public function countInLastDays(int $idRole, int $days): int
  {
    $sql = "
      SELECT COUNT(*)
      FROM tbrolphonehistorical
      WHERE tbroleid = :idRole
        AND tbrolphonehistoricaldate >= :since
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idRole' => $idRole,
      ':since'  => date('Y-m-d H:i:s', strtotime("-$days days")),
    ]);

    return (int) $stmt->fetchColumn();
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): RolePhoneHistorical
  {
    return new RolePhoneHistorical(
      idRolePhoneHistorical: (int) $row['tbrolphonehistoricalid'],
      idRole: (int) $row['tbroleid'],
      actualPhone: $row['tbrolphonehistoricalactualphone'] ?? null,
      newPhone: $row['tbrolphonehistoricalnewphone'],
      date: $row['tbrolphonehistoricaldate']
    );
  }
}