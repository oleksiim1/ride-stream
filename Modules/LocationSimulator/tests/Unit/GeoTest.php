<?php

namespace Modules\LocationSimulator\Tests\Unit;

use Modules\LocationSimulator\Simulation\Geo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GeoTest extends TestCase
{
    public function test_interpolation_returns_the_end_points_and_the_middle(): void
    {
        $from = [30.0, 50.0];
        $to = [31.0, 52.0];

        $this->assertSame($from, Geo::interpolate($from, $to, 0.0));
        $this->assertSame($to, Geo::interpolate($from, $to, 1.0));
        $this->assertEqualsWithDelta([30.5, 51.0], Geo::interpolate($from, $to, 0.5), 1e-9);
        $this->assertEqualsWithDelta([30.25, 50.5], Geo::interpolate($from, $to, 0.25), 1e-9);
    }

    public function test_distance_is_in_meters_and_longitude_comes_first(): void
    {
        // 0.01 degrees of latitude is about 1112 m.
        $this->assertEqualsWithDelta(1112.0, Geo::distanceMeters([30.0, 50.0], [30.0, 50.01]), 2.0);
        $this->assertSame(0.0, Geo::distanceMeters([30.5, 50.4], [30.5, 50.4]));
    }

    /**
     * @param  array{0: float, 1: float}  $to
     */
    #[DataProvider('bearings')]
    public function test_heading_follows_the_direction_of_travel(array $to, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, Geo::bearingDegrees([30.0, 50.0], $to), 0.5);
    }

    /**
     * @return array<string, array{0: array{0: float, 1: float}, 1: float}>
     */
    public static function bearings(): array
    {
        return [
            'north' => [[30.0, 50.01], 0.0],
            'east' => [[30.01, 50.0], 90.0],
            'south' => [[30.0, 49.99], 180.0],
            'west' => [[29.99, 50.0], 270.0],
        ];
    }
}
