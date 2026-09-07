<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/RoleEmailHistorical.php';

class RoleEmailHistoricalRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR (append-only)
  // =========================================================
  public function save(RoleEmailHistorical $history): int
  {
    $sql = "
      INSERT INTO tbrolemailhistorical (
        tbroleid,
        tbrolemailhistoricalactualemail,
        tbrolemailhistoricalnewemail
      )
      VALUES (
        :idRole,
        :actualEmail,
        :newEmail
      )
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idRole'    => $history->getIdRole(),
      ':actualEmail'=> $history->getActualEmail(),
      ':newEmail'  => $history->getNewEmail(),
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
        tbrolemailhistoricalid,
        tbroleid,
        tbrolemailhistoricalactualemail,
        tbrolemailhistoricalnewemail,
        tbrolemailhistoricaldate
      FROM tbrolemailhistorical
      WHERE tbroleid = :idRole
      ORDER BY tbrolemailhistoricaldate DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idRole' => $idRole]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // ÚLTIMO CAMBIO DE CORREO (fecha)
  // =========================================================
  public function findLastChangeDate(int $idRole): ?string
  {
    $sql = "
      SELECT tbrolemailhistoricaldate
      FROM tbrolemailhistorical
      WHERE tbroleid = :idRole
      ORDER BY tbrolemailhistoricaldate DESC
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
      FROM tbrolemailhistorical
      WHERE tbroleid = :idRole
        AND tbrolemailhistoricaldate >= :since
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
  private function mapRow(array $row): RoleEmailHistorical
  {
    return new RoleEmailHistorical(
      idRoleEmailHistorical: (int) $row['tbrolemailhistoricalid'],
      idRole: (int) $row['tbroleid'],
      actualEmail: $row['tbrolemailhistoricalactualemail'] ?? null,
      newEmail: $row['tbrolemailhistoricalnewemail'],
      date: $row['tbrolemailhistoricaldate']
    );
  }
}