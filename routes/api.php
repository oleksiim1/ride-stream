<?php

use App\Http\Controllers\Api\V1\DriverLocationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('driver-locations', DriverLocationController::class)->name('driver-locations.store');
});
