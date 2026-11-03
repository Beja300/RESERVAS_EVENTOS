<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/RolePasswordHistorical.php';

class RolePasswordHistoricalRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR (append-only: solo INSERT, no se actualiza)
  // =========================================================
  public function save(RolePasswordHistorical $history): int
  {
    $sql = "
      INSERT INTO tbrolpasswordhistorical (
        tbroleid,
        tbrolpasswordhistoricalactualpassword,
        tbrolpasswordhistoricalnewpassword
      )
      VALUES (
        :idRole,
        :actualPassword,
        :newPassword
      )
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idRole'        => $history->getIdRole(),
      ':actualPassword'=> $history->getActualPassword(),
      ':newPassword'   => $history->getNewPassword(),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // HISTORIAL DE UN ROL (más reciente primero)
  // =========================================================
  public function findByRole(int $idRole): array
  {
    $sql = "
      SELECT
        tbrolpasswordhistoricalid,
        tbroleid,
        tbrolpasswordhistoricalactualpassword,
        tbrolpasswordhistoricalnewpassword,
        tbrolpasswordhistoricaldate
      FROM tbrolpasswordhistorical
      WHERE tbroleid = :idRole
      ORDER BY tbrolpasswordhistoricaldate DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idRole' => $idRole]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // ÚLTIMO CAMBIO DE CONTRASEÑA (fecha) de un rol
  // =========================================================
  public function findLastChangeDate(int $idRole): ?string
  {
    $sql = "
      SELECT tbrolpasswordhistoricaldate
      FROM tbrolpasswordhistorical
      WHERE tbroleid = :idRole
      ORDER BY tbrolpasswordhistoricaldate DESC
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
      FROM tbrolpasswordhistorical
      WHERE tbroleid = :idRole
        AND tbrolpasswordhistoricaldate >= :since
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idRole' => $idRole,
      ':since'  => date('Y-m-d H:i:s', strtotime("-$days days")),
    ]);

    return (int) $stmt->fetchColumn();
  }

  // =========================================================
  // ¿SE USÓ ESTA CONTRASEÑA ANTES? Verifica contra las últimas
  // $samples contraseñas (actual y nueva de cada registro) porque
  // vienen haseadas con bcrypt.
  // =========================================================
  public function wasUsedInLast(int $idRole, string $plainPassword, int $samples = 5): bool
  {
    $sql = "
      SELECT
        tbrolpasswordhistoricalactualpassword,
        tbrolpasswordhistoricalnewpassword
      FROM tbrolpasswordhistorical
      WHERE tbroleid = :idRole
      ORDER BY tbrolpasswordhistoricaldate DESC
      LIMIT :samples
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->bindValue(':idRole', $idRole, PDO::PARAM_INT);
    $stmt->bindValue(':samples', $samples, PDO::PARAM_INT);
    $stmt->execute();

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      foreach (['tbrolpasswordhistoricalactualpassword', 'tbrolpasswordhistoricalnewpassword'] as $column) {
        if (!empty($row[$column]) && password_verify($plainPassword, $row[$column])) {
          return true;
        }
      }
    }

    return false;
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): RolePasswordHistorical
  {
    return new RolePasswordHistorical(
      idRolePasswordHistorical: (int) $row['tbrolpasswordhistoricalid'],
      idRole: (int) $row['tbroleid'],
      actualPassword: $row['tbrolpasswordhistoricalactualpassword'],
      newPassword: $row['tbrolpasswordhistoricalnewpassword'],
      date: $row['tbrolpasswordhistoricaldate']
    );
  }
}