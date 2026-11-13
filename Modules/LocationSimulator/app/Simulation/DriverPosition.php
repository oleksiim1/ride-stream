<?php

namespace Modules\LocationSimulator\Simulation;

final readonly class DriverPosition
{
    public function __construct(
        public string $driverId,
        public float $lat,
        public float $lng,
        public int $heading,
        public float $speedKmh,
        public float $accuracyM,
    ) {}
}
