<?php

require_once __DIR__ . '/BusinessRuleException.php';
require_once __DIR__ . '/../Repository/ServiceRatingRepository.php';
require_once __DIR__ . '/../Repository/ServiceRepository.php';
require_once __DIR__ . '/../Model/ServiceRating.php';

class ServiceRatingService
{
  private ServiceRatingRepository $ratingRepo;
  private ServiceRepository $serviceRepo;

  public function __construct(PDO $connection)
  {
    $this->ratingRepo = new ServiceRatingRepository($connection);
    $this->serviceRepo = new ServiceRepository($connection);
  }

  // =========================================================
  // CALIFICAR UN SERVICIO (cualquier usuario autenticado)
  // =========================================================
  public function rate(int $servicePk, int $rolePk, int $stars, ?string $comment = null): int
  {
    if ($stars < 1 || $stars > 5) {
      throw new BusinessRuleException('La calificación debe ser de 1 a 5 estrellas.');
    }

    if ($this->serviceRepo->findById($servicePk) === null) {
      throw new BusinessRuleException('El servicio a calificar no existe.');
    }

    if ($this->ratingRepo->findByServiceAndRole($servicePk, $rolePk) !== null) {
      throw new BusinessRuleException('Ya calificaste este servicio.');
    }

    return $this->ratingRepo->save(
      new ServiceRating(
        idServiceRating: 0,
        idService: $servicePk,
        idRole: $rolePk,
        stars: $stars,
        comment: $comment ?? ''
      )
    );
  }

  // =========================================================
  // PROMEDIO DE ESTRELLAS (lo que ve el público)
  // =========================================================
  public function getAverage(int $servicePk): ?float
  {
    return $this->ratingRepo->findAverageByService($servicePk);
  }

  // =========================================================
  // DETALLE COMPLETO (lo que ve el propietario)
  // =========================================================
  public function getDetail(int $servicePk): array
  {
    return [
      'ratings' => $this->ratingRepo->findByService($servicePk),
      'count'   => $this->ratingRepo->countByService($servicePk),
      'average' => $this->ratingRepo->findAverageByService($servicePk),
    ];
  }
}
