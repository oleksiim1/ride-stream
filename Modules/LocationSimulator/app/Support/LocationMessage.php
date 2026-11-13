<?php

namespace Modules\LocationSimulator\Support;

use App\Shared\Data\DriverLocation;

final class LocationMessage
{
    public const SCHEMA_VERSION = 1;

    /**
     * The Kafka message body for a driver location.
     *
     * @return array{schema_version: int, driver_id: string, lat: float, lng: float, heading: int|null, speed_kmh: float|null, accuracy_m: float|null, recorded_at: string}
     */
    public static function toPayload(DriverLocation $location): array
    {
        return [
            'schema_version' => self::SCHEMA_VERSION,
            'driver_id' => $location->driverId,
            'lat' => $location->lat,
            'lng' => $location->lng,
            'heading' => $location->heading,
            'speed_kmh' => $location->speedKmh,
            'accuracy_m' => $location->accuracyM,
            'recorded_at' => $location->recordedAt->utc()->toIso8601String(),
        ];
    }
}
