<?php

namespace Modules\LocationIngest\Providers;

use Illuminate\Support\Facades\Route;
use Nwidart\Modules\Support\ModuleServiceProvider;

class LocationIngestServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'LocationIngest';

    protected string $nameLower = 'locationingest';

    /**
     * Bootstrap the module and register its API routes under the /api prefix.
     */
    public function boot(): void
    {
        parent::boot();

        Route::middleware('api')
            ->prefix('api')
            ->group(module_path($this->name, 'routes/api.php'));
    }
}
