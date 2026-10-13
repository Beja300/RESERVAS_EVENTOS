<?php

require_once __DIR__ . '/RecommendationStrategy.php';
require_once __DIR__ . '/RecommendationContext.php';
require_once __DIR__ . '/../GeoService.php';

/**
 * LocationStrategy — proximidad geográfica.
 *
 * Señal "geo": usa GeoService::proximityScore, lo cual aplica la fórmula de
 * Haversine cuando cliente y local tienen coordenadas y cae al nivel de
 * distrito/cantón/provincia cuando no las tienen. Sin ubicación del cliente
 * todos los candidatos reciben 0.5 (neutro) y la señal queda sin efecto.
 */
class LocationStrategy implements RecommendationStrategy
{
  public function scoreVenues(array $venueIds, RecommendationContext $ctx): array
  {
    if ($ctx->clientLocation === null) {
      return [];
    }

    $scores = [];
    foreach ($venueIds as $id) {
      $scores[$id] = GeoService::proximityScore(
        $ctx->clientLocation,
        $ctx->locationByVenue[$id] ?? null
      );
    }

    return $scores;
  }
}