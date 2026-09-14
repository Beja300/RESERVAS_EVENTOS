<?php

require_once __DIR__ . '/../Model/Location.php';

/**
 * GeoService — cálculos geográficos para el ranking/recomendación.
 *
 * La fórmula de Haversine calcula la distancia de "línea recta" sobre la
 * superficie de la esfera terrestre a partir de latitud/longitud en grados.
 */
class GeoService
{
    private const EARTH_RADIUS_KM = 6371.0;

    /**
     * Distancia en kilómetros entre dos coordenadas (fórmula de Haversine).
     * Devuelve null si alguna coordenada falta o es inválida.
     */
    public static function haversineKm(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;

        $a = max(0.0, min(1.0, $a));

        return 2 * self::EARTH_RADIUS_KM * asin(sqrt($a));
    }

    /**
     * Distancia en kilómetros entre dos ubicaciones.
     * Solo es exacta cuando ambas tienen latitud/longitud.
     */
    public static function distanceKm(?Location $a, ?Location $b): ?float
    {
        if ($a === null || $b === null) {
            return null;
        }

        return self::haversineKm(
            $a->getLatitudeLocation(),
            $a->getLongitudeLocation(),
            $b->getLatitudeLocation(),
            $b->getLongitudeLocation()
        );
    }

    /**
     * Puntaje de proximidad (0..1) entre la ubicación del cliente y la del local.
     *
     * - Con ambas coordenadas: 1 / (1 + d) donde d es la distancia en km.
     * - Sin coordenadas: fallback por niveles administrativos, dando prioridad
     *   al distrito (más puntual que la provincia).
     * - Sin ubicación de cliente o de local: 0.5 neutro.
     */
    public static function proximityScore(?Location $clientLocation, ?Location $venueLocation): float
    {
        if ($clientLocation === null || $venueLocation === null) {
            return 0.5;
        }

        $distance = self::distanceKm($clientLocation, $venueLocation);

        if ($distance !== null && is_finite($distance)) {
            return 1 / (1 + $distance);
        }

        if ($clientLocation->getProvinceLocation() !== $venueLocation->getProvinceLocation()) {
            return 0.1;
        }

        if ($clientLocation->getCantonLocation() === $venueLocation->getCantonLocation()) {
            return $clientLocation->getDistrictLocation() === $venueLocation->getDistrictLocation()
                ? 1.0
                : 0.7;
        }

        return 0.4;
    }

    /**
     * Etiqueta legible de distancia: "a X km". Devuelve null si no se puede
     * calcular (faltan coordenadas).
     */
    public static function distanceLabel(?Location $clientLocation, ?Location $venueLocation): ?string
    {
        $distance = self::distanceKm($clientLocation, $venueLocation);

        if ($distance === null) {
            return null;
        }

        if ($distance < 1) {
            return 'a ' . number_format($distance * 1000, 0) . ' m';
        }

        return 'a ' . number_format($distance, 1) . ' km';
    }
}