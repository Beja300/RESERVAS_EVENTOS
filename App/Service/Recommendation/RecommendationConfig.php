<?php

require_once __DIR__ . '/../../Model/HistoryAction.php';

/**
 * RecommendationConfig — pesos y constantes del sistema híbrido.
 *
 * El motor combina 4 señales (estrategias) ponderadas:
 *
 *   - geo           proximidad geográfica (Haversine o fallback de distrito)
 *   - rating        calificación promedio del local
 *   - content       afinidad por tipo de local según el historial del usuario
 *   - collaborative popularidad global ponderada de todos los usuarios
 *
 * Cada modo usa sus propios pesos (definidos en el `README` del proyecto).
 * TODO(ML): cuando se introduzca un modelo de ML, bastará con agregar su
 * estrategia aquí y ajustar los pesos, sin tocar el resto del motor.
 */
class RecommendationConfig
{
  public const MODE_CATALOG = 'catalog';
  public const MODE_NEARBY  = 'nearby';

  public const WEIGHTS_CATALOG = [
    'geo'           => 0.40,
    'rating'        => 0.25,
    'content'       => 0.20,
    'collaborative' => 0.15,
  ];

  public const WEIGHTS_NEARBY = [
    'geo'           => 0.60,
    'rating'        => 0.20,
    'content'       => 0.05,
    'collaborative' => 0.15,
  ];

  public const ENTITY_VENUE = 'Venue';

  /**
   * Peso de cada interacción para el puntaje colaborativo global.
   * Las acciones positivas suman popularidad; CANCEL la resta.
   */
  public const INTERACTION_WEIGHTS = [
    HistoryAction::VIEW      => 1,
    HistoryAction::SEARCH    => 1,
    HistoryAction::FAVORITE  => 4,
    HistoryAction::BOOKING   => 7,
    HistoryAction::PURCHASE  => 10,
    HistoryAction::RATING    => 6,
    HistoryAction::CANCEL    => -4,
  ];

  public static function weightsFor(string $mode): array
  {
    if ($mode === self::MODE_NEARBY) {
      return self::WEIGHTS_NEARBY;
    }

    return self::WEIGHTS_CATALOG;
  }
}