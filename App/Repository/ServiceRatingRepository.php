<?php

require_once __DIR__ . '/../../Configuration/DataBase.php';
require_once __DIR__ . '/../Model/ServiceRating.php';

class ServiceRatingRepository
{
  private PDO $connection;

  public function __construct(PDO $connection)
  {
    $this->connection = $connection;
  }

  // =========================================================
  // GUARDAR
  // =========================================================
  public function save(ServiceRating $rating): int
  {
    $sql = "
      INSERT INTO tbservicerating (
        tbserviceratingserviceid,
        tbserviceratingroleid,
        tbserviceratingstars,
        tbserviceratingcomment
      )
      VALUES (
        :idService,
        :idRole,
        :stars,
        :comment
      )
    ";

    $stmt = $this->connection->prepare($sql);

    $stmt->execute([
      ':idService' => $rating->getIdService(),
      ':idRole'    => $rating->getIdRole(),
      ':stars'     => $rating->getStars(),
      ':comment'   => $rating->getComment() !== '' ? $rating->getComment() : null,
    ]);

    return (int) $this->connection->lastInsertId();
  }

  // =========================================================
  // CALIFICACIÓN EXISTENTE DE UN ROL SOBRE UN SERVICIO
  // =========================================================
  public function findByServiceAndRole(int $idService, int $idRole): ?ServiceRating
  {
    $sql = "
      SELECT
        tbserviceratingid,
        tbserviceratingserviceid,
        tbserviceratingroleid,
        tbserviceratingstars,
        tbserviceratingcomment
      FROM tbservicerating
      WHERE tbserviceratingserviceid = :idService
        AND tbserviceratingroleid = :idRole
        AND tbserviceratingactive = true
      LIMIT 1
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([
      ':idService' => $idService,
      ':idRole'    => $idRole,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? $this->mapRow($row) : null;
  }

  // =========================================================
  // DETALLE DE CALIFICACIONES DE UN SERVICIO
  // =========================================================
  public function findByService(int $idService): array
  {
    $sql = "
      SELECT
        tbserviceratingid,
        tbserviceratingserviceid,
        tbserviceratingroleid,
        tbserviceratingstars,
        tbserviceratingcomment
      FROM tbservicerating
      WHERE tbserviceratingserviceid = :idService
        AND tbserviceratingactive = true
      ORDER BY tbserviceratingid DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idService' => $idService]);

    return array_map([$this, 'mapRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }

  // =========================================================
  // PROMEDIO DE ESTRELLAS DE UN SERVICIO
  // =========================================================
  public function findAverageByService(int $idService): ?float
  {
    $sql = "
      SELECT AVG(tbserviceratingstars) AS average
      FROM tbservicerating
      WHERE tbserviceratingserviceid = :idService
        AND tbserviceratingactive = true
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idService' => $idService]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row && $row['average'] !== null ? (float) $row['average'] : null;
  }

  // =========================================================
  // CANTIDAD DE CALIFICACIONES DE UN SERVICIO
  // =========================================================
  public function countByService(int $idService): int
  {
    $sql = "
      SELECT COUNT(*) AS total
      FROM tbservicerating
      WHERE tbserviceratingserviceid = :idService
        AND tbserviceratingactive = true
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idService' => $idService]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return (int) $row['total'];
  }

  // =========================================================
  // MAPEO FILA -> OBJETO
  // =========================================================
  private function mapRow(array $row): ServiceRating
  {
    return new ServiceRating(
      idServiceRating: (int) $row['tbserviceratingid'],
      idService: (int) $row['tbserviceratingserviceid'],
      idRole: (int) $row['tbserviceratingroleid'],
      stars: (int) $row['tbserviceratingstars'],
      comment: $row['tbserviceratingcomment'] ?? ''
    );
  }
}
