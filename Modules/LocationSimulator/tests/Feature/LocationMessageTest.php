<?php

namespace Modules\LocationSimulator\Tests\Feature;

use App\Shared\Data\DriverLocation;
use Illuminate\Support\Carbon;
use Modules\LocationSimulator\Support\LocationMessage;
use Tests\TestCase;

class LocationMessageTest extends TestCase
{
    private function payload(): array
    {
        return LocationMessage::toPayload(new DriverLocation(
            driverId: 'drv_001',
            lat: 50.4501,
            lng: 30.5234,
            recordedAt: Carbon::parse('2026-11-13T10:00:00+02:00'),
            heading: 90,
            speedKmh: 42.5,
            accuracyM: 6.5,
        ));
    }

    public function test_message_has_exactly_the_consumer_fields(): void
    {
        $payload = $this->payload();

        $this->assertSame(
            ['schema_version', 'driver_id', 'lat', 'lng', 'heading', 'speed_kmh', 'accuracy_m', 'recorded_at'],
            array_keys($payload),
        );
        $this->assertSame(1, $payload['schema_version']);
        $this->assertSame('drv_001', $payload['driver_id']);
        $this->assertSame(90, $payload['heading']);
        $this->assertSame(42.5, $payload['speed_kmh']);
        $this->assertSame(6.5, $payload['accuracy_m']);
    }

    public function test_coordinates_are_in_range(): void
    {
        $payload = $this->payload();

        $this->assertGreaterThanOrEqual(-90, $payload['lat']);
        $this->assertLessThanOrEqual(90, $payload['lat']);
        $this->assertGreaterThanOrEqual(-180, $payload['lng']);
        $this->assertLessThanOrEqual(180, $payload['lng']);
    }

    public function test_recorded_at_is_a_utc_iso_8601_timestamp(): void
    {
        $recordedAt = $this->payload()['recorded_at'];

        $this->assertSame('2026-11-13T08:00:00+00:00', $recordedAt);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/', $recordedAt);
        $this->assertSame($recordedAt, Carbon::parse($recordedAt)->utc()->toIso8601String());
    }

    public function test_the_consumer_dto_accepts_the_message(): void
    {
        $location = DriverLocation::fromArray($this->payload());

        $this->assertSame('drv_001', $location->driverId);
        $this->assertSame(90, $location->heading);
    }
}
