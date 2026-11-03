<?php

require_once __DIR__ . '/../Model/Location.php';

class OrderingService
{
  // Orden léxico "natural" de cadenas: compara minúscula a minúscula,
  // ignora tildes (orden de diccionario en español) y trata números
  // como valores (Salón 2 < Salón 10).
  public static function strings(string $a, string $b): int
  {
    $normalize = static function (string $value): string {
      $value = strtr($value, [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'Ü' => 'U', 'Ñ' => 'N',
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n'
      ]);

      return strtolower(trim($value));
    };

    return strnatcmp($normalize($a), $normalize($b));
  }

  // Algoritmo léxico genérico sobre secuencias: compara componente a
  // componente con strings(); el primer elemento que difiera decide.
  // Si una secuencia es prefijo de la otra, la más corta va primero.
  public static function sequences(array $a, array $b): int
  {
    $n = min(count($a), count($b));

    for ($i = 0; $i < $n; $i++) {
      $cmp = self::strings((string) $a[$i], (string) $b[$i]);
      if ($cmp !== 0) {
        return $cmp;
      }
    }

    return count($a) <=> count($b);
  }

  // Orden léxico de ubicaciones por tupla (provincia, cantón, distrito).
  public static function locations(Location $a, Location $b): int
  {
    return self::sequences(
      [$a->getProvinceLocation(), $a->getCantonLocation(), $a->getDistrictLocation()],
      [$b->getProvinceLocation(), $b->getCantonLocation(), $b->getDistrictLocation()]
    );
  }
}