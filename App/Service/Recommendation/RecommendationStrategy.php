<?php

require_once __DIR__ . '/RecommendationContext.php';

/**
 * RecommendationStrategy — contrato de las señales del motor híbrido.
 *
 * Cada estrategia recibe los venues candidatos y el contexto y devuelve un
 * mapa venueId => puntaje RAW (sin normalizar). El HybridEngine normaliza
 * cada señal a [0,1] antes de combinarla con los pesos del modo activo.
 */
interface RecommendationStrategy
{
  /**
   * @param int[] $venueIds ids de los venues candidatos (deduplicados)
   * @return array<int, float> mapa venueId => puntaje raw
   */
  public function scoreVenues(array $venueIds, RecommendationContext $ctx): array;
}