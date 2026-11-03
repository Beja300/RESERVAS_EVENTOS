<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/CommissionConfig.php';

class CommissionConfigRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(CommissionConfig $config): int
  {
    $sql = "
      INSERT INTO tbcommissionconfig (
        tbcommissionconfigpercentage,
        tbcommissionconfigtax
      )
      VALUES (
        :percentage,
        :tax
      )
    ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':percentage' => $config->getPercentage(),
      ':tax'        => $config->getTax(),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // ACTUALIZAR
  // =========================================================
  public function update(CommissionConfig $config): bool
  {
    $sql = "
      UPDATE tbcommissionconfig
      SET
        tbcommissionconfigpercentage = :percentage,
        tbcommissionconfigtax = :tax,
        tbcommissionconfigactive = :isActive
      WHERE tbcommissionconfigid = :idConfig
    ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':percentage' => $config->getPercentage(),
      ':tax'        => $config->getTax(),
      ':isActive'   => $this->toDb($config->getIsActive()),
      ':idConfig'   => $config->getIdConfig(),
    ]);
  }

  // =========================================================
  // CONFIGURACIÓN ACTIVA (solo debe haber una)
  // =========================================================
  public function findActive(): ?CommissionConfig
  {
    $sql = "
      SELECT
        tbcommissionconfigid,
        tbcommissionconfigpercentage,
        tbcommissionconfigtax
      FROM tbcommissionconfig
      WHERE tbcommissionconfigactive = true
      ORDER BY tbcommissionconfigid ASC
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): CommissionConfig
  {
    return new CommissionConfig(
      idConfig: (int) $row['tbcommissionconfigid'],
      percentage: (float) $row['tbcommissionconfigpercentage'],
      tax: (float) $row['tbcommissionconfigtax']
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
