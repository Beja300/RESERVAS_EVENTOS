<?php

require_once __DIR__ . '/RecommendationConfig.php';
require_once __DIR__ . '/RecommendationContext.php';
require_once __DIR__ . '/RecommendationStrategy.php';
require_once __DIR__ . '/ContentStrategy.php';
require_once __DIR__ . '/CollaborativeStrategy.php';
require_once __DIR__ . '/LocationStrategy.php';
require_once __DIR__ . '/RatingStrategy.php';

/**
 * HybridEngine — motor de recomendación híbrido por reglas (+ geográfica).
 *
 * Combina cuatro señales normalizadas, cada una con su peso según el modo:
 *
 *   catálogo   geo=0.40 rating=0.25 content=0.20 collaborative=0.15
 *   cerca      geo=0.60 rating=0.20 content=0.05 collaborative=0.15
 *
 * Las señales "vacías" (sin datos de historial, sin coordenadas, etc.) se
 * anulan a 0 para no deformar el ranking; las demás se normalizan a [0,1].
 *
 * TODO(ML): el día que exista un modelo entrenado, se agrega una estrategia
 * "ml" más en el HybridEngine y sus pesos en RecommendationConfig; el resto
 * del flujo no cambia.
 */
class HybridEngine
{
  /** @var array<string, RecommendationStrategy> */
  private array $strategies;

  public function __construct()
  {
    $this->strategies = [
      'geo'           => new LocationStrategy(),
      'rating'        => new RatingStrategy(),
      'content'       => new ContentStrategy(),
      'collaborative' => new CollaborativeStrategy(),
    ];
  }

  /**
   * @param Venue[] $venues candidatos (activos, ya filtrados por el llamador)
   * @return Venue[] candidatos rankeados por el híbrido
   */
  public function rankVenues(array $venues, RecommendationContext $ctx, string $mode, int $limit): array
  {
    if (empty($venues) || $limit <= 0) {
      return [];
    }

    $ctx->venues = $venues;

    $venueIds = [];
    foreach ($venues as $venue) {
      $venueIds[$venue->getIdVenue()] = true;
    }
    $venueIds = array_keys($venueIds);

    $weights = RecommendationConfig::weightsFor($mode);

    $total = [];
    foreach ($venueIds as $id) {
      $total[$id] = 0.0;
    }

    foreach ($weights as $key => $weight) {
      $strategy = $this->strategies[$key] ?? null;
      if ($strategy === null || $weight <= 0) {
        continue;
      }

      $normalized = $this->normalize($strategy->scoreVenues($venueIds, $ctx));

      if (empty($normalized)) {
        continue;
      }

      foreach ($venueIds as $id) {
        $total[$id] += $weight * $normalized[$id];
      }
    }

    $venueById = [];
    foreach ($venues as $venue) {
      $venueById[$venue->getIdVenue()] = $venue;
    }

    $order = $venueIds;
    usort($order, static function (int $a, int $b) use ($total, $ctx, $venueById): int {
      if ($total[$a] !== $total[$b]) {
        return $total[$b] <=> $total[$a];
      }

      $ratingA = $ctx->ratingsByVenue[$a] ?? 0.0;
      $ratingB = $ctx->ratingsByVenue[$b] ?? 0.0;
      if ($ratingA !== $ratingB) {
        return $ratingB <=> $ratingA;
      }

      return strcasecmp(
        (string) ($venueById[$a]->getNameVenue() ?? ''),
        (string) ($venueById[$b]->getNameVenue() ?? '')
      );
    });

    $result = [];
    foreach (array_slice($order, 0, $limit) as $id) {
      $result[] = $venueById[$id];
    }

    return $result;
  }

  /**
   * Normaliza min-max un mapa de puntajes raw a [0,1].
   * Si no hay datos o todos son iguales, devuelve 0 para todos (señal neutra).
   */
  private function normalize(array $raw): array
  {
    if (empty($raw)) {
      return [];
    }

    $values = array_values($raw);
    $min = min($values);
    $max = max($values);

    if ($max === $min) {
      $out = [];
      foreach ($raw as $id => $_score) {
        $out[$id] = 0.0;
      }
      return $out;
    }

    $range = $max - $min;

    $out = [];
    foreach ($raw as $id => $score) {
      $out[$id] = ($score - $min) / $range;
    }

    return $out;
  }
}