<?php

namespace Modules\LocationIngest\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Modules\LocationIngest\Http\Requests\Api\V1\StoreDriverLocationRequest;

class DriverLocationController
{
    /**
     * Publish a driver location update to Kafka.
     */
    public function __invoke(StoreDriverLocationRequest $request): JsonResponse
    {
        $payload = [
            ...$request->validated(),
            'recorded_at' => Carbon::parse($request->validated('recorded_at'))->utc()->toIso8601String(),
        ];

        Kafka::publish()
            ->onTopic(config('kafka.topics.driver_location'))
            ->withMessage(new Message(body: $payload, key: $payload['driver_id']))
            ->send();

        return response()->json(['status' => 'accepted'], 202);
    }
}
