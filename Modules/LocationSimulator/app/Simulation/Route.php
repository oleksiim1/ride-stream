<?php

namespace Modules\LocationSimulator\Simulation;

use InvalidArgumentException;

final class Route
{
    /**
     * @var list<array{0: float, 1: float}>
     */
    private array $points = [];

    /**
     * Distance from the start of the route to each point, in meters.
     *
     * @var list<float>
     */
    private array $cumulativeMeters = [0.0];

    /**
     * @param  list<array{0: float, 1: float}>  $points  [lng, lat] pairs
     */
    public function __construct(public readonly string $name, array $points)
    {
        foreach ($points as $point) {
            $last = $this->points[array_key_last($this->points)] ?? null;

            if ($last !== null) {
                $segment = Geo::distanceMeters($last, $point);

                if ($segment <= 0.0) {
                    continue;
                }

                $this->cumulativeMeters[] = end($this->cumulativeMeters) + $segment;
            }

            $this->points[] = $point;
        }

        if (count($this->points) < 2) {
            throw new InvalidArgumentException("Route [{$name}] needs at least two distinct points.");
        }
    }

    public function lengthMeters(): float
    {
        return end($this->cumulativeMeters);
    }

    /**
     * Position and travel heading at a distance from the start of the route.
     * The distance is clamped to the route; the heading is for travelling start to end.
     *
     * @return array{lng: float, lat: float, heading: float}
     */
    public function pointAt(float $distanceMeters): array
    {
        $distance = max(0.0, min($distanceMeters, $this->lengthMeters()));
        $lastSegment = count($this->points) - 2;

        $index = 0;
        while ($index < $lastSegment && $this->cumulativeMeters[$index + 1] < $distance) {
            $index++;
        }

        $from = $this->points[$index];
        $to = $this->points[$index + 1];
        $segmentLength = $this->cumulativeMeters[$index + 1] - $this->cumulativeMeters[$index];
        $fraction = ($distance - $this->cumulativeMeters[$index]) / $segmentLength;
        [$lng, $lat] = Geo::interpolate($from, $to, $fraction);

        return [
            'lng' => $lng,
            'lat' => $lat,
            'heading' => Geo::bearingDegrees($from, $to),
        ];
    }
}
