<?php

namespace Modules\LocationSimulator\Console\Commands;

use App\Shared\Data\DriverLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\LocationSimulator\Services\DriverLocationPublisher;
use Modules\LocationSimulator\Services\RouteLoader;
use Modules\LocationSimulator\Simulation\DriverSimulator;
use Modules\LocationSimulator\Support\LocationMessage;
use Throwable;

class RunDriverSimulation extends Command
{
    protected $signature = 'simulator:drivers
        {--drivers= : Number of simulated drivers (default: config locationsimulator.drivers)}
        {--interval= : Seconds between ticks (default: env LOCATION_INTERVAL_SECONDS)}
        {--min-speed= : Minimum driver speed in km/h}
        {--max-speed= : Maximum driver speed in km/h}
        {--ticks=0 : Stop after this many ticks (0 runs until stopped)}
        {--dry-run : Print messages to the console instead of publishing to Kafka}';

    protected $description = 'Simulate drivers moving along Kyiv routes and publish their locations to Kafka';

    private bool $running = true;

    public function handle(): int
    {
        $driverCount = (int) ($this->option('drivers') ?? config('locationsimulator.drivers'));
        $interval = (float) ($this->option('interval') ?? config('locationsimulator.interval_seconds'));
        $minSpeed = (float) ($this->option('min-speed') ?? config('locationsimulator.min_speed_kmh'));
        $maxSpeed = (float) ($this->option('max-speed') ?? config('locationsimulator.max_speed_kmh'));
        $maxTicks = (int) $this->option('ticks');
        $dryRun = (bool) $this->option('dry-run');

        if ($driverCount < 1 || $interval <= 0 || $minSpeed <= 0 || $minSpeed > $maxSpeed) {
            $this->error('Invalid options: need drivers >= 1, interval > 0 and 0 < min-speed <= max-speed.');

            return self::FAILURE;
        }

        $routes = (new RouteLoader((string) config('locationsimulator.routes_path')))->load();
        $simulator = new DriverSimulator(speedVariation: (float) config('locationsimulator.speed_variation'));
        $prefix = (string) config('locationsimulator.driver_id_prefix');

        $drivers = [];
        for ($index = 1; $index <= $driverCount; $index++) {
            // The golden ratio spreads start positions evenly along each route.
            $drivers[] = $simulator->spawn(
                driverId: $prefix.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                route: $routes[($index - 1) % count($routes)],
                startFraction: fmod($index * 0.6180339887, 1.0),
                minSpeedKmh: $minSpeed,
                maxSpeedKmh: $maxSpeed,
            );
        }

        $publisher = $dryRun ? null : app(DriverLocationPublisher::class);

        $this->listenForStopSignals();
        $this->info(sprintf(
            'Simulating %d drivers on %d routes every %s s%s. Press Ctrl+C to stop.',
            $driverCount,
            count($routes),
            rtrim(rtrim(number_format($interval, 3, '.', ''), '0'), '.'),
            $dryRun ? ' (dry run)' : '',
        ));

        $tick = 0;
        while ($this->running && ($maxTicks === 0 || $tick < $maxTicks)) {
            $tick++;
            $startedAt = microtime(true);
            $sent = 0;
            $failed = 0;

            foreach ($drivers as $driver) {
                $position = $simulator->tick($driver, $interval);
                $location = new DriverLocation(
                    driverId: $position->driverId,
                    lat: $position->lat,
                    lng: $position->lng,
                    recordedAt: now()->utc(),
                    heading: $position->heading,
                    speedKmh: $position->speedKmh,
                    accuracyM: $position->accuracyM,
                );

                try {
                    if ($publisher === null) {
                        $this->line(json_encode(LocationMessage::toPayload($location), JSON_THROW_ON_ERROR));
                    } else {
                        $publisher->publish($location);
                    }
                    $sent++;
                } catch (Throwable $exception) {
                    $failed++;
                    Log::error('Could not publish driver location.', [
                        'driver_id' => $location->driverId,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            $this->line(sprintf('[%s] tick %d: sent %d messages%s', now()->utc()->toIso8601String(), $tick, $sent, $failed > 0 ? ", {$failed} failed" : ''));

            if ($maxTicks === 0 || $tick < $maxTicks) {
                $this->sleepUnlessStopped($interval - (microtime(true) - $startedAt));
            }
        }

        try {
            $publisher?->flush();
        } catch (Throwable $exception) {
            Log::error('Could not flush the publisher.', ['error' => $exception->getMessage()]);
        }

        $this->info('Stopped.');

        return self::SUCCESS;
    }

    /**
     * Stop the loop on SIGINT/SIGTERM so the publisher can be flushed and the exit code is 0.
     */
    private function listenForStopSignals(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        foreach ([SIGINT, SIGTERM] as $signal) {
            pcntl_signal($signal, function (): void {
                $this->running = false;
            });
        }
    }

    private function sleepUnlessStopped(float $seconds): void
    {
        $until = microtime(true) + $seconds;

        while ($this->running && microtime(true) < $until) {
            usleep((int) min(100000, max(0, ($until - microtime(true)) * 1000000)));
        }
    }
}
