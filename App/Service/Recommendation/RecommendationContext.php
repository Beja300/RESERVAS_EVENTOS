<?php

/**
 * RecommendationContext — paquete de datos que el motor híbrido necesita
 * para rankear locales. Se construye una vez por solicitud y se comparte
 * con todas las estrategias (evita consultas repetidas a la BD).
 */
class RecommendationContext
{
  /** @var Venue[] locales candidatos (activos) */
  public array $venues = [];

  /** @var array<int, Location|null> mapa venueId => ubicación */
  public array $locationByVenue = [];

  /** @var array<int, float> mapa venueId => calificación promedio */
  public array $ratingsByVenue = [];

  /** @var array<int, float> mapa venueId => puntaje colaborativo global */
  public array $collaborativeScores = [];

  /** @var History[] historial completo del rol (para afinidad de contenido) */
  public array $venueHistory = [];

  public ?Location $clientLocation = null;

  public function __construct(array $venues = [])
  {
    $this->venues = $venues;
  }
}