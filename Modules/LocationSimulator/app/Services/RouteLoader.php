<?php

namespace Modules\LocationSimulator\Services;

use JsonException;
use Modules\LocationSimulator\Simulation\Route;
use RuntimeException;

class RouteLoader
{
    public function __construct(private readonly string $directory) {}

    /**
     * Load every *.json file in the routes directory. Each file is an OSRM geometry:
     * a list of [lng, lat] points (longitude first).
     *
     * @return list<Route>
     */
    public function load(): array
    {
        $files = glob(rtrim($this->directory, '/\\').DIRECTORY_SEPARATOR.'*.json') ?: [];
        sort($files);

        $routes = [];
        foreach ($files as $file) {
            $routes[] = new Route(pathinfo($file, PATHINFO_FILENAME), $this->readPoints($file));
        }

        if ($routes === []) {
            throw new RuntimeException("No route files found in [{$this->directory}]. Run simulator:fetch-routes first.");
        }

        return $routes;
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    private function readPoints(string $file): array
    {
        try {
            $decoded = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Route file [{$file}] is not valid JSON.", previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException("Route file [{$file}] must contain a list of [lng, lat] points.");
        }

        $points = [];
        foreach ($decoded as $point) {
            if (! is_array($point) || count($point) < 2 || ! is_numeric($point[0]) || ! is_numeric($point[1])) {
                throw new RuntimeException("Route file [{$file}] must contain a list of [lng, lat] points.");
            }

            $lng = (float) $point[0];
            $lat = (float) $point[1];

            if (abs($lng) > 180 || abs($lat) > 90) {
                throw new RuntimeException("Route file [{$file}] has a point out of range: [{$lng}, {$lat}]. Points are [lng, lat].");
            }

            $points[] = [$lng, $lat];
        }

        return $points;
    }
}
