<?php

require_once __DIR__ . '/RecommendationStrategy.php';
require_once __DIR__ . '/RecommendationContext.php';
require_once __DIR__ . '/../VenueService.php';
require_once __DIR__ . '/../../Model/HistoryAction.php';

/**
 * ContentStrategy — afinidad por contenido.
 *
 * Señal "content": puntúa los locales según el tipo de local que el usuario
 * más ha visto/buscado/reservado/calificado en su historial. Los locales que
 * coinciden con el tipo favorito obtienen puntaje proporcional al peso que el
 * tipo tiene en su historial; los demás, 0.
 *
 * Sin historial útil => todos los puntajes en 0 (la estrategia queda neutra).
 */
class ContentStrategy implements RecommendationStrategy
{
  /** @var array<int, Venue> */
  private array $venueById = [];

  /** @var array<string, int> mapa tipo => puntaje del historial */
  private array $typeScores = [];

  /** @var bool */
  private bool $built = false;

  public function scoreVenues(array $venueIds, RecommendationContext $ctx): array
  {
    $this->build($ctx);

    if (empty($this->typeScores)) {
      return [];
    }

    $max = max($this->typeScores);

    if ($max <= 0) {
      return [];
    }

    $scores = [];
    foreach ($venueIds as $id) {
      $venue = $this->venueById[$id] ?? null;
      if ($venue === null) {
        $scores[$id] = 0.0;
        continue;
      }

      $type = $venue->getTypeVenue();
      if ($type === '' || !isset($this->typeScores[$type])) {
        $scores[$id] = 0.0;
        continue;
      }

      $scores[$id] = (float) ($this->typeScores[$type] / $max);
    }

    return $scores;
  }

  private function build(RecommendationContext $ctx): void
  {
    if ($this->built) {
      return;
    }
    $this->built = true;

    $this->venueById = [];
    foreach ($ctx->venues as $venue) {
      $this->venueById[$venue->getIdVenue()] = $venue;
    }

    $this->typeScores = [];
    foreach ($ctx->venueHistory as $item) {
      if (
        $item->getEntity() !== 'Venue' ||
        $item->getEntityId() === null
      ) {
        continue;
      }

      $venue = $this->venueById[$item->getEntityId()] ?? null;
      if ($venue === null) {
        continue;
      }

      $type = $venue->getTypeVenue();
      if ($type === '') {
        continue;
      }

      $weight = $item->getAction() === HistoryAction::CANCEL ? -1 : 1;
      $this->typeScores[$type] = ($this->typeScores[$type] ?? 0) + $weight;
    }
  }
}