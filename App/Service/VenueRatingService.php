<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/VenueRatingRepository.php';
require_once __DIR__ . '/../Repository/VenueRepository.php';
require_once __DIR__ . '/../Model/VenueRating.php';

class VenueRatingService
{
  private VenueRatingRepository $ratingRepo;
  private VenueRepository $venueRepo;

  public function __construct(PDO $connection)
  {
    $this->ratingRepo = new VenueRatingRepository($connection);
    $this->venueRepo = new VenueRepository($connection);
  }

  // =========================================================
  // COMENTARIO NUEVO (cualquier usuario autenticado)
  // Permite varios comentarios por usuario sobre el mismo local:
  // siempre inserta un registro nuevo.
  // =========================================================
  public function rate(int $venuePk, int $rolePk, int $stars, ?string $comment = null): int
  {
    if ($stars < 1 || $stars > 5) {
      throw new BusinessRuleException('La calificación debe ser de 1 a 5 estrellas.');
    }

    if ($this->venueRepo->findById($venuePk) === null) {
      throw new BusinessRuleException('El local a calificar no existe.');
    }

    return $this->ratingRepo->save(
      new VenueRating(
        idVenueRating: 0,
        idVenue: $venuePk,
        idRole: $rolePk,
        stars: $stars,
        comment: $comment ?? ''
      )
    );
  }

  // =========================================================
  // EDITAR UN COMENTARIO PROPIO (solo el autor puede modificarlo)
  // =========================================================
  public function updateComment(int $idVenueRating, int $rolePk, int $stars, ?string $comment = null): void
  {
    if ($stars < 1 || $stars > 5) {
      throw new BusinessRuleException('La calificación debe ser de 1 a 5 estrellas.');
    }

    $rating = $this->ratingRepo->findById($idVenueRating);

    if ($rating === null) {
      throw new BusinessRuleException('El comentario que intentas editar no existe.');
    }

    if ($rating->getIdRole() !== $rolePk) {
      throw new BusinessRuleException('No puedes editar el comentario de otro usuario.');
    }

    $rating->setStars($stars);
    $rating->setComment($comment ?? '');

    $this->ratingRepo->update($rating);
  }

  // =========================================================
  // PROMEDIO DE ESTRELLAS (lo que ve el público)
  // =========================================================
  public function getAverage(int $venuePk): ?float
  {
    return $this->ratingRepo->findAverageByVenue($venuePk);
  }

  // =========================================================
  // CALIFICACIÓN EXISTENTE DE UN ROL SOBRE EL LOCAL
  // =========================================================
  public function getByVenueAndRole(int $venuePk, int $rolePk): ?VenueRating
  {
    return $this->ratingRepo->findByVenueAndRole($venuePk, $rolePk);
  }

  // =========================================================
  // COMENTARIOS PÚBLICOS DE UN LOCAL
  // =========================================================
  public function getPublicComments(int $venuePk): array
  {
    return $this->ratingRepo->findByVenueWithUser($venuePk);
  }

  // =========================================================
  // DETALLE COMPLETO (lo que ve el propietario)
  // =========================================================
  public function getDetail(int $venuePk): array
  {
    return [
      'ratings' => $this->ratingRepo->findByVenue($venuePk),
      'count'   => $this->ratingRepo->countByVenue($venuePk),
      'average' => $this->ratingRepo->findAverageByVenue($venuePk),
    ];
  }
}
