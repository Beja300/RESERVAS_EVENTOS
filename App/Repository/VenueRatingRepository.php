<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/VenueRating.php';

class VenueRatingRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(VenueRating $rating): int
  {
    $sql = "
      INSERT INTO tbvenuerating (
        tbvenueratingvenueid,
        tbvenueratingroleid,
        tbvenueratingstars,
        tbvenueratingcomment
      )
      VALUES (
        :idVenue,
        :idRole,
        :stars,
        :comment
      )
    ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idVenue' => $rating->getIdVenue(),
      ':idRole'  => $rating->getIdRole(),
      ':stars'   => $rating->getStars(),
      ':comment' => $rating->getComment() !== '' ? $rating->getComment() : null,
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // CALIFICACIÓN EXISTENTE DE UN ROL SOBRE UN LOCAL
  // =========================================================
  public function findByVenueAndRole(int $idVenue, int $idRole): ?VenueRating
  {
    $sql = "
      SELECT
        tbvenueratingid,
        tbvenueratingvenueid,
        tbvenueratingroleid,
        tbvenueratingstars,
        tbvenueratingcomment
      FROM tbvenuerating
      WHERE tbvenueratingvenueid = :idVenue
        AND tbvenueratingroleid = :idRole
        AND tbvenueratingactive = true
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idVenue' => $idVenue,
      ':idRole'  => $idRole,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // DETALLE DE CALIFICACIONES DE UN LOCAL
  // =========================================================
  public function findByVenue(int $idVenue): array
  {
    $sql = "
      SELECT
        tbvenueratingid,
        tbvenueratingvenueid,
        tbvenueratingroleid,
        tbvenueratingstars,
        tbvenueratingcomment
      FROM tbvenuerating
      WHERE tbvenueratingvenueid = :idVenue
        AND tbvenueratingactive = true
      ORDER BY tbvenueratingid DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idVenue' => $idVenue]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // PROMEDIO DE ESTRELLAS DE UN LOCAL
  // =========================================================
  public function findAverageByVenue(int $idVenue): ?float
  {
    $sql = "
      SELECT AVG(tbvenueratingstars) AS average
      FROM tbvenuerating
      WHERE tbvenueratingvenueid = :idVenue
        AND tbvenueratingactive = true
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idVenue' => $idVenue]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row && $row['average'] !== null ? (float) $row['average'] : null;
  }

  // =========================================================
  // CANTIDAD DE CALIFICACIONES DE UN LOCAL
  // =========================================================
  public function countByVenue(int $idVenue): int
  {
    $sql = "
      SELECT COUNT(*) AS total
      FROM tbvenuerating
      WHERE tbvenueratingvenueid = :idVenue
        AND tbvenueratingactive = true
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idVenue' => $idVenue]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int) $row['total'];
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): VenueRating
  {
    return new VenueRating(
      idVenueRating: (int) $row['tbvenueratingid'],
      idVenue: (int) $row['tbvenueratingvenueid'],
      idRole: (int) $row['tbvenueratingroleid'],
      stars: (int) $row['tbvenueratingstars'],
      comment: $row['tbvenueratingcomment'] ?? ''
    );
  }
}
