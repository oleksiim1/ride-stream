<?php

namespace Modules\LocationSimulator\Tests\Unit;

use InvalidArgumentException;
use Modules\LocationSimulator\Simulation\DriverSimulator;
use Modules\LocationSimulator\Simulation\Route;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;

class DriverSimulatorTest extends TestCase
{
    /**
     * A straight route going north, about 1112 m long, with a middle point.
     */
    private function northRoute(): Route
    {
        return new Route('north', [[30.0, 50.0], [30.0, 50.005], [30.0, 50.01]]);
    }

    private function simulator(float $speedVariation = 0.0): DriverSimulator
    {
        return new DriverSimulator(new Randomizer(new Mt19937(42)), $speedVariation);
    }

    public function test_route_interpolates_between_points(): void
    {
        $route = $this->northRoute();
        $half = $route->pointAt($route->lengthMeters() / 2);
        $quarter = $route->pointAt($route->lengthMeters() / 4);

        $this->assertEqualsWithDelta(50.005, $half['lat'], 1e-6);
        $this->assertEqualsWithDelta(50.0025, $quarter['lat'], 1e-6);
        $this->assertEqualsWithDelta(30.0, $quarter['lng'], 1e-9);
        $this->assertEqualsWithDelta(0.0, $quarter['heading'], 0.5);
    }

    public function test_route_rejects_fewer_than_two_distinct_points(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Route('broken', [[30.0, 50.0], [30.0, 50.0]]);
    }

    public function test_driver_advances_speed_times_interval(): void
    {
        $simulator = $this->simulator();
        $driver = $simulator->spawn('drv_001', $this->northRoute(), 0.0, 36.0, 36.0);

        $simulator->tick($driver, 5.0);

        // 36 km/h is 10 m/s, so 5 seconds is 50 m.
        $this->assertEqualsWithDelta(50.0, $driver->distanceMeters, 0.001);

        $simulator->tick($driver, 10.0);

        $this->assertEqualsWithDelta(150.0, $driver->distanceMeters, 0.001);
    }

    public function test_tick_reports_heading_speed_and_accuracy(): void
    {
        $simulator = $this->simulator();
        $driver = $simulator->spawn('drv_001', $this->northRoute(), 0.1, 36.0, 36.0);

        $position = $simulator->tick($driver, 5.0);

        $this->assertSame('drv_001', $position->driverId);
        $this->assertSame(0, $position->heading);
        $this->assertSame(36.0, $position->speedKmh);
        $this->assertGreaterThanOrEqual(3.0, $position->accuracyM);
        $this->assertLessThanOrEqual(10.0, $position->accuracyM);
    }

    public function test_gps_noise_is_small(): void
    {
        $simulator = $this->simulator();
        $route = $this->northRoute();
        $driver = $simulator->spawn('drv_001', $route, 0.0, 36.0, 36.0);

        $position = $simulator->tick($driver, 5.0);
        $truth = $route->pointAt(50.0);

        // Noise stays within a few meters (0.0001 degrees is about 11 m).
        $this->assertEqualsWithDelta($truth['lat'], $position->lat, 0.0001);
        $this->assertEqualsWithDelta($truth['lng'], $position->lng, 0.0001);
    }

    public function test_speed_varies_a_little_around_the_base_speed(): void
    {
        $simulator = $this->simulator(0.1);
        $driver = $simulator->spawn('drv_001', $this->northRoute(), 0.0, 40.0, 40.0);

        $speeds = [];
        for ($i = 0; $i < 20; $i++) {
            $driver->distanceMeters = 0.0;
            $speeds[] = $simulator->tick($driver, 1.0)->speedKmh;
        }

        $this->assertGreaterThanOrEqual(36.0, min($speeds));
        $this->assertLessThanOrEqual(44.0, max($speeds));
        $this->assertGreaterThan(1, count(array_unique($speeds)));
    }

    public function test_driver_turns_around_at_the_end_of_the_route(): void
    {
        $simulator = $this->simulator();
        $route = $this->northRoute();
        $driver = $simulator->spawn('drv_001', $route, 0.0, 36.0, 36.0);
        $length = $route->lengthMeters();

        $simulator->advance($driver, $length + 100.0);

        $this->assertSame(-1, $driver->direction);
        $this->assertEqualsWithDelta($length - 100.0, $driver->distanceMeters, 0.001);
    }

    public function test_driver_heads_back_after_turning_around(): void
    {
        $simulator = $this->simulator();
        $route = $this->northRoute();
        $driver = $simulator->spawn('drv_001', $route, 1.0, 36.0, 36.0);

        $position = $simulator->tick($driver, 5.0);

        $this->assertSame(-1, $driver->direction);
        $this->assertSame(180, $position->heading);
        $this->assertEqualsWithDelta($route->lengthMeters() - 50.0, $driver->distanceMeters, 0.001);
    }

    public function test_driver_turns_around_again_at_the_start_and_never_leaves_the_route(): void
    {
        $simulator = $this->simulator();
        $route = $this->northRoute();
        $driver = $simulator->spawn('drv_001', $route, 0.5, 36.0, 36.0);

        for ($i = 0; $i < 200; $i++) {
            $simulator->tick($driver, 5.0);

            $this->assertGreaterThanOrEqual(0.0, $driver->distanceMeters);
            $this->assertLessThanOrEqual($route->lengthMeters(), $driver->distanceMeters);
        }
    }

    public function test_start_positions_follow_the_requested_fraction(): void
    {
        $simulator = $this->simulator();
        $route = $this->northRoute();

        $first = $simulator->spawn('drv_001', $route, 0.25, 30.0, 60.0);
        $second = $simulator->spawn('drv_002', $route, 0.75, 30.0, 60.0);

        $this->assertEqualsWithDelta($route->lengthMeters() * 0.25, $first->distanceMeters, 0.001);
        $this->assertEqualsWithDelta($route->lengthMeters() * 0.75, $second->distanceMeters, 0.001);
        $this->assertGreaterThanOrEqual(30.0, $first->baseSpeedKmh);
        $this->assertLessThanOrEqual(60.0, $first->baseSpeedKmh);
    }
}
