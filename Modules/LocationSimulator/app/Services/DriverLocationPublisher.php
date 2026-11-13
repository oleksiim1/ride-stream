<?php

namespace Modules\LocationSimulator\Services;

use App\Shared\Data\DriverLocation;
use Junges\Kafka\Contracts\Producer;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;
use Modules\LocationSimulator\Support\LocationMessage;
use RuntimeException;

/**
 * The only place in this module that talks to Kafka.
 */
class DriverLocationPublisher
{
    private ?Producer $producer = null;

    /**
     * Send a location to Kafka, keyed by driver id.
     *
     * @throws RuntimeException when the message could not be produced
     */
    public function publish(DriverLocation $location): void
    {
        $topic = (string) config('kafka.topics.driver_location');

        $message = Message::create($topic)
            ->withBody(LocationMessage::toPayload($location))
            ->withKey($location->driverId);

        $this->producer ??= Kafka::publish()->onTopic($topic)->build();

        if (! $this->producer->produce($message)) {
            throw new RuntimeException("Kafka did not accept the message for [{$location->driverId}].");
        }
    }

    /**
     * Wait for queued messages to be delivered.
     */
    public function flush(): void
    {
        $this->producer?->flush();
    }
}
