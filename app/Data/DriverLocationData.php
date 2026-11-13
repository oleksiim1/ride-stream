<?php

namespace App\Data;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class DriverLocationData
{
    public function __construct(
        public string $driverId,
        public float $lat,
        public float $lng,
        public Carbon $recordedAt,
        public ?int $heading = null,
        public ?float $speedKmh = null,
        public ?float $accuracyM = null,
    ) {}

    /**
     * Build the DTO from a decoded Kafka payload.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public static function fromArray(array $payload): self
    {
        $validated = Validator::make($payload, [
            'driver_id' => ['required', 'string', 'max:64'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'recorded_at' => ['required', 'date'],
            'heading' => ['nullable', 'integer', 'between:0,359'],
            'speed_kmh' => ['nullable', 'numeric', 'min:0'],
            'accuracy_m' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        return new self(
            driverId: $validated['driver_id'],
            lat: (float) $validated['lat'],
            lng: (float) $validated['lng'],
            recordedAt: Carbon::parse($validated['recorded_at'])->utc(),
            heading: isset($validated['heading']) ? (int) $validated['heading'] : null,
            speedKmh: isset($validated['speed_kmh']) ? (float) $validated['speed_kmh'] : null,
            accuracyM: isset($validated['accuracy_m']) ? (float) $validated['accuracy_m'] : null,
        );
    }
}
