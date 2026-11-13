<?php

namespace Modules\LocationSimulator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class FetchRoutes extends Command
{
    protected $signature = 'simulator:fetch-routes';

    protected $description = 'Fetch Kyiv route geometries from the OSRM demo server and save them as JSON files (run by hand only)';

    public function handle(): int
    {
        $directory = (string) config('locationsimulator.routes_path');
        $baseUrl = rtrim((string) config('locationsimulator.osrm.base_url'), '/');

        File::ensureDirectoryExists($directory);

        $failed = 0;

        foreach ((array) config('locationsimulator.osrm.routes') as $name => [$start, $end]) {
            $coordinates = implode(';', [implode(',', $start), implode(',', $end)]);

            try {
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->get("{$baseUrl}/route/v1/driving/{$coordinates}", [
                        'overview' => 'full',
                        'geometries' => 'geojson',
                    ])
                    ->throw();

                $geometry = $response->json('routes.0.geometry.coordinates');

                if (! is_array($geometry) || count($geometry) < 2) {
                    throw new RuntimeException('OSRM returned no route geometry.');
                }

                File::put("{$directory}/{$name}.json", json_encode($geometry, JSON_THROW_ON_ERROR).PHP_EOL);
                $this->info("Saved {$name}.json (".count($geometry).' points)');
            } catch (Throwable $exception) {
                $failed++;
                $this->error("Could not fetch {$name}: {$exception->getMessage()}");
            }

            // Be polite to the public demo server.
            sleep(1);
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
