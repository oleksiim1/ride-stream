<?php

namespace Modules\LocationIngest\Tests\Feature\Api\V1;

use Illuminate\Testing\TestResponse;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DriverLocationTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'driver_id' => 'drv_001',
            'lat' => 50.4501,
            'lng' => 30.5234,
            'heading' => 90,
            'speed_kmh' => 0,
            'accuracy_m' => 8,
            'recorded_at' => '2026-11-13T10:00:00+00:00',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postLocation(array $payload): TestResponse
    {
        return $this->postJson(route('api.v1.driver-locations.store'), $payload);
    }

    public function test_it_publishes_the_location_to_the_driver_location_topic(): void
    {
        Kafka::fake();

        $this->postLocation($this->validPayload())
            ->assertStatus(202)
            ->assertJson(['status' => 'accepted']);

        Kafka::assertPublishedOnTimes('driver.location.updated', 1, null, function (Message $message): bool {
            return $message->getKey() === 'drv_001'
                && $message->getBody() === $this->validPayload();
        });
    }

    public function test_it_normalizes_recorded_at_to_utc(): void
    {
        Kafka::fake();

        $this->postLocation([...$this->validPayload(), 'recorded_at' => '2026-11-13T12:00:00+02:00'])
            ->assertStatus(202);

        Kafka::assertPublishedOn('driver.location.updated', null, function (Message $message): bool {
            return $message->getBody()['recorded_at'] === '2026-11-13T10:00:00+00:00';
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    #[DataProvider('invalidPayloads')]
    public function test_it_rejects_invalid_payloads_without_publishing(string $field, array $overrides): void
    {
        Kafka::fake();

        $this->postLocation([...$this->validPayload(), ...$overrides])
            ->assertStatus(422)
            ->assertJsonValidationErrors($field);

        Kafka::assertNothingPublished();
    }

    /**
     * @return array<string, array{string, array<string, mixed>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'latitude out of range' => ['lat', ['lat' => 91]],
            'longitude out of range' => ['lng', ['lng' => -181]],
            'heading out of range' => ['heading', ['heading' => 360]],
            'negative speed' => ['speed_kmh', ['speed_kmh' => -1]],
            'negative accuracy' => ['accuracy_m', ['accuracy_m' => -1]],
            'invalid timestamp' => ['recorded_at', ['recorded_at' => 'not-a-date']],
            'missing driver id' => ['driver_id', ['driver_id' => '']],
        ];
    }
}
