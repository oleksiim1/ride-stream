<?php

namespace Modules\LocationSimulator\Providers;

use Modules\LocationSimulator\Console\Commands\FetchRoutes;
use Modules\LocationSimulator\Console\Commands\RunDriverSimulation;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LocationSimulatorServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'LocationSimulator';

    protected string $nameLower = 'locationsimulator';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    protected array $commands = [
        FetchRoutes::class,
        RunDriverSimulation::class,
    ];
}
