<?php

require_once __DIR__ . '/RecommendationStrategy.php';
require_once __DIR__ . '/RecommendationContext.php';

/**
 * RatingStrategy — calidad percibida.
 *
 * Señal "rating": usa la calificación promedio del local (0..5) ÷ 5 para
 * llevarla a escala 0..1, donde 0 indica "sin calificaciones aún".
 */
class RatingStrategy implements RecommendationStrategy
{
  public function scoreVenues(array $venueIds, RecommendationContext $ctx): array
  {
    $scores = [];
    foreach ($venueIds as $id) {
      $avg = $ctx->ratingsByVenue[$id] ?? 0.0;
      $scores[$id] = $avg > 0 ? $avg / 5.0 : 0.0;
    }

    return $scores;
  }
}