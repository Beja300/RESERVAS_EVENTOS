<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/Promotion.php';

class PromotionRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(Promotion $promotion): int
  {
    $sql = "
      INSERT INTO tbpromotion (
        tbvenueid,
        tbpromotiondescription,
        tbpromotionlabel,
        tbpromotionstart,
        tbpromotionend,
        tbpromotionminservices
      )
      VALUES (
        :idVenue,
        :description,
        :label,
        :startDate,
        :endDate,
        :minServices
      )
    ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idVenue'      => $promotion->getIdVenue(),
      ':description'  => $promotion->getDescription() !== '' ? $promotion->getDescription() : null,
      ':label'        => $promotion->getLabel(),
      ':startDate'    => $promotion->getStartDate(),
      ':endDate'      => $promotion->getEndDate(),
      ':minServices'  => $promotion->getMinServices(),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // ACTUALIZAR
  // =========================================================
  public function update(Promotion $promotion): bool
  {
    $sql = "
      UPDATE tbpromotion
      SET
        tbpromotiondescription = :description,
        tbpromotionlabel = :label,
        tbpromotionstart = :startDate,
        tbpromotionend = :endDate,
        tbpromotionminservices = :minServices,
        tbpromotionactive = :isActive
      WHERE tbpromotionid = :idPromotion
    ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':description'  => $promotion->getDescription() !== '' ? $promotion->getDescription() : null,
      ':label'        => $promotion->getLabel(),
      ':startDate'    => $promotion->getStartDate(),
      ':endDate'      => $promotion->getEndDate(),
      ':minServices'  => $promotion->getMinServices(),
      ':isActive'     => $this->toDb($promotion->getIsActive()),
      ':idPromotion'  => $promotion->getIdPromotion(),
    ]);
  }

  // =========================================================
  // BUSCAR POR ID
  // =========================================================
  public function findById(int $idPromotion): ?Promotion
  {
    $sql = "
      SELECT
        tbpromotionid,
        tbvenueid,
        tbpromotiondescription,
        tbpromotionlabel,
        tbpromotionstart,
        tbpromotionend,
        tbpromotionminservices,
        tbpromotionactive
      FROM tbpromotion
      WHERE tbpromotionid = :idPromotion
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idPromotion' => $idPromotion]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // PROMOCIONES ACTIVAS DE UN LOCAL (según vigencia)
  // =========================================================
  public function findActiveByVenue(int $idVenue, string $date): array
  {
    $sql = "
      SELECT
        tbpromotionid,
        tbvenueid,
        tbpromotiondescription,
        tbpromotionlabel,
        tbpromotionstart,
        tbpromotionend,
        tbpromotionminservices
      FROM tbpromotion
      WHERE tbvenueid = :idVenue
        AND tbpromotionactive = true
        AND (tbpromotionstart IS NULL OR tbpromotionstart <= :date)
        AND (tbpromotionend IS NULL OR tbpromotionend >= :date)
      ORDER BY tbpromotionid DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idVenue' => $idVenue,
      ':date'    => $date,
    ]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // TODAS LAS PROMOCIONES DE UN LOCAL (panel del propietario)
  // =========================================================
  public function findByVenue(int $idVenue): array
  {
    $sql = "
      SELECT
        tbpromotionid,
        tbvenueid,
        tbpromotiondescription,
        tbpromotionlabel,
        tbpromotionstart,
        tbpromotionend,
        tbpromotionminservices,
        tbpromotionactive
      FROM tbpromotion
      WHERE tbvenueid = :idVenue
      ORDER BY tbpromotionid DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idVenue' => $idVenue]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  public function existsByLabel(int $idVenue, string $label, int $excludeId = 0): bool
  {
    $label = trim($label);
    $sql = "
      SELECT COUNT(*) as cnt
      FROM tbpromotion
      WHERE tbvenueid = :idVenue
        AND LOWER(TRIM(tbpromotionlabel)) = LOWER(:label)
        AND (:excludeId = 0 OR tbpromotionid <> :excludeId)
    ";
    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idVenue'=>$idVenue, ':label'=>$label, ':excludeId'=>$excludeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return !empty($row) && (int)$row['cnt'] > 0;
  }


  private function mapRow(array $row): Promotion
  {
    return new Promotion(
      idPromotion: (int) $row['tbpromotionid'],
      idVenue: (int) $row['tbvenueid'],
      description: $row['tbpromotiondescription'] ?? '',
      label: $row['tbpromotionlabel'],
      startDate: $row['tbpromotionstart'],
      endDate: $row['tbpromotionend'],
      minServices: (int) $row['tbpromotionminservices'],
      isActive: (bool) ($row['tbpromotionactive'] ?? true)
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
