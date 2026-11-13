<?php

namespace Modules\LocationSimulator\Simulation;

final class DriverState
{
    /**
     * @param  float  $distanceMeters  Distance travelled along the route from its start.
     * @param  int  $direction  1 towards the end of the route, -1 back towards the start.
     */
    public function __construct(
        public readonly string $driverId,
        public readonly Route $route,
        public readonly float $baseSpeedKmh,
        public float $distanceMeters = 0.0,
        public int $direction = 1,
    ) {}
}
