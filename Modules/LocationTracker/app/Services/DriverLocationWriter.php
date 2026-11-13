<?php

namespace Modules\LocationTracker\Services;

use App\Shared\Data\DriverLocation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;
use JsonException;
use Modules\LocationTracker\Enums\DriverLocationWriteResult;

class DriverLocationWriter
{
    public const LOCATIONS_KEY = 'drivers:locations';

    public const LAST_SEEN_KEY = 'drivers:last_seen';

    /**
     * Decode, validate and store a raw driver location message.
     *
     * Invalid and stale messages are skipped and never throw.
     */
    public function handle(string $rawMessage): DriverLocationWriteResult
    {
        $location = $this->parse($rawMessage);

        if ($location === null) {
            return DriverLocationWriteResult::Invalid;
        }

        if ($this->isStale($location)) {
            return DriverLocationWriteResult::Stale;
        }

        Redis::geoadd(self::LOCATIONS_KEY, $location->lng, $location->lat, $location->driverId);
        Redis::zadd(self::LAST_SEEN_KEY, $location->recordedAt->getTimestampMs(), $location->driverId);

        return DriverLocationWriteResult::Stored;
    }

    private function parse(string $rawMessage): ?DriverLocation
    {
        $payload = null;

        try {
            $payload = json_decode($rawMessage, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($payload)) {
                Log::warning('Skipping driver location: payload is not a JSON object.');

                return null;
            }

            return DriverLocation::fromArray($payload);
        } catch (JsonException $exception) {
            Log::warning('Skipping driver location: malformed JSON.', ['error' => $exception->getMessage()]);
        } catch (ValidationException $exception) {
            Log::warning('Skipping driver location: validation failed.', [
                'driver_id' => $payload['driver_id'] ?? null,
                'errors' => $exception->errors(),
            ]);
        }

        return null;
    }

    /**
     * A message is stale when it is not newer than the last one stored for the driver.
     */
    private function isStale(DriverLocation $location): bool
    {
        $lastSeenMs = Redis::zscore(self::LAST_SEEN_KEY, $location->driverId);

        return is_numeric($lastSeenMs)
            && $location->recordedAt->getTimestampMs() <= (int) $lastSeenMs;
    }
}
