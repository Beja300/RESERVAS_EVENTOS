<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/PromotionService.php';

class PromotionServiceRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(PromotionServiceLink $promotionService): int
  {
    $sql = "
      INSERT INTO tbpromotionservice (
        tbpromotionservicepromotionid,
        tbpromotionserviceserviceid
      )
      VALUES (
        :idPromotion,
        :idService
      )
    ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idPromotion' => $promotionService->getIdPromotion(),
      ':idService'   => $promotionService->getIdService(),
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // SERVICIOS INCLUIDOS EN UNA PROMOCIÓN
  // =========================================================
  public function findByPromotion(int $idPromotion): array
  {
    $sql = "
      SELECT
        tbpromotionserviceid,
        tbpromotionservicepromotionid,
        tbpromotionserviceserviceid
      FROM tbpromotionservice
      WHERE tbpromotionservicepromotionid = :idPromotion
        AND tbpromotionserviceactive = true
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idPromotion' => $idPromotion]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): PromotionServiceLink
  {
    return new PromotionServiceLink(
      idPromotionService: (int) $row['tbpromotionserviceid'],
      idPromotion: (int) $row['tbpromotionservicepromotionid'],
      idService: (int) $row['tbpromotionserviceserviceid']
    );
  }
}
