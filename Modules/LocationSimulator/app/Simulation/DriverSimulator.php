<?php

namespace Modules\LocationSimulator\Simulation;

use Random\Randomizer;

/**
 * Movement logic for simulated drivers. Pure PHP: no Kafka, no Laravel.
 */
final class DriverSimulator
{
    private const METERS_PER_DEGREE_LAT = 111320.0;

    private readonly Randomizer $random;

    public function __construct(
        ?Randomizer $random = null,
        private readonly float $speedVariation = 0.1,
    ) {
        $this->random = $random ?? new Randomizer;
    }

    /**
     * Create a driver on a route with its own random speed.
     *
     * @param  float  $startFraction  Where on the route the driver starts, 0 (start) to 1 (end).
     */
    public function spawn(
        string $driverId,
        Route $route,
        float $startFraction,
        float $minSpeedKmh,
        float $maxSpeedKmh,
    ): DriverState {
        $fraction = max(0.0, min(1.0, $startFraction));

        return new DriverState(
            driverId: $driverId,
            route: $route,
            baseSpeedKmh: $this->between($minSpeedKmh, $maxSpeedKmh),
            distanceMeters: $route->lengthMeters() * $fraction,
        );
    }

    /**
     * Move the driver for one tick and report where the GPS says it is.
     */
    public function tick(DriverState $driver, float $intervalSeconds): DriverPosition
    {
        $variation = $this->speedVariation;
        $speedKmh = $driver->baseSpeedKmh * (1 + $this->between(-$variation, $variation));

        $this->advance($driver, $speedKmh / 3.6 * $intervalSeconds);

        $point = $driver->route->pointAt($driver->distanceMeters);
        $heading = $driver->direction === 1 ? $point['heading'] : $point['heading'] + 180.0;

        $accuracyM = round($this->between(3.0, 10.0), 1);
        $northMeters = $this->between(-$accuracyM / 2, $accuracyM / 2);
        $eastMeters = $this->between(-$accuracyM / 2, $accuracyM / 2);
        $metersPerDegreeLng = self::METERS_PER_DEGREE_LAT * max(0.01, cos(deg2rad($point['lat'])));

        return new DriverPosition(
            driverId: $driver->driverId,
            lat: round($point['lat'] + $northMeters / self::METERS_PER_DEGREE_LAT, 6),
            lng: round($point['lng'] + $eastMeters / $metersPerDegreeLng, 6),
            heading: (int) round(fmod($heading, 360.0)) % 360,
            speedKmh: round($speedKmh, 1),
            accuracyM: $accuracyM,
        );
    }

    /**
     * Move the driver along its route. At either end of the route it turns around
     * and keeps going with whatever distance is left.
     */
    public function advance(DriverState $driver, float $meters): void
    {
        $length = $driver->route->lengthMeters();
        $remaining = max(0.0, $meters);

        while ($remaining > 0.0) {
            $room = $driver->direction === 1 ? $length - $driver->distanceMeters : $driver->distanceMeters;

            if ($remaining <= $room) {
                $driver->distanceMeters += $driver->direction * $remaining;

                return;
            }

            $driver->distanceMeters = $driver->direction === 1 ? $length : 0.0;
            $remaining -= $room;
            $driver->direction = -$driver->direction;
        }
    }

    /**
     * A random float between two bounds. Randomizer::getFloat() rejects equal bounds.
     */
    private function between(float $min, float $max): float
    {
        return $max > $min ? $this->random->getFloat($min, $max) : $min;
    }
}
