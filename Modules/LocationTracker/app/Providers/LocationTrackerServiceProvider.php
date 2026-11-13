<?php

namespace Modules\LocationTracker\Providers;

use Modules\LocationTracker\Console\Commands\ConsumeDriverLocations;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LocationTrackerServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'LocationTracker';

    protected string $nameLower = 'locationtracker';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        ConsumeDriverLocations::class,
    ];
}
