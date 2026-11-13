<?php

namespace Modules\LocationSimulator\Simulation;

/**
 * Geographic helpers. All points are [lng, lat] pairs (longitude first, like OSRM).
 */
final class Geo
{
    public const EARTH_RADIUS_METERS = 6371000.0;

    /**
     * Great-circle distance between two points in meters.
     *
     * @param  array{0: float, 1: float}  $from
     * @param  array{0: float, 1: float}  $to
     */
    public static function distanceMeters(array $from, array $to): float
    {
        [$lng1, $lat1] = $from;
        [$lng2, $lat2] = $to;

        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lng2 - $lng1);

        $a = sin($deltaPhi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($deltaLambda / 2) ** 2;

        return 2 * self::EARTH_RADIUS_METERS * asin(min(1.0, sqrt($a)));
    }

    /**
     * Initial compass bearing from one point to another, in degrees [0, 360).
     *
     * @param  array{0: float, 1: float}  $from
     * @param  array{0: float, 1: float}  $to
     */
    public static function bearingDegrees(array $from, array $to): float
    {
        [$lng1, $lat1] = $from;
        [$lng2, $lat2] = $to;

        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $deltaLambda = deg2rad($lng2 - $lng1);

        $y = sin($deltaLambda) * cos($phi2);
        $x = cos($phi1) * sin($phi2) - sin($phi1) * cos($phi2) * cos($deltaLambda);

        return fmod(rad2deg(atan2($y, $x)) + 360.0, 360.0);
    }

    /**
     * Linear interpolation between two points. $fraction 0 is $from, 1 is $to.
     *
     * @param  array{0: float, 1: float}  $from
     * @param  array{0: float, 1: float}  $to
     * @return array{0: float, 1: float}
     */
    public static function interpolate(array $from, array $to, float $fraction): array
    {
        return [
            $from[0] + ($to[0] - $from[0]) * $fraction,
            $from[1] + ($to[1] - $from[1]) * $fraction,
        ];
    }
}
