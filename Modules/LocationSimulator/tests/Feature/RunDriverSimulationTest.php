<?php

namespace Modules\LocationSimulator\Tests\Feature;

use App\Shared\Data\DriverLocation;
use Junges\Kafka\Facades\Kafka;
use Modules\LocationSimulator\Services\DriverLocationPublisher;
use Modules\LocationSimulator\Support\LocationMessage;
use Tests\TestCase;

class RunDriverSimulationTest extends TestCase
{
    public function test_dry_run_prints_messages_and_does_not_touch_kafka(): void
    {
        Kafka::fake();

        $this->artisan('simulator:drivers', ['--drivers' => 3, '--interval' => 0.01, '--ticks' => 2, '--dry-run' => true])
            ->expectsOutputToContain('tick 1: sent 3 messages')
            ->expectsOutputToContain('tick 2: sent 3 messages')
            ->expectsOutputToContain('"driver_id":"drv_003"')
            ->assertSuccessful();

        Kafka::assertNothingPublished();
    }

    public function test_it_rejects_invalid_options(): void
    {
        $this->artisan('simulator:drivers', ['--min-speed' => 80, '--max-speed' => 20, '--dry-run' => true])
            ->assertFailed();
    }

    public function test_publisher_sends_the_message_keyed_by_driver_id(): void
    {
        Kafka::fake();

        $location = new DriverLocation(
            driverId: 'drv_007',
            lat: 50.45,
            lng: 30.52,
            recordedAt: now()->utc(),
            heading: 10,
            speedKmh: 40.0,
            accuracyM: 5.0,
        );

        $publisher = new DriverLocationPublisher;
        $publisher->publish($location);
        $publisher->flush();

        Kafka::assertPublishedOn(config('kafka.topics.driver_location'), null, function ($message): bool {
            return $message->getKey() === 'drv_007'
                && $message->getBody() === LocationMessage::toPayload(new DriverLocation(
                    driverId: 'drv_007',
                    lat: 50.45,
                    lng: 30.52,
                    recordedAt: now()->utc(),
                    heading: 10,
                    speedKmh: 40.0,
                    accuracyM: 5.0,
                ));
        });
    }
}
