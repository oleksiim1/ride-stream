<?php

namespace Modules\LocationTracker\Console\Commands;

use Illuminate\Console\Command;
use Junges\Kafka\Contracts\ConsumerMessage;
use Junges\Kafka\Facades\Kafka;
use Modules\LocationTracker\Kafka\RawBodyDeserializer;
use Modules\LocationTracker\Services\DriverLocationWriter;

class ConsumeDriverLocations extends Command
{
    protected $signature = 'kafka:consume-driver-locations';

    protected $description = 'Consume driver location updates from Kafka and write them to Redis';

    public function handle(DriverLocationWriter $writer): int
    {
        $this->info('Consuming '.config('kafka.topics.driver_location').' as group location-writers...');

        Kafka::consumer([config('kafka.topics.driver_location')], 'location-writers')
            ->withOption('auto.offset.reset', 'earliest')
            ->usingDeserializer(new RawBodyDeserializer)
            ->withHandler(function (ConsumerMessage $message) use ($writer): void {
                $writer->handle((string) $message->getBody());
            })
            ->build()
            ->consume();

        return self::SUCCESS;
    }
}
