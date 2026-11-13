<?php

namespace App\Kafka;

use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Contracts\MessageDeserializer;

/**
 * Leaves the message body untouched so the handler can decode and reject bad JSON itself.
 */
class RawBodyDeserializer implements MessageDeserializer
{
    public function deserialize(ConsumerMessage $message): ConsumerMessage
    {
        return $message;
    }
}
