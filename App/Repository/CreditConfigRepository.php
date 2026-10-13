<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/CreditConfig.php';

class CreditConfigRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // CONFIGURACIÓN ACTIVA DE UN PROPIETARIO
  // =========================================================
  public function findByOwner(int $idOwner): ?CreditConfig
  {
    $sql = "
      SELECT
        tbcreditconfigid,
        tbownerid,
        tbcreditconfigenabled,
        tbcreditconfigdays,
        tbcreditconfigfrequentmin,
        tbcreditconfigactive
      FROM tbcreditconfig
      WHERE tbownerid = :idOwner
        AND tbcreditconfigactive = true
      ORDER BY tbcreditconfigid DESC
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idOwner' => $idOwner]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // GUARDAR (crear)
  // =========================================================
  public function save(CreditConfig $config): int
  {
    $sql = "
      INSERT INTO tbcreditconfig (
        tbownerid,
        tbcreditconfigenabled,
        tbcreditconfigdays,
        tbcreditconfigfrequentmin,
        tbcreditconfigactive
      )
      VALUES (
        :idOwner,
        :enabled,
        :days,
        :frequentMin,
        :isActive
      )
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idOwner'     => $config->getIdOwner(),
      ':enabled'     => $this->toDb($config->isEnabled()),
      ':days'        => $config->getDays(),
      ':frequentMin' => $config->getFrequentMin(),
      ':isActive'    => $this->toDb($config->getIsActive()),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // ACTUALIZAR
  // =========================================================
  public function update(CreditConfig $config): bool
  {
    $sql = "
      UPDATE tbcreditconfig
      SET
        tbcreditconfigenabled = :enabled,
        tbcreditconfigdays = :days,
        tbcreditconfigfrequentmin = :frequentMin,
        tbcreditconfigactive = :isActive
      WHERE tbcreditconfigid = :idCreditConfig
    ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':enabled'        => $this->toDb($config->isEnabled()),
      ':days'           => $config->getDays(),
      ':frequentMin'    => $config->getFrequentMin(),
      ':isActive'       => $this->toDb($config->getIsActive()),
      ':idCreditConfig' => $config->getIdCreditConfig(),
    ]);
  }

  private function mapRow(array $row): CreditConfig
  {
    return new CreditConfig(
      idCreditConfig: (int) $row['tbcreditconfigid'],
      idOwner: (int) $row['tbownerid'],
      enabled: $this->toBool($row['tbcreditconfigenabled']),
      days: (int) $row['tbcreditconfigdays'],
      frequentMin: (int) $row['tbcreditconfigfrequentmin'],
      isActive: $this->toBool($row['tbcreditconfigactive'])
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
