<?php

namespace Modules\LocationTracker\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\LocationTracker\Enums\DriverLocationWriteResult;
use Modules\LocationTracker\Services\DriverLocationWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DriverLocationWriterTest extends TestCase
{
    private DriverLocationWriter $writer;

    protected function setUp(): void
    {
        parent::setUp();

        Redis::del(DriverLocationWriter::LOCATIONS_KEY, DriverLocationWriter::LAST_SEEN_KEY);

        $this->writer = new DriverLocationWriter;
    }

    protected function tearDown(): void
    {
        Redis::del(DriverLocationWriter::LOCATIONS_KEY, DriverLocationWriter::LAST_SEEN_KEY);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function message(array $overrides = []): string
    {
        return json_encode([
            'driver_id' => 'drv_001',
            'lat' => 50.4501,
            'lng' => 30.5234,
            'heading' => 90,
            'speed_kmh' => 12.5,
            'accuracy_m' => 8,
            'recorded_at' => '2026-11-13T10:00:00+00:00',
            ...$overrides,
        ], JSON_THROW_ON_ERROR);
    }

    public function test_it_stores_a_valid_location_and_last_seen_timestamp(): void
    {
        $result = $this->writer->handle($this->message());

        $this->assertSame(DriverLocationWriteResult::Stored, $result);

        $position = Redis::geopos(DriverLocationWriter::LOCATIONS_KEY, 'drv_001');
        $this->assertEqualsWithDelta(30.5234, (float) $position[0][0], 0.0001);
        $this->assertEqualsWithDelta(50.4501, (float) $position[0][1], 0.0001);
        $this->assertEquals(
            strtotime('2026-11-13T10:00:00+00:00') * 1000,
            Redis::zscore(DriverLocationWriter::LAST_SEEN_KEY, 'drv_001'),
        );
    }

    public function test_it_accepts_a_location_without_optional_fields(): void
    {
        $result = $this->writer->handle(json_encode([
            'driver_id' => 'drv_001',
            'lat' => 50.4501,
            'lng' => 30.5234,
            'recorded_at' => '2026-11-13T10:00:00+00:00',
        ]));

        $this->assertSame(DriverLocationWriteResult::Stored, $result);
    }

    public function test_it_replaces_the_position_when_a_newer_message_arrives(): void
    {
        $this->writer->handle($this->message());

        $result = $this->writer->handle($this->message([
            'lat' => 50.5,
            'lng' => 30.6,
            'recorded_at' => '2026-11-13T10:00:05+00:00',
        ]));

        $this->assertSame(DriverLocationWriteResult::Stored, $result);
        $position = Redis::geopos(DriverLocationWriter::LOCATIONS_KEY, 'drv_001');
        $this->assertEqualsWithDelta(50.5, (float) $position[0][1], 0.0001);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidMessages(): array
    {
        return [
            'malformed json' => ['{not json'],
            'json scalar' => ['"hello"'],
            'missing driver_id' => [json_encode(['lat' => 1, 'lng' => 1, 'recorded_at' => '2026-11-13T10:00:00Z'])],
            'missing lat' => [json_encode(['driver_id' => 'd', 'lng' => 1, 'recorded_at' => '2026-11-13T10:00:00Z'])],
            'missing recorded_at' => [json_encode(['driver_id' => 'd', 'lat' => 1, 'lng' => 1])],
            'lat too high' => [json_encode(['driver_id' => 'd', 'lat' => 90.1, 'lng' => 1, 'recorded_at' => '2026-11-13T10:00:00Z'])],
            'lng too low' => [json_encode(['driver_id' => 'd', 'lat' => 1, 'lng' => -180.1, 'recorded_at' => '2026-11-13T10:00:00Z'])],
            'bad timestamp' => [json_encode(['driver_id' => 'd', 'lat' => 1, 'lng' => 1, 'recorded_at' => 'yesterday-ish'])],
            'negative speed' => [json_encode(['driver_id' => 'd', 'lat' => 1, 'lng' => 1, 'recorded_at' => '2026-11-13T10:00:00Z', 'speed_kmh' => -1])],
        ];
    }

    #[DataProvider('invalidMessages')]
    public function test_it_skips_an_invalid_message_with_a_warning(string $rawMessage): void
    {
        Log::spy();

        $result = $this->writer->handle($rawMessage);

        $this->assertSame(DriverLocationWriteResult::Invalid, $result);
        Log::shouldHaveReceived('warning')->once();
        $this->assertSame(0, Redis::exists(DriverLocationWriter::LOCATIONS_KEY));
        $this->assertSame(0, Redis::exists(DriverLocationWriter::LAST_SEEN_KEY));
    }

    public function test_it_skips_a_message_older_than_the_last_one_stored(): void
    {
        $this->writer->handle($this->message());

        $result = $this->writer->handle($this->message([
            'lat' => 10,
            'lng' => 10,
            'recorded_at' => '2026-11-13T09:59:59+00:00',
        ]));

        $this->assertSame(DriverLocationWriteResult::Stale, $result);
        $position = Redis::geopos(DriverLocationWriter::LOCATIONS_KEY, 'drv_001');
        $this->assertEqualsWithDelta(50.4501, (float) $position[0][1], 0.0001);
    }

    public function test_it_skips_a_message_with_the_same_timestamp_as_the_last_one_stored(): void
    {
        $this->writer->handle($this->message());

        $result = $this->writer->handle($this->message(['lat' => 10, 'lng' => 10]));

        $this->assertSame(DriverLocationWriteResult::Stale, $result);
    }

    public function test_staleness_is_tracked_per_driver(): void
    {
        $this->writer->handle($this->message());

        $result = $this->writer->handle($this->message([
            'driver_id' => 'drv_002',
            'recorded_at' => '2026-11-13T09:00:00+00:00',
        ]));

        $this->assertSame(DriverLocationWriteResult::Stored, $result);
    }
}
