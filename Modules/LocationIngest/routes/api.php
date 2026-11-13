<?php

use Illuminate\Support\Facades\Route;
use Modules\LocationIngest\Http\Controllers\Api\V1\DriverLocationController;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('driver-locations', DriverLocationController::class)->name('driver-locations.store');
});
