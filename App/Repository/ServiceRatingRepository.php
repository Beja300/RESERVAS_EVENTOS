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
  // ACTUALIZAR (re-calificar: cambia estrellas y comentario)
  // =========================================================
  public function update(ServiceRating $rating): bool
  {
    $sql = "
      UPDATE tbservicerating
      SET
        tbserviceratingstars = :stars,
        tbserviceratingcomment = :comment
      WHERE tbserviceratingid = :idServiceRating
    ";

    $stmt = $this->connection->prepare($sql);

    return $stmt->execute([
      ':idServiceRating' => $rating->getIdServiceRating(),
      ':stars'           => $rating->getStars(),
      ':comment'         => $rating->getComment() !== '' ? $rating->getComment() : null,
    ]);
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
  // COMENTARIOS PÚBLICOS DE UN SERVICIO (con el nombre de quien calificó)
  // =========================================================
  public function findByServiceWithUser(int $idService): array
  {
    $sql = "
      SELECT
        sr.tbserviceratingroleid,
        sr.tbserviceratingstars,
        sr.tbserviceratingcomment,
        r.tbrolename
      FROM tbservicerating sr
      INNER JOIN tbrole r ON r.tbroleid = sr.tbserviceratingroleid
      WHERE sr.tbserviceratingserviceid = :idService
        AND sr.tbserviceratingactive = true
      ORDER BY sr.tbserviceratingid DESC
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute([':idService' => $idService]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
  // PROMEDIO GLOBAL DE ESTRELLAS (todos los servicios)
  // =========================================================
  public function averageStars(): ?float
  {
    $sql = "
      SELECT AVG(tbserviceratingstars) AS average
      FROM tbservicerating
      WHERE tbserviceratingactive = true
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row && $row['average'] !== null ? (float) $row['average'] : null;
  }

  // =========================================================
  // TOTAL DE CALIFICACIONES (todos los servicios)
  // =========================================================
  public function countAll(): int
  {
    $sql = "
      SELECT COUNT(*) AS total
      FROM tbservicerating
      WHERE tbserviceratingactive = true
    ";

    $stmt = $this->connection->prepare($sql);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
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
