<?php

namespace Modules\LocationSimulator\Tests\Unit;

use Modules\LocationSimulator\Services\RouteLoader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RouteLoaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'route-loader-'.bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory.DIRECTORY_SEPARATOR.'*') ?: []);
        rmdir($this->directory);

        parent::tearDown();
    }

    public function test_loads_every_json_file_as_a_route(): void
    {
        file_put_contents($this->directory.'/b-route.json', '[[30.0,50.0],[30.0,50.01]]');
        file_put_contents($this->directory.'/a-route.json', '[[30.5,50.4],[30.5,50.41],[30.5,50.42]]');

        $routes = (new RouteLoader($this->directory))->load();

        $this->assertSame(['a-route', 'b-route'], array_map(fn ($route) => $route->name, $routes));
        $this->assertEqualsWithDelta(1112.0, $routes[1]->lengthMeters(), 2.0);
    }

    public function test_points_are_read_as_lng_then_lat(): void
    {
        file_put_contents($this->directory.'/route.json', '[[30.5234,50.4501],[30.5234,50.4601]]');

        $route = (new RouteLoader($this->directory))->load()[0];

        $this->assertEqualsWithDelta(50.4501, $route->pointAt(0)['lat'], 1e-9);
        $this->assertEqualsWithDelta(30.5234, $route->pointAt(0)['lng'], 1e-9);
    }

    public function test_rejects_swapped_coordinates(): void
    {
        file_put_contents($this->directory.'/route.json', '[[200.0,30.0],[200.1,30.0]]');

        $this->expectException(RuntimeException::class);

        (new RouteLoader($this->directory))->load();
    }

    public function test_rejects_invalid_json(): void
    {
        file_put_contents($this->directory.'/route.json', 'not json');

        $this->expectException(RuntimeException::class);

        (new RouteLoader($this->directory))->load();
    }

    public function test_fails_when_there_are_no_route_files(): void
    {
        $this->expectException(RuntimeException::class);

        (new RouteLoader($this->directory))->load();
    }

    public function test_committed_routes_are_valid_kyiv_routes(): void
    {
        $routes = (new RouteLoader(dirname(__DIR__, 2).'/resources/routes'))->load();

        $this->assertGreaterThanOrEqual(3, count($routes));

        foreach ($routes as $route) {
            $start = $route->pointAt(0);

            $this->assertEqualsWithDelta(50.45, $start['lat'], 0.2, $route->name);
            $this->assertEqualsWithDelta(30.52, $start['lng'], 0.3, $route->name);
            $this->assertGreaterThan(1000.0, $route->lengthMeters(), $route->name);
        }
    }
}
