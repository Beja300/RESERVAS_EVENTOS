<?php

require_once __DIR__ . '/RecommendationStrategy.php';
require_once __DIR__ . '/RecommendationContext.php';

/**
 * CollaborativeStrategy — popularidad global ponderada.
 *
 * Señal "collaborative": usa el puntaje de interacción GLOBAL (todos los
 * usuarios) por local, sumando cada acción con su peso de la configuración
 * (VIEW=1, FAVORITE=4, BOOKING=7, PURCHASE=10, RATING=6, CANCEL=-4).
 * No aplica ML; simplemente le da relieve a lo que "a la comunidad en
 * general" le funciona, y por eso se estampa como colaborativa.
 *
 * Los puntajes vienen precargados en el contexto (interactionWeightedScores);
 * sin datos devuelve 0 (neutro).
 */
class CollaborativeStrategy implements RecommendationStrategy
{
  public function scoreVenues(array $venueIds, RecommendationContext $ctx): array
  {
    if (empty($ctx->collaborativeScores)) {
      return [];
    }

    $scores = [];
    foreach ($venueIds as $id) {
      $scores[$id] = (float) ($ctx->collaborativeScores[$id] ?? 0.0);
    }

    return $scores;
  }
}